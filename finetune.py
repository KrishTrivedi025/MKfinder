# -*- coding: utf-8 -*-
"""
MKfinder - finetune.py
Fine-tunes prithivMLmods/Bird-Species-Classifier-526 using images
collected in the training_images database table.

HOW TO RUN:
  python finetune.py

REQUIREMENTS:
  pip install torch transformers pillow pymysql
"""

import os, sys, json, time, io

# Fix Windows console encoding so no UnicodeEncodeError
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

# DB CONFIG - must match config.php
DB_HOST  = "localhost"
DB_PORT  = 3306
DB_NAME  = "mkfinder"
DB_USER  = "root"
DB_PASS  = ""

HF_MODEL      = "prithivMLmods/Bird-Species-Classifier-526"
SAVE_DIR      = os.path.join(os.path.dirname(os.path.abspath(__file__)), "fine_tuned_model")
EPOCHS        = 5
LEARNING_RATE = 1e-4
BATCH_SIZE    = 4
MIN_IMAGES    = 1


def log(msg):
    print(f"[{time.strftime('%H:%M:%S')}] {msg}", flush=True)


def connect_db():
    try:
        import pymysql
        conn = pymysql.connect(
            host=DB_HOST, port=DB_PORT, db=DB_NAME,
            user=DB_USER, password=DB_PASS,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        return conn
    except ImportError:
        log("ERROR: pymysql not installed. Run: pip install pymysql")
        sys.exit(1)
    except Exception as e:
        log(f"ERROR: DB connection failed: {e}")
        sys.exit(1)


def load_training_data(conn):
    with conn.cursor() as cur:
        cur.execute("""
            SELECT id, image_filename, image_path, species_name, model_label
            FROM training_images
            WHERE used_in_training = 0
            ORDER BY added_at ASC
        """)
        rows = cur.fetchall()
    return rows


def get_label_map(model):
    id2label = model.config.id2label
    label2id = {v.upper().strip(): k for k, v in id2label.items()}
    return id2label, label2id


def finetune():
    log("=" * 55)
    log("MKfinder Fine-Tune Script")
    log("=" * 55)

    # Step 1: Connect to DB
    log("Step 1: Connecting to database...")
    conn = connect_db()
    log("  [OK] Connected to mkfinder database")

    # Step 2: Load training data
    log("Step 2: Loading training images from DB...")
    rows = load_training_data(conn)

    if len(rows) < MIN_IMAGES:
        log(f"  [WARN] Only {len(rows)} training image(s) found.")
        log(f"  Need at least {MIN_IMAGES}. Add more via admin submissions.")
        conn.close()
        sys.exit(0)

    log(f"  [OK] Found {len(rows)} training image(s)")
    for r in rows:
        log(f"     - {r['image_filename']} -> {r['species_name']}")

    # Step 3: Import ML libraries
    log("Step 3: Importing PyTorch + Transformers...")
    try:
        import torch
        import torch.nn as nn
        from torch.optim import AdamW
        from transformers import AutoImageProcessor, SiglipForImageClassification
        from PIL import Image as PILImage
        import warnings, logging as pylog
        warnings.filterwarnings("ignore")
        pylog.getLogger("transformers").setLevel(pylog.ERROR)
        log(f"  [OK] PyTorch {torch.__version__}")
    except ImportError as e:
        log(f"  [ERROR] Missing library: {e}")
        log("  Run: pip install torch transformers pillow pymysql")
        sys.exit(1)

    # Step 4: Detect GPU
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    log(f"  [OK] Device: {device}")
    if device.type == "cuda":
        log(f"     GPU: {torch.cuda.get_device_name(0)}")

    # Step 5: Load base model
    log(f"Step 4: Loading base model ({HF_MODEL})...")
    try:
        processor = AutoImageProcessor.from_pretrained(HF_MODEL)
        model     = SiglipForImageClassification.from_pretrained(HF_MODEL)
        model.to(device)
        log(f"  [OK] Model loaded - {len(model.config.id2label)} classes")
    except Exception as e:
        log(f"  [ERROR] Failed to load model: {e}")
        sys.exit(1)

    # Step 6: Load existing fine-tuned weights if present
    ft_weights = os.path.join(SAVE_DIR, "classifier_head.pt")
    if os.path.exists(ft_weights):
        try:
            state = torch.load(ft_weights, map_location=device, weights_only=True)
            model.classifier.load_state_dict(state)
            log(f"  [OK] Loaded existing fine-tuned weights from {ft_weights}")
        except Exception as e:
            log(f"  [WARN] Could not load existing weights ({e}), starting fresh")

    # Step 7: Build label map
    id2label, label2id = get_label_map(model)

    # Step 8: Prepare images
    log("Step 5: Preparing images...")
    valid_rows     = []
    images_tensors = []
    label_indices  = []

    for r in rows:
        img_path = r['image_path']

        if not os.path.exists(img_path):
            rel = os.path.join(os.path.dirname(os.path.abspath(__file__)), "uploads", r['image_filename'])
            if os.path.exists(rel):
                img_path = rel
            else:
                log(f"  [WARN] Image not found, skipping: {r['image_filename']}")
                continue

        model_label = r['model_label'].upper().strip()

        if model_label not in label2id:
            matches = [l for l in label2id if model_label in l or l in model_label]
            if matches:
                model_label = matches[0]
                log(f"  [INFO] Matched '{r['model_label']}' -> '{model_label}'")
            else:
                log(f"  [WARN] Label '{r['model_label']}' not found in model - skipping")
                log(f"         (model has 526 species; check spelling exactly)")
                continue

        label_idx = label2id[model_label]

        try:
            img     = PILImage.open(img_path).convert("RGB")
            inputs  = processor(images=img, return_tensors="pt")
            pixel_v = inputs['pixel_values'].squeeze(0)
            images_tensors.append(pixel_v)
            label_indices.append(label_idx)
            valid_rows.append(r)
            log(f"  [OK] {r['image_filename']} -> class index {label_idx} ({model_label})")
        except Exception as e:
            log(f"  [WARN] Could not process image {r['image_filename']}: {e}")
            continue

    if not valid_rows:
        log("  [ERROR] No valid images to train on. Check image paths and label names.")
        conn.close()
        sys.exit(0)

    log(f"  [OK] {len(valid_rows)} image(s) ready for training")

    # Step 9: Freeze all layers except classifier head
    log("Step 6: Freezing base model layers (training head only)...")
    for param in model.parameters():
        param.requires_grad = False
    for param in model.classifier.parameters():
        param.requires_grad = True

    trainable = sum(p.numel() for p in model.parameters() if p.requires_grad)
    total     = sum(p.numel() for p in model.parameters())
    log(f"  [OK] Trainable params: {trainable:,} / {total:,} total")

    # Step 10: Train
    log(f"Step 7: Training for {EPOCHS} epoch(s)...")
    optimizer   = AdamW(filter(lambda p: p.requires_grad, model.parameters()), lr=LEARNING_RATE)
    loss_fn     = nn.CrossEntropyLoss()
    model.train()

    pixel_batch = torch.stack(images_tensors).to(device)
    label_batch = torch.tensor(label_indices, dtype=torch.long).to(device)

    for epoch in range(1, EPOCHS + 1):
        epoch_loss = 0.0
        correct    = 0

        for start in range(0, len(valid_rows), BATCH_SIZE):
            end      = min(start + BATCH_SIZE, len(valid_rows))
            px_batch = pixel_batch[start:end]
            lb_batch = label_batch[start:end]

            optimizer.zero_grad()
            outputs  = model(pixel_values=px_batch)
            loss     = loss_fn(outputs.logits, lb_batch)
            loss.backward()
            optimizer.step()

            epoch_loss += loss.item()
            preds       = outputs.logits.argmax(dim=-1)
            correct    += (preds == lb_batch).sum().item()

        acc = correct / len(valid_rows) * 100
        log(f"  Epoch {epoch}/{EPOCHS} - Loss: {epoch_loss:.4f} - Accuracy: {acc:.1f}%")

    log("  [OK] Training complete!")

    # Step 11: Save fine-tuned weights
    log(f"Step 8: Saving fine-tuned weights to {SAVE_DIR}...")
    os.makedirs(SAVE_DIR, exist_ok=True)

    torch.save(model.classifier.state_dict(), ft_weights)

    meta = {
        "base_model":  HF_MODEL,
        "trained_on":  [r['species_name'] for r in valid_rows],
        "model_labels":[r['model_label'].upper().strip() for r in valid_rows],
        "trained_at":  time.strftime("%Y-%m-%d %H:%M:%S"),
        "epochs":      EPOCHS,
        "num_images":  len(valid_rows),
    }
    with open(os.path.join(SAVE_DIR, "meta.json"), "w", encoding='utf-8') as f:
        json.dump(meta, f, indent=2)

    log(f"  [OK] Saved: {ft_weights}")
    log(f"  [OK] Saved: {os.path.join(SAVE_DIR, 'meta.json')}")

    # Step 12: Mark rows as used in DB
    log("Step 9: Marking training images as used in DB...")
    used_ids = [r['id'] for r in valid_rows]
    with conn.cursor() as cur:
        placeholders = ','.join(['%s'] * len(used_ids))
        cur.execute(
            f"UPDATE training_images SET used_in_training=1 WHERE id IN ({placeholders})",
            used_ids
        )
    conn.commit()
    conn.close()
    log(f"  [OK] Marked {len(used_ids)} image(s) as used")

    # Done
    log("=" * 55)
    log("Fine-tuning complete!")
    log(f"   Trained species: {', '.join(set(r['species_name'] for r in valid_rows))}")
    log(f"   Model saved to:  {SAVE_DIR}/")
    log("   predict.py will auto-load these weights on next run.")
    log("=" * 55)


if __name__ == "__main__":
    finetune()