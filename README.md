# Quiz Access Proctoring (`quizaccess_proctoring`)

A Moodle **quiz access rule** plugin that verifies a student's identity with
webcam face matching before, during, and after a proctored quiz.

- **Release:** 2.0.0 — `version 2026052304`
- **Requires:** Moodle 4.3+ (`2023100900`)
- **License:** GNU GPL v3 or later (see `LICENSE.md`)

---

## What it does

| Stage | Behaviour |
|-------|-----------|
| **Enrollment** | Each student enrolls a reference photo of their face (self-service or admin-uploaded). |
| **Quiz start** | A preflight face check must pass before the attempt begins. |
| **During the quiz** | Silent webcam snapshots are stored for the report. A **blocking re-verification modal** also appears at a **teacher-configurable interval** (default every 2 minutes; can be disabled per quiz). Optionally, the quiz timer is paused while verification is in progress. |
| **Before submission** | On the quiz summary page the **Submit all and finish** button stays disabled until a face check passes. |
| **Behavior analysis** | Every captured frame is also sent to the AI backend's `/detect/behavior` endpoint, which flags multiple faces, no face, unusual head pose, and unusual eye gaze (eye-tracker output). Results land in the report as risk badges. |
| **Per-student report** | A **Session Summary** card aggregates the attempt (success rate, average score, no-face / multi-face / unusual-head / unusual-gaze counts, risk distribution, overall verdict). Each frame row shows score, risk badge, and suspicious indicators. |

Face matching is performed by an **external AI backend** (see Requirements). The
plugin sends the enrolled reference photo and the live webcam frame; the backend
returns a similarity score.

---

## Requirements

1. **Moodle 4.3+** with a working cron.
2. **The proctoring AI backend service** running and reachable from the Moodle
   server. It must expose `POST /verify/face` accepting
   `{ "reference_face": <data-url>, "current_face": <data-url> }` and returning
   `{ "is_match": bool, "match_score": 0..1, "confidence": 0..1, "details": {...} }`.
   The reference implementation runs at `http://127.0.0.1:5000`.
3. A browser with webcam access, served over **HTTPS or `localhost`** (browsers
   block `getUserMedia` on plain-HTTP non-localhost origins).

---

## Installation

1. Copy this folder to `mod/quiz/accessrule/proctoring` in your Moodle tree.
2. Visit **Site administration → Notifications** to run the install/upgrade.
3. Configure the plugin (below) and **purge all caches**.

---

## Configuration

### Plugin-wide (admin)

**Site administration → Plugins → Activity modules → Quiz → Proctoring.**

| Setting | Purpose | Recommended |
|---------|---------|-------------|
| `fcmethod` | Face match method. **AI face match** enables matching via the AI backend; **None** disables it. | AI face match |
| `bsapi` | AI backend base URL. `/verify/face` is appended automatically if missing. | `http://127.0.0.1:5000` |
| `bs_api_key` | Optional `x-api-key` header. Leave blank for a local backend. | *(blank)* |
| `threshold` | Match cutoff as a **similarity percentage**. A match requires `score > threshold`. **Higher = stricter.** | `62` |
| `autoreconfigurecamshotdelay` | Seconds between silent in-quiz snapshots. | `30` |
| `autoreconfigureimagewidth` | Captured image width in pixels. | `230` |
| `debuglog` | Write `[quizaccess_proctoring] FaceMatch …` lines to the PHP error log. Off in production. | Off |

> **Threshold note:** the AI backend's similarity score for the *same* person
> fluctuates (typically 65–85%). Set `threshold` a few points below the lowest
> genuine score you observe in the logs to avoid falsely rejecting real students.

### Per-quiz (teacher)

On the quiz settings page, under **Proctoring**:

| Setting | Purpose | Default |
|---------|---------|---------|
| `proctoringrequired` | Turn proctoring on for this quiz. | Off |
| `reverifyinterval` | How often the mid-quiz blocking modal fires, in seconds. **0 disables** mid-quiz re-verification (preflight and pre-submission still run). | `120` (2 min) |
| `pausequiztime` | If on, the quiz `timestart` is extended by the duration of each verification, so the clock effectively pauses while the student verifies. | Off |

---

## Enrolling a face

- **Students (self-service):** open your profile page → **Proctoring - Enroll
  Your Face** (also available in the user menu), then capture a photo with the
  webcam or upload one. The full frame is stored — the AI backend does its own
  face detection.
- **Admins:** **Site admin → … → Proctoring → Users list → Upload image**.

A student cannot pass the quiz face check until a reference photo is enrolled.

---

## Verifying the setup

Run the bundled diagnostic script from the Moodle root:

```
php mod/quiz/accessrule/proctoring/verify_proctoring_setup.php
```

It checks the database tables, plugin settings, and configuration.

---

## Troubleshooting

Face match attempts are logged to the **PHP error log** (on XAMPP:
`C:\xampp\apache\logs\error.log`). Each attempt writes:

```
[quizaccess_proctoring] FaceMatch reportid=N threshold=62% raw_response=...
[quizaccess_proctoring] FaceMatch reportid=N result=MATCH score=81% > threshold=62%
```

| Log result | Meaning | Fix |
|------------|---------|-----|
| `result=MATCH` | Identity verified. | — |
| `result=NO_MATCH score=X%` | Faces compared, score below threshold. | Lower `threshold` below the genuine score. |
| `reason=no_face_in_current_frame` | Backend found no face in an image. | Improve lighting/framing; ensure a full frame is sent. |
| `result=BACKEND_ERROR` | Backend rejected the request. | Check the backend logs / image data. |
| `result=INVALID_RESPONSE` | Backend unreachable or wrong response. | Check `bsapi`, that the service is running, and Moodle cURL access. |
| `raw_response=The URL is blocked.` | Moodle cURL blocked the host. | Handled in code via `ignoresecurity`; ensure `bsapi` is correct. |

**Always "Face not matched"?** Confirm the face match method is set to
**AI face match** — with **None** no comparison runs and every check fails.

---

## Documentation

- `ARCHITECTURE.md` — how the pieces fit together (data flow, key files, DB tables).
- `DEPLOYMENT.md` — production deployment, HTTPS / CORS, smoke test, tuning.
- `docs/PROCTORING_GUIDE_EN.docx` — first-year-friendly guide in English.
- `docs/PANDUAN_PROCTORING_ID.docx` — first-year-friendly guide in Bahasa Indonesia.
