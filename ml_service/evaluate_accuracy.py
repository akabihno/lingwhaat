"""
Script to evaluate model accuracy on test data
"""
import os
import re
import torch
import pandas as pd
from evaluate import load_model, greedy_decode, beam_search_decode
from utils import build_vocab, tokenize
import config


def calculate_accuracy(predictions, targets):
    """Calculate character-level and sequence-level accuracy"""
    total_sequences = len(predictions)
    correct_sequences = 0
    total_chars = 0
    correct_chars = 0

    for pred, target in zip(predictions, targets):
        # Sequence-level accuracy
        if pred == target:
            correct_sequences += 1

        # Character-level accuracy
        for i in range(max(len(pred), len(target))):
            total_chars += 1
            if i < len(pred) and i < len(target) and pred[i] == target[i]:
                correct_chars += 1

    seq_accuracy = (correct_sequences / total_sequences) * 100 if total_sequences > 0 else 0
    char_accuracy = (correct_chars / total_chars) * 100 if total_chars > 0 else 0

    return seq_accuracy, char_accuracy


def evaluate_model(csv_path, model_path, model_dir='models', use_beam_search=True, test_size=100):
    """
    Evaluate model accuracy on a sample of test data

    Args:
        csv_path: Path to CSV file with word-IPA pairs
        model_path: Path to trained model
        model_dir: Directory containing models
        use_beam_search: Whether to use beam search (True) or greedy decoding (False)
        test_size: Number of samples to test
    """
    print(f"\n{'='*60}")
    print(f"Evaluating model: {model_path}")
    print(f"Decoding strategy: {'Beam Search' if use_beam_search else 'Greedy'}")
    print(f"{'='*60}\n")

    # Load data
    pairs_df = pd.read_csv(csv_path)
    pairs = [
        (list(str(word)), list(re.sub(r'[\[\]/]', '', str(ipa))))
        for word, ipa in zip(pairs_df["word"], pairs_df["ipa"])
        if pd.notna(word) and pd.notna(ipa) and str(word).strip() and str(ipa).strip()
    ]

    # Take a sample for testing
    import random
    random.seed(42)
    test_pairs = random.sample(pairs, min(test_size, len(pairs)))

    src_seqs, trg_seqs = zip(*test_pairs)

    # Load model
    model_name = os.path.basename(model_path)
    model, device, src_stoi, trg_stoi, trg_itos = load_model(model_name, model_dir)

    # Make predictions
    predictions = []
    targets = []

    print(f"Testing on {len(test_pairs)} samples...\n")

    for i, (src, trg) in enumerate(test_pairs):
        if use_beam_search:
            pred = beam_search_decode(model, src, src_stoi, trg_stoi, trg_itos, beam_width=5)
        else:
            pred = greedy_decode(model, src, src_stoi, trg_stoi, trg_itos)

        target = ''.join(trg)
        predictions.append(pred)
        targets.append(target)

        # Show some examples
        if i < 5:
            input_str = ''.join(src)
            print(f"Input:  {input_str}")
            print(f"Pred:   {pred}")
            print(f"Target: {target}")
            print(f"Match:  {'✓' if pred == target else '✗'}\n")

    # Calculate accuracy
    seq_accuracy, char_accuracy = calculate_accuracy(predictions, targets)

    print(f"{'='*60}")
    print(f"Results:")
    print(f"  Sequence Accuracy: {seq_accuracy:.2f}%")
    print(f"  Character Accuracy: {char_accuracy:.2f}%")
    print(f"{'='*60}\n")

    return seq_accuracy, char_accuracy


if __name__ == "__main__":
    import sys

    if len(sys.argv) < 4:
        print("Usage: python evaluate_accuracy.py <csv_path> <model_name> <model_dir> [beam_search] [test_size]")
        print("\nExample:")
        print("  python evaluate_accuracy.py data/english.csv english_model.pt models true 100")
        sys.exit(1)

    csv_path = sys.argv[1]
    model_name = sys.argv[2]
    model_dir = sys.argv[3]
    use_beam_search = sys.argv[4].lower() == 'true' if len(sys.argv) > 4 else True
    test_size = int(sys.argv[5]) if len(sys.argv) > 5 else 100

    evaluate_model(csv_path, model_name, model_dir, use_beam_search, test_size)
