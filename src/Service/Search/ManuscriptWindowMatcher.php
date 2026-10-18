<?php

namespace App\Service\Search;

/**
 * In-memory isomorph join between manuscript windows and one batch of Wikipedia corpus windows.
 *
 * The manuscript side is registered up front (it is tiny — a few hundred windows), keyed by its
 * canonical pattern. The corpus scanner then streams every corpus window of the batch through
 * observe(); a window whose canonical pattern equals a manuscript window's is recorded as a hit for
 * that manuscript window. This replaces the per-batch Elasticsearch scratch index (index the batch
 * → _msearch the manuscript windows → evict), which did the same join through a single overloaded
 * node.
 *
 * Hit order matches the old `sort: article_id asc, local_position asc` query: the scanner feeds
 * articles by ascending id and windows by ascending position, so the first $hitsPerWindow hits kept
 * per manuscript window are exactly the ones Elasticsearch would have returned for the batch.
 */
final class ManuscriptWindowMatcher
{
    /** @var array<string, list<int>> canonical pattern => ids of the manuscript windows with it */
    private array $windowIdsByPattern = [];

    /** @var list<array{matchId: int, sourceId: int, position: int, window: string, pattern: string}> */
    private array $windows = [];

    /** @var array<int, list<array{int, int}>> window id => [articleId, localPosition] hits */
    private array $hits = [];

    public function __construct(
        private readonly int $windowSize,
        private readonly int $hitsPerWindow,
    ) {
    }

    public function getWindowSize(): int
    {
        return $this->windowSize;
    }

    public function isEmpty(): bool
    {
        return $this->windows === [];
    }

    /**
     * Register one manuscript window. Windows must be added in ascending position per match so
     * getResultsByMatch() returns them in cipher reading order.
     *
     * @param list<string> $symbols the window's glyphs (grouped ligatures count as one symbol)
     */
    public function addWindow(int $matchId, int $sourceId, int $position, array $symbols): void
    {
        if (count($symbols) !== $this->windowSize) {
            throw new \InvalidArgumentException(sprintf(
                'Window has %d symbols, expected %d.',
                count($symbols),
                $this->windowSize,
            ));
        }

        $pattern = CanonicalPattern::fromChars($symbols);
        $id = count($this->windows);

        $this->windows[] = [
            'matchId'  => $matchId,
            'sourceId' => $sourceId,
            'position' => $position,
            'window'   => implode('', $symbols),
            'pattern'  => $pattern,
        ];
        $this->windowIdsByPattern[$pattern][] = $id;
    }

    /**
     * Offer one corpus window. Callers must feed windows in (articleId, localPosition) ascending
     * order; that order is what makes the kept hits the top-N of the batch.
     */
    public function observe(string $pattern, int $articleId, int $localPosition): void
    {
        if (!isset($this->windowIdsByPattern[$pattern])) {
            return;
        }

        foreach ($this->windowIdsByPattern[$pattern] as $id) {
            if (count($this->hits[$id] ?? []) < $this->hitsPerWindow) {
                $this->hits[$id][] = [$articleId, $localPosition];
            }
        }
    }

    /**
     * Every registered window grouped by match (in registration order), each with its hits.
     *
     * @return array<int, list<array{sourceId: int, position: int, window: string, pattern: string, hits: list<array{int, int}>}>>
     */
    public function getResultsByMatch(): array
    {
        $byMatch = [];
        foreach ($this->windows as $id => $window) {
            $byMatch[$window['matchId']][] = [
                'sourceId' => $window['sourceId'],
                'position' => $window['position'],
                'window'   => $window['window'],
                'pattern'  => $window['pattern'],
                'hits'     => $this->hits[$id] ?? [],
            ];
        }

        return $byMatch;
    }
}
