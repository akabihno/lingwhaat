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


def load_model(model_name: str, model_dir: str = 'models'):
    model_path = os.path.join(model_dir, model_name)
    if not os.path.exists(model_path):
        logging.error("Model file not found: %s", model_path)
        raise FileNotFoundError(f"Model file not found: {model_path}")

    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

    checkpoint = torch.load(model_path, map_location=device)

    src_stoi = checkpoint['input_stoi']
    trg_stoi = checkpoint['output_stoi']
    trg_itos = checkpoint['output_itos']

    INPUT_DIM = len(src_stoi)
    OUTPUT_DIM = len(trg_stoi)

    encoder = Encoder(INPUT_DIM, config.ENC_EMB_DIM, config.HID_DIM, config.ENC_LAYERS, config.ENC_DROPOUT)
    decoder = Decoder(OUTPUT_DIM, config.DEC_EMB_DIM, config.HID_DIM, config.DEC_LAYERS, config.DEC_DROPOUT)
    model = Seq2Seq(encoder, decoder, device).to(device)

    model.load_state_dict(checkpoint['model_state_dict'])
    model.eval()

    return model, device, src_stoi, trg_stoi, trg_itos


def encode_sequence(sequence, vocab):
    return [vocab.get('<sos>')] + [vocab.get(char, vocab['<unk>']) for char in sequence] + [vocab.get('<eos>')]


def greedy_decode(model, input_seq, src_stoi, trg_stoi, trg_itos, max_length=30):
    device = next(model.parameters()).device
    src_tensor = torch.tensor(encode_sequence(input_seq, src_stoi)).unsqueeze(1).to(device)

    with torch.no_grad():
        encoder_outputs, hidden = model.encoder(src_tensor)

    input_token = torch.tensor([trg_stoi['<sos>']]).to(device)
    result = []

    for _ in range(max_length):
        with torch.no_grad():
            output, hidden = model.decoder(input_token, hidden, encoder_outputs)
        top1 = output.argmax(1).item()

        if trg_itos[top1] == '<eos>':
            break
        result.append(trg_itos[top1])
        input_token = torch.tensor([top1]).to(device)

    return ''.join(result)


def beam_search_decode(model, input_seq, src_stoi, trg_stoi, trg_itos, beam_width=5, max_length=30):
    device = next(model.parameters()).device
    src_tensor = torch.tensor(encode_sequence(input_seq, src_stoi)).unsqueeze(1).to(device)

    with torch.no_grad():
        encoder_outputs, hidden = model.encoder(src_tensor)

    # Initialize beams: (score, tokens, hidden_state)
    beams = [(0.0, [trg_stoi['<sos>']], hidden)]

    for _ in range(max_length):
        candidates = []

        for score, tokens, h in beams:
            if tokens[-1] == trg_stoi.get('<eos>', -1):
                candidates.append((score, tokens, h))
                continue

            input_token = torch.tensor([tokens[-1]]).to(device)

            with torch.no_grad():
                output, new_hidden = model.decoder(input_token, h, encoder_outputs)

            # Get top k predictions
            log_probs = torch.log_softmax(output, dim=1)
            top_k_log_probs, top_k_indices = torch.topk(log_probs, beam_width, dim=1)

            for i in range(beam_width):
                token_id = top_k_indices[0, i].item()
                token_score = top_k_log_probs[0, i].item()
                new_score = score + token_score
                new_tokens = tokens + [token_id]
                candidates.append((new_score, new_tokens, new_hidden))

        # Select top beam_width candidates
        candidates.sort(key=lambda x: x[0] / len(x[1]), reverse=True)  # Normalize by length
        beams = candidates[:beam_width]

        # Check if all beams ended
        if all(tokens[-1] == trg_stoi.get('<eos>', -1) for _, tokens, _ in beams):
            break

    # Return best beam
    best_score, best_tokens, _ = beams[0]
    result = [trg_itos[token] for token in best_tokens[1:] if token != trg_stoi.get('<eos>', -1)]

    return ''.join(result)


def predict_ipa(csv_name: str, word: str, model_name: str, model_dir: str = 'models', csv_dir: str = 'data', use_beam_search: bool = True):
    csv_path = os.path.join(csv_dir, csv_name)
    if not os.path.exists(csv_path):
        logging.error("CSV file not found: %s", csv_path)
        raise FileNotFoundError(f"CSV file not found: {csv_path}")

    model, device, src_stoi, trg_stoi, trg_itos = load_model(model_name, model_dir)

    if use_beam_search:
        ipa = beam_search_decode(model, list(word), src_stoi, trg_stoi, trg_itos, beam_width=5)
    else:
        ipa = greedy_decode(model, list(word), src_stoi, trg_stoi, trg_itos)

    print(f"Predicted IPA ({'beam' if use_beam_search else 'greedy'}): {ipa}")

    return ipa

def predict_word(csv_name: str, ipa: str, model_name: str, model_dir: str = 'word-models', csv_dir: str = 'data', use_beam_search: bool = True):
    csv_path = os.path.join(csv_dir, csv_name)
    if not os.path.exists(csv_path):
        logging.error("CSV file not found: %s", csv_path)
        raise FileNotFoundError(f"CSV file not found: {csv_path}")

    model, device, src_stoi, trg_stoi, trg_itos = load_model(model_name, model_dir)

    ipa_clean = re.sub(r'[\[\]/]', '', ipa)

    if use_beam_search:
        word = beam_search_decode(model, list(ipa_clean), src_stoi, trg_stoi, trg_itos, beam_width=5)
    else:
        word = greedy_decode(model, list(ipa_clean), src_stoi, trg_stoi, trg_itos)

    print(f"Predicted word ({'beam' if use_beam_search else 'greedy'}): {word}")

    return word