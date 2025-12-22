import config
import os
import re
import torch
import torch.optim as optim
import torch.nn as nn
from torch.utils.data import DataLoader, Dataset
from torch.nn.utils.rnn import pad_sequence
from helpers.encoder import Encoder
from helpers.decoder import Decoder
from helpers.seq2seq import Seq2Seq
from utils import *
from typing import List
import pandas as pd
import traceback
import logging


def encode(sequence, vocab):
    encoded = [vocab.get(token, vocab['<unk>']) for token in sequence]
    for idx in encoded:
        if idx >= len(vocab):
            print("⚠️ Out of bounds index:", idx, "for vocab size", len(vocab))
    return encoded


class IPADataset(Dataset):
    def __init__(self, src_seqs, trg_seqs, src_stoi, trg_stoi):
        self.data = list(zip(src_seqs, trg_seqs))
        self.src_stoi = src_stoi
        self.trg_stoi = trg_stoi

    def __len__(self):
        return len(self.data)

    def __getitem__(self, idx):
        src, trg = self.data[idx]
        src_tensor = torch.tensor(encode(src, self.src_stoi), dtype=torch.long)
        trg_tensor = torch.tensor(encode(trg, self.trg_stoi), dtype=torch.long)
        return src_tensor, trg_tensor


def collate_fn(batch):
    src, trg = zip(*batch)
    src_pad = nn.utils.rnn.pad_sequence(src, padding_value=0)
    trg_pad = nn.utils.rnn.pad_sequence(trg, padding_value=0)
    return src_pad, trg_pad

def train_ipa_model_background(csv_path: str, model_path: str):
    try:
        train_ipa_model(csv_path, model_save_path=model_path)
        print("Training finished successfully.")
    except Exception as e:
        import traceback
        logging.error("Training failed", exc_info=True)
        with open("training_error.log", "w") as f:
            f.write(traceback.format_exc())
    finally:
        os.remove(csv_path)


def train_ipa_model(csv_path, model_save_path=None, resume_from_checkpoint=False):
    if model_save_path is None:
        base = os.path.basename(csv_path)
        model_name = base.replace('.csv', '_model.pt')
        model_save_path = os.path.join(models, model_name)

    # Select best available device: CUDA (NVIDIA GPU) > MPS (Apple Silicon GPU) > CPU
    if torch.cuda.is_available():
        device = torch.device('cuda')
        device_name = f"CUDA GPU ({torch.cuda.get_device_name(0)})"
    elif hasattr(torch.backends, 'mps') and torch.backends.mps.is_available():
        device = torch.device('mps')
        device_name = "Apple Silicon GPU (Metal)"
    else:
        device = torch.device('cpu')
        device_name = "CPU"

    print(f"Using device: {device_name}")

    # Load and prepare data
    print("Loading data...")
    pairs_df = pd.read_csv(csv_path)
    pairs = [
        (list(str(word)), list(re.sub(r'[\[\]/]', '', str(ipa))))
        for word, ipa in zip(pairs_df["word"], pairs_df["ipa"])
        if pd.notna(word) and pd.notna(ipa) and str(word).strip() and str(ipa).strip()
    ]
    print(f"Loaded {len(pairs)} word-IPA pairs")

    src_seqs, trg_seqs = tokenize(pairs)

    src_stoi, src_itos = build_vocab(src_seqs)
    trg_stoi, trg_itos = build_vocab(trg_seqs)

    config.INPUT_DIM = len(src_stoi)
    config.OUTPUT_DIM = len(trg_stoi)

    # Train/validation split
    from sklearn.model_selection import train_test_split
    train_src, val_src, train_trg, val_trg = train_test_split(
        src_seqs, trg_seqs, test_size=config.VALIDATION_SPLIT, random_state=42
    )

    train_data = IPADataset(train_src, train_trg, src_stoi, trg_stoi)
    val_data = IPADataset(val_src, val_trg, src_stoi, trg_stoi)

    train_iterator = DataLoader(train_data, batch_size=config.BATCH_SIZE, shuffle=True, collate_fn=collate_fn)
    val_iterator = DataLoader(val_data, batch_size=config.BATCH_SIZE, shuffle=False, collate_fn=collate_fn)

    print(f"\n{'='*60}")
    print(f"Dataset Statistics:")
    print(f"  Total pairs: {len(pairs)}")
    print(f"  Training samples: {len(train_data)} ({len(train_data)/len(pairs)*100:.1f}%)")
    print(f"  Validation samples: {len(val_data)} ({len(val_data)/len(pairs)*100:.1f}%)")
    print(f"  Input vocab size: {len(src_stoi)}")
    print(f"  Output vocab size: {len(trg_stoi)}")
    print(f"  Batches per epoch: {len(train_iterator)}")
    print(f"{'='*60}\n")

    # Initialize model with attention, multiple layers, and dropout
    enc = Encoder(config.INPUT_DIM, config.ENC_EMB_DIM, config.HID_DIM, config.ENC_LAYERS, config.ENC_DROPOUT)
    dec = Decoder(config.OUTPUT_DIM, config.DEC_EMB_DIM, config.HID_DIM, config.DEC_LAYERS, config.DEC_DROPOUT)
    model = Seq2Seq(enc, dec, device).to(device)

    # Calculate and display model size
    total_params = sum(p.numel() for p in model.parameters())
    trainable_params = sum(p.numel() for p in model.parameters() if p.requires_grad)
    print(f"Model Architecture:")
    print(f"  Total parameters: {total_params:,}")
    print(f"  Trainable parameters: {trainable_params:,}")
    print(f"  Estimated model size: {total_params * 4 / (1024*1024):.2f} MB")
    print(f"{'='*60}\n")

    optimizer = optim.Adam(model.parameters(), lr=config.LEARNING_RATE)
    criterion = nn.CrossEntropyLoss(ignore_index=0)

    # Learning rate scheduler with updated patience
    scheduler = optim.lr_scheduler.ReduceLROnPlateau(
        optimizer, mode='min', factor=0.5,
        patience=config.LR_SCHEDULER_PATIENCE
    )

    # Early stopping variables
    best_val_loss = float('inf')
    patience_counter = 0
    start_epoch = 0

    # Resume from checkpoint if requested and exists
    if resume_from_checkpoint and os.path.exists(model_save_path):
        print(f"Resuming from checkpoint: {model_save_path}")
        checkpoint = torch.load(model_save_path, map_location=device)
        model.load_state_dict(checkpoint['model_state_dict'])
        best_val_loss = checkpoint.get('val_loss', float('inf'))
        start_epoch = checkpoint.get('epoch', 0)
        print(f"Resumed from epoch {start_epoch}, best val loss: {best_val_loss:.4f}\n")

    print(f"Training Configuration:")
    print(f"  Max epochs: {config.N_EPOCHS}")
    print(f"  Early stopping patience: {config.PATIENCE}")
    print(f"  Minimum epochs: {config.MIN_EPOCHS}")
    print(f"  Learning rate: {config.LEARNING_RATE}")
    print(f"  Batch size: {config.BATCH_SIZE}")
    print(f"{'='*60}\n")

    # Training loop
    import time
    training_start_time = time.time()

    for epoch in range(start_epoch, config.N_EPOCHS):
        epoch_start_time = time.time()

        # Training
        model.train()
        epoch_loss = 0
        batch_count = 0

        for src, trg in train_iterator:
            src, trg = src.to(device), trg.to(device)
            optimizer.zero_grad()
            output = model(src, trg)

            output_dim = output.shape[-1]
            output = output[1:].view(-1, output_dim)
            trg = trg[1:].view(-1)
            loss = criterion(output, trg)
            loss.backward()
            torch.nn.utils.clip_grad_norm_(model.parameters(), config.CLIP)
            optimizer.step()
            epoch_loss += loss.item()
            batch_count += 1

            # Progress indicator every 1000 batches
            if batch_count % 1000 == 0:
                print(f"  Epoch {epoch + 1} - Batch {batch_count}/{len(train_iterator)} - Avg Loss: {epoch_loss/batch_count:.4f}")

        train_loss = epoch_loss / len(train_iterator)

        # Validation
        model.eval()
        val_loss = 0
        with torch.no_grad():
            for src, trg in val_iterator:
                src, trg = src.to(device), trg.to(device)
                output = model(src, trg, teacher_forcing_ratio=0)  # No teacher forcing during validation

                output_dim = output.shape[-1]
                output = output[1:].view(-1, output_dim)
                trg = trg[1:].view(-1)
                loss = criterion(output, trg)
                val_loss += loss.item()

        val_loss = val_loss / len(val_iterator)
        epoch_time = time.time() - epoch_start_time

        # Calculate improvement
        improvement = ""
        if val_loss < best_val_loss:
            improvement = f" (↓ {best_val_loss - val_loss:.4f})"
        else:
            improvement = f" (↑ {val_loss - best_val_loss:.4f})"

        print(f'\n{"="*60}')
        print(f'Epoch {epoch + 1}/{config.N_EPOCHS} ({epoch_time:.1f}s)')
        print(f'  Train Loss: {train_loss:.4f}')
        print(f'  Val Loss:   {val_loss:.4f}{improvement}')
        print(f'  Best Val:   {best_val_loss:.4f}')
        print(f'  LR:         {optimizer.param_groups[0]["lr"]:.6f}')
        print(f'  Patience:   {patience_counter}/{config.PATIENCE}')

        # Learning rate scheduling
        old_lr = optimizer.param_groups[0]['lr']
        scheduler.step(val_loss)
        new_lr = optimizer.param_groups[0]['lr']
        if new_lr != old_lr:
            print(f'  ⚠ Learning rate reduced: {old_lr:.6f} -> {new_lr:.6f}')

        # Early stopping
        if val_loss < best_val_loss:
            best_val_loss = val_loss
            patience_counter = 0
            # Save best model
            torch.save({
                'model_state_dict': model.state_dict(),
                'optimizer_state_dict': optimizer.state_dict(),
                'input_stoi': src_stoi,
                'output_stoi': trg_stoi,
                'input_itos': src_itos,
                'output_itos': trg_itos,
                'train_loss': train_loss,
                'val_loss': val_loss,
                'epoch': epoch + 1,
                'config': {
                    'INPUT_DIM': config.INPUT_DIM,
                    'OUTPUT_DIM': config.OUTPUT_DIM,
                    'ENC_EMB_DIM': config.ENC_EMB_DIM,
                    'DEC_EMB_DIM': config.DEC_EMB_DIM,
                    'HID_DIM': config.HID_DIM,
                    'ENC_LAYERS': config.ENC_LAYERS,
                    'DEC_LAYERS': config.DEC_LAYERS
                }
            }, model_save_path)
            print(f'  ✓ Model saved to {model_save_path}')
        else:
            patience_counter += 1
            # Only trigger early stopping after minimum epochs
            if patience_counter >= config.PATIENCE and epoch + 1 >= config.MIN_EPOCHS:
                print(f'\n{"="*60}')
                print(f'Early stopping triggered after {epoch + 1} epochs')
                print(f'Minimum epochs requirement ({config.MIN_EPOCHS}) satisfied')
                break
            elif patience_counter >= config.PATIENCE:
                print(f'  ⚠ Would stop early, but minimum epochs ({config.MIN_EPOCHS}) not reached')

        print(f'{"="*60}\n')

    training_time = time.time() - training_start_time
    print(f'\n{"="*60}')
    print(f'Training completed!')
    print(f'  Total time: {training_time/60:.1f} minutes')
    print(f'  Final epoch: {epoch + 1}')
    print(f'  Best validation loss: {best_val_loss:.4f}')
    print(f'  Model saved to: {model_save_path}')
    print(f'{"="*60}\n')

def train_word_model_background(csv_path: str, model_path: str):
    try:
        train_word_model(csv_path, model_save_path=model_path)
        print("Training finished successfully.")
    except Exception as e:
        import traceback
        logging.error("Training failed", exc_info=True)
        with open("training_error.log", "w") as f:
            f.write(traceback.format_exc())
    finally:
        os.remove(csv_path)

def train_word_model(csv_path, model_save_path=None, resume_from_checkpoint=False):
    if model_save_path is None:
        base = os.path.basename(csv_path)
        model_name = base.replace('.csv', '_model.pt')
        model_save_path = os.path.join(models, model_name)

    # Select best available device: CUDA (NVIDIA GPU) > MPS (Apple Silicon GPU) > CPU
    if torch.cuda.is_available():
        device = torch.device('cuda')
        device_name = f"CUDA GPU ({torch.cuda.get_device_name(0)})"
    elif hasattr(torch.backends, 'mps') and torch.backends.mps.is_available():
        device = torch.device('mps')
        device_name = "Apple Silicon GPU (Metal)"
    else:
        device = torch.device('cpu')
        device_name = "CPU"

    print(f"Using device: {device_name}")

    # Load and prepare data
    print("Loading data...")
    pairs_df = pd.read_csv(csv_path)
    pairs = [
        (list(re.sub(r'[\[\]/]', '', str(ipa))), list(str(word)))
        for word, ipa in zip(pairs_df["word"], pairs_df["ipa"])
        if pd.notna(word) and pd.notna(ipa) and str(word).strip() and str(ipa).strip()
    ]
    print(f"Loaded {len(pairs)} IPA-word pairs")

    src_seqs, trg_seqs = tokenize(pairs)

    src_stoi, src_itos = build_vocab(src_seqs)
    trg_stoi, trg_itos = build_vocab(trg_seqs)

    config.INPUT_DIM = len(src_stoi)
    config.OUTPUT_DIM = len(trg_stoi)

    # Train/validation split
    from sklearn.model_selection import train_test_split
    train_src, val_src, train_trg, val_trg = train_test_split(
        src_seqs, trg_seqs, test_size=config.VALIDATION_SPLIT, random_state=42
    )

    train_data = IPADataset(train_src, train_trg, src_stoi, trg_stoi)
    val_data = IPADataset(val_src, val_trg, src_stoi, trg_stoi)

    train_iterator = DataLoader(train_data, batch_size=config.BATCH_SIZE, shuffle=True, collate_fn=collate_fn)
    val_iterator = DataLoader(val_data, batch_size=config.BATCH_SIZE, shuffle=False, collate_fn=collate_fn)

    print(f"\n{'='*60}")
    print(f"Dataset Statistics:")
    print(f"  Total pairs: {len(pairs)}")
    print(f"  Training samples: {len(train_data)} ({len(train_data)/len(pairs)*100:.1f}%)")
    print(f"  Validation samples: {len(val_data)} ({len(val_data)/len(pairs)*100:.1f}%)")
    print(f"  Input vocab size: {len(src_stoi)}")
    print(f"  Output vocab size: {len(trg_stoi)}")
    print(f"  Batches per epoch: {len(train_iterator)}")
    print(f"{'='*60}\n")

    # Initialize model with attention, multiple layers, and dropout
    enc = Encoder(config.INPUT_DIM, config.ENC_EMB_DIM, config.HID_DIM, config.ENC_LAYERS, config.ENC_DROPOUT)
    dec = Decoder(config.OUTPUT_DIM, config.DEC_EMB_DIM, config.HID_DIM, config.DEC_LAYERS, config.DEC_DROPOUT)
    model = Seq2Seq(enc, dec, device).to(device)

    # Calculate and display model size
    total_params = sum(p.numel() for p in model.parameters())
    trainable_params = sum(p.numel() for p in model.parameters() if p.requires_grad)
    print(f"Model Architecture:")
    print(f"  Total parameters: {total_params:,}")
    print(f"  Trainable parameters: {trainable_params:,}")
    print(f"  Estimated model size: {total_params * 4 / (1024*1024):.2f} MB")
    print(f"{'='*60}\n")

    optimizer = optim.Adam(model.parameters(), lr=config.LEARNING_RATE)
    criterion = nn.CrossEntropyLoss(ignore_index=0)

    # Learning rate scheduler with updated patience
    scheduler = optim.lr_scheduler.ReduceLROnPlateau(
        optimizer, mode='min', factor=0.5,
        patience=config.LR_SCHEDULER_PATIENCE
    )

    # Early stopping variables
    best_val_loss = float('inf')
    patience_counter = 0
    start_epoch = 0

    # Resume from checkpoint if requested and exists
    if resume_from_checkpoint and os.path.exists(model_save_path):
        print(f"Resuming from checkpoint: {model_save_path}")
        checkpoint = torch.load(model_save_path, map_location=device)
        model.load_state_dict(checkpoint['model_state_dict'])
        best_val_loss = checkpoint.get('val_loss', float('inf'))
        start_epoch = checkpoint.get('epoch', 0)
        print(f"Resumed from epoch {start_epoch}, best val loss: {best_val_loss:.4f}\n")

    print(f"Training Configuration:")
    print(f"  Max epochs: {config.N_EPOCHS}")
    print(f"  Early stopping patience: {config.PATIENCE}")
    print(f"  Minimum epochs: {config.MIN_EPOCHS}")
    print(f"  Learning rate: {config.LEARNING_RATE}")
    print(f"  Batch size: {config.BATCH_SIZE}")
    print(f"{'='*60}\n")

    # Training loop
    import time
    training_start_time = time.time()

    for epoch in range(start_epoch, config.N_EPOCHS):
        epoch_start_time = time.time()

        # Training
        model.train()
        epoch_loss = 0
        batch_count = 0

        for src, trg in train_iterator:
            src, trg = src.to(device), trg.to(device)
            optimizer.zero_grad()
            output = model(src, trg)

            output_dim = output.shape[-1]
            output = output[1:].view(-1, output_dim)
            trg = trg[1:].view(-1)
            loss = criterion(output, trg)
            loss.backward()
            torch.nn.utils.clip_grad_norm_(model.parameters(), config.CLIP)
            optimizer.step()
            epoch_loss += loss.item()
            batch_count += 1

            # Progress indicator every 1000 batches
            if batch_count % 1000 == 0:
                print(f"  Epoch {epoch + 1} - Batch {batch_count}/{len(train_iterator)} - Avg Loss: {epoch_loss/batch_count:.4f}")

        train_loss = epoch_loss / len(train_iterator)

        # Validation
        model.eval()
        val_loss = 0
        with torch.no_grad():
            for src, trg in val_iterator:
                src, trg = src.to(device), trg.to(device)
                output = model(src, trg, teacher_forcing_ratio=0)  # No teacher forcing during validation

                output_dim = output.shape[-1]
                output = output[1:].view(-1, output_dim)
                trg = trg[1:].view(-1)
                loss = criterion(output, trg)
                val_loss += loss.item()

        val_loss = val_loss / len(val_iterator)
        epoch_time = time.time() - epoch_start_time

        # Calculate improvement
        improvement = ""
        if val_loss < best_val_loss:
            improvement = f" (↓ {best_val_loss - val_loss:.4f})"
        else:
            improvement = f" (↑ {val_loss - best_val_loss:.4f})"

        print(f'\n{"="*60}')
        print(f'Epoch {epoch + 1}/{config.N_EPOCHS} ({epoch_time:.1f}s)')
        print(f'  Train Loss: {train_loss:.4f}')
        print(f'  Val Loss:   {val_loss:.4f}{improvement}')
        print(f'  Best Val:   {best_val_loss:.4f}')
        print(f'  LR:         {optimizer.param_groups[0]["lr"]:.6f}')
        print(f'  Patience:   {patience_counter}/{config.PATIENCE}')

        # Learning rate scheduling
        old_lr = optimizer.param_groups[0]['lr']
        scheduler.step(val_loss)
        new_lr = optimizer.param_groups[0]['lr']
        if new_lr != old_lr:
            print(f'  ⚠ Learning rate reduced: {old_lr:.6f} -> {new_lr:.6f}')

        # Early stopping
        if val_loss < best_val_loss:
            best_val_loss = val_loss
            patience_counter = 0
            # Save best model
            torch.save({
                'model_state_dict': model.state_dict(),
                'optimizer_state_dict': optimizer.state_dict(),
                'input_stoi': src_stoi,
                'output_stoi': trg_stoi,
                'input_itos': src_itos,
                'output_itos': trg_itos,
                'train_loss': train_loss,
                'val_loss': val_loss,
                'epoch': epoch + 1,
                'config': {
                    'INPUT_DIM': config.INPUT_DIM,
                    'OUTPUT_DIM': config.OUTPUT_DIM,
                    'ENC_EMB_DIM': config.ENC_EMB_DIM,
                    'DEC_EMB_DIM': config.DEC_EMB_DIM,
                    'HID_DIM': config.HID_DIM,
                    'ENC_LAYERS': config.ENC_LAYERS,
                    'DEC_LAYERS': config.DEC_LAYERS
                }
            }, model_save_path)
            print(f'  ✓ Model saved to {model_save_path}')
        else:
            patience_counter += 1
            # Only trigger early stopping after minimum epochs
            if patience_counter >= config.PATIENCE and epoch + 1 >= config.MIN_EPOCHS:
                print(f'\n{"="*60}')
                print(f'Early stopping triggered after {epoch + 1} epochs')
                print(f'Minimum epochs requirement ({config.MIN_EPOCHS}) satisfied')
                break
            elif patience_counter >= config.PATIENCE:
                print(f'  ⚠ Would stop early, but minimum epochs ({config.MIN_EPOCHS}) not reached')

        print(f'{"="*60}\n')

    training_time = time.time() - training_start_time
    print(f'\n{"="*60}')
    print(f'Training completed!')
    print(f'  Total time: {training_time/60:.1f} minutes')
    print(f'  Final epoch: {epoch + 1}')
    print(f'  Best validation loss: {best_val_loss:.4f}')
    print(f'  Model saved to: {model_save_path}')
    print(f'{"="*60}\n')