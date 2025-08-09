import torch
from helpers.encoder import Encoder
from helpers.decoder import Decoder
from helpers.seq2seq import Seq2Seq
from utils import *
import config

_loaded_models = {}

def encode_sequence(sequence, vocab):
    return [vocab.get('<sos>')] + [vocab.get(char, vocab['<unk>']) for char in sequence] + [vocab.get('<eos>')]

def predict_ipa(word: str, model_name: str, model_dir: str = 'models'):
    if model_name not in _loaded_models:
        print(f"Loading model: {model_name}")
        model_path = os.path.join(model_dir, model_name)
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"Model file not found: {model_path}")

        checkpoint = torch.load(model_path, map_location=torch.device('cpu'))
        required_keys = ['model_state_dict', 'input_stoi', 'output_stoi', 'output_itos']
            for key in required_keys:
                if key not in checkpoint:
                    raise KeyError(f"Checkpoint missing required key: '{key}'")

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

    seq = torch.tensor(encode_sequence(word, input_stoi), dtype=torch.long).unsqueeze(1)
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