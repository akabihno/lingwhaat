<?php

namespace App\Service\Search;

use App\Repository\WikipediaArticleRepository;
use InvalidArgumentException;

/**
 * Streams a language's Wikipedia articles (from a cursor) through a {@see ManuscriptWindowMatcher}:
 * every fixed-size window of each normalized article is reduced to its canonical pattern and offered
 * to the matcher. Nothing is persisted here — the batch is joined in memory and discarded, which is
 * what the old per-batch Elasticsearch scratch index (index → search → evict) amounted to.
 */
class WikipediaCorpusScanner
{
    private const int ARTICLE_FETCH_BATCH_SIZE = 500;

    public function __construct(
        private readonly WikipediaArticleRepository $articleRepository,
    ) {
    }

    /**
     * Scan up to $articleLimit articles whose id is greater than $afterId. Returns
     * ['processed' => <count>, 'lastArticleId' => <id>]; a processed count below $articleLimit means
     * end of data and the caller should reset the cursor to 0, otherwise it should persist
     * lastArticleId to resume the next batch from there.
     *
     * $heartbeat is called after every fetched chunk so the caller can refresh a lock TTL.
     *
     * @return array{processed: int, lastArticleId: int}
     */
    public function scan(
        ManuscriptWindowMatcher $matcher,
        string $languageCode,
        int $articleLimit,
        int $afterId = 0,
        ?callable $heartbeat = null,
    ): array {
        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        $windowSize = $matcher->getWindowSize();
        $lastArticleId = $afterId;
        $processed = 0;

        while ($processed < $articleLimit) {
            $articles = $this->articleRepository->findIdAndTextByLanguageCodeAfterId(
                $languageCode,
                min(self::ARTICLE_FETCH_BATCH_SIZE, $articleLimit - $processed),
                $lastArticleId,
            );

            if ($articles === []) {
                break;
            }

            foreach ($articles as $article) {
                $lastArticleId = $article['id'];

                // With no manuscript windows there is nothing to match; still walk the cursor so the
                // sweep keeps its position, but skip the per-window pattern work.
                if ($matcher->isEmpty()) {
                    continue;
                }

                $chars = mb_str_split($this->normalize($article['text']));
                $lastWindowStart = count($chars) - $windowSize;

                for ($windowStart = 0; $windowStart <= $lastWindowStart; $windowStart++) {
                    $matcher->observe(
                        CanonicalPattern::fromWindow($chars, $windowStart, $windowSize),
                        $article['id'],
                        $windowStart,
                    );
                }
            }

            $processed += count($articles);

            if ($heartbeat !== null) {
                ($heartbeat)();
            }
        }

        return ['processed' => $processed, 'lastArticleId' => $lastArticleId];
    }

    // Must stay identical to the scorer's normalize() (AbstractManuscriptLanguageScoreService): hit
    // local_positions are offsets into this normalized text, and the scorer slices it back out.
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
