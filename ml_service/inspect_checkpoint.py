#!/usr/bin/env python3
"""
Model Checkpoint Inspector

This utility allows you to inspect saved model checkpoints to understand:
- What epoch the model was saved at
- Training and validation losses
- Model configuration
- Vocabulary sizes
- Parameter count

Usage:
    python inspect_checkpoint.py <model_path>

Example:
    python inspect_checkpoint.py models/russian_model.pt
"""

import torch
import sys
import os


def inspect_checkpoint(model_path):
    """Inspect a model checkpoint and print detailed information."""

    if not os.path.exists(model_path):
        print(f"Error: Model file not found: {model_path}")
        return False

    try:
        print(f"\n{'='*70}")
        print(f"Inspecting Model Checkpoint")
        print(f"{'='*70}")
        print(f"File: {model_path}")

        # Get file size
        file_size_mb = os.path.getsize(model_path) / (1024 * 1024)
        print(f"File size: {file_size_mb:.2f} MB")

        # Load checkpoint
        checkpoint = torch.load(model_path, map_location='cpu')

        print(f"\n{'='*70}")
        print(f"Checkpoint Contents")
        print(f"{'='*70}")
        print(f"Available keys: {list(checkpoint.keys())}")

        # Training information
        if 'epoch' in checkpoint or 'train_loss' in checkpoint or 'val_loss' in checkpoint:
            print(f"\n{'='*70}")
            print(f"Training Information")
            print(f"{'='*70}")

            if 'epoch' in checkpoint:
                print(f"Saved at epoch: {checkpoint['epoch']}")

            if 'train_loss' in checkpoint:
                print(f"Training loss: {checkpoint['train_loss']:.4f}")

            if 'val_loss' in checkpoint:
                print(f"Validation loss: {checkpoint['val_loss']:.4f}")

        # Model configuration
        if 'config' in checkpoint:
            print(f"\n{'='*70}")
            print(f"Model Configuration")
            print(f"{'='*70}")
            config = checkpoint['config']
            for key, value in config.items():
                print(f"  {key}: {value}")

        # Vocabulary information
        if 'input_stoi' in checkpoint or 'output_stoi' in checkpoint:
            print(f"\n{'='*70}")
            print(f"Vocabulary Information")
            print(f"{'='*70}")

            if 'input_stoi' in checkpoint:
                input_vocab = checkpoint['input_stoi']
                print(f"Input vocab size: {len(input_vocab)}")
                print(f"Sample input tokens (first 30):")
                sample_tokens = list(input_vocab.keys())[:30]
                print(f"  {sample_tokens}")

            if 'output_stoi' in checkpoint:
                output_vocab = checkpoint['output_stoi']
                print(f"\nOutput vocab size: {len(output_vocab)}")
                print(f"Sample output tokens (first 30):")
                sample_tokens = list(output_vocab.keys())[:30]
                print(f"  {sample_tokens}")

        # Model parameters
        if 'model_state_dict' in checkpoint:
            print(f"\n{'='*70}")
            print(f"Model Parameters")
            print(f"{'='*70}")

            state_dict = checkpoint['model_state_dict']

            # Count parameters
            total_params = 0
            layer_info = []

            for key, value in state_dict.items():
                if isinstance(value, torch.Tensor):
                    params = value.numel()
                    total_params += params
                    layer_info.append((key, params, value.shape))

            print(f"Total parameters: {total_params:,}")
            print(f"Approximate size: {total_params * 4 / (1024*1024):.2f} MB (float32)")

            print(f"\nLayer breakdown:")
            for name, params, shape in layer_info:
                print(f"  {name:50s} {params:>12,} params  {str(shape):>20s}")

        # Optimizer state (if present)
        if 'optimizer_state_dict' in checkpoint:
            print(f"\n{'='*70}")
            print(f"Optimizer Information")
            print(f"{'='*70}")
            print(f"Optimizer state saved: Yes")
            print(f"(Model can be resumed from this checkpoint)")
        else:
            print(f"\n{'='*70}")
            print(f"Optimizer Information")
            print(f"{'='*70}")
            print(f"Optimizer state saved: No")
            print(f"(Model cannot be resumed for continued training)")

        print(f"\n{'='*70}")
        print(f"Diagnosis")
        print(f"{'='*70}")

        # Provide diagnostic information
        if 'epoch' in checkpoint:
            epoch = checkpoint['epoch']
            if epoch < 20:
                print(f"⚠ WARNING: Model was saved at epoch {epoch}, which is very early.")
                print(f"  This model is likely undertrained and will perform poorly.")
                print(f"  Recommendation: Train for more epochs (at least 50-100).")
            elif epoch < 50:
                print(f"⚠ CAUTION: Model was saved at epoch {epoch}.")
                print(f"  This might be sufficient, but more training could improve performance.")
            else:
                print(f"✓ Model was saved at epoch {epoch}, which suggests reasonable training.")

        if 'train_loss' in checkpoint and 'val_loss' in checkpoint:
            train_loss = checkpoint['train_loss']
            val_loss = checkpoint['val_loss']

            if val_loss > train_loss * 1.5:
                print(f"⚠ WARNING: Validation loss ({val_loss:.4f}) is much higher than")
                print(f"  training loss ({train_loss:.4f}). Model is overfitting.")
                print(f"  Recommendation: Increase dropout or get more training data.")
            elif val_loss > 2.0:
                print(f"⚠ WARNING: Validation loss ({val_loss:.4f}) is high.")
                print(f"  Model performance is likely poor.")
                print(f"  Recommendation: Train longer or adjust hyperparameters.")
            elif val_loss < 0.5:
                print(f"✓ Validation loss ({val_loss:.4f}) is good!")
            else:
                print(f"ℹ Validation loss ({val_loss:.4f}) is moderate.")

        print(f"\n{'='*70}\n")
        return True

    except Exception as e:
        print(f"\nError loading checkpoint: {e}")
        import traceback
        traceback.print_exc()
        return False


def main():
    if len(sys.argv) < 2:
        print("Usage: python inspect_checkpoint.py <model_path>")
        print("\nExample:")
        print("  python inspect_checkpoint.py models/russian_model.pt")
        sys.exit(1)

    model_path = sys.argv[1]
    success = inspect_checkpoint(model_path)

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
