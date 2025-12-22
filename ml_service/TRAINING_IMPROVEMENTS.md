# ML Service Training Improvements

## Overview

This document describes the improvements made to the ML training system to fix fundamental issues with model training, particularly for large datasets (100k+ samples).

## Problems Fixed

### 1. **Undertrained Models**
- **Problem**: Models were stopping training after only 10-15 epochs due to aggressive early stopping
- **Solution**: Increased max epochs to 500, patience to 30, and added minimum epoch requirement of 20

### 2. **Poor Model Performance**
- **Problem**: Models gave incorrect predictions (e.g., "солнце" → "st͡sɛ" instead of "ˈsolnt͡sɛ")
- **Root Cause**: Model saved at epoch 2-3 when it barely learned anything, then never improved
- **Solution**: Extended training duration and better learning rate scheduling

### 3. **Lack of Training Visibility**
- **Problem**: No way to know what epoch model was saved at or why training stopped
- **Solution**: Comprehensive logging with progress indicators and checkpoint inspection tool

### 4. **Model File Size Confusion**
- **Problem**: Users thought 27MB file size indicated training stopped
- **Clarification**: 27MB is the expected size for this architecture - it's based on model parameters, not training data

## Configuration Changes

### `/ml_service/config.py`

| Parameter | Old Value | New Value | Reason |
|-----------|-----------|-----------|--------|
| `N_EPOCHS` | 100 | 500 | Allow sufficient training for large datasets |
| `PATIENCE` | 10 | 30 | Prevent premature stopping |
| `VALIDATION_SPLIT` | 0.1 (10%) | 0.05 (5%) | More data for training with large datasets |
| `LR_SCHEDULER_PATIENCE` | 5 | 10 | Give model more time before reducing LR |
| `MIN_EPOCHS` | N/A | 20 | New: Minimum epochs before early stopping |

## New Features

### 1. Comprehensive Training Logs

Training now displays detailed information:

```
============================================================
Dataset Statistics:
  Total pairs: 402466
  Training samples: 382142 (95.0%)
  Validation samples: 20324 (5.0%)
  Input vocab size: 156
  Output vocab size: 234
  Batches per epoch: 11942
============================================================

Model Architecture:
  Total parameters: 6,847,234
  Trainable parameters: 6,847,234
  Estimated model size: 26.12 MB
============================================================

Training Configuration:
  Max epochs: 500
  Early stopping patience: 30
  Minimum epochs: 20
  Learning rate: 0.001
  Batch size: 32
============================================================

Epoch 1/500 (245.3s)
  Train Loss: 2.3456
  Val Loss:   2.1234 (↓ inf)
  Best Val:   2.1234
  LR:         0.001000
  Patience:   0/30
  ✓ Model saved to models/russian_model.pt
============================================================
```

### 2. Progress Indicators

During training, you'll see progress every 1000 batches:
```
  Epoch 5 - Batch 1000/11942 - Avg Loss: 1.8234
  Epoch 5 - Batch 2000/11942 - Avg Loss: 1.7891
  ...
```

### 3. Checkpoint Resumption

Models can now resume from checkpoints:

```python
# Resume training from existing checkpoint
train_ipa_model(
    csv_path="data/russian.csv",
    model_save_path="models/russian_model.pt",
    resume_from_checkpoint=True
)
```

### 4. Model Inspection Tool

New utility to inspect saved models:

```bash
python ml_service/inspect_checkpoint.py models/russian_model.pt
```

Output:
```
======================================================================
Inspecting Model Checkpoint
======================================================================
File: models/russian_model.pt
File size: 26.12 MB

Training Information:
  Saved at epoch: 3
  Training loss: 2.3456
  Validation loss: 2.1234

Diagnosis:
⚠ WARNING: Model was saved at epoch 3, which is very early.
  This model is likely undertrained and will perform poorly.
  Recommendation: Train for more epochs (at least 50-100).
```

## How to Retrain Your Models

### Option 1: Start Fresh (Recommended)

Delete the old undertrained model and train from scratch:

```bash
# On your training server
cd /opt/lingwhaat/ml_service
rm models/russian_model.pt
python -c "from train import train_ipa_model; train_ipa_model('data/russian.csv', 'models/russian_model.pt')"
```

The new training will:
- Run for up to 500 epochs (or until early stopping after 30 epochs of no improvement)
- Provide detailed progress updates
- Save only when validation loss improves
- Ensure at least 20 epochs before allowing early stopping

### Option 2: Resume from Existing Checkpoint

If you want to continue training an existing model:

```bash
python -c "from train import train_ipa_model; train_ipa_model('data/russian.csv', 'models/russian_model.pt', resume_from_checkpoint=True)"
```

## Understanding the Training Process

### Model File Size

The model file size (~27MB) is determined by:
- Architecture: 2-layer encoder/decoder with hidden size 512
- Vocabulary sizes (input/output)
- Number of parameters (~6.8M)

**The file size does NOT change based on:**
- Amount of training data
- Number of epochs trained
- How well the model performs

### When Models Are Saved

Models are saved ONLY when validation loss improves:
- Epoch 1: Val loss 2.5 → Model saved (27MB file created)
- Epoch 2: Val loss 2.1 → Model saved (file updated)
- Epoch 3: Val loss 2.0 → Model saved (file updated)
- Epochs 4-33: Val loss never goes below 2.0 → File NOT updated
- Epoch 34: Early stopping triggered

In this example, the 27MB file contains the model from epoch 3, even though training ran for 34 epochs.

### Monitoring Training Progress

**On the training server**, you can monitor progress by:

1. **Check the training output** (if you see it):
   - Look for "Epoch X/500" messages
   - Check if validation loss is decreasing
   - Look for "Model saved" messages

2. **Inspect the saved model**:
   ```bash
   python ml_service/inspect_checkpoint.py models/russian_model.pt
   ```

3. **Check for training completion**:
   - Look for "Training completed!" message
   - Or "Early stopping triggered after X epochs"

## Expected Training Time

For 402k samples with batch size 32:
- ~11,942 batches per epoch
- ~3-5 minutes per epoch (GPU)
- ~60-90 minutes per epoch (CPU)

For proper training:
- Minimum: 20 epochs (1-30 hours depending on hardware)
- Typical: 50-150 epochs (3-12 hours GPU, longer on CPU)

## Troubleshooting

### Problem: Training taking too long

**Solution**: Check if you're using GPU:
```python
import torch
print(torch.cuda.is_available())  # Should print True
```

If False, training will be much slower on CPU.

### Problem: Validation loss not improving after epoch 2-3

This is the issue you were experiencing! Possible causes:
1. Learning rate too high → model overshoots
2. Model too simple for the data
3. Data quality issues

**Solution**: The new configuration should help, but if problem persists:
- Try reducing learning rate to 0.0005
- Increase model capacity (HID_DIM=1024)

### Problem: Training stops at 27MB

This is not actually a problem! The file size is correct. What matters is:
- What epoch was the model saved at? (Use inspect_checkpoint.py)
- What was the validation loss?
- Did training complete or stop early?

## API Changes

The training functions now accept an additional parameter:

```python
def train_ipa_model(csv_path, model_save_path=None, resume_from_checkpoint=False):
    ...

def train_word_model(csv_path, model_save_path=None, resume_from_checkpoint=False):
    ...
```

The API endpoints remain unchanged - they'll use the new training code automatically.

## Recommendations for Large Datasets

For datasets with 100k+ samples:

1. **Use GPU if possible** - Training will be 10-20x faster
2. **Monitor first few epochs** - Make sure loss is decreasing
3. **Don't interrupt training** - Let it run to completion or early stopping
4. **Inspect the model afterward** - Use inspect_checkpoint.py to verify it trained properly
5. **Test predictions** - Try a few test words to verify quality

## Summary of Changes

### Files Modified:
- `ml_service/config.py` - Updated training parameters
- `ml_service/train.py` - Enhanced logging, checkpoint resumption, progress tracking

### Files Added:
- `ml_service/inspect_checkpoint.py` - Model inspection utility
- `ml_service/TRAINING_IMPROVEMENTS.md` - This documentation

### Key Improvements:
- ✅ Extended training duration (100 → 500 epochs)
- ✅ Increased early stopping patience (10 → 30 epochs)
- ✅ Added minimum epoch requirement (20 epochs)
- ✅ Comprehensive training logs with progress indicators
- ✅ Checkpoint resumption capability
- ✅ Model inspection utility
- ✅ Better learning rate scheduling
- ✅ Detailed diagnostics and timing information

## Next Steps

1. **Inspect your current models** to see what epoch they're from:
   ```bash
   python ml_service/inspect_checkpoint.py models/russian_model.pt
   python ml_service/inspect_checkpoint.py models/latvian_model.pt
   ```

2. **Retrain your models** with the new configuration:
   - They should train to much better completion
   - You'll see detailed progress during training
   - Final models should perform much better

3. **Test the predictions** to verify improvement:
   ```bash
   php bin/console ml:use:ipa-predictor --lang russian --word солнце
   ```

   Expected improvement:
   - Before: "st͡sɛ" (incorrect, truncated)
   - After: "ˈsolnt͡sɛ" (correct, full transcription)
