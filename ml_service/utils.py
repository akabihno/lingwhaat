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


