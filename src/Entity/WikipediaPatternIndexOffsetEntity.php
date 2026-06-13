<?php

namespace App\Entity;

use App\Repository\WikipediaPatternIndexOffsetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WikipediaPatternIndexOffsetRepository::class)]
#[ORM\Table(name: "wikipedia_pattern_index_offset")]
#[ORM\UniqueConstraint(name: "uq_language_code", columns: ["language_code"])]
class WikipediaPatternIndexOffsetEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "language_code", type: "string", length: 8)]
    private string $languageCode;

    // Keyset cursor: the id of the last wikipedia_article processed for this language. The next
    // batch resumes from id > this value; reset to 0 to restart a full pass. (Despite the table
    // name, this is no longer a row offset — see Version20260609120000 migration.)
    #[ORM\Column(name: "last_article_id", type: "bigint")]
    private int $lastArticleId = 0;

    #[ORM\Column(name: "window_size", type: "integer")]
    private int $windowSize;

    // Timestamp of the last completed batch (success or failure after retries). Used by the
    // dispatch handler to detect dead self-chains and re-kick them. Updated at the start of
    // each processing attempt so a crashed worker still advances the clock.
    #[ORM\Column(name: "last_run_at", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $lastRunAt = null;

    // Article limit to use on the next batch, calculated from the previous run's timing so that
    // batches converge on a target wall-clock duration. Capped by the handler's MAX_ARTICLE_LIMIT.
    #[ORM\Column(name: "next_article_limit", type: "integer")]
    private int $nextArticleLimit = 5;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getLastArticleId(): int
    {
        return $this->lastArticleId;
    }

    public function setLastArticleId(int $lastArticleId): self
    {
        $this->lastArticleId = $lastArticleId;
        return $this;
    }

    public function getWindowSize(): int
    {
        return $this->windowSize;
    }

    public function setWindowSize(int $windowSize): self
    {
        $this->windowSize = $windowSize;
        return $this;
    }

    public function getLastRunAt(): ?\DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(\DateTimeImmutable $lastRunAt): self
    {
        $this->lastRunAt = $lastRunAt;
        return $this;
    }

    public function getNextArticleLimit(): int
    {
        return $this->nextArticleLimit;
    }

    public function setNextArticleLimit(int $nextArticleLimit): self
    {
        $this->nextArticleLimit = $nextArticleLimit;
        return $this;
    }
}
