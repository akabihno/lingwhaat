import os
import torch
import torch.nn as nn
from torch.nn.utils.rnn import pad_sequence
from typing import List
import pandas as pd
import traceback
import logging

# --- Simple character-level vocab utils ---
def build_vocab(sequences: List[str]):
    chars = set(char for seq in sequences for char in seq)
    stoi = {ch: i+1 for i, ch in enumerate(sorted(chars))}  # 0 is padding
    itos = {i: ch for ch, i in stoi.items()}
    return stoi, itos

def encode_sequence(seq: str, stoi: dict):
    return torch.tensor([stoi[ch] for ch in seq if ch in stoi], dtype=torch.long)

def decode_sequence(seq: List[int], itos: dict):
    return ''.join(itos[i] for i in seq if i in itos)

# --- Seq2Seq with Attention ---
class Encoder(nn.Module):
    def __init__(self, input_dim, emb_dim, hid_dim):
        super().__init__()
        self.embedding = nn.Embedding(input_dim, emb_dim, padding_idx=0)
        self.rnn = nn.LSTM(emb_dim, hid_dim, batch_first=True)

    def forward(self, src):
        embedded = self.embedding(src)
        outputs, (hidden, cell) = self.rnn(embedded)
        return outputs, hidden, cell

class Attention(nn.Module):
    def __init__(self, hid_dim):
        super().__init__()
        self.attn = nn.Linear(hid_dim * 2, 1)

    def forward(self, decoder_hidden, encoder_outputs):
        batch_size = encoder_outputs.size(0)
        src_len = encoder_outputs.size(1)

        decoder_hidden = decoder_hidden[-1].unsqueeze(1).repeat(1, src_len, 1)
        energy = self.attn(torch.cat((decoder_hidden, encoder_outputs), dim=2)).squeeze(2)
        return torch.softmax(energy, dim=1)

class Decoder(nn.Module):
    def __init__(self, output_dim, emb_dim, hid_dim):
        super().__init__()
        self.embedding = nn.Embedding(output_dim, emb_dim, padding_idx=0)
        self.rnn = nn.LSTM(emb_dim + hid_dim, hid_dim, batch_first=True)
        self.fc_out = nn.Linear(hid_dim * 2, output_dim)
        self.attention = Attention(hid_dim)

    def forward(self, input, hidden, cell, encoder_outputs):
        input = input.unsqueeze(1)
        embedded = self.embedding(input)

        attn_weights = self.attention(hidden, encoder_outputs).unsqueeze(1)
        context = torch.bmm(attn_weights, encoder_outputs)

        rnn_input = torch.cat((embedded, context), dim=2)
        output, (hidden, cell) = self.rnn(rnn_input, (hidden, cell))

        prediction = self.fc_out(torch.cat((output, context), dim=2).squeeze(1))
        return prediction, hidden, cell

class Seq2Seq(nn.Module):
    def __init__(self, encoder, decoder, device):
        super().__init__()
        self.encoder = encoder
        self.decoder = decoder
        self.device = device

    def forward(self, src, trg, teacher_forcing_ratio=0.5):
        batch_size = src.size(0)
        trg_len = trg.size(1)
        trg_vocab_size = self.decoder.embedding.num_embeddings

        outputs = torch.zeros(batch_size, trg_len, trg_vocab_size).to(self.device)
        encoder_outputs, hidden, cell = self.encoder(src)

        input = trg[:, 0]  # <sos>
        for t in range(1, trg_len):
            output, hidden, cell = self.decoder(input, hidden, cell, encoder_outputs)
            outputs[:, t] = output
            teacher_force = torch.rand(1).item() < teacher_forcing_ratio
            top1 = output.argmax(1)
            input = trg[:, t] if teacher_force else top1

        return outputs

def train_model(csv_path, model_save_dir='models', model_save_path=None, n_epochs=20):
    if model_save_path is None:
            base = os.path.basename(csv_path)
            model_name = base.replace('.csv', '_model.pt')
            model_save_path = os.path.join(model_save_dir, model_name)

    df = pd.read_csv(csv_path)
    words = df['word'].astype(str).tolist()
    ipas = df['ipa'].astype(str).tolist()

    input_stoi, input_itos = build_vocab(words)
    output_stoi, output_itos = build_vocab(["<sos>", "<eos>"] + ipas)
    output_stoi["<sos>"] = len(output_stoi) + 1
    output_stoi["<eos>"] = len(output_stoi) + 1

    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

    src_seqs = [encode_sequence(w, input_stoi) for w in words]
    trg_seqs = [torch.tensor([output_stoi["<sos>"]] + [output_stoi[c] for c in ipa if c in output_stoi] + [output_stoi["<eos>"]]) for ipa in ipas]

    src_seqs = pad_sequence(src_seqs, batch_first=True)
    trg_seqs = pad_sequence(trg_seqs, batch_first=True)

    input_dim = len(input_stoi) + 1
    output_dim = max(output_stoi.values()) + 1
    emb_dim = 64
    hid_dim = 128

    enc = Encoder(input_dim, emb_dim, hid_dim)
    dec = Decoder(output_dim, emb_dim, hid_dim)
    model = Seq2Seq(enc, dec, device).to(device)

    optimizer = torch.optim.Adam(model.parameters())
    criterion = nn.CrossEntropyLoss(ignore_index=0)

    model.train()
    for epoch in range(n_epochs):
        optimizer.zero_grad()
        output = model(src_seqs.to(device), trg_seqs.to(device))
        output_dim = output.shape[-1]

        output = output[:, 1:].reshape(-1, output_dim)
        trg = trg_seqs[:, 1:].reshape(-1).to(device)

        loss = criterion(output, trg)
        loss.backward()
        optimizer.step()

        print(f"Epoch {epoch+1}/{n_epochs}, Loss: {loss.item():.4f}")

    torch.save({
        'model_state_dict': model.state_dict(),
        'input_stoi': input_stoi,
        'output_stoi': output_stoi,
        'output_itos': output_itos
    }, model_save_path)

def train_model_background(csv_path: str, model_path: str):
    try:
        train_model(csv_path, model_save_path=model_path)
        print("Training finished successfully.")
    except Exception as e:
        import traceback
        logging.error("Training failed", exc_info=True)
        with open("training_error.log", "w") as f:
            f.write(traceback.format_exc())
    finally:
        os.remove(csv_path)

def predict_ipa(word: str, model_name: str, model_dir: str = 'models'):
    if model_name not in _loaded_models:
        print(f"Loading model: {model_name}")
        model_path = os.path.join(model_dir, model_name)
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"Model file not found: {model_path}")

        checkpoint = torch.load(model_path, map_location=torch.device('cpu'))

        input_stoi = checkpoint['input_stoi']
        output_stoi = checkpoint['output_stoi']
        output_itos = checkpoint['output_itos']

        input_dim = len(input_stoi) + 1
        output_dim = max(output_stoi.values()) + 1
        emb_dim = 64
        hid_dim = 128

        enc = Encoder(input_dim, emb_dim, hid_dim)
        dec = Decoder(output_dim, emb_dim, hid_dim)
        model = Seq2Seq(enc, dec, torch.device('cpu'))
        model.load_state_dict(checkpoint['model_state_dict'])
        model.eval()

        _loaded_models[model_name] = {
            "model": model,
            "input_stoi": input_stoi,
            "output_stoi": output_stoi,
            "output_itos": output_itos
        }

    model_data = _loaded_models[model_name]
    model = model_data["model"]
    input_stoi = model_data["input_stoi"]
    output_stoi = model_data["output_stoi"]
    output_itos = model_data["output_itos"]

    seq = encode_sequence(word, input_stoi).unsqueeze(0)
    encoder_outputs, hidden, cell = model.encoder(seq)

    input_token = torch.tensor([output_stoi['<sos>']])
    output_seq = []
    for _ in range(30):
        with torch.no_grad():
            output, hidden, cell = model.decoder(input_token, hidden, cell, encoder_outputs)
        top1 = output.argmax(1).item()
        if top1 == output_stoi['<eos>']:
            break
        output_seq.append(top1)
        input_token = torch.tensor([top1])

    return decode_sequence(output_seq, output_itos)
