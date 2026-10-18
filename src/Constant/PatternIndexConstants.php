<?php

namespace App\Constant;

class PatternIndexConstants
{
    // Sliding-window size (in characters) for the Wikipedia canonical-pattern corpus. The dispatch
    // message carries it to every per-language batch, which uses it for both the corpus windows and
    // the manuscript windows. The per-language cursor records the size it was swept with, so changing
    // this restarts every language's sweep from the beginning of its corpus.
    public const int WINDOW_SIZE = 21;
}
