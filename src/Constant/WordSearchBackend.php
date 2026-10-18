<?php

namespace App\Constant;

/**
 * Which store serves word searches while the Elasticsearch words_index is being replaced by the
 * word_search MySQL tables:
 *  - WORD_SEARCH_BACKEND: pattern and top-word lookups (elasticsearch | mysql).
 *  - WORD_LOOKUP_BACKEND: exact/fuzzy word token lookups (elasticsearch | shadow | mysql);
 *    shadow serves Elasticsearch and compares MySQL alongside, see WordSearchShadow.
 *  - WORD_PATTERN_BACKEND: wildcard and positional pattern searches (elasticsearch | shadow | mysql).
 */
final class WordSearchBackend
{
    public const string ELASTICSEARCH = 'elasticsearch';
    public const string SHADOW = 'shadow';
    public const string MYSQL = 'mysql';
}
