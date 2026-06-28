import pandas as pd

SPECIAL_TOKENS = ['<pad>', '<sos>', '<eos>', '<unk>']


def build_vocab(sequences):
    """
    Build a token<->index mapping.
    SPECIAL_TOKENS are fixed at the front, and then
    we append all other tokens found in 'sequences'.
    """

    tokens = {tok for seq in sequences for tok in seq if tok not in SPECIAL_TOKENS}
    sorted_tokens = sorted(tokens)
    itos = SPECIAL_TOKENS + sorted_tokens
    stoi = {tok: idx for idx, tok in enumerate(itos)}

    return stoi, itos


def tokenize(pairs):
    """
    Wrap each sequence with <sos> and <eos>.
    Returns src and trg lists of token lists.
    """
    src_sequences = [['<sos>'] + seq + ['<eos>'] for seq, _ in pairs]
    trg_sequences = [['<sos>'] + seq + ['<eos>'] for _, seq in pairs]
    return src_sequences, trg_sequences


def split_pairs(pairs, val_split, test_split, seed=42):
    """
    Deterministic 3-way split of (src, trg) pairs.

    Carves out the test set first, then validation from the remainder, so the
    test split is fully held out from both training and early stopping.
    val_split and test_split are fractions of the full dataset.
    Returns (train_pairs, val_pairs, test_pairs).
    """
    from sklearn.model_selection import train_test_split

    train_val, test = train_test_split(pairs, test_size=test_split, random_state=seed)
    val_relative = val_split / (1.0 - test_split)
    train, val = train_test_split(train_val, test_size=val_relative, random_state=seed)
    return train, val, test


