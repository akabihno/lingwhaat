<?php

namespace App\DTO;

/**
 * Represents 100 semantic category dimensions for a word.
 * Each field is a nullable float in the range [0.0, 1.0].
 * Null means the dimension is unknown or not applicable.
 * For bipolar dimensions, 0.0 = one extreme, 1.0 = the opposite extreme.
 */
class WordCategoryData
{
    // ── Existence & Nature ──────────────────────────────────────────────────
    /** 0=non-living (rock, idea), 1=living being (dog, tree) */
    public ?float $livingBeing = null;
    /** 0=inanimate, 1=animate (self-moving) */
    public ?float $animate = null;
    /** 0=non-human, 1=human */
    public ?float $human = null;
    /** 0=non-animal, 1=animal */
    public ?float $animal = null;
    /** 0=non-plant, 1=plant */
    public ?float $plant = null;
    /** 0=artificial/man-made, 1=natural/wild */
    public ?float $naturalOrigin = null;
    /** 0=abstract/intangible, 1=physical/tangible */
    public ?float $physical = null;
    /** 0=inorganic, 1=organic/biological */
    public ?float $organic = null;
    /** 0=collective concept (flock, data), 1=singular discrete entity */
    public ?float $discrete = null;
    /** 0=partial/fragment, 1=whole/self-contained */
    public ?float $complete = null;

    // ── Size & Scale ────────────────────────────────────────────────────────
    /** 0=tiny/microscopic, 1=huge/enormous */
    public ?float $size = null;
    /** 0=lightweight/weightless, 1=heavy/massive */
    public ?float $weight = null;
    /** 0=short/brief, 1=long/extended */
    public ?float $length = null;
    /** 0=new/recent, 1=old/ancient */
    public ?float $age = null;
    /** 0=singular/one, 1=many/numerous */
    public ?float $quantity = null;
    /** 0=mild/weak, 1=intense/strong */
    public ?float $intensity = null;
    /** 0=simple/elementary, 1=complex/intricate */
    public ?float $complexity = null;
    /** 0=sparse/diluted, 1=dense/concentrated */
    public ?float $density = null;
    /** 0=shallow/surface-level, 1=deep/profound */
    public ?float $depth = null;
    /** 0=narrow/specific/local, 1=broad/general/universal */
    public ?float $scope = null;

    // ── Motion & Change ─────────────────────────────────────────────────────
    /** 0=still/static, 1=moving/dynamic */
    public ?float $motion = null;
    /** 0=slow, 1=fast */
    public ?float $speed = null;
    /** 0=random/omnidirectional, 1=directed/purposeful */
    public ?float $directed = null;
    /** 0=stable/permanent, 1=volatile/transient */
    public ?float $volatile = null;
    /** 0=sudden/abrupt, 1=gradual/incremental */
    public ?float $gradual = null;
    /** 0=linear/one-time, 1=cyclic/recurring */
    public ?float $cyclic = null;
    /** 0=shrinking/decreasing, 1=growing/increasing */
    public ?float $growth = null;
    /** 0=discrete/intermittent, 1=continuous/unbroken */
    public ?float $continuous = null;
    /** 0=irreversible, 1=reversible/undoable */
    public ?float $reversible = null;
    /** 0=preserving/conservative, 1=transformative/disruptive */
    public ?float $transformative = null;

    // ── Physical Properties ─────────────────────────────────────────────────
    /** 0=cold/freezing, 1=hot/burning */
    public ?float $temperature = null;
    /** 0=dark, 1=bright/luminous */
    public ?float $brightness = null;
    /** 0=silent/quiet, 1=loud/noisy */
    public ?float $loudness = null;
    /** 0=soft/pliant, 1=hard/rigid */
    public ?float $hardness = null;
    /** 0=dry/arid, 1=wet/moist */
    public ?float $wetness = null;
    /** 0=smooth/sleek, 1=rough/coarse */
    public ?float $roughness = null;
    /** 0=blunt/dull, 1=sharp/pointed */
    public ?float $sharpness = null;
    /** 0=robust/durable, 1=fragile/delicate */
    public ?float $fragility = null;
    /** 0=opaque, 1=transparent/clear */
    public ?float $transparency = null;
    /** 0=rigid/stiff, 1=flexible/pliable */
    public ?float $flexibility = null;

    // ── Semantic & Conceptual ───────────────────────────────────────────────
    /** 0=metaphorical/figurative, 1=literal/denotative */
    public ?float $literal = null;
    /** 0=abstract/conceptual, 1=concrete/observable */
    public ?float $concrete = null;
    /** 0=negative sentiment, 1=positive sentiment */
    public ?float $positiveValence = null;
    /** 0=informal/casual, 1=formal/official */
    public ?float $formal = null;
    /** 0=colloquial/everyday, 1=technical/specialized */
    public ?float $technical = null;
    /** 0=culture-specific, 1=universal/cross-cultural */
    public ?float $universal = null;
    /** 0=vague/approximate, 1=precise/exact */
    public ?float $precise = null;
    /** 0=subjective/opinion, 1=objective/factual */
    public ?float $objective = null;
    /** 0=indefinite/uncertain, 1=definite/certain */
    public ?float $definite = null;
    /** 0=incidental/peripheral, 1=essential/fundamental */
    public ?float $essential = null;

    // ── Emotional & Psychological ───────────────────────────────────────────
    /** 0=neutral/cognitive, 1=emotional/affective */
    public ?float $emotional = null;
    /** 0=calming/sedating, 1=arousing/exciting */
    public ?float $arousal = null;
    /** 0=solitary/individual, 1=social/communal */
    public ?float $social = null;
    /** 0=foreign/alien/exotic, 1=familiar/domestic */
    public ?float $familiar = null;
    /** 0=violent/aggressive, 1=peaceful/calm */
    public ?float $peaceful = null;
    /** 0=painful/unpleasant, 1=pleasurable/pleasant */
    public ?float $pleasurable = null;
    /** 0=safe/reassuring, 1=fear-inducing/threatening */
    public ?float $fearful = null;
    /** 0=aversive/unwanted, 1=desirable/wanted */
    public ?float $desirable = null;
    /** 0=deceptive/untrustworthy, 1=trustworthy/reliable */
    public ?float $trustworthy = null;
    /** 0=proud/grandiose, 1=humble/modest */
    public ?float $humble = null;

    // ── Grammatical Role ────────────────────────────────────────────────────
    /** 0=unlikely noun, 1=primarily used as noun */
    public ?float $nounLikelihood = null;
    /** 0=unlikely verb, 1=primarily used as verb */
    public ?float $verbLikelihood = null;
    /** 0=unlikely adjective, 1=primarily used as adjective */
    public ?float $adjectiveLikelihood = null;
    /** 0=unlikely adverb, 1=primarily used as adverb */
    public ?float $adverbLikelihood = null;
    /** 0=common noun/word, 1=proper noun (name, place) */
    public ?float $properNoun = null;
    /** 0=uncountable/mass noun, 1=countable */
    public ?float $countable = null;
    /** 0=intransitive, 1=transitive (for verb-like words) */
    public ?float $transitive = null;
    /** 0=simple root word, 1=compound/derived word */
    public ?float $compound = null;
    /** 0=native/inherited word, 1=loanword/borrowed */
    public ?float $borrowed = null;
    /** 0=arbitrary phonetic form, 1=onomatopoeic */
    public ?float $onomatopoeic = null;

    // ── Domain & Field ──────────────────────────────────────────────────────
    /** relevance to nature/environment domain */
    public ?float $natureDomain = null;
    /** relevance to science/technology domain */
    public ?float $scienceDomain = null;
    /** relevance to art/culture domain */
    public ?float $artDomain = null;
    /** relevance to social/interpersonal domain */
    public ?float $socialDomain = null;
    /** relevance to economic/commercial domain */
    public ?float $economicDomain = null;
    /** relevance to political/governance domain */
    public ?float $politicalDomain = null;
    /** relevance to religious/spiritual domain */
    public ?float $religiousDomain = null;
    /** relevance to sports/physical activity domain */
    public ?float $activityDomain = null;
    /** relevance to food/nourishment domain */
    public ?float $foodDomain = null;
    /** relevance to time/temporal concepts */
    public ?float $timeDomain = null;

    // ── Social & Cultural ───────────────────────────────────────────────────
    /** 0=adult-associated, 1=child-associated */
    public ?float $childlike = null;
    /** 0=gender-neutral, 1=gender-marked */
    public ?float $gendered = null;
    /** 0=standard/safe, 1=taboo/offensive */
    public ?float $taboo = null;
    /** 0=serious/solemn, 1=humorous/playful */
    public ?float $humorous = null;
    /** 0=contemporary/current, 1=archaic/obsolete */
    public ?float $archaic = null;
    /** 0=standard register, 1=slang/very informal */
    public ?float $slang = null;
    /** 0=geographically neutral, 1=geographically specific */
    public ?float $geographic = null;
    /** 0=year-round/timeless, 1=seasonal/periodic */
    public ?float $seasonal = null;
    /** 0=mundane/everyday, 1=ritual/ceremonial */
    public ?float $ritual = null;
    /** 0=factual/real, 1=mythological/legendary */
    public ?float $mythological = null;

    // ── Relational & Functional ─────────────────────────────────────────────
    /** 0=independent whole, 1=part/component of something */
    public ?float $partOf = null;
    /** 0=non-container, 1=container/holder/vessel */
    public ?float $container = null;
    /** 0=non-tool, 1=tool/instrument/means */
    public ?float $tool = null;
    /** 0=patient/object acted upon, 1=agent/actor/initiator */
    public ?float $agent = null;
    /** 0=non-locational, 1=location/place */
    public ?float $location = null;
    /** 0=static state/object, 1=process/event/action */
    public ?float $process = null;
    /** 0=consequential/resultant, 1=causal/initiating */
    public ?float $causal = null;
    /** 0=standalone concept, 1=relational/connective concept */
    public ?float $relational = null;
    /** 0=inedible, 1=edible/consumable */
    public ?float $edible = null;
    /** 0=wild/external, 1=domestic/household */
    public ?float $domestic = null;

    public function toArray(): array
    {
        return array_filter(
            get_object_vars($this),
            fn($v) => $v !== null
        );
    }

    public static function fromArray(array $data): self
    {
        $dto = new self();
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key) && $value !== null) {
                $dto->$key = (float) $value;
            }
        }
        return $dto;
    }

    /**
     * Returns all valid category field names.
     *
     * @return string[]
     */
    public static function getFieldNames(): array
    {
        return array_keys(get_class_vars(self::class));
    }
}
