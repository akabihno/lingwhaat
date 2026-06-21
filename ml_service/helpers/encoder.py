import torch
import torch.nn as nn

class Encoder(nn.Module):
    def __init__(self, input_dim, emb_dim, hid_dim, n_layers=1, dropout=0.0):
        super().__init__()
        self.hid_dim = hid_dim
        self.n_layers = n_layers

        self.embedding = nn.Embedding(input_dim, emb_dim, padding_idx=0)
        self.rnn = nn.GRU(
            emb_dim, hid_dim, n_layers,
            dropout=dropout if n_layers > 1 else 0,
            bidirectional=True,
        )
        # Project the bidirectional (forward+backward) states down to hid_dim so the
        # decoder and attention operate in a single hid_dim space.
        self.fc_out = nn.Linear(hid_dim * 2, hid_dim)
        self.fc_hidden = nn.Linear(hid_dim * 2, hid_dim)
        self.dropout = nn.Dropout(dropout)

    def forward(self, src, src_len=None):
        # src = [src_len, batch_size]
        embedded = self.dropout(self.embedding(src))
        # embedded = [src_len, batch_size, emb_dim]

        if src_len is not None:
            # Pack so the GRU never reads <pad> positions.
            packed = nn.utils.rnn.pack_padded_sequence(
                embedded, src_len.cpu(), enforce_sorted=False
            )
            packed_outputs, hidden = self.rnn(packed)
            outputs, _ = nn.utils.rnn.pad_packed_sequence(packed_outputs)
        else:
            outputs, hidden = self.rnn(embedded)
        # outputs = [src_len, batch_size, hid_dim * 2]
        # hidden  = [n_layers * 2, batch_size, hid_dim]

        # Per-timestep context projected to hid_dim.
        outputs = torch.tanh(self.fc_out(outputs))
        # outputs = [src_len, batch_size, hid_dim]

        # Combine the forward and backward final states of each layer, then project.
        hidden = hidden.view(self.n_layers, 2, hidden.shape[1], self.hid_dim)
        hidden = torch.cat((hidden[:, 0], hidden[:, 1]), dim=2)
        hidden = torch.tanh(self.fc_hidden(hidden))
        # hidden = [n_layers, batch_size, hid_dim]

        return outputs, hidden
