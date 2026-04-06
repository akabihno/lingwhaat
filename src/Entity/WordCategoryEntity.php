<?php

namespace App\Entity;

use App\DTO\WordCategoryData;
use App\Repository\WordCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordCategoryRepository::class)]
#[ORM\Table(name: "word_category")]
#[ORM\UniqueConstraint(name: "uq_language_word", columns: ["language_code", "word"])]
#[ORM\Index(name: "idx_wc_language_code", columns: ["language_code"])]
class WordCategoryEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "language_code", length: 10)]
    private string $languageCode;

    #[ORM\Column(length: 255)]
    private string $word;

    /**
     * JSON object containing up to 100 semantic category dimensions.
     * Each key corresponds to a field in WordCategoryData.
     * Values are floats in [0.0, 1.0] or omitted when unknown.
     *
     * @var array<string, float>
     */
    #[ORM\Column(type: "json")]
    private array $categories = [];

    #[ORM\Column(name: "ts_created", type: "datetime")]
    private \DateTime $tsCreated;

    #[ORM\Column(name: "ts_updated", type: "datetime")]
    private \DateTime $tsUpdated;

    public function __construct()
    {
        $this->tsCreated = new \DateTime();
        $this->tsUpdated = new \DateTime();
    }

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

    public function getWord(): string
    {
        return $this->word;
    }

    public function setWord(string $word): self
    {
        $this->word = $word;
        return $this;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): self
    {
        $this->categories = $categories;
        return $this;
    }

    public function getCategoryData(): WordCategoryData
    {
        return WordCategoryData::fromArray($this->categories);
    }

    public function setCategoryData(WordCategoryData $data): self
    {
        $this->categories = $data->toArray();
        return $this;
    }

    public function getTsCreated(): \DateTime
    {
        return $this->tsCreated;
    }

    public function getTsUpdated(): \DateTime
    {
        return $this->tsUpdated;
    }

    public function setTsUpdated(\DateTime $tsUpdated): self
    {
        $this->tsUpdated = $tsUpdated;
        return $this;
    }
}
