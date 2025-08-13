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


def load_model(model_name: str, src_stoi, trg_stoi, model_dir: str = 'models'):
    model_path = os.path.join(model_dir, model_name)
    if not os.path.exists(model_path):
        import traceback
        logging.error("Model file not found: %s", model_path)
        raise FileNotFoundError(f"Model file not found: {model_path}")

    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    INPUT_DIM = len(src_stoi)
    OUTPUT_DIM = len(trg_stoi)

    encoder = Encoder(INPUT_DIM, config.ENC_EMB_DIM, config.HID_DIM)
    decoder = Decoder(OUTPUT_DIM, config.DEC_EMB_DIM, config.HID_DIM)
    model = Seq2Seq(encoder, decoder, device).to(device)

    model.load_state_dict(torch.load(model_path, map_location=device))
    model.eval()

    return model, device


def encode_sequence(sequence, vocab):
    return [vocab.get('<sos>')] + [vocab.get(char, vocab['<unk>']) for char in sequence] + [vocab.get('<eos>')]


def greedy_decode(model, input_seq, src_stoi, trg_stoi, trg_itos):
    device = next(model.parameters()).device
    src_tensor = torch.tensor(encode_sequence(input_seq, src_stoi)).unsqueeze(1).to(device)

    with torch.no_grad():
        hidden = model.encoder(src_tensor)

    input_token = torch.tensor([trg_stoi['<sos>']]).to(device)
    result = []

    for _ in range(config.N_EPOCHS):
        with torch.no_grad():
            output, hidden = model.decoder(input_token, hidden)
        top1 = output.argmax(1).item()

        if trg_itos[top1] == '<eos>':
            break
        result.append(trg_itos[top1])
        input_token = torch.tensor([top1]).to(device)

    return ''.join(result)


def predict_ipa(csv_name: str, word: str, model_name: str, model_dir: str = 'models', csv_dir: str = 'data'):
    csv_path = os.path.join(csv_dir, csv_name)
    if not os.path.exists(model_path):
        import traceback
        logging.error("CSV file not found: %s", csv_path)
        raise FileNotFoundError(f"CSV file not found: {csv_path}")

    pairs_df = pd.read_csv(csv_path)
    pairs = [
        (list(word), list(re.sub(r'[\[\]/]', '', ipa)))
        for word, ipa in zip(pairs_df["word"], pairs_df["ipa"])
    ]
    src_seqs, trg_seqs = tokenize(pairs)
    src_stoi, src_itos = build_vocab(src_seqs)
    trg_stoi, trg_itos = build_vocab(trg_seqs)


    model, device = load_model(model_name, src_stoi, trg_stoi)

    ipa = greedy_decode(model, list(word), src_stoi, trg_stoi, trg_itos)
    print(f"Predicted IPA: {ipa}")

    return ipa

