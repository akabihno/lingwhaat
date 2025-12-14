import torch.nn as nn

class Encoder(nn.Module):
    def __init__(self, input_dim, emb_dim, hid_dim, n_layers=1, dropout=0.0):
        super().__init__()
        self.hid_dim = hid_dim
        self.n_layers = n_layers

        self.embedding = nn.Embedding(input_dim, emb_dim)
        self.rnn = nn.GRU(emb_dim, hid_dim, n_layers, dropout=dropout if n_layers > 1 else 0, bidirectional=False)
        self.dropout = nn.Dropout(dropout)

    def forward(self, src):
        # src = [src_len, batch_size]
        embedded = self.dropout(self.embedding(src))
        # embedded = [src_len, batch_size, emb_dim]

        outputs, hidden = self.rnn(embedded)
        # outputs = [src_len, batch_size, hid_dim]
        # hidden = [n_layers, batch_size, hid_dim]

        return outputs, hidden
