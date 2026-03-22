"""
MKfinder predict.py — Fixed stdout issue + MobileNet properly working
"""
import sys
import json
import os
import warnings
import logging

warnings.filterwarnings("ignore")
os.environ["HF_HUB_DISABLE_PROGRESS_BARS"]     = "1"
os.environ["TRANSFORMERS_NO_ADVISORY_WARNINGS"] = "1"
os.environ["HF_HUB_VERBOSITY"]                 = "error"
logging.getLogger("huggingface_hub").setLevel(logging.ERROR)
logging.getLogger("transformers").setLevel(logging.ERROR)

HF_MODEL = "prithivMLmods/Bird-Species-Classifier-526"

SUPPORTED_SPECIES = {
    "AMERICAN ROBIN":            "American Robin",
    "BLUE GROSBEAK":             "Blue Grosbeak",
    "NORTHERN CARDINAL":         "Northern Cardinal",
    "BALD EAGLE":                "Bald Eagle",
    "RUBY THROATED HUMMINGBIRD": "Ruby Throated Hummingbird",
    "EASTERN BLUEBIRD":          "Eastern Bluebird",
}

CONFIDENCE_THRESHOLD = 8.0

IMAGENET_BIRD_INDICES = set(range(7, 24))

BIRD_WORDS = [
    "cock","hen","ostrich","brambling","goldfinch","finch","junco",
    "bunting","robin","bulbul","jay","magpie","chickadee","ouzel",
    "kite","eagle","vulture","bird","hawk","owl","duck","goose",
    "crane","heron","woodpecker","wren","thrush","warbler","dove",
    "pigeon","parrot","penguin","flamingo","pelican","stork","swift",
    "swallow","kingfisher","quail","peacock","toucan","hornbill",
    "macaw","cockatoo","ibis","albatross","raven","falcon","osprey",
    "kestrel","grouse","sparrow","hummingbird","cardinal",
]

# These short bird words can appear inside non-bird labels (e.g. "cock" in "cocktail")
# so we check them with word boundaries
BIRD_WORDS_EXACT = {"cock","hen","kite","swift","crane","martin","swift","rail","snipe"}


def is_bird_label(label):
    """Check if a label contains a bird word, avoiding false positives."""
    label_lower = label.lower()
    # Check long bird words as substrings (safe — unlikely to appear in non-bird labels)
    long_words = [w for w in BIRD_WORDS if len(w) > 4]
    if any(w in label_lower for w in long_words):
        return True
    # Check short bird words only as whole words
    import re
    short_words = [w for w in BIRD_WORDS if len(w) <= 4]
    for w in short_words:
        if re.search(r'\b' + re.escape(w) + r'\b', label_lower):
            return True
    return False

NON_BIRD_KEYWORDS = [
    # Person / clothing
    "sunglass","sunglasses","glasses","spectacles",
    "suit","tie","bow tie","bolo tie","windsor tie","jersey","lab coat",
    "dress","shirt","skirt","swimwear","bra","mitten","glove","stole",
    "hair","beard","lipstick","wig","bandage","mask","neck brace",
    "seat belt","crutch","snorkel","apron","cardigan","cloak","poncho",
    # Vehicles
    "car","truck","bus","bicycle","motorcycle","airplane","train","boat",
    "cab","minivan","ambulance","tractor","forklift","scooter",
    # Electronics
    "laptop","phone","keyboard","monitor","television","camera","remote",
    "cellular telephone","ipod","projector","printer","modem","mouse",
    # Food / kitchen
    "pizza","burger","sandwich","bottle","cup","bowl","plate","vase",
    "wine bottle","beer bottle","water bottle","pop bottle",
    "coffee mug","pitcher","pot","pan","ladle","spatula","tongs",
    # Furniture / indoor
    "chair","sofa","table","desk","bed","shelf","bookcase","filing cabinet",
    "couch","rocking chair","stool","wardrobe","chest","safe",
    # Tools / objects
    "book","pen","pencil","scissors","ruler","hammer","nail","screwdriver",
    "wrench","plier","axe","shovel","hatchet","chisel",
    "plunger","drumstick","Band Aid","eraser","torch","lighter",
    "umbrella","crutch","stretcher","bucket","barrel","cistern",
    "radio","dial telephone","clock","stopwatch","hourglass",
    "pillow","blanket","bath towel","shower curtain","toilet",
    "candle","lamp","spotlight","chandelier","lantern",
    # Animals (not birds)
    "dog","cat","horse","cow","bear","lion","tiger","elephant","monkey",
    "snake","lizard","frog","spider","scorpion","fish","shark","whale",
    "rabbit","hamster","squirrel","fox","wolf","deer","zebra","giraffe",
    # Buildings / outdoor
    "building","house","bridge","tower","wall","window","door",
    "fountain","statue","column","pedestal","streetcar","barn",
    # Toys
    "teddy","toy","doll","stuffed","puppet","rocking horse",
]


def mobilenet_check(image_path):
    """
    Returns (is_not_bird: bool, label: str, conf: float)
    IMPORTANT: Uses a completely separate real_stdout so print() still works.
    """
    real_stdout = sys.__stdout__
    real_stderr = sys.__stderr__

    try:
        import torch
        import torch.nn.functional as F
        import torchvision.models as tvm
        import torchvision.transforms as T
        from PIL import Image as PILImage
        import io

        # Redirect to buffer to suppress download progress
        buf = io.StringIO()
        sys.stdout = buf
        sys.stderr = buf
        try:
            weights = tvm.MobileNet_V3_Small_Weights.DEFAULT
            model   = tvm.mobilenet_v3_small(weights=weights)
        finally:
            sys.stdout = real_stdout
            sys.stderr = real_stderr

        model.eval()
        labels = weights.meta["categories"]

        transform = T.Compose([
            T.Resize(256), T.CenterCrop(224), T.ToTensor(),
            T.Normalize([0.485,0.456,0.406],[0.229,0.224,0.225]),
        ])

        img    = PILImage.open(image_path).convert("RGB")
        tensor = transform(img).unsqueeze(0)

        with torch.no_grad():
            probs = F.softmax(model(tensor), dim=1).squeeze()

        top_probs, top_idxs = probs.topk(20)

        # STEP 1 — if ANY top-20 result is a bird index or bird word → PASS
        for i in range(20):
            conf  = top_probs[i].item() * 100
            if conf < 0.5:
                break
            idx   = top_idxs[i].item()
            label = labels[idx]
            if idx in IMAGENET_BIRD_INDICES and conf >= 1.0:
                return False, label.lower(), conf
            if is_bird_label(label) and conf >= 1.0:
                return False, label.lower(), conf

        # STEP 2 — check ALL top-20 for non-bird keywords at low threshold
        for i in range(20):
            conf  = top_probs[i].item() * 100
            if conf < 0.5:
                break
            label = labels[top_idxs[i].item()].lower()
            for kw in NON_BIRD_KEYWORDS:
                if kw.lower() in label and conf >= 10.0:
                    return True, labels[top_idxs[i].item()], conf

        # STEP 3 — CATCH-ALL: if zero bird evidence in top-20 at all
        # and top-1 has reasonable confidence → definitely not a bird
        top_label = labels[top_idxs[0].item()].lower()
        top_conf  = top_probs[0].item() * 100
        if top_conf >= 15.0:
            # No bird word or bird index found in any top-20 result
            # This means the image has no bird-like features at all
            return True, labels[top_idxs[0].item()], top_conf

        top_label = labels[top_idxs[0].item()].lower()
        top_conf  = top_probs[0].item() * 100
        return False, top_label, top_conf

    except Exception as ex:
        sys.stdout = real_stdout
        sys.stderr = real_stderr
        return False, "unknown", 0.0


def load_siglip():
    """Load Siglip model, suppressing all output safely."""
    real_stdout = sys.__stdout__
    real_stderr = sys.__stderr__

    import io
    buf = io.StringIO()
    sys.stdout = buf
    sys.stderr = buf
    try:
        from transformers import AutoImageProcessor, SiglipForImageClassification
        import torch

        processor = AutoImageProcessor.from_pretrained(
            HF_MODEL, local_files_only=True, use_fast=False
        )
        model = SiglipForImageClassification.from_pretrained(
            HF_MODEL, local_files_only=True
        )
    finally:
        sys.stdout = real_stdout
        sys.stderr = real_stderr

    # Fine-tuned weights
    ft = os.path.join(os.path.dirname(__file__), "fine_tuned_model", "classifier_head.pt")
    if os.path.exists(ft):
        try:
            import torch
            buf2 = io.StringIO()
            sys.stdout = buf2
            sys.stderr = buf2
            try:
                state = torch.load(ft, map_location="cpu", weights_only=True)
            finally:
                sys.stdout = real_stdout
                sys.stderr = real_stderr
            model.classifier.load_state_dict(state)
        except Exception:
            sys.stdout = real_stdout
            sys.stderr = real_stderr

    model.eval()
    return processor, model


def not_bird_message(label):
    l = label.lower()
    if any(w in l for w in ["sunglass","glasses","suit","tie","dress","shirt",
                              "jersey","hair","beard","person","human","face",
                              "coat","skirt","swimwear","neck","crutch","snorkel",
                              "seat belt","stole","bandage","mask","wig","lipstick",
                              "apron","cardigan","cloak","poncho","mitten","glove"]):
        return "This looks like a photo of a person. Please upload a clear photo of a bird."
    if any(w in l for w in ["teddy","toy","doll","stuffed","puppet","rocking horse"]):
        return "This looks like a photo of a toy or object. Please upload a clear photo of a bird."
    if any(w in l for w in ["dog","cat","horse","cow","bear","lion","tiger",
                              "elephant","monkey","snake","lizard","frog","fish",
                              "rabbit","hamster","squirrel","fox","wolf","deer"]):
        return "This looks like a photo of an animal, but not a bird. Please upload a bird photo."
    if any(w in l for w in ["car","truck","bus","bicycle","motorcycle",
                              "airplane","train","boat","phone","laptop","scooter"]):
        return "This looks like a photo of a vehicle or device. Please upload a bird photo."
    if any(w in l for w in ["bottle","cup","bowl","plate","vase","chair","lamp",
                              "sofa","table","desk","building","house","statue",
                              "fountain","pillow","candle","umbrella","bucket"]):
        return "This looks like a photo of an object or place. Please upload a bird photo."
    return "This doesn't appear to be a bird photo. Please upload a clear photo of a bird."


def predict_bird(image_path):
    # ALWAYS use sys.__stdout__ for the final JSON output
    # This guarantees PHP receives the JSON even if sys.stdout was redirected
    out = sys.__stdout__

    result = {
        "success": False, "species": "", "confidence": 0.0,
        "all_scores": {}, "error": "", "mode": ""
    }

    if not os.path.exists(image_path):
        result["error"] = f"Image not found: {image_path}"
        print(json.dumps(result), file=out, flush=True)
        return

    # ── STAGE 1: MobileNet non-bird check ─────────────────────────────────────
    is_not_bird, mn_label, mn_conf = mobilenet_check(image_path)

    if is_not_bird:
        result.update({
            "success":    False,
            "error":      not_bird_message(mn_label),
            "error_code": "NOT_A_BIRD",
            "mode":       "not_a_bird",
            "detected_as": mn_label,
        })
        print(json.dumps(result), file=out, flush=True)
        return

    # ── STAGE 2: Siglip bird identification ───────────────────────────────────
    try:
        import torch
        import torch.nn.functional as F
        from PIL import Image as PILImage
    except ImportError as e:
        result["error"] = f"Missing library: {e}"
        print(json.dumps(result), file=out, flush=True)
        return

    try:
        processor, model = load_siglip()
    except Exception as e:
        result["error"] = f"Failed to load model: {e}"
        print(json.dumps(result), file=out, flush=True)
        return

    try:
        img    = PILImage.open(image_path).convert("RGB")
        inputs = processor(images=img, return_tensors="pt")
        with torch.no_grad():
            probs = F.softmax(model(**inputs).logits, dim=1).squeeze().tolist()
    except Exception as e:
        result["error"] = f"Inference failed: {e}"
        print(json.dumps(result), file=out, flush=True)
        return

    id2label  = model.config.id2label
    score_map = {
        id2label.get(i, f"Class_{i}").upper().strip(): float(p) * 100
        for i, p in enumerate(probs)
    }

    top_label = max(score_map, key=score_map.get)
    top_conf  = score_map[top_label]

    your_scores = {
        app: round(score_map.get(lbl, 0.0), 2)
        for lbl, app in SUPPORTED_SPECIES.items()
    }

    best_match, best_conf = None, 0.0
    for lbl, app in SUPPORTED_SPECIES.items():
        score = score_map.get(lbl, 0.0)
        if score >= CONFIDENCE_THRESHOLD and score > best_conf:
            best_match, best_conf = app, score

    if best_match:
        result.update({
            "success":    True,
            "species":    best_match,
            "confidence": round(best_conf, 2),
            "all_scores": your_scores,
            "error":      "",
            "mode":       "ai_model",
        })
    else:
        result.update({
            "success":    True,
            "species":    "Unknown",
            "confidence": round(top_conf, 2),
            "all_scores": your_scores,
            "error":      "",
            "mode":       "ai_model_other_species",
            "debug":      f"Top: '{top_label}' at {top_conf:.1f}%",
        })

    print(json.dumps(result), file=out, flush=True)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Usage: python predict.py <image_path>"}),
              file=sys.__stdout__, flush=True)
        sys.exit(1)
    predict_bird(sys.argv[1])