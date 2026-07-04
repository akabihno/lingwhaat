<?php

namespace App\Constant;

class PatternIndexConstants
{
    // Sliding-window size (in characters) for the Wikipedia canonical-pattern corpus.
    // The indexer bakes this into every ES doc (`length` field and the deterministic `_id`),
    // and the search side filters on `length` = window size, so the index and search sides
    // must always agree — both read this single constant. Changing it triggers a per-language
    // index rebuild and a full corpus re-sweep (see WikipediaPatternIndexerService).
    public const int WINDOW_SIZE = 29;
}
