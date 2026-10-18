<?php

namespace App\Constant;

/**
 * Which store serves word searches (env WORD_SEARCH_BACKEND) while the Elasticsearch words_index
 * is being replaced by the word_search MySQL tables. Query shapes move over one at a time; shapes
 * not yet ported always use Elasticsearch.
 */
final class WordSearchBackend
{
    public const string ELASTICSEARCH = 'elasticsearch';
    public const string MYSQL = 'mysql';
}
