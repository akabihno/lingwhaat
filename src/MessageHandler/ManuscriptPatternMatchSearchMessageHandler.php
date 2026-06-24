<?php

namespace App\MessageHandler;

use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\ManuscriptCorpusSearchService;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Manual/ad-hoc entrypoint to run the manuscript search against whatever is currently resident in the
 * corpus index. In normal operation the search runs inline per batch from
 * {@see WikipediaPatternIndexLanguageMessageHandler} (index batch -> search -> evict), so this handler
 * is not on the hot path; it remains for manual dispatch.
 */
#[AsMessageHandler]
class ManuscriptPatternMatchSearchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptPatternMatchSearchMessageHandler]';
    private const string LOCK_RESOURCE_PREFIX = 'manuscript-pattern-search-language:';
    // Safety net: if a worker dies mid-search without releasing the lock, this is the maximum
    // time before another search for the same language can run. Normal runs release explicitly.
    private const int LOCK_TTL_SECONDS = 900;

    public function __construct(
        private readonly ManuscriptCorpusSearchService $corpusSearchService,
        private readonly LockFactory $lockFactory,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptPatternMatchSearchMessage $message): void
    {
        $languageCode = $message->getLanguageCode();

        // Per-language (or 'all') dedup lock: running the heavy ES sweep more than once concurrently
        // is pure waste. If a search for this language is already in flight, ack and move on.
        $lock = $this->lockFactory->createLock(
            self::LOCK_RESOURCE_PREFIX . ($languageCode ?? 'all'),
            self::LOCK_TTL_SECONDS,
            false,
        );

        if (!$lock->acquire()) {
            $this->logger->info(sprintf('Search for %s already in flight — skipping duplicate', $languageCode ?? 'all'), [
                'service' => self::LOG_SERVICE,
                'languageCode' => $languageCode,
            ]);
            return;
        }

        try {
            $this->corpusSearchService->searchLanguage($languageCode);
        } finally {
            try {
                $lock->release();
            } catch (LockReleasingException) {
                // Lock already expired or taken over; nothing to release.
            }
        }
    }
}
