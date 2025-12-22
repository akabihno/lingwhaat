import torch
import torch.nn as nn
import torch.nn.functional as F
from helpers.attention import Attention

class Decoder(nn.Module):
    def __init__(self, output_dim, emb_dim, hid_dim, n_layers=1, dropout=0.0):
        super().__init__()
        self.output_dim = output_dim
        self.hid_dim = hid_dim
        self.n_layers = n_layers

        self.embedding = nn.Embedding(output_dim, emb_dim)
        self.attention = Attention(hid_dim)
        self.rnn = nn.GRU(emb_dim + hid_dim, hid_dim, n_layers, dropout=dropout if n_layers > 1 else 0)
        self.fc_out = nn.Linear(emb_dim + hid_dim * 2, output_dim)
        self.dropout = nn.Dropout(dropout)

    def forward(self, input, hidden, encoder_outputs):
        # input = [batch_size]
        # hidden = [n_layers, batch_size, hid_dim]
        # encoder_outputs = [src_len, batch_size, hid_dim]

        input = input.unsqueeze(0)
        # input = [1, batch_size]

        embedded = self.dropout(self.embedding(input))
        # embedded = [1, batch_size, emb_dim]

        # Calculate attention using top layer hidden state
        a = self.attention(hidden[-1], encoder_outputs)
        # a = [batch_size, src_len]

        a = a.unsqueeze(1)
        # a = [batch_size, 1, src_len]

        encoder_outputs = encoder_outputs.permute(1, 0, 2)
        # encoder_outputs = [batch_size, src_len, hid_dim]

        weighted = torch.bmm(a, encoder_outputs)
        # weighted = [batch_size, 1, hid_dim]

        weighted = weighted.permute(1, 0, 2)
        # weighted = [1, batch_size, hid_dim]

        rnn_input = torch.cat((embedded, weighted), dim=2)
        # rnn_input = [1, batch_size, emb_dim + hid_dim]

        output, hidden = self.rnn(rnn_input, hidden)
        # output = [1, batch_size, hid_dim]
        # hidden = [n_layers, batch_size, hid_dim]

        embedded = embedded.squeeze(0)
        output = output.squeeze(0)
        weighted = weighted.squeeze(0)

        prediction = self.fc_out(torch.cat((output, weighted, embedded), dim=1))
        # prediction = [batch_size, output_dim]

        return prediction, hidden
