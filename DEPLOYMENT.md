# Deployment Guide

This guide deploys **`quizaccess_proctoring`** (the Moodle plugin) wired to the
**`capstone-backend`** AI service. It covers production-ready setup on a
single bare-metal/VM host. Docker is mentioned at the end as a *future* path.

---

## 1. Prerequisites

| Component | Requirement |
| --- | --- |
| Moodle | 4.3+ (`$plugin->requires = 2023100900`) |
| PHP | 8.0+ with `curl`, `gd`, `openssl`, `intl`, `mbstring` |
| Database | MariaDB 10.6+ / MySQL 8+ / PostgreSQL 13+ |
| AI backend | `capstone-backend` v2.0+ (Flask + Gunicorn, see its `README.md`) |
| TLS | A valid certificate for the Moodle hostname **and** the backend hostname |
| Webcam | Modern Chromium, Firefox, or Edge — `getUserMedia` only works on `https://` or `localhost` |

---

## 2. Deploy the backend

Follow `C:\capstone-backend\README.md` to bring up the backend. Minimum:

```bash
cd /opt/capstone-backend
cp .env.example .env
# Edit .env:
#   ENVIRONMENT=production
#   SECRET_KEY=<generate one>
#   API_KEY=<generate one — you will paste this into Moodle later>
#   CORS_ORIGINS=https://moodle.example.com    # NOT '*'

python -m venv venv && . venv/bin/activate
pip install -r requirements.txt
gunicorn -w 4 -b 0.0.0.0:5000 wsgi:app
```

Put a reverse proxy (Nginx, Caddy, or your cloud LB) in front of it with TLS
terminated at the proxy. The backend itself should bind to localhost and the
proxy forwards `https://proctoring.example.com → 127.0.0.1:5000`.

Sanity check:

```bash
curl https://proctoring.example.com/health
curl -X POST https://proctoring.example.com/detect/faces \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"image":"data:image/jpeg;base64,..."}'
```

---

## 3. Install / update the plugin

```bash
# From the repo root
cp -r quizaccess_proctoring  <moodle>/mod/quiz/accessrule/proctoring
```

Then visit **Site administration → Notifications** in the browser, or run:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

This installs the schema (including the new `behavior_result` column added in
v2026052301) and registers scheduled tasks.

---

## 4. Configure the plugin

**Site administration → Plugins → Quiz access → Proctoring**, fill in:

| Setting | Value |
| --- | --- |
| Face match method | **AI face match** |
| Face match service API | `https://proctoring.example.com` (no path; the plugin auto-appends `/verify/face`) |
| Face match API key | The **same string** you set as `API_KEY` in the backend `.env` |
| Face match threshold | Start at **62–68**. Watch the logs and tune (see §6). |
| Face match diagnostic logging | **Off** for production. Turn on only when debugging. |

> The setting keys still use the legacy `bs*` names internally for backward
> compatibility with stored data — see `settings.php`. Display labels were
> renamed.

### Per-quiz settings (teachers)

Each quiz now exposes three proctoring options on its settings page:

| Setting | Effect |
| --- | --- |
| **Proctoring required** | Turns the whole pipeline on for this quiz. |
| **Re-verify interval** (seconds) | How often the mid-quiz blocking modal fires. **`0` disables** the in-quiz cadence; preflight and pre-submission checks still run. |
| **Pause quiz time during verification** | When on, every successful verification extends `quiz_attempts.timestart` by the elapsed seconds, so the timer effectively pauses while the student verifies. |

Communicate the default cadence to teachers — they don't have to leave it at
2 minutes for every quiz.

---

## 5. HTTPS, CORS, and webcam access

`navigator.mediaDevices.getUserMedia` (the webcam API) **refuses** on plain
HTTP unless the origin is `localhost`. For any real deployment:

1. Serve Moodle on `https://...`. A self-signed cert is **not** enough on
   modern Chrome — use Let's Encrypt or a trusted CA.
2. Serve the backend on `https://...` too (otherwise mixed-content blocks the
   `validate_face` AJAX call from the HTTPS Moodle page).
3. In the backend `.env` set `CORS_ORIGINS=https://moodle.example.com`. Use
   the **exact origin** of your Moodle, comma-separated for multiples. Do
   **not** use `*` in production — `compat-mode` browsers will reject it.

---

## 6. Smoke test

After the deploy, run one end-to-end pass:

1. As a student, log in → **Profile → Proctoring - Enroll Your Face** →
   capture or upload a face.
2. As a student, open a quiz with proctoring enabled → preflight modal opens
   → click **Verify face** → expect ✅ "Face matched".
3. During the quiz, the re-verification modal appears at the configured
   `reverifyinterval` (default every 2 minutes; set to `0` per-quiz to
   disable). It should pass on a clean capture.
4. If `pausequiztime` is enabled on the quiz, confirm the displayed
   countdown picks back up where it was after each successful re-check —
   the AJAX call to `quizaccess_proctoring_extend_attempt_time` adjusts
   `quiz_attempts.timestart` for that attempt.
5. On the summary page, **Submit all and finish** is disabled until the
   final face check passes (this runs regardless of `reverifyinterval`).
6. As an admin / teacher, open **Proctoring report → click a student**.
   You should see:
   - The **Session Summary** card (success rate, average score,
     no-face / multi-face / unusual-head / unusual-gaze counts, risk
     distribution, overall verdict)
   - Per-frame rows with timestamp, face score, risk badge, and
     suspicious-indicator list
   - High-risk frames flagged red (overriding green) on the thumbnail border
   - `behavior_result` JSON populated in `mdl_quizaccess_proctoring_logs`
     by the `/detect/behavior` call

Check the PHP error log for the diagnostic lines:

```
[quizaccess_proctoring] FaceMatch reportid=… result=MATCH score=82% > threshold=62%
[quizaccess_proctoring] Behavior reportid=… risk=low indicators=[]
```

If you see `result=SERVICE_ERROR` or status `serviceunavailable` on the UI,
check the proxy / backend health. If you see `invalidApi`, the Moodle
**Face match API key** does not match the backend's `API_KEY`.

---

## 7. Tuning the threshold

The plugin's `threshold` setting is the **minimum match percentage** required
to count as the same person.

- Default: 68
- Recommended starting point: 62 (matches the backend's own `0.6` cutoff)
- Watch the log lines `result=NO_MATCH score=NN%` — if genuine students are
  being rejected with NN in the high 50s/low 60s, lower the threshold by a
  few points.

---

## 8. Front-end build step

The browser-side code in `amd/src/*.js` is loaded by Moodle from
`amd/build/*.min.js` in production. If you edited any AMD source you must
rebuild before deploying:

```bash
cd <moodle>
nvm use 18
npm install
npx grunt amd --root=mod/quiz/accessrule/proctoring
```

If you don't have the build chain set up, set `$CFG->cachejs = false;` in
`config.php` temporarily — Moodle will serve from `amd/src/` directly. **Do
not leave this off in production**, it kills performance.

---

## 9. Privacy & retention

Webcam frames and reference photos are **biometric data**. Before going
live:

- Decide on a retention period (e.g. delete frames > 90 days after the quiz)
- Schedule the `delete_images_task` cron task to enforce it
- Update your privacy policy to disclose what's collected, where it's sent
  (the AI backend), and how long it's kept
- The plugin ships a `classes/privacy/provider.php` that already declares the
  stored data for Moodle's GDPR export/delete tooling — verify it covers any
  new data you add

---

## 10. Troubleshooting

| Symptom | Likely cause |
| --- | --- |
| Every face check shows `serviceunavailable` | Backend down, hostname wrong, or reverse proxy not running |
| Every check shows `invalidApi` | API key mismatch — backend rejects 401/403 |
| Camera doesn't open for students | Not on `https://` (or origin not `localhost`) |
| `Face not matched` on real students | Threshold too high, or lighting; check `score=` in error log |
| Analyze button does nothing | Backend URL not set, or `bsapi` empty |
| Multiple-face / gaze flags missing | Backend model files (`models_data/*.pth`) missing — backend falls back to weaker Haar Cascade detection |

---

## 11. Future: Docker

The backend already ships a `Dockerfile` + `docker-compose.yml`. A future
Moodle deployment on Docker would compose roughly:

```yaml
services:
  proctoring-backend:    # from C:\capstone-backend\docker-compose.yml
  moodle:                # bitnami/moodle or custom image
  db:                    # mariadb
  reverse-proxy:         # caddy or nginx with TLS
```

The plugin needs no changes to run in Docker — it makes outbound HTTPS calls
to whatever URL is in the **Face match service API** setting. In the same
Docker network that becomes `http://proctoring-backend:5000`; from outside
it's the public HTTPS URL.

Defer this until the bare-metal deploy is healthy; container networking
hides config bugs that the manual deploy surfaces.

---

## 12. End-user documentation

For teachers and students who don't read deployment guides:

- `docs/PROCTORING_GUIDE_EN.docx` — first-year-friendly walkthrough in English
  (what proctoring is, every feature, the architecture, annotated code, the
  full settings reference).
- `docs/PANDUAN_PROCTORING_ID.docx` — the same guide in Bahasa Indonesia.

Hand these to the teaching staff before rollout so the per-quiz settings
(re-verify interval, pause-quiz-time) and the Session Summary in the report
aren't a surprise.
