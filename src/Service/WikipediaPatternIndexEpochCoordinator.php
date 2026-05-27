<?php

namespace App\Service;

use Redis;

/**
 * Coordinates a single Wikipedia pattern-index "epoch" across multiple workers.
 *
 * An epoch is: nuke index → fan out per-language indexing → run ManuscriptPatternMatchSearch.
 * With multiple workers, the dispatch handler kicks off the epoch but can't synchronously wait
 * for all language handlers to finish; this class lets the *last* language handler trigger the
 * search exactly once.
 *
 * State stored in Redis:
 *  - LOCK_KEY (SET NX EX 30min): held for the duration of an epoch; prevents overlapping epochs
 *    even if indexing crosses the 5-min schedule boundary.
 *  - EXPECTED_KEY: number of languages this epoch dispatched.
 *  - COMPLETED_SET_KEY: set of language_codes whose handlers finished. Set semantics make
 *    Messenger retries idempotent.
 *  - SEARCH_DISPATCHED_KEY (SET NX): guarantees the search message is dispatched exactly once
 *    even if two language handlers race past the "all done" check simultaneously.
 */
class WikipediaPatternIndexEpochCoordinator
{
    private const string LOCK_KEY = 'lingwhaat:wp_pattern_index:epoch_lock';
    private const string EXPECTED_KEY = 'lingwhaat:wp_pattern_index:expected_languages';
    private const string COMPLETED_SET_KEY = 'lingwhaat:wp_pattern_index:completed_languages';
    private const string SEARCH_DISPATCHED_KEY = 'lingwhaat:wp_pattern_index:search_dispatched';
    private const int LOCK_TTL_SECONDS = 1800; // 30 min safety net for stuck epochs

    private Redis $redis;

    public function __construct(string $redisDsn)
    {
        $parsed = parse_url($redisDsn) ?: [];

        $this->redis = new Redis();
        $this->redis->connect(
            $parsed['host'] ?? '127.0.0.1',
            (int) ($parsed['port'] ?? 6379),
        );
    }

    /**
     * Try to start a new epoch. Returns false if another epoch is already in flight
     * (i.e. the previous tick hasn't finished within LOCK_TTL_SECONDS).
     */
    public function tryStartEpoch(int $languageCount): bool
    {
        $acquired = $this->redis->set(self::LOCK_KEY, '1', ['NX', 'EX' => self::LOCK_TTL_SECONDS]);
        if ($acquired !== true) {
            return false;
        }

        $this->redis->del(self::COMPLETED_SET_KEY);
        $this->redis->del(self::SEARCH_DISPATCHED_KEY);
        $this->redis->set(self::EXPECTED_KEY, (string) $languageCount, ['EX' => self::LOCK_TTL_SECONDS]);

        return true;
    }

    /**
     * Record that a language handler has finished. Returns true when the set of completed
     * languages first reaches the expected count (i.e. this call observed the final
     * completion). Safe to call multiple times for the same language — set semantics dedup.
     */
    public function recordLanguageCompletion(string $languageCode): bool
    {
        $this->redis->sAdd(self::COMPLETED_SET_KEY, $languageCode);

        $expected = (int) ($this->redis->get(self::EXPECTED_KEY) ?: 0);
        if ($expected <= 0) {
            return false;
        }

        $completed = (int) $this->redis->sCard(self::COMPLETED_SET_KEY);
        return $completed >= $expected;
    }

    /**
     * Atomic "dispatch search exactly once" gate. Returns true to the *first* caller that
     * passes it; subsequent callers (e.g. a second worker that observed completion at the
     * same moment) get false and should not dispatch.
     */
    public function tryMarkSearchDispatched(): bool
    {
        $set = $this->redis->set(self::SEARCH_DISPATCHED_KEY, '1', ['NX', 'EX' => self::LOCK_TTL_SECONDS]);
        return $set === true;
    }

    public function endEpoch(): void
    {
        $this->redis->del(self::LOCK_KEY);
        $this->redis->del(self::COMPLETED_SET_KEY);
        $this->redis->del(self::EXPECTED_KEY);
        $this->redis->del(self::SEARCH_DISPATCHED_KEY);
    }
}
