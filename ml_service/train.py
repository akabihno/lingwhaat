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


def train_ipa_model(csv_path, model_save_path=None):
    if model_save_path is None:
        base = os.path.basename(csv_path)
        model_name = base.replace('.csv', '_model.pt')
        model_save_path = os.path.join(models, model_name)

    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    pairs_df = pd.read_csv(csv_path)
    pairs = [
        (list(str(word)), list(re.sub(r'[\[\]/]', '', str(ipa))))
        for word, ipa in zip(pairs_df["word"], pairs_df["ipa"])
        if pd.notna(word) and pd.notna(ipa) and str(word).strip() and str(ipa).strip()
    ]
    src_seqs, trg_seqs = tokenize(pairs)

    src_stoi, src_itos = build_vocab(src_seqs)
    trg_stoi, trg_itos = build_vocab(trg_seqs)

    config.INPUT_DIM = len(src_stoi)
    config.OUTPUT_DIM = len(trg_stoi)

    train_data = IPADataset(src_seqs, trg_seqs, src_stoi, trg_stoi)
    train_iterator = DataLoader(train_data, batch_size=config.BATCH_SIZE, shuffle=True, collate_fn=collate_fn)

    print(
        f"Max src index in dataset: {max(i for seq in src_seqs for i in [src_stoi.get(c, src_stoi['<unk>']) for c in seq])}")
    print(f"config.INPUT_DIM: {len(src_stoi)}")

    enc = Encoder(config.INPUT_DIM, config.ENC_EMB_DIM, config.HID_DIM)
    dec = Decoder(config.OUTPUT_DIM, config.DEC_EMB_DIM, config.HID_DIM)
    model = Seq2Seq(enc, dec, device).to(device)

    optimizer = optim.Adam(model.parameters())
    criterion = nn.CrossEntropyLoss(ignore_index=0)

    for epoch in range(config.N_EPOCHS):
        model.train()
        epoch_loss = 0

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

        print(f'Epoch {epoch + 1}: Loss = {epoch_loss / len(train_iterator):.4f}')

    torch.save({
        'model_state_dict': model.state_dict(),
        'input_stoi': src_stoi,
        'output_stoi': trg_stoi,
        'output_itos': trg_itos
    }, model_save_path)

def train_words_model_background(csv_path: str, model_path: str):
    try:
        train_words_model(csv_path, model_save_path=model_path)
        print("Training finished successfully.")
    except Exception as e:
        import traceback
        logging.error("Training failed", exc_info=True)
        with open("training_error.log", "w") as f:
            f.write(traceback.format_exc())
    finally:
        os.remove(csv_path)

def train_words_model(csv_path, model_save_path=None):
    if model_save_path is None:
        base = os.path.basename(csv_path)
        model_name = base.replace('.csv', '_model.pt')
        model_save_path = os.path.join(models, model_name)

    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    pairs_df = pd.read_csv(csv_path)
    pairs = [
        (list(re.sub(r'[\[\]/]', '', str(ipa))), list(str(word)))
        for word, ipa in zip(pairs_df["word"], pairs_df["ipa"])
        if pd.notna(word) and pd.notna(ipa) and str(word).strip() and str(ipa).strip()
    ]
    src_seqs, trg_seqs = tokenize(pairs)

    src_stoi, src_itos = build_vocab(src_seqs)
    trg_stoi, trg_itos = build_vocab(trg_seqs)

    config.INPUT_DIM = len(src_stoi)
    config.OUTPUT_DIM = len(trg_stoi)

    train_data = IPADataset(src_seqs, trg_seqs, src_stoi, trg_stoi)
    train_iterator = DataLoader(train_data, batch_size=config.BATCH_SIZE, shuffle=True, collate_fn=collate_fn)

    print(
        f"Max src index in dataset: {max(i for seq in src_seqs for i in [src_stoi.get(c, src_stoi['<unk>']) for c in seq])}")
    print(f"config.INPUT_DIM: {len(src_stoi)}")

    enc = Encoder(config.INPUT_DIM, config.ENC_EMB_DIM, config.HID_DIM)
    dec = Decoder(config.OUTPUT_DIM, config.DEC_EMB_DIM, config.HID_DIM)
    model = Seq2Seq(enc, dec, device).to(device)

    optimizer = optim.Adam(model.parameters())
    criterion = nn.CrossEntropyLoss(ignore_index=0)

    for epoch in range(config.N_EPOCHS):
        model.train()
        epoch_loss = 0

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

        print(f'Epoch {epoch + 1}: Loss = {epoch_loss / len(train_iterator):.4f}')

    torch.save({
        'model_state_dict': model.state_dict(),
        'input_stoi': src_stoi,
        'output_stoi': trg_stoi,
        'output_itos': trg_itos
    }, model_save_path)