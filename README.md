# MKfinder — AI Bird Species Identifier

MKfinder is a full-stack web app that identifies bird species from a photo in
real time, then lets users share and discuss their sightings in a community
feed. The identification pipeline runs a two-stage computer vision model, and
the model itself keeps improving from community-submitted photos through an
admin-triggered fine-tuning loop.

> Upload a photo → get species, confidence score, and field-guide info
> (habitat, diet, behavior, conservation status) in seconds.

## How identification works

Every uploaded image goes through two models before a result is returned:

1. **Gatekeeper — MobileNetV3-Small (ImageNet).** A lightweight check that
   rejects non-bird photos (people, pets, objects, vehicles...) before the
   expensive model runs, using ImageNet class indices plus keyword matching
   over the top-20 predictions. This keeps the UX fast and avoids wasting
   inference time/cost on obviously-wrong uploads.
2. **Classifier — fine-tuned SigLIP.** Bird photos are passed to
   [`prithivMLmods/Bird-Species-Classifier-526`](https://huggingface.co/prithivMLmods/Bird-Species-Classifier-526)
   (526-class base model), with a custom fine-tuned classifier head layered
   on top for the species the app actively supports end-to-end (American
   Robin, Blue Grosbeak, Northern Cardinal, Bald Eagle, Ruby-throated
   Hummingbird, Eastern Bluebird).

```
Browser (upload) → identify.php → predict.py (PyTorch/Transformers) → JSON result → MySQL (history) → response
```

PHP shells out to Python (`shell_exec`) rather than running a persistent
inference server — a deliberate trade-off for a self-hosted/XAMPP deployment
without a dedicated ML serving layer.

### Continual learning loop

Community submissions that the AI doesn't confidently recognize are queued
in `unknown_submissions` / `training_images`. From the admin panel, an admin
can approve one and trigger `finetune.py`, which:

- pulls newly-approved images from MySQL,
- freezes the SigLIP backbone and trains only the classifier head (cheap,
  fast, no GPU required),
- saves the updated head to `fine_tuned_model/classifier_head.pt`, which
  `predict.py` picks up automatically on the next request.

This means the model's coverage grows from real user contributions instead
of requiring a full retrain/redeploy cycle.

## Features

**Identification**
- Drag-and-drop photo upload with instant AI identification and confidence score
- Non-bird detection with a helpful, specific rejection message ("looks like a person/vehicle/object...")
- Per-species field guide info: scientific name, characteristics, habitat, diet, behavior, conservation status
- Manual override table (`prediction_overrides`) for correcting known-bad identifications by image hash

**Community**
- Public "Bird Explorer" feed of identified sightings with likes and comments
- User accounts (signup/login, session-based auth)
- Optional location tagging per sighting

**Admin panel**
- Review and approve/reject user-submitted "unknown" species
- Add/manage species entries (with photos, approval workflow for user-submitted species)
- Queue images for training and trigger fine-tuning directly from the browser
- AI-assisted species info autofill via OpenRouter (LLM fills in scientific
  name, habitat, diet, etc. for a new species from just its common name)
- Moderate community posts/comments

## Tech stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, Bootstrap 5, vanilla JS |
| Backend | PHP (procedural, no framework) |
| Database | MySQL / MariaDB (XAMPP) |
| ML inference & training | Python, PyTorch, Hugging Face Transformers, torchvision |
| Base model | SigLIP (`prithivMLmods/Bird-Species-Classifier-526`) + fine-tuned classifier head |
| Gatekeeper model | MobileNetV3-Small (torchvision, ImageNet weights) |
| LLM assist | OpenRouter API (admin species autofill) |

## Project structure

```
MKfinder/
├── index.html, login.html, signup.html   # Static entry pages
├── identify.php                          # Upload endpoint → runs predict.py, saves result
├── gallery.php, species.php              # Browse identifications / species field guide
├── community.php, community_api.php      # Public feed: likes, comments, detail view
├── admin.php                             # Admin dashboard (species, submissions, training)
├── gemini_autofill.php                   # LLM-assisted species info autofill (OpenRouter)
├── trigger_training.php                  # Kicks off finetune.py from the admin panel
├── auth.php, config.php, database.php    # Auth, app config, DB access layer
├── predict.py                            # Two-stage inference (MobileNet gate → SigLIP)
├── finetune.py                           # Classifier-head fine-tuning from DB-approved images
├── fine_tuned_model/                     # Current fine-tuned classifier head + metadata
├── mkfinder_database.sql                 # Full schema + seed data
└── uploads/                               # User-uploaded photos (gitignored; .htaccess locks it down)
```

Large training data, base model weights, and the local torch cache are kept
out of version control (see `.gitignore`) — see "Local setup" below for
where they live on disk.

## Local setup

**Requirements:** XAMPP (Apache + MySQL/MariaDB, PHP 8+), Python 3.10+.

1. **Database** — import `mkfinder_database.sql` via phpMyAdmin (creates the
   `mkfinder` schema, seed species, and a demo user). Set a real admin
   password afterwards; do not use the SQL file's placeholder hash.
2. **PHP app** — drop the repo into `htdocs/`, start Apache + MySQL in XAMPP.
3. **Python environment**
   ```bash
   pip install torch transformers pillow pymysql torchvision
   ```
4. **Environment variables** — the app never hardcodes API keys. Set:
   ```bash
   OPENROUTER_API_KEY=your-key-here        # admin species autofill (optional)
   BIRD_IDENTIFICATION_API_KEY=            # optional external ID API, unused if blank
   ```
5. Visit `index.html` in the browser, sign up, and try identifying a bird photo.

## Security notes

- Uploads are restricted by extension + MIME type, size-capped, and served
  from a directory whose `.htaccess` blocks PHP execution and non-image file
  types.
- All API keys are loaded from environment variables — none are committed
  to the repo.
- Admin-only endpoints check `$_SESSION['is_admin']` server-side before
  running privileged actions (species management, triggering fine-tuning).

## Roadmap ideas

- Move inference from `shell_exec` to a persistent Python service (FastAPI)
  for lower latency and easier scaling
- Expand the actively-supported species list beyond the current six
- Automated tests for the identification pipeline and PHP endpoints
