# Architecture

How `quizaccess_proctoring` is put together. See `README.md` for install/config.

---

## Components

```
Browser (student)            Moodle (PHP)                  AI backend
─────────────────            ────────────                  ──────────
preflight / quiz JS  ──ajax──▶ external WS                  POST /verify/face
re-verification modal          ▼                            { reference_face,
camshot capture                lib.php face-match  ──HTTP──▶   current_face }
                               ▼                            ◀── { is_match,
                               DB + file storage                match_score }
```

The plugin never matches faces itself — it brokers images between Moodle's file
storage and the external AI backend, then applies the `threshold` setting.

---

## Key files

| File | Responsibility |
|------|----------------|
| `rule.php` | The `quiz_access_rule` subclass. Preflight check, `setup_attempt_page()`, and the injected re-verification JavaScript (`get_reverification_js()`). |
| `lib.php` | Core library: file serving (`_pluginfile`), face-image helpers, and the **face match pipeline**. |
| `classes/external.php` | Web service functions, incl. `validate_face` and `send_camshot`. |
| `settings.php` | Admin settings (see README). |
| `user_enroll_photo.php` | Student self-service face enrollment page. |
| `upload_image.php` / `userslist.php` | Admin face enrollment. |
| `classes/form/user_image_upload_form.php` | Self-enrollment form + webcam capture JS. |
| `amd/src/proctoring.js` | In-quiz silent camshots + webcam box. |
| `amd/src/startAttempt.js` | Preflight (quiz-start) face check. |
| `verify_proctoring_setup.php` | CLI diagnostic script. |
| `db/` | `install.xml` (schema), `services.php` (WS), `access.php`, `upgrade.php`. |

---

## Face match pipeline

The single matching path, used by the preflight check and every re-verification:

```
external.php  validate_face($courseid, $cmid, ..., $webcampicture, ...)
   │  saves the webcam frame to the `picture` file area + a logs row
   ▼
lib.php  quizaccess_proctoring_bs_analyze_specific_image_from_validate($reportid)
   │  picks the FULL webcam screenshot (logs.webcampicture)
   │  and the FULL enrolled photo (quizaccess_proctoring_get_image_url())
   ▼
lib.php  quizaccess_proctoring_extracted($reference, $webcam, $reportid)
   │  parses the response, compares match_score% against `threshold`
   ▼
lib.php  quizaccess_proctoring_check_similarity_bs(...)
   │  downloads both images into memory, base64-encodes them,
   │  POSTs to <bsapi>/verify/face using Moodle curl (ignoresecurity)
   ▼
   AI backend → { is_match, match_score, confidence, details }
```

Result handling:

- `match_score × 100 > threshold` ⇒ stored as the score, `awsflag = 2` (matched).
- Below threshold ⇒ a warning row is logged in `quizaccess_proctoring_fm_warnings`.
- `validate_face` returns `success` when the stored score beats the threshold,
  else `failed` / `photonotuploaded` / `invalidApi`.

**Important design points**

- **Full images, not crops.** The backend runs its own face detection; tight
  client-side crops are too small for it. The webcam *full screenshot* and the
  *full enrolled photo* are sent.
- **In-memory download.** Images are fetched straight into memory — no temp
  files — so URL query strings (e.g. `?forcedownload=1`) can't create invalid
  filenames on Windows.
- **`ignoresecurity`.** Moodle's cURL blocks internal hosts (`127.0.0.1`) by
  default; the face-match request opts out so it can reach a local backend.
- **Diagnostics.** Every attempt writes `[quizaccess_proctoring] FaceMatch …`
  to the PHP error log (see README → Troubleshooting).

---

## Re-verification (in-quiz + pre-submission)

Implemented entirely in `rule.php::get_reverification_js()` and injected via
`$page->requires->js_init_code()` from `setup_attempt_page()` — only when a
face match method is configured. No AMD build step; it loads `core/ajax`
through Moodle's global `require`.

- **During the quiz:** a blocking full-screen modal appears at the per-quiz
  **`reverifyinterval`** (default 120 s). Setting it to `0` turns the mid-quiz
  cadence off entirely. The interval is anchored in `sessionStorage`, so it
  survives page navigation in multi-page quizzes instead of resetting.
- **Pause-the-clock:** when the per-quiz `pausequiztime` is on, every
  successful verification calls `quizaccess_proctoring_extend_attempt_time`,
  which adds the elapsed verification seconds to `quiz_attempts.timestart`.
  The student doesn't lose time to a face check.
- **Before submission:** on the quiz summary page the modal appears immediately
  and the `.mod_quiz-next-nav` (Submit all and finish) button stays disabled
  until verification succeeds. Pre-submission verification runs even when the
  mid-quiz cadence is disabled.
- All three flows reuse the `quizaccess_proctoring_validate_face` web service,
  sending the full webcam frame.

---

## Reference photo storage

A student's enrolled photo lives in **two** places, both populated by the
enrollment pages:

1. **File storage** — `quizaccess_proctoring / user_photo` file area, system
   context, `itemid = userid`. Read by `quizaccess_proctoring_get_image_url()`.
2. **Database** — `quizaccess_proctoring_user_images` (one row per user) plus a
   `quizaccess_proctoring_face_images` row with `parent_type = 'admin_image'`.

The enrollment link on the profile page comes from
`quizaccess_proctoring_myprofile_navigation()` in `lib.php`.

---

## Database tables

| Table | Holds |
|-------|-------|
| `quizaccess_proctoring` | Per-quiz rule settings. |
| `quizaccess_proctoring_logs` | One row per webcam capture / check (incl. `webcampicture`, `awsscore`, `awsflag`). |
| `quizaccess_proctoring_user_images` | One row per enrolled user. |
| `quizaccess_proctoring_face_images` | Face image references (`admin_image` = reference, `camshot_image` = capture). |
| `quizaccess_proctoring_facematch_task` | Queue for asynchronous (cron) face matching. |
| `quizaccess_proctoring_fm_warnings` | Logged failed-match warnings. |
| `quizaccess_proctoring_multiuser_alerts` | Multiple-faces-detected alerts. |
| `quizaccess_proctoring_eyetrack_alerts` | Eye-tracking alerts. |
| `quizaccess_proctoring_reverification` | Re-verification records. |
| `quizaccess_proctoring_analytics` | Aggregated proctoring analytics. |

---

## Behavior analysis (`/detect/behavior`)

In parallel with face matching, every saved frame is sent to the backend's
`/detect/behavior` endpoint. The response is stored as JSON in
`quizaccess_proctoring_logs.behavior_result` and surfaced in the report:

```
{
  "face_count": 1,
  "multiple_faces_detected": false,
  "no_face_detected": false,
  "unusual_head_pose": false,
  "unusual_eye_gaze": true,
  "gaze_offset": -0.32,
  "suspicious_indicators": ["gaze_offset:-0.32"],
  "risk_level": "medium"
}
```

- The eye-gaze signal (`unusual_eye_gaze`, `gaze_offset`) is produced by the
  backend's `eye_tracker.py`. It compares the eye landmarks to the nose
  position to decide whether the student is looking off-screen.
- `risk_level` is used to colour the frame row in the report (`high` overrides
  a green face-match border).
- `report.php` aggregates these per-attempt into the **Session Summary** card
  (counts of `no_face_detected`, `multiple_faces_detected`, `unusual_head_pose`,
  `unusual_eye_gaze`, plus average `gaze_offset` and an overall risk verdict).

---

## Report rendering

`report.php` builds two contexts for `templates/studentreport.mustache`:

1. **`summary`** — the aggregate object computed by walking the
   `quizaccess_proctoring_logs` rows for the attempt: total frames, analyzed
   frames, success/fail counts, average score, behavior counts, risk
   distribution, duration, and an overall risk verdict.
2. **`data[]`** — one entry per frame, enriched with `timestamp`, `score`,
   `risk_level`, `risk_indicators`, `gaze_offset`, and the existing
   `border_color` / `image_url` / `lightbox_data` fields.

Styles for the risk badges live in `styles.css`
(`.proctoring-risk-low|medium|high`, `.proctoring-summary-card`).

---

## Additional modules

The 2.0 "enhanced" build also ships multi-user detection, deeper eye-tracking
session aggregation, and analytics (`classes/ai_model_integration/`,
`amd/src/enhancedProctoring.js`, and the `*_alerts` / `_analytics` tables).
They share the same AI backend but are independent of the face-match pipeline
documented above.
