<?php

namespace App\Entity;

use App\Repository\ManuscriptCharacterGroupRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A run of manuscript characters that must be treated as a single glyph before the canonical
 * pattern is computed (e.g. a digraph / ligature written with several letters). Keyed by
 * source_id (see {@see ManuscriptPatternMatchEntity::$sourceId} — the manuscript schedule id);
 * a source may declare any number of groups and they are not language-specific.
 */
#[ORM\Entity(repositoryClass: ManuscriptCharacterGroupRepository::class)]
#[ORM\Table(name: "manuscript_character_group")]
#[ORM\Index(name: "idx_mcg_source_id", columns: ["source_id"])]
class ManuscriptCharacterGroupEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $sourceId;

    #[ORM\Column(type: "string", length: 255)]
    private string $sequence;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ManuscriptCharacterGroupEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    public function setSourceId(int $sourceId): ManuscriptCharacterGroupEntity
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getSequence(): string
    {
        return $this->sequence;
    }

    public function setSequence(string $sequence): ManuscriptCharacterGroupEntity
    {
        $this->sequence = $sequence;
        return $this;
    }
}
