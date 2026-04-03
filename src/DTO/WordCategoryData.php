<?php

namespace App\DTO;

/**
 * Represents 1000 semantic category dimensions for a word.
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

    // ── Cognitive & Mental ──────────────────────────────────────────────────
    /** 0=instinctive/automatic, 1=deliberate/logical */
    public ?float $reasoning = null;
    /** 0=unconscious/automatic, 1=conscious/self-aware */
    public ?float $conscious = null;
    /** 0=accidental/unintended, 1=intentional/purposeful */
    public ?float $intentional = null;
    /** 0=conventional/standard, 1=creative/novel */
    public ?float $creative = null;
    /** 0=holistic/intuitive, 1=analytical/systematic */
    public ?float $analytical = null;
    /** 0=innate/instinctive, 1=learned/acquired skill */
    public ?float $learned = null;
    /** 0=forgettable/trivial, 1=salient/memorable */
    public ?float $memorable = null;
    /** 0=purely physical/sensory, 1=cognitively demanding */
    public ?float $cognitive = null;
    /** 0=impulsive/reactive, 1=reflective/contemplative */
    public ?float $reflective = null;
    /** 0=realistic/literal-minded, 1=imaginative/fantastical */
    public ?float $imaginative = null;

    // ── Spatial & Geometric ─────────────────────────────────────────────────
    /** 0=one-dimensional/linear, 1=three-dimensional/volumetric */
    public ?float $dimensionality = null;
    /** 0=asymmetric/irregular, 1=symmetric/regular */
    public ?float $symmetry = null;
    /** 0=open/exposed, 1=enclosed/contained */
    public ?float $enclosed = null;
    /** 0=low/ground-level, 1=high/elevated */
    public ?float $elevated = null;
    /** 0=peripheral/marginal, 1=central/focal */
    public ?float $centered = null;
    /** 0=unbounded/infinite extent, 1=bounded/finite */
    public ?float $bounded = null;
    /** 0=omnidirectional, 1=directionally oriented */
    public ?float $oriented = null;
    /** 0=distant/far, 1=near/proximate */
    public ?float $proximate = null;
    /** 0=horizontal/flat, 1=vertical/upright */
    public ?float $vertical = null;
    /** 0=straight/angular, 1=curved/rounded */
    public ?float $curved = null;

    // ── Temporal Properties ─────────────────────────────────────────────────
    /** 0=momentary/fleeting, 1=long-lasting/enduring */
    public ?float $duration = null;
    /** 0=extended/prolonged, 1=instantaneous/immediate */
    public ?float $instantaneous = null;
    /** 0=spontaneous/unscheduled, 1=scheduled/planned */
    public ?float $scheduled = null;
    /** 0=sequential/one-at-a-time, 1=simultaneous/concurrent */
    public ?float $simultaneous = null;
    /** 0=past-oriented/backward-looking, 1=future-oriented */
    public ?float $futureOriented = null;
    /** 0=timeless/eternal, 1=present-day/contemporary */
    public ?float $presentDay = null;
    /** 0=leisurely/unhurried, 1=urgent/time-critical */
    public ?float $urgency = null;
    /** 0=parallel/concurrent execution, 1=sequential/step-by-step */
    public ?float $sequential = null;
    /** 0=ordinary/routine, 1=epochal/historically significant */
    public ?float $epochal = null;
    /** 0=arrhythmic/irregular, 1=rhythmic/metered */
    public ?float $rhythmic = null;

    // ── Material & Substance ────────────────────────────────────────────────
    /** 0=non-metallic, 1=metallic */
    public ?float $metallic = null;
    /** 0=amorphous/disordered, 1=crystalline/lattice-structured */
    public ?float $crystalline = null;
    /** 0=solid/rigid state, 1=fluid/liquid or gaseous */
    public ?float $fluid = null;
    /** 0=impermeable/solid, 1=porous/permeable */
    public ?float $porous = null;
    /** 0=inelastic/brittle, 1=elastic/springy */
    public ?float $elastic = null;
    /** 0=fireproof/incombustible, 1=flammable/combustible */
    public ?float $combustible = null;
    /** 0=insulating/non-conductive, 1=conductive */
    public ?float $conductive = null;
    /** 0=non-magnetic, 1=magnetic */
    public ?float $magnetic = null;
    /** 0=insoluble, 1=soluble/dissolvable */
    public ?float $soluble = null;
    /** 0=natural/organic origin, 1=synthetic/manufactured */
    public ?float $synthetic = null;

    // ── Biological Features ─────────────────────────────────────────────────
    /** 0=non-cellular/abiotic, 1=cellular/biological unit */
    public ?float $cellular = null;
    /** 0=non-reproductive/sterile, 1=reproductive/generative */
    public ?float $reproductive = null;
    /** 0=mutualistic/symbiotic, 1=parasitic/exploitative */
    public ?float $parasitic = null;
    /** 0=prey/passive organism, 1=predatory/hunter */
    public ?float $predatory = null;
    /** 0=sedentary/stationary, 1=migratory/seasonally mobile */
    public ?float $migratory = null;
    /** 0=terrestrial/land-based, 1=aquatic/water-dwelling */
    public ?float $aquatic = null;
    /** 0=diurnal/daytime-active, 1=nocturnal/night-active */
    public ?float $nocturnal = null;
    /** 0=wild/feral, 1=domesticated/tamed animal */
    public ?float $domesticatedAnimal = null;
    /** 0=abundant/thriving species, 1=rare/endangered */
    public ?float $endangered = null;
    /** 0=harmless/non-toxic, 1=venomous/toxic */
    public ?float $venomous = null;

    // ── Technology & Digital ────────────────────────────────────────────────
    /** 0=analog/physical medium, 1=digital/virtual */
    public ?float $digital = null;
    /** 0=manual/handmade, 1=automated/mechanized */
    public ?float $automated = null;
    /** 0=standalone/isolated, 1=networked/connected */
    public ?float $networked = null;
    /** 0=fixed/hardwired, 1=programmable/configurable */
    public ?float $programmable = null;
    /** 0=passive/static, 1=interactive/responsive */
    public ?float $interactive = null;
    /** 0=fixed-capacity/limited, 1=scalable/expandable */
    public ?float $scalable = null;
    /** 0=delayed/asynchronous, 1=real-time/synchronous */
    public ?float $realtime = null;
    /** 0=plaintext/unprotected, 1=encrypted/secured */
    public ?float $encrypted = null;
    /** 0=fixed/stationary device, 1=portable/mobile */
    public ?float $portable = null;
    /** 0=depleting/finite resource, 1=renewable/self-replenishing */
    public ?float $renewable = null;

    // ── Economic & Commercial ───────────────────────────────────────────────
    /** 0=non-tradable/personal, 1=tradable/marketable */
    public ?float $tradable = null;
    /** 0=abundant/plentiful, 1=scarce/rare commodity */
    public ?float $scarce = null;
    /** 0=worthless/low-value, 1=highly valuable/precious */
    public ?float $valuable = null;
    /** 0=unproductive/wasteful, 1=productive/efficient */
    public ?float $productive = null;
    /** 0=loss-making/costly, 1=profitable/lucrative */
    public ?float $profitable = null;
    /** 0=monopolistic/unique offering, 1=competitive/contested market */
    public ?float $competitive = null;
    /** 0=consumable/expensed, 1=investable/capital asset */
    public ?float $investable = null;
    /** 0=illiquid/locked asset, 1=liquid/easily exchangeable */
    public ?float $liquid = null;
    /** 0=unregulated/free market, 1=heavily regulated/controlled */
    public ?float $regulated = null;
    /** 0=tax-exempt/untaxed, 1=taxable/assessed */
    public ?float $taxable = null;

    // ── Political & Governance ──────────────────────────────────────────────
    /** 0=authoritarian/top-down, 1=democratic/participatory */
    public ?float $democratic = null;
    /** 0=decentralized/distributed, 1=centralized/unified */
    public ?float $centralized = null;
    /** 0=subject/subordinate entity, 1=sovereign/independent */
    public ?float $sovereign = null;
    /** 0=private/exclusive, 1=public/open to all */
    public ?float $publicAccess = null;
    /** 0=executive/administrative, 1=legislative/rule-making */
    public ?float $legislative = null;
    /** 0=advisory/optional, 1=enforceable/mandatory */
    public ?float $enforceable = null;
    /** 0=appointed/hereditary, 1=elected/chosen by vote */
    public ?float $elected = null;
    /** 0=direct/unmediated, 1=representative/delegated */
    public ?float $representative = null;
    /** 0=arbitrary/discretionary, 1=constitutional/rule-bound */
    public ?float $constitutional = null;
    /** 0=pragmatic/neutral, 1=ideologically driven */
    public ?float $ideological = null;

    // ── Linguistic Features ─────────────────────────────────────────────────
    /** 0=non-verbal/gestural, 1=verbal/spoken */
    public ?float $verbal = null;
    /** 0=oral/unwritten tradition, 1=written/textual */
    public ?float $written = null;
    /** 0=monosemous/single meaning, 1=polysemous/multiple meanings */
    public ?float $polysemous = null;
    /** 0=direct/blunt expression, 1=euphemistic/softened */
    public ?float $euphemistic = null;
    /** 0=informational/neutral, 1=persuasive/rhetorical */
    public ?float $persuasive = null;
    /** 0=expository/descriptive mode, 1=narrative/story-based */
    public ?float $narratorial = null;
    /** 0=declarative/stating, 1=interrogative/questioning */
    public ?float $interrogative = null;
    /** 0=suggestive/optional, 1=imperative/commanding */
    public ?float $imperative = null;
    /** 0=descriptive/representational, 1=performative/enactive */
    public ?float $performative = null;
    /** 0=compositional/transparent meaning, 1=idiomatic/non-compositional */
    public ?float $idiomatic = null;

    // ── Aesthetic & Artistic ────────────────────────────────────────────────
    /** 0=ugly/unaesthetic, 1=beautiful/attractive */
    public ?float $beautiful = null;
    /** 0=plain/minimalist, 1=ornate/heavily decorated */
    public ?float $ornate = null;
    /** 0=discordant/jarring, 1=harmonious/consonant */
    public ?float $harmonious = null;
    /** 0=restrained/contained, 1=expressive/emotive */
    public ?float $expressive = null;
    /** 0=naturalistic/realistic, 1=stylized/abstracted */
    public ?float $stylized = null;
    /** 0=crude/raw, 1=refined/polished */
    public ?float $refined = null;
    /** 0=traditional/conventional, 1=innovative/avant-garde */
    public ?float $innovative = null;
    /** 0=understated/subtle, 1=dramatic/theatrical */
    public ?float $dramatic = null;
    /** 0=clumsy/awkward, 1=elegant/graceful */
    public ?float $elegant = null;
    /** 0=ordinary/mundane in impact, 1=sublime/transcendent */
    public ?float $sublime = null;

    // ── Mathematical & Logical ──────────────────────────────────────────────
    /** 0=unquantifiable/qualitative, 1=quantifiable/measurable */
    public ?float $quantifiable = null;
    /** 0=non-linear/complex relationship, 1=linear/proportional */
    public ?float $linearScale = null;
    /** 0=finite/non-recursive, 1=recursive/self-referential */
    public ?float $recursive = null;
    /** 0=stochastic/random outcome, 1=deterministic/predictable */
    public ?float $deterministic = null;
    /** 0=finite/bounded quantity, 1=infinite/unbounded */
    public ?float $infinite = null;
    /** 0=ordinal/relative, 1=cardinal/absolute count */
    public ?float $cardinal = null;
    /** 0=continuous/graduated, 1=binary/either-or */
    public ?float $binary = null;
    /** 0=geometric/spatial, 1=algebraic/symbolic */
    public ?float $algebraic = null;
    /** 0=divergent/spreading, 1=convergent/narrowing */
    public ?float $convergent = null;
    /** 0=empirical/informal, 1=axiomatic/proof-based */
    public ?float $axiomatic = null;

    // ── Ecological & Environmental ──────────────────────────────────────────
    /** 0=isolated/independent organism, 1=deeply embedded in ecosystem */
    public ?float $ecosystemic = null;
    /** 0=native/indigenous species, 1=invasive/introduced */
    public ?float $invasive = null;
    /** 0=clean/purifying, 1=polluting/contaminating */
    public ?float $polluting = null;
    /** 0=persistent/non-degradable, 1=biodegradable/decomposing */
    public ?float $biodegradable = null;
    /** 0=cosmopolitan/widespread, 1=endemic/locally specific */
    public ?float $endemic = null;
    /** 0=depleting/unsustainable, 1=sustainable/regenerative */
    public ?float $sustainable = null;
    /** 0=protected/sheltered, 1=weathered/exposed to elements */
    public ?float $weathered = null;
    /** 0=inland/terrestrial, 1=water-adjacent/riparian */
    public ?float $riparian = null;
    /** 0=open/barren landscape, 1=forested/wooded */
    public ?float $forested = null;
    /** 0=climate-independent, 1=climate-dependent/sensitive */
    public ?float $climatic = null;

    // ── Social Hierarchy & Power ────────────────────────────────────────────
    /** 0=subordinate/submissive, 1=authoritative/dominant */
    public ?float $authoritative = null;
    /** 0=egalitarian/flat structure, 1=hierarchical/ranked */
    public ?float $hierarchical = null;
    /** 0=low-status/stigmatized, 1=high-status/prestigious */
    public ?float $prestigious = null;
    /** 0=inclusive/open, 1=exclusive/restricted access */
    public ?float $exclusive = null;
    /** 0=underprivileged/marginalized, 1=privileged/advantaged */
    public ?float $privileged = null;
    /** 0=powerless/insignificant, 1=influential/powerful */
    public ?float $influential = null;
    /** 0=informal/personal, 1=institutional/official */
    public ?float $institutional = null;
    /** 0=individual/personal, 1=collective/group-based */
    public ?float $collective = null;
    /** 0=cooperative/collaborative, 1=rivalrous/competitive */
    public ?float $rivalrous = null;
    /** 0=achieved/earned status, 1=inherited/ascribed */
    public ?float $inherited = null;

    // ── Moral & Ethical ─────────────────────────────────────────────────────
    /** 0=immoral/unethical, 1=moral/virtuous */
    public ?float $moral = null;
    /** 0=deceptive/dishonest, 1=honest/truthful */
    public ?float $honest = null;
    /** 0=unjust/unfair, 1=just/equitable */
    public ?float $just = null;
    /** 0=indifferent/callous, 1=compassionate/caring */
    public ?float $compassionate = null;
    /** 0=cowardly/timid, 1=courageous/brave */
    public ?float $courageous = null;
    /** 0=irresponsible/negligent, 1=responsible/accountable */
    public ?float $responsible = null;
    /** 0=selfish/self-serving, 1=altruistic/generous */
    public ?float $altruistic = null;
    /** 0=opportunistic/unprincipled, 1=principled/consistent */
    public ?float $principled = null;
    /** 0=harsh/punitive, 1=merciful/forgiving */
    public ?float $merciful = null;
    /** 0=vicious/corrupt, 1=virtuous/morally excellent */
    public ?float $virtuous = null;

    // ── Educational & Knowledge ─────────────────────────────────────────────
    /** 0=non-educational/trivial, 1=educational/instructive */
    public ?float $educational = null;
    /** 0=general/common knowledge, 1=highly specialized/expert */
    public ?float $specialized = null;
    /** 0=theoretical/speculative, 1=empirically grounded */
    public ?float $empirical = null;
    /** 0=undocumented/oral tradition, 1=documented/recorded */
    public ?float $documented = null;
    /** 0=intuitive/unteachable skill, 1=teachable/transferable */
    public ?float $teachable = null;
    /** 0=book-learned/theoretical, 1=experiential/hands-on */
    public ?float $experiential = null;
    /** 0=practical/applied, 1=academic/theoretical */
    public ?float $academic = null;
    /** 0=narrowly disciplinary, 1=interdisciplinary/cross-domain */
    public ?float $interdisciplinary = null;
    /** 0=peripheral/derivative knowledge, 1=foundational/prerequisite */
    public ?float $foundational = null;
    /** 0=standalone knowledge, 1=builds on prior knowledge */
    public ?float $cumulative = null;

    // ── Mechanical & Structural ─────────────────────────────────────────────
    /** 0=decorative/non-structural, 1=load-bearing/structural */
    public ?float $structural = null;
    /** 0=non-mechanical/biological, 1=mechanical/engineered */
    public ?float $mechanical = null;
    /** 0=monolithic/integrated, 1=modular/component-based */
    public ?float $modular = null;
    /** 0=rigid/fixed joint, 1=articulated/jointed */
    public ?float $articulated = null;
    /** 0=unprotected/bare, 1=reinforced/strengthened */
    public ?float $reinforced = null;
    /** 0=unbalanced/lopsided, 1=balanced/stable */
    public ?float $balanced = null;
    /** 0=grown/organic formation, 1=assembled/constructed */
    public ?float $assembled = null;
    /** 0=disposable/throwaway, 1=repairable/maintainable */
    public ?float $repairable = null;
    /** 0=friction-heavy/dry mechanism, 1=lubricated/smooth-running */
    public ?float $lubricated = null;
    /** 0=independent/non-fitting parts, 1=interlocking/fitted together */
    public ?float $interlocking = null;

    // ── Chemical States ─────────────────────────────────────────────────────
    /** 0=alkaline/basic, 1=acidic */
    public ?float $acidic = null;
    /** 0=reducing/anti-oxidizing, 1=oxidizing/reactive with oxygen */
    public ?float $oxidizing = null;
    /** 0=non-volatile/stable at room temp, 1=volatile/evaporating */
    public ?float $evaporative = null;
    /** 0=inert/chemically stable, 1=chemically reactive */
    public ?float $reactive = null;
    /** 0=stable/non-radioactive, 1=radioactive */
    public ?float $radioactive = null;
    /** 0=electrically neutral, 1=ionized/charged */
    public ?float $ionized = null;
    /** 0=amorphous solid, 1=crystallized/structured */
    public ?float $crystallized = null;
    /** 0=monomeric/simple molecule, 1=polymerized/complex chain */
    public ?float $polymerized = null;
    /** 0=inert/non-catalytic, 1=catalytic/accelerating reactions */
    public ?float $catalytic = null;
    /** 0=unsaturated/reactive bonds, 1=saturated/stable bonds */
    public ?float $saturated = null;

    // ── Sensory Experience ──────────────────────────────────────────────────
    /** 0=invisible/hidden, 1=visible/observable */
    public ?float $visible = null;
    /** 0=silent/inaudible, 1=audible/hearable */
    public ?float $audible = null;
    /** 0=untouchable/intangible, 1=tactile/touchable */
    public ?float $tactile = null;
    /** 0=odorless/scent-free, 1=strongly olfactory/scented */
    public ?float $olfactory = null;
    /** 0=tasteless/bland, 1=flavorful/gustatory */
    public ?float $gustatory = null;
    /** 0=still/passive, 1=involving body movement/kinesthetic */
    public ?float $kinesthetic = null;
    /** 0=balance-stable/grounded, 1=disorienting/vertiginous */
    public ?float $vestibular = null;
    /** 0=thermally neutral, 1=thermally distinct/extreme */
    public ?float $thermal = null;
    /** 0=painless/comfortable, 1=painful/nociceptive */
    public ?float $painful = null;
    /** 0=unpleasant/malodorous, 1=fragrant/pleasant-smelling */
    public ?float $fragrant = null;

    // ── Nutritional & Culinary ──────────────────────────────────────────────
    /** 0=non-nutritious/empty calories, 1=nutritious/nourishing */
    public ?float $nutritious = null;
    /** 0=low-calorie/light, 1=high-calorie/energy-dense */
    public ?float $caloric = null;
    /** 0=protein-poor, 1=protein-rich */
    public ?float $proteinRich = null;
    /** 0=fresh/unfermented, 1=fermented/aged */
    public ?float $fermented = null;
    /** 0=raw/unprocessed, 1=cooked/heat-prepared */
    public ?float $cooked = null;
    /** 0=mild/bland in heat, 1=spicy/pungent */
    public ?float $spicy = null;
    /** 0=savory/salty, 1=sweet/sugary */
    public ?float $sweet = null;
    /** 0=shelf-stable/long-lasting, 1=perishable/short shelf life */
    public ?float $perishable = null;
    /** 0=raw ingredient, 1=culinary preparation/dish */
    public ?float $culinary = null;
    /** 0=whole/minimally processed, 1=highly processed/refined */
    public ?float $processed = null;

    // ── Medical & Health ────────────────────────────────────────────────────
    /** 0=beneficial/probiotic, 1=pathogenic/disease-causing */
    public ?float $pathogenic = null;
    /** 0=harmful/detrimental to health, 1=therapeutic/healing */
    public ?float $therapeutic = null;
    /** 0=acute/sudden onset, 1=chronic/long-term */
    public ?float $chronic = null;
    /** 0=local/topical effect, 1=systemic/body-wide */
    public ?float $systemic = null;
    /** 0=non-contagious/non-spreading, 1=contagious/infectious */
    public ?float $contagious = null;
    /** 0=asymptomatic/silent, 1=symptomatic/obvious signs */
    public ?float $symptomatic = null;
    /** 0=non-invasive/conservative, 1=surgical/invasive */
    public ?float $surgical = null;
    /** 0=reactive/curative only, 1=preventive/prophylactic */
    public ?float $preventive = null;
    /** 0=acute treatment only, 1=rehabilitative/restorative */
    public ?float $rehabilitative = null;
    /** 0=curative/restorative aim, 1=palliative/comfort-focused */
    public ?float $palliative = null;

    // ── Architectural & Built Environment ───────────────────────────────────
    /** 0=uninhabitable/hostile space, 1=inhabitable/livable */
    public ?float $inhabitable = null;
    /** 0=decorative/non-structural, 1=supportive/load-bearing */
    public ?float $supportive = null;
    /** 0=utilitarian/plain, 1=ornamental/decorative */
    public ?float $ornamental = null;
    /** 0=inaccessible/exclusive space, 1=accessible/open */
    public ?float $accessible = null;
    /** 0=exposed/uninsulated, 1=thermally insulated */
    public ?float $insulated = null;
    /** 0=combustible/fire-prone, 1=fireproof/fire-resistant */
    public ?float $fireproof = null;
    /** 0=porous/leaky, 1=weatherproof/sealed */
    public ?float $weatherproof = null;
    /** 0=single-level/ground floor, 1=multi-story/vertical */
    public ?float $multistory = null;
    /** 0=modest/small-scale, 1=monumental/grand */
    public ?float $monumental = null;
    /** 0=private/residential, 1=civic/public function */
    public ?float $civic = null;

    // ── Transport & Mobility ────────────────────────────────────────────────
    /** 0=non-motorized/manual, 1=motorized/engine-powered */
    public ?float $motorized = null;
    /** 0=ground-based, 1=airborne/flying */
    public ?float $airborne = null;
    /** 0=land-based, 1=seafaring/nautical */
    public ?float $seafaring = null;
    /** 0=cargo/freight transport, 1=passenger/people-carrying */
    public ?float $passenger = null;
    /** 0=externally propelled/pushed, 1=self-propelled/autonomous */
    public ?float $selfPropelled = null;
    /** 0=wheeled/unguided, 1=rail-tracked/guided */
    public ?float $railTracked = null;
    /** 0=long-distance/intercity, 1=commuter/local transport */
    public ?float $commuter = null;
    /** 0=electric/non-combustion, 1=fuel-burning/combustion engine */
    public ?float $fuelBurning = null;
    /** 0=local/short-range, 1=long-range/expeditionary */
    public ?float $expeditionary = null;
    /** 0=private/personal vehicle, 1=shared/public transport */
    public ?float $sharedTransport = null;

    // ── Communication & Media ───────────────────────────────────────────────
    /** 0=point-to-point/private, 1=broadcast/one-to-many */
    public ?float $broadcast = null;
    /** 0=unicast/one-to-one, 1=multicast/one-to-many */
    public ?float $multicast = null;
    /** 0=digital/screen-based medium, 1=printed/physical medium */
    public ?float $printed = null;
    /** 0=live/ephemeral, 1=recorded/archived */
    public ?float $recorded = null;
    /** 0=uncensored/free expression, 1=censored/restricted content */
    public ?float $censored = null;
    /** 0=niche/limited reach, 1=viral/widely shared */
    public ?float $viral = null;
    /** 0=passive/consumable media, 1=participatory/interactive */
    public ?float $participatory = null;
    /** 0=unreliable/dubious source, 1=credible/trustworthy source */
    public ?float $credible = null;
    /** 0=measured/factual reporting, 1=sensational/dramatic */
    public ?float $sensational = null;
    /** 0=standalone/one-shot, 1=serialized/episodic */
    public ?float $serialized = null;

    // ── Religious & Spiritual ───────────────────────────────────────────────
    /** 0=profane/secular, 1=sacred/holy */
    public ?float $sacred = null;
    /** 0=immanent/worldly, 1=transcendent/otherworldly */
    public ?float $transcendent = null;
    /** 0=open/pluralistic, 1=dogmatic/orthodox */
    public ?float $dogmatic = null;
    /** 0=rational/logical, 1=mystical/ineffable */
    public ?float $mystical = null;
    /** 0=casual/incidental, 1=devotional/worship-oriented */
    public ?float $devotional = null;
    /** 0=present-focused/immanent, 1=eschatological/afterlife-focused */
    public ?float $eschatological = null;
    /** 0=retrospective/historical, 1=prophetic/forward-looking */
    public ?float $prophetic = null;
    /** 0=solitary/individual practice, 1=congregational/communal worship */
    public ?float $congregational = null;
    /** 0=apocryphal/non-canonical, 1=canonical/scriptural */
    public ?float $canonical = null;
    /** 0=direct/unmediated access, 1=mediated/intercessory */
    public ?float $intercessory = null;

    // ── Folkloric & Mythological Nuance ─────────────────────────────────────
    /** 0=historical/documented, 1=folkloric/traditional tale */
    public ?float $folkloric = null;
    /** 0=natural/mundane, 1=supernatural/magical */
    public ?float $supernatural = null;
    /** 0=non-symbolic/literal object, 1=totemic/symbolically charged */
    public ?float $totemic = null;
    /** 0=ordinary/common, 1=heroic/exceptional */
    public ?float $heroic = null;
    /** 0=straightforward/honest, 1=trickster/deceptive-playful */
    public ?float $trickster = null;
    /** 0=local/temporal, 1=cosmogonic/creation-related */
    public ?float $cosmogonic = null;
    /** 0=celestial/sky-related, 1=chthonic/underworld-related */
    public ?float $chthonic = null;
    /** 0=inanimate/spiritless, 1=animistic/spirit-inhabited */
    public ?float $animistic = null;
    /** 0=mundane/ordinary, 1=oracular/prophetic */
    public ?float $oracular = null;
    /** 0=fixed/stable state, 1=liminal/transitional threshold */
    public ?float $liminal = null;

    // ── Geographic & Topographic ────────────────────────────────────────────
    /** 0=inland/landlocked, 1=coastal/littoral */
    public ?float $coastal = null;
    /** 0=flat/lowland, 1=mountainous/highland */
    public ?float $mountainous = null;
    /** 0=polar/arctic climate, 1=tropical/equatorial */
    public ?float $tropical = null;
    /** 0=humid/wet, 1=arid/desert-like */
    public ?float $arid = null;
    /** 0=surface/above-ground, 1=subterranean/underground */
    public ?float $subterranean = null;
    /** 0=tectonically stable, 1=volcanic/seismically active */
    public ?float $volcanic = null;
    /** 0=warm/non-glacial, 1=glacial/ice-covered */
    public ?float $glacial = null;
    /** 0=non-riverine, 1=river/fluvial system */
    public ?float $fluvial = null;
    /** 0=continental/inland, 1=peninsular/island */
    public ?float $peninsular = null;
    /** 0=upland/source area, 1=deltaic/estuarine */
    public ?float $deltaic = null;

    // ── Astronomical & Cosmic ───────────────────────────────────────────────
    /** 0=planetary/substellar, 1=stellar/star-like */
    public ?float $stellar = null;
    /** 0=local/small-scale, 1=galactic/cosmic-scale */
    public ?float $galactic = null;
    /** 0=free-floating, 1=orbital/in regular orbit */
    public ?float $orbital = null;
    /** 0=absorbing/dark body, 1=radiant/emitting energy */
    public ?float $radiant = null;
    /** 0=low-gravity/lightweight, 1=high-gravity/massive */
    public ?float $gravitational = null;
    /** 0=solid/compact body, 1=nebular/diffuse cloud */
    public ?float $nebular = null;
    /** 0=solitary/single body, 1=paired/binary system */
    public ?float $paired = null;
    /** 0=recently formed, 1=primordial/ancient cosmic */
    public ?float $primordial = null;
    /** 0=contracting/imploding, 1=expansive/growing */
    public ?float $expansive = null;
    /** 0=circular orbit, 1=highly elliptical/eccentric orbit */
    public ?float $eccentric = null;

    // ── Musical & Acoustic ──────────────────────────────────────────────────
    /** 0=atonal/non-melodic, 1=melodic/tuneful */
    public ?float $melodic = null;
    /** 0=sustained/held tone, 1=percussive/struck */
    public ?float $percussive = null;
    /** 0=dissonant/clashing, 1=harmonic/consonant */
    public ?float $harmonic = null;
    /** 0=atonal/pantonal, 1=tonal/key-centered */
    public ?float $tonal = null;
    /** 0=instrumental/wordless, 1=lyrical/vocal */
    public ?float $lyrical = null;
    /** 0=composed/notated, 1=improvised/spontaneous */
    public ?float $improvised = null;
    /** 0=acoustic/unamplified, 1=electrically amplified */
    public ?float $amplified = null;
    /** 0=slow/largo tempo, 1=fast/presto tempo */
    public ?float $tempo = null;
    /** 0=monophonic/single voice, 1=polyphonic/multi-voice */
    public ?float $polyphonic = null;
    /** 0=concert/seated listening, 1=dance/movement-inducing */
    public ?float $danceable = null;

    // ── Visual & Color ──────────────────────────────────────────────────────
    /** 0=achromatic/grayscale, 1=chromatic/colorful */
    public ?float $chromatic = null;
    /** 0=cool/blue-toned, 1=warm/red-yellow-toned */
    public ?float $warmColor = null;
    /** 0=muted/dull, 1=vibrant/vivid */
    public ?float $vibrant = null;
    /** 0=low contrast/blended, 1=high contrast/stark */
    public ?float $contrasting = null;
    /** 0=uniform/solid, 1=patterned/textured */
    public ?float $patterned = null;
    /** 0=multi-colored, 1=monochromatic/single-hue */
    public ?float $monochromatic = null;
    /** 0=non-luminescent, 1=luminescent/glowing */
    public ?float $luminescent = null;
    /** 0=abstract/non-representational, 1=pictorial/figurative */
    public ?float $pictorial = null;
    /** 0=close-up/limited view, 1=panoramic/wide-angle */
    public ?float $panoramic = null;
    /** 0=simple/uniform, 1=kaleidoscopic/complex varied */
    public ?float $kaleidoscopic = null;

    // ── Tactile & Haptic ────────────────────────────────────────────────────
    /** 0=slippery/frictionless, 1=grippy/high-friction */
    public ?float $grippy = null;
    /** 0=hard/unpadded, 1=cushioned/padded */
    public ?float $cushioned = null;
    /** 0=fluid/thin, 1=viscous/thick/sticky */
    public ?float $viscous = null;
    /** 0=smooth/fine-grained, 1=granular/gritty */
    public ?float $granular = null;
    /** 0=inelastic/dead feel, 1=springy/resilient */
    public ?float $springy = null;
    /** 0=non-adhesive/releasing, 1=clinging/adhesive */
    public ?float $clinging = null;
    /** 0=smooth/harmless to touch, 1=prickly/sharp to touch */
    public ?float $prickly = null;
    /** 0=rough/coarse texture, 1=velvety/ultra-smooth */
    public ?float $velvety = null;
    /** 0=rough/matte surface, 1=slick/polished surface */
    public ?float $slick = null;
    /** 0=non-fibrous/uniform, 1=fibrous/stringy */
    public ?float $fibrous = null;

    // ── Olfactory & Gustatory Nuance ────────────────────────────────────────
    /** 0=odorless, 1=strongly fragrant/perfumed */
    public ?float $fragrantSmell = null;
    /** 0=fresh/clean scent, 1=putrid/rotting smell */
    public ?float $putrid = null;
    /** 0=sweet/sugary taste, 1=savory/umami */
    public ?float $savory = null;
    /** 0=sweet/mild taste, 1=bitter/acrid */
    public ?float $bitter = null;
    /** 0=neutral/non-acidic taste, 1=sour/acidic */
    public ?float $sour = null;
    /** 0=unsalted/bland, 1=salty/briny */
    public ?float $salty = null;
    /** 0=non-savory/plain, 1=umami/meaty/savory-rich */
    public ?float $umami = null;
    /** 0=inodorous/scent-free, 1=aromatic/spiced */
    public ?float $aromatic = null;
    /** 0=non-astringent, 1=astringent/drying in mouth */
    public ?float $astringent = null;
    /** 0=mild/subtle, 1=pungent/sharp/strong-smelling */
    public ?float $pungent = null;

    // ── Emotional Nuance ────────────────────────────────────────────────────
    /** 0=future-looking, 1=nostalgic/past-evoking */
    public ?float $nostalgic = null;
    /** 0=cheerful/upbeat, 1=melancholic/wistful */
    public ?float $melancholic = null;
    /** 0=subdued/muted feeling, 1=euphoric/elated */
    public ?float $euphoric = null;
    /** 0=calm/composed, 1=anxiety-inducing/tense */
    public ?float $anxious = null;
    /** 0=emotionally neutral, 1=cathartic/releasing */
    public ?float $cathartic = null;
    /** 0=alienating/cold, 1=empathy-inducing/warm */
    public ?float $empathic = null;
    /** 0=unremarkable/ordinary, 1=awe-inspiring/magnificent */
    public ?float $awesome = null;
    /** 0=joyful/celebratory, 1=grief/sorrow-related */
    public ?float $grief = null;
    /** 0=satisfied/complete, 1=longing/yearning */
    public ?float $longing = null;
    /** 0=serious/grave, 1=whimsical/fanciful */
    public ?float $whimsical = null;

    // ── Social Relationships ────────────────────────────────────────────────
    /** 0=non-kinship/stranger, 1=kinship/family-related */
    public ?float $kinship = null;
    /** 0=adversarial/hostile, 1=friendly/amicable */
    public ?float $friendly = null;
    /** 0=platonic/non-romantic, 1=romantic/intimate */
    public ?float $romantic = null;
    /** 0=personal/private relationship, 1=professional/work-related */
    public ?float $professional = null;
    /** 0=peer/equal relationship, 1=mentor/guide relationship */
    public ?float $mentoring = null;
    /** 0=isolated/anonymous, 1=neighborly/community-oriented */
    public ?float $neighborly = null;
    /** 0=relational/emotional bond, 1=transactional/exchange-based */
    public ?float $transactional = null;
    /** 0=assertive/equal standing, 1=deferential/respectful-upward */
    public ?float $deferential = null;
    /** 0=one-way/unilateral, 1=reciprocal/mutual */
    public ?float $reciprocal = null;
    /** 0=distant/formal, 1=intimate/close */
    public ?float $intimate = null;

    // ── Economic Exchange ───────────────────────────────────────────────────
    /** 0=monetary/abstract value, 1=barter/direct exchange */
    public ?float $barter = null;
    /** 0=purchased/transactional, 1=gifted/freely given */
    public ?float $gifted = null;
    /** 0=owned outright, 1=leased/rented/borrowed */
    public ?float $leased = null;
    /** 0=market-priced/full cost, 1=subsidized/artificially cheap */
    public ?float $subsidized = null;
    /** 0=stable/non-speculative, 1=speculative/uncertain value */
    public ?float $speculative = null;
    /** 0=unique/non-fungible, 1=fungible/interchangeable */
    public ?float $fungible = null;
    /** 0=single-use/disposable, 1=reusable/long-lasting */
    public ?float $reusable = null;
    /** 0=retail/consumer-scale, 1=wholesale/bulk */
    public ?float $wholesale = null;
    /** 0=locally produced, 1=imported/foreign */
    public ?float $imported = null;
    /** 0=generic/unbranded, 1=branded/trademarked */
    public ?float $branded = null;

    // ── Legal & Institutional ───────────────────────────────────────────────
    /** 0=non-binding/advisory, 1=legally binding/obligatory */
    public ?float $binding = null;
    /** 0=public domain/unprotected, 1=patented/IP-protected */
    public ?float $patented = null;
    /** 0=unregulated/free use, 1=licensed/regulated */
    public ?float $licensed = null;
    /** 0=legal/permitted, 1=criminal/prohibited */
    public ?float $criminal = null;
    /** 0=informal/handshake agreement, 1=contractual/formally agreed */
    public ?float $contractual = null;
    /** 0=universal/borderless, 1=jurisdictionally specific */
    public ?float $jurisdictional = null;
    /** 0=novel/unprecedented, 1=precedential/case-law based */
    public ?float $precedential = null;
    /** 0=rehabilitative/restorative, 1=punitive/penalty-based */
    public ?float $punitive = null;
    /** 0=punitive/preventive, 1=remedial/corrective */
    public ?float $remedial = null;
    /** 0=common law/customary, 1=statutory/codified */
    public ?float $statutory = null;

    // ── Biological Functions ────────────────────────────────────────────────
    /** 0=non-respiratory, 1=respiratory/breathing-related */
    public ?float $respiratory = null;
    /** 0=non-digestive, 1=digestive/metabolic */
    public ?float $digestive = null;
    /** 0=non-circulatory, 1=circulatory/blood-flow */
    public ?float $circulatory = null;
    /** 0=non-neural, 1=neural/nerve-related */
    public ?float $neural = null;
    /** 0=non-hormonal, 1=hormonal/endocrine */
    public ?float $hormonal = null;
    /** 0=non-immune, 1=immune/defensive response */
    public ?float $immune = null;
    /** 0=non-muscular/passive, 1=muscular/movement-producing */
    public ?float $muscular = null;
    /** 0=soft-bodied/no skeleton, 1=skeletal/bone-related */
    public ?float $skeletal = null;
    /** 0=non-sensory, 1=sensory organ/perception-related */
    public ?float $perceptory = null;
    /** 0=absorbing/retaining, 1=excretory/waste-eliminating */
    public ?float $excretory = null;

    // ── Developmental & Life Stage ──────────────────────────────────────────
    /** 0=mature/adult, 1=infantile/neonatal */
    public ?float $infantile = null;
    /** 0=adult/mature, 1=juvenile/young */
    public ?float $juvenile = null;
    /** 0=childhood/adulthood stage, 1=adolescent/transitional */
    public ?float $adolescent = null;
    /** 0=immature/developing, 1=mature/fully developed */
    public ?float $mature = null;
    /** 0=young/early-stage, 1=geriatric/late-stage */
    public ?float $geriatric = null;
    /** 0=adult form/post-metamorphic, 1=larval/immature stage */
    public ?float $larval = null;
    /** 0=post-natal/developed, 1=embryonic/earliest stage */
    public ?float $embryonic = null;
    /** 0=pre-/post-pubescent, 1=pubescent/sexually maturing */
    public ?float $pubescent = null;
    /** 0=youthful/vigorous, 1=senescent/aging/declining */
    public ?float $senescent = null;
    /** 0=living/current, 1=posthumous/after death */
    public ?float $posthumous = null;

    // ── Evolutionary & Adaptive ─────────────────────────────────────────────
    /** 0=derived/evolved form, 1=primitive/ancestral form */
    public ?float $primitive = null;
    /** 0=functional/active trait, 1=vestigial/remnant */
    public ?float $vestigial = null;
    /** 0=homologous/shared ancestry, 1=analogous/independently evolved */
    public ?float $analogous = null;
    /** 0=widespread/common, 1=relict/surviving remnant */
    public ?float $relict = null;
    /** 0=generalist/flexible, 1=specialist/narrow adaptation */
    public ?float $specialist = null;
    /** 0=non-adaptive/fixed, 1=highly adaptive/plastic */
    public ?float $adaptive = null;
    /** 0=independent/asocial, 1=symbiotic/mutualistic */
    public ?float $symbiotic = null;
    /** 0=prey/lower trophic level, 1=apex/top of food chain */
    public ?float $apex = null;
    /** 0=conspicuous/visible, 1=cryptic/camouflaged */
    public ?float $cryptic = null;
    /** 0=established/endemic, 1=colonizing/pioneering */
    public ?float $colonizing = null;

    // ── Computational & Information ─────────────────────────────────────────
    /** 0=incompressible/maximal entropy, 1=compressible/reducible */
    public ?float $compressible = null;
    /** 0=unsearchable/opaque, 1=searchable/indexed */
    public ?float $searchable = null;
    /** 0=unstructured/freeform, 1=structured/schematic */
    public ?float $schematic = null;
    /** 0=static/non-queryable, 1=queryable/dynamic */
    public ?float $queryable = null;
    /** 0=unversioned/single-state, 1=versioned/tracked history */
    public ?float $versioned = null;
    /** 0=centralized/local, 1=distributed/decentralized */
    public ?float $distributed = null;
    /** 0=computed on demand, 1=cached/precomputed */
    public ?float $cached = null;
    /** 0=batch/all-at-once, 1=streamed/continuous flow */
    public ?float $streamed = null;
    /** 0=immutable/read-only, 1=mutable/changeable */
    public ?float $mutable = null;
    /** 0=deterministic/exact, 1=probabilistic/uncertain */
    public ?float $probabilistic = null;

    // ── Philosophical & Ontological ─────────────────────────────────────────
    /** 0=conventional/nominal, 1=ontologically real/substantial */
    public ?float $ontological = null;
    /** 0=noumenal/thing-in-itself, 1=phenomenal/as-experienced */
    public ?float $phenomenal = null;
    /** 0=necessary/inevitable, 1=contingent/could-be-otherwise */
    public ?float $contingent = null;
    /** 0=reducible/simple, 1=emergent/arising from complexity */
    public ?float $emergent = null;
    /** 0=non-purposive/blind process, 1=teleological/goal-directed */
    public ?float $teleological = null;
    /** 0=static/fixed, 1=dialectical/containing opposites */
    public ?float $dialectical = null;
    /** 0=holistic/irreducible, 1=reductive/explained by parts */
    public ?float $reductive = null;
    /** 0=descriptive/value-neutral, 1=normative/prescriptive */
    public ?float $normative = null;
    /** 0=empirical/sense-based, 1=epistemic/knowledge-theory focus */
    public ?float $epistemic = null;
    /** 0=trivial/superficial, 1=existentially significant */
    public ?float $existential = null;

    // ── Psychological States ────────────────────────────────────────────────
    /** 0=confident/assured, 1=apprehensive/worried */
    public ?float $apprehensive = null;
    /** 0=elated/manic, 1=depressive/sad/low */
    public ?float $depressive = null;
    /** 0=flexible/indifferent, 1=obsessive/compulsive */
    public ?float $obsessive = null;
    /** 0=comfortable/neutral, 1=phobia-related/fear trigger */
    public ?float $phobic = null;
    /** 0=depressed/low energy, 1=manic/high energy */
    public ?float $manic = null;
    /** 0=grounded/present, 1=dissociative/detached */
    public ?float $dissociative = null;
    /** 0=trusting/open, 1=paranoid/suspicious */
    public ?float $paranoid = null;
    /** 0=self-effacing/humble, 1=narcissistic/self-centered */
    public ?float $narcissistic = null;
    /** 0=antisocial/withdrawn, 1=prosocial/other-focused */
    public ?float $prosocial = null;
    /** 0=fragile/easily overwhelmed, 1=resilient/stress-resistant */
    public ?float $resilient = null;

    // ── Behavioral & Habitual ───────────────────────────────────────────────
    /** 0=novel/unprecedented, 1=habitual/routine */
    public ?float $habitual = null;
    /** 0=deliberate/planned, 1=impulsive/spontaneous */
    public ?float $impulsive = null;
    /** 0=voluntary/chosen, 1=compulsive/driven */
    public ?float $compulsive = null;
    /** 0=improvised/flexible, 1=ritualistic/formulaic */
    public ?float $ritualistic = null;
    /** 0=non-addictive, 1=addictive/habit-forming */
    public ?float $addictive = null;
    /** 0=non-conformist/deviant, 1=conformist/rule-following */
    public ?float $conformist = null;
    /** 0=risk-averse/cautious, 1=risk-taking/bold */
    public ?float $riskTaking = null;
    /** 0=giving up easily, 1=tenacious/persistent */
    public ?float $tenacious = null;
    /** 0=dominant/assertive, 1=submissive/yielding */
    public ?float $submissive = null;
    /** 0=rigid/fixed behavior, 1=versatile/adaptable */
    public ?float $versatile = null;

    // ── Narrative & Storytelling ────────────────────────────────────────────
    /** 0=antagonist/villain, 1=protagonist/hero */
    public ?float $protagonist = null;
    /** 0=comedic/uplifting ending, 1=tragic/sorrowful ending */
    public ?float $tragic = null;
    /** 0=serious/humorless, 1=comic/funny */
    public ?float $comic = null;
    /** 0=small-scale/intimate, 1=epic/grand scope */
    public ?float $epic = null;
    /** 0=entertaining/pure pleasure, 1=didactic/lesson-teaching */
    public ?float $didactic = null;
    /** 0=predictable/unsurprising, 1=suspenseful/tension-filled */
    public ?float $suspenseful = null;
    /** 0=sincere/earnest, 1=satirical/ironic */
    public ?float $satirical = null;
    /** 0=unsentimental/dry, 1=sentimental/emotionally warm */
    public ?float $sentimental = null;
    /** 0=realistic/grounded, 1=mythic/archetypal */
    public ?float $mythic = null;
    /** 0=literal/surface-level, 1=allegorical/symbolic meaning */
    public ?float $allegorical = null;

    // ── Sports & Games ──────────────────────────────────────────────────────
    /** 0=sedentary/non-physical, 1=athletic/physically demanding */
    public ?float $athletic = null;
    /** 0=luck-based/random, 1=strategic/skill-based */
    public ?float $strategic = null;
    /** 0=individual/solo, 1=team-based/cooperative */
    public ?float $teamBased = null;
    /** 0=unscored/non-competitive, 1=scored/competitive */
    public ?float $scored = null;
    /** 0=untimed/open-ended, 1=timed/time-limited */
    public ?float $timed = null;
    /** 0=elite/professional, 1=recreational/casual */
    public ?float $recreational = null;
    /** 0=outdoor/open air, 1=indoor/enclosed */
    public ?float $indoor = null;
    /** 0=non-contact/safe, 1=contact/physical */
    public ?float $contact = null;
    /** 0=sprint/explosive, 1=endurance/long-duration */
    public ?float $endurance = null;
    /** 0=non-gambling, 1=gambling/wagering element */
    public ?float $gambling = null;

    // ── Fashion & Appearance ────────────────────────────────────────────────
    /** 0=unfashionable/dated, 1=fashionable/trendy */
    public ?float $fashionable = null;
    /** 0=impractical/decorative, 1=practical/wearable */
    public ?float $wearable = null;
    /** 0=revealing/exposed, 1=modest/covered */
    public ?float $modest = null;
    /** 0=basic/budget, 1=luxurious/premium */
    public ?float $luxurious = null;
    /** 0=loose/oversized, 1=form-fitting/tailored */
    public ?float $fitted = null;
    /** 0=plain/unadorned, 1=embellished/decorated */
    public ?float $embellished = null;
    /** 0=modern/contemporary fashion, 1=vintage/retro-styled */
    public ?float $vintage = null;
    /** 0=strongly gendered, 1=gender-neutral/androgynous */
    public ?float $androgynous = null;
    /** 0=casual/everyday attire, 1=formal/dressy attire */
    public ?float $dressy = null;
    /** 0=generic/nondescript, 1=iconic/recognizable */
    public ?float $iconic = null;

    // ── Agricultural & Rural ────────────────────────────────────────────────
    /** 0=wild/uncultivated, 1=farmed/cultivated */
    public ?float $farmed = null;
    /** 0=perennial/unharvested, 1=seasonally harvested */
    public ?float $harvested = null;
    /** 0=rain-fed/dryland, 1=irrigated/water-managed */
    public ?float $irrigated = null;
    /** 0=forested/non-pastoral, 1=grazed/pastoral */
    public ?float $grazed = null;
    /** 0=organic/unfertilized, 1=fertilized/enriched */
    public ?float $fertilized = null;
    /** 0=organic/pesticide-free, 1=pesticide-treated */
    public ?float $pesticideTreated = null;
    /** 0=polyculture/diverse, 1=monoculture/single-crop */
    public ?float $monoculture = null;
    /** 0=artisanal/small-scale, 1=industrial/large-scale farming */
    public ?float $industrialFarmed = null;
    /** 0=hybrid/modern variety, 1=heirloom/traditional variety */
    public ?float $heirloom = null;
    /** 0=seed-grown/natural, 1=grafted/propagated */
    public ?float $grafted = null;

    // ── Urban & Metropolitan ─────────────────────────────────────────────────
    /** 0=rural/provincial, 1=metropolitan/cosmopolitan */
    public ?float $metropolitan = null;
    /** 0=working-class/traditional, 1=gentrified/upscale */
    public ?float $gentrified = null;
    /** 0=single-use/zoned, 1=mixed-use/diverse */
    public ?float $mixedUse = null;
    /** 0=car-dependent, 1=transit-oriented/walkable */
    public ?float $transitOriented = null;
    /** 0=low-rise/horizontal, 1=high-rise/vertical */
    public ?float $highRise = null;
    /** 0=residential/non-commercial, 1=commercial/business */
    public ?float $commercialArea = null;
    /** 0=vehicular/car-centric, 1=pedestrian/foot-friendly */
    public ?float $pedestrian = null;
    /** 0=quiet/residential, 1=vibrant nightlife/entertainment */
    public ?float $nightlife = null;
    /** 0=monocultural/homogeneous, 1=multicultural/diverse */
    public ?float $multicultural = null;
    /** 0=organic/unplanned growth, 1=planned/designed city */
    public ?float $plannedCity = null;

    // ── Maritime & Aquatic ──────────────────────────────────────────────────
    /** 0=terrestrial/land, 1=marine/ocean-based */
    public ?float $marine = null;
    /** 0=non-tidal/inland, 1=tidal/influenced by tides */
    public ?float $tidal = null;
    /** 0=shallow/littoral, 1=deep-sea/abyssal */
    public ?float $deepSea = null;
    /** 0=freshwater/saltwater extreme, 1=brackish/mixed-salinity */
    public ?float $brackish = null;
    /** 0=sinking/dense, 1=buoyant/floating */
    public ?float $buoyant = null;
    /** 0=non-nautical, 1=nautical/seafaring */
    public ?float $nautical = null;
    /** 0=benthic/bottom-dwelling, 1=pelagic/open-water */
    public ?float $pelagic = null;
    /** 0=open-ocean, 1=estuarine/delta/river-mouth */
    public ?float $estuarine = null;
    /** 0=non-reef/open water, 1=coral reef/reef-associated */
    public ?float $coralReef = null;
    /** 0=purely marine or freshwater, 1=migrating between fresh/salt water */
    public ?float $anadromous = null;

    // ── Military & Combat ───────────────────────────────────────────────────
    /** 0=defensive/protective, 1=offensive/attacking */
    public ?float $offensive = null;
    /** 0=non-lethal/incapacitating, 1=lethal/deadly */
    public ?float $lethal = null;
    /** 0=overt/open, 1=covert/clandestine */
    public ?float $covert = null;
    /** 0=strategic/long-range planning, 1=tactical/immediate action */
    public ?float $tactical = null;
    /** 0=unarmored/vulnerable, 1=armored/protected */
    public ?float $armored = null;
    /** 0=melee/close-combat, 1=ranged/long-distance combat */
    public ?float $ranged = null;
    /** 0=non-explosive, 1=explosive/blast-based */
    public ?float $explosive = null;
    /** 0=physical/kinetic, 1=psychological/information warfare */
    public ?float $psychological = null;
    /** 0=static/fortified, 1=mobile/maneuver-based */
    public ?float $mobilized = null;
    /** 0=chaotic/unorganized, 1=disciplined/structured */
    public ?float $disciplined = null;

    // ── Scientific Research ─────────────────────────────────────────────────
    /** 0=observational/theoretical, 1=experimental/interventional */
    public ?float $experimental = null;
    /** 0=unreproducible/one-off, 1=reproducible/repeatable */
    public ?float $reproducible = null;
    /** 0=unfalsifiable, 1=falsifiable/testable */
    public ?float $falsifiable = null;
    /** 0=qualitative/descriptive, 1=quantitative/numerical */
    public ?float $quantitative = null;
    /** 0=cross-sectional/snapshot, 1=longitudinal/over-time */
    public ?float $longitudinal = null;
    /** 0=uncontrolled/naturalistic, 1=controlled/laboratory */
    public ?float $controlled = null;
    /** 0=anecdotal/unrefereed, 1=peer-reviewed/validated */
    public ?float $peerReviewed = null;
    /** 0=basic/pure research, 1=translational/applied */
    public ?float $translational = null;
    /** 0=exploratory/descriptive, 1=hypothesis-driven */
    public ?float $hypothesisDriven = null;
    /** 0=unique/non-replicable, 1=replicable/standard method */
    public ?float $replicable = null;

    // ── Cultural Practices ──────────────────────────────────────────────────
    /** 0=informal/casual practice, 1=ceremonial/formal rite */
    public ?float $ceremonial = null;
    /** 0=global/universal practice, 1=indigenous/culture-specific */
    public ?float $indigenous = null;
    /** 0=invented/modern, 1=transmitted/culturally inherited */
    public ?float $transmitted = null;
    /** 0=private/individual practice, 1=communal/shared rite */
    public ?float $communalRite = null;
    /** 0=recreational/non-therapeutic, 1=medicinal/healing practice */
    public ?float $medicinal = null;
    /** 0=permitted/culturally approved, 1=forbidden/taboo culturally */
    public ?float $forbidden = null;
    /** 0=industrial/mass-produced, 1=artisanal/handcrafted */
    public ?float $artisanal = null;
    /** 0=everyday/mundane, 1=festive/celebratory */
    public ?float $festive = null;
    /** 0=celebratory/joyful, 1=mourning/grief-related */
    public ?float $mourning = null;
    /** 0=ongoing/non-transitional, 1=initiatory/rite of passage */
    public ?float $initiatory = null;

    // ── Innovation & Creativity ─────────────────────────────────────────────
    /** 0=incremental/evolutionary, 1=disruptive/revolutionary */
    public ?float $disruptive = null;
    /** 0=follower/derivative, 1=pioneering/first-of-its-kind */
    public ?float $pioneering = null;
    /** 0=mature/refined, 1=prototypical/early-stage */
    public ?float $prototypical = null;
    /** 0=single-field/specialized, 1=cross-disciplinary */
    public ?float $crossDisciplinary = null;
    /** 0=obvious/prior-art, 1=novel/patentable */
    public ?float $patentable = null;
    /** 0=one-off/artisanal, 1=replicatable/scalable */
    public ?float $replicatable = null;
    /** 0=proprietary/closed, 1=open/freely shared */
    public ?float $openSource = null;
    /** 0=waterfall/big-bang, 1=iterative/agile */
    public ?float $iterative = null;
    /** 0=resource-intensive, 1=frugal/minimal-resource */
    public ?float $frugal = null;
    /** 0=top-down/institutional, 1=grassroots/bottom-up */
    public ?float $grassroots = null;

    // ── Environmental Issues ────────────────────────────────────────────────
    /** 0=pristine/clean, 1=polluted/contaminated */
    public ?float $polluted = null;
    /** 0=forested/wooded, 1=deforested/cleared */
    public ?float $deforested = null;
    /** 0=fertile/vegetated, 1=desertified/barren */
    public ?float $desertified = null;
    /** 0=sustainably fished, 1=overfished/depleted */
    public ?float $overfished = null;
    /** 0=abundant/secure, 1=threatened/at risk */
    public ?float $threatened = null;
    /** 0=carbon-neutral/negative, 1=high carbon footprint */
    public ?float $carbonFootprint = null;
    /** 0=non-toxic/benign, 1=toxic/hazardous */
    public ?float $toxic = null;
    /** 0=virgin/new material, 1=recycled/reclaimed */
    public ?float $recycled = null;
    /** 0=extractive/depleting, 1=regenerative/restoring */
    public ?float $regenerative = null;
    /** 0=climate-stable, 1=climate-affected/vulnerable */
    public ?float $climateVulnerable = null;

    // ── Historical Context ──────────────────────────────────────────────────
    /** 0=modern/contemporary, 1=medieval/pre-modern */
    public ?float $medieval = null;
    /** 0=pre/post-colonial, 1=colonial-era/imperial */
    public ?float $colonial = null;
    /** 0=ordinary/unremarkable, 1=watershed/turning-point event */
    public ?float $watershed = null;
    /** 0=historical/recorded, 1=prehistoric/pre-literate */
    public ?float $prehistoric = null;
    /** 0=modern/new, 1=antique/very old */
    public ?float $antique = null;
    /** 0=historical/past, 1=current/present-day */
    public ?float $current = null;
    /** 0=established/mature, 1=nascent/just emerging */
    public ?float $nascent = null;
    /** 0=unique/unprecedented, 1=recurrent/historically repeated */
    public ?float $recurrent = null;
    /** 0=unrecorded/oral, 1=chronicled/documented */
    public ?float $chronicled = null;
    /** 0=derivative/minor, 1=seminal/highly influential */
    public ?float $seminal = null;

    // ── Demographic & Population ─────────────────────────────────────────────
    /** 0=sparse/uninhabited, 1=populous/densely inhabited */
    public ?float $populous = null;
    /** 0=homogeneous/uniform, 1=heterogeneous/diverse */
    public ?float $heterogeneous = null;
    /** 0=youthful/young population, 1=aging/older demographic */
    public ?float $aging = null;
    /** 0=settled/native population, 1=migrant/mobile population */
    public ?float $migrant = null;
    /** 0=rural/agricultural, 1=urbanized/city-dwelling */
    public ?float $urbanized = null;
    /** 0=illiterate/uneducated, 1=literate/educated */
    public ?float $literate = null;
    /** 0=impoverished/low-income, 1=affluent/wealthy */
    public ?float $affluent = null;
    /** 0=secular/irreligious, 1=devout/religious */
    public ?float $devout = null;
    /** 0=extended/communal family, 1=nuclear/small family unit */
    public ?float $nuclear = null;
    /** 0=indigenous/homeland, 1=diaspora/dispersed population */
    public ?float $diaspora = null;

    // ── Microscopic & Atomic ─────────────────────────────────────────────────
    /** 0=macroscopic/visible, 1=subatomic/quantum-scale */
    public ?float $subatomic = null;
    /** 0=bulk/macroscopic, 1=molecular/nanoscale */
    public ?float $molecular = null;
    /** 0=macroscopic/visible to eye, 1=microscopic/only under lens */
    public ?float $microscopic = null;
    /** 0=amorphous/disordered arrangement, 1=crystalline/lattice-structured */
    public ?float $latticed = null;
    /** 0=classical/macroscopic behavior, 1=quantum/wave-particle */
    public ?float $quantum = null;
    /** 0=standard/common isotope, 1=isotopically distinct */
    public ?float $isotopic = null;
    /** 0=non-protein, 1=protein/amino-acid-based */
    public ?float $proteinaceous = null;
    /** 0=non-enzymatic, 1=enzyme-mediated/catalytic */
    public ?float $enzymatic = null;
    /** 0=non-viral, 1=viral/pathogenic microorganism */
    public ?float $viralMicro = null;
    /** 0=sterile/non-bacterial, 1=bacterial/prokaryotic */
    public ?float $bacterial = null;

    // ── Network & Connectivity ──────────────────────────────────────────────
    /** 0=isolated/disconnected, 1=highly connected/networked */
    public ?float $connected = null;
    /** 0=peripheral/leaf node, 1=hub/central node */
    public ?float $hub = null;
    /** 0=single-path/fragile, 1=redundant/robust */
    public ?float $redundant = null;
    /** 0=high-latency/slow, 1=low-latency/fast */
    public ?float $lowLatency = null;
    /** 0=wired/physical connection, 1=wireless/over-the-air */
    public ?float $wireless = null;
    /** 0=anonymous/unauthenticated, 1=authenticated/verified */
    public ?float $authenticated = null;
    /** 0=centralized/single-point, 1=decentralized/distributed */
    public ?float $decentralized = null;
    /** 0=proprietary/closed protocol, 1=standardized/open protocol */
    public ?float $protocol = null;
    /** 0=low-bandwidth/limited, 1=high-bandwidth/fast throughput */
    public ?float $bandwidth = null;
    /** 0=client-server/hierarchical, 1=peer-to-peer/equal */
    public ?float $peerToPeer = null;

    // ── Security & Safety ───────────────────────────────────────────────────
    /** 0=safe/benign, 1=hazardous/dangerous */
    public ?float $hazardous = null;
    /** 0=unmonitored/private, 1=monitored/surveilled */
    public ?float $monitored = null;
    /** 0=unprotected/vulnerable, 1=fortified/hardened */
    public ?float $fortified = null;
    /** 0=visible/detectable, 1=stealthy/hidden */
    public ?float $stealthy = null;
    /** 0=unalarmed/silent, 1=alarmed/alerting */
    public ?float $alarmed = null;
    /** 0=uninsured/unprotected, 1=insured/covered */
    public ?float $insured = null;
    /** 0=inescapable/trapped, 1=escapable/exit available */
    public ?float $escapable = null;
    /** 0=harmless, 1=injury-causing */
    public ?float $injurious = null;
    /** 0=private/unobserved, 1=under surveillance */
    public ?float $surveilled = null;
    /** 0=no backup/single point of failure, 1=failsafe/backup */
    public ?float $failsafe = null;

    // ── Privacy & Confidentiality ───────────────────────────────────────────
    /** 0=public/open, 1=private/confidential */
    public ?float $private = null;
    /** 0=identified/traceable, 1=anonymous/untraceable */
    public ?float $anonymous = null;
    /** 0=transparent/clear, 1=obfuscated/hidden */
    public ?float $obfuscated = null;
    /** 0=disclosed/public, 1=secret/undisclosed */
    public ?float $secret = null;
    /** 0=non-consensual/forced, 1=consensual/agreed */
    public ?float $consensual = null;
    /** 0=shared/collective, 1=personal/individual */
    public ?float $personal = null;
    /** 0=benign/non-sensitive, 1=sensitive/requiring protection */
    public ?float $sensitive = null;
    /** 0=public domain, 1=proprietary/owned */
    public ?float $proprietary = null;
    /** 0=fully disclosed, 1=redacted/partially hidden */
    public ?float $redacted = null;
    /** 0=under surveillance, 1=surveillance-free/private */
    public ?float $surveillanceFree = null;

    // ── Trust & Authority ───────────────────────────────────────────────────
    /** 0=unreliable/inconsistent, 1=reliable/dependable */
    public ?float $reliable = null;
    /** 0=illegitimate/unauthorized, 1=legitimate/properly authorized */
    public ?float $legitimate = null;
    /** 0=novice/layperson, 1=expert/highly skilled */
    public ?float $expertLevel = null;
    /** 0=unaccountable, 1=accountable/responsible */
    public ?float $accountable = null;
    /** 0=unverifiable/opaque, 1=verifiable/provable */
    public ?float $verifiable = null;
    /** 0=optional/voluntary, 1=mandated/required */
    public ?float $mandated = null;
    /** 0=unofficial/unendorsed, 1=officially endorsed */
    public ?float $endorsed = null;
    /** 0=unaudited/unverified, 1=audited/checked */
    public ?float $audited = null;
    /** 0=self-directed, 1=delegated/authorized by higher authority */
    public ?float $delegated = null;
    /** 0=informal/unratified, 1=ratified/formally approved */
    public ?float $ratified = null;

    // ── Gastronomy & Food Culture ───────────────────────────────────────────
    /** 0=home cooking/peasant food, 1=haute cuisine/gourmet */
    public ?float $gourmet = null;
    /** 0=fresh/new food, 1=aged/matured food */
    public ?float $agedFood = null;
    /** 0=fine dining, 1=street food/casual */
    public ?float $streetFood = null;
    /** 0=omnivorous/carnivorous, 1=plant-based/vegetarian */
    public ?float $vegetarian = null;
    /** 0=familiar/common ingredient, 1=exotic/unusual ingredient */
    public ?float $exoticFood = null;
    /** 0=fresh/unprocessed, 1=preserved/long-shelf */
    public ?float $preserved = null;
    /** 0=traditional/authentic cuisine, 1=fusion/cross-cultural */
    public ?float $fusion = null;
    /** 0=everyday food, 1=ritual/ceremonial food */
    public ?float $ritualFood = null;
    /** 0=sophisticated/challenging dish, 1=comforting/familiar food */
    public ?float $comfortFood = null;
    /** 0=cooked/processed, 1=raw/uncooked */
    public ?float $rawFood = null;

    // ── Philosophical Ethics ─────────────────────────────────────────────────
    /** 0=deontological/rule-based, 1=utilitarian/outcome-based */
    public ?float $utilitarian = null;
    /** 0=consequentialist/outcome-focused, 1=deontological/duty-based */
    public ?float $deontological = null;
    /** 0=non-consequentialist, 1=consequentialist/results-focused */
    public ?float $consequentialist = null;
    /** 0=ascetic/pain-embracing, 1=hedonistic/pleasure-seeking */
    public ?float $hedonistic = null;
    /** 0=epicurean/pleasure-seeking, 1=stoic/endurance-focused */
    public ?float $stoic = null;
    /** 0=meaning-affirming, 1=nihilistic/meaning-denying */
    public ?float $nihilistic = null;
    /** 0=idealistic/theoretical, 1=pragmatic/practical */
    public ?float $pragmaticEth = null;
    /** 0=absolutist/universal, 1=relativistic/context-dependent */
    public ?float $relativistic = null;
    /** 0=relativistic/flexible, 1=absolutist/universal rules */
    public ?float $absolutist = null;
    /** 0=monistic/single-view, 1=pluralistic/multiple-view */
    public ?float $pluralistic = null;

    // ── Cognitive Biases & Heuristics ──────────────────────────────────────
    /** 0=unbiased/neutral, 1=biased/slanted */
    public ?float $biased = null;
    /** 0=individual/nuanced, 1=stereotyped/overgeneralized */
    public ?float $stereotyped = null;
    /** 0=unprejudiced/fair, 1=prejudiced/discriminatory */
    public ?float $prejudiced = null;
    /** 0=realistic/accurate, 1=idealized/unrealistically positive */
    public ?float $idealized = null;
    /** 0=internalized/self-attributed, 1=projected/attributed to others */
    public ?float $projected = null;
    /** 0=accepted/honest, 1=rationalized/post-hoc justified */
    public ?float $rationalized = null;
    /** 0=self-attributed, 1=scapegoated/blame-shifted */
    public ?float $scapegoated = null;
    /** 0=sound/valid reasoning, 1=fallacious/invalid reasoning */
    public ?float $fallacious = null;
    /** 0=systematic/methodical, 1=heuristic/rule-of-thumb */
    public ?float $heuristic = null;
    /** 0=well-founded/verified, 1=assumptive/taken for granted */
    public ?float $assumptive = null;

    // ── Linguistic Register ─────────────────────────────────────────────────
    /** 0=formal/standard, 1=colloquial/everyday */
    public ?float $colloquial = null;
    /** 0=standard/accent-neutral, 1=dialectal/regional */
    public ?float $dialectal = null;
    /** 0=general/lay, 1=jargon/field-specific */
    public ?float $jargon = null;
    /** 0=established/monolingual, 1=pidgin/contact language */
    public ?float $pidgin = null;
    /** 0=pidgin/simplified, 1=creolized/nativized */
    public ?float $creolized = null;
    /** 0=non-standard/dialectal, 1=standardized/prescriptive */
    public ?float $standardized = null;
    /** 0=plain/vernacular, 1=elevated/high register */
    public ?float $elevatedRegister = null;
    /** 0=refined/polite, 1=vulgar/crude language */
    public ?float $vulgar = null;
    /** 0=prosaic/everyday language, 1=poetic/heightened language */
    public ?float $poetic = null;
    /** 0=plain/straightforward, 1=rhetorical/oratorical */
    public ?float $rhetorical = null;

    // ── Spatial Orientation ─────────────────────────────────────────────────
    /** 0=southward/equatorward, 1=northward/poleward */
    public ?float $northward = null;
    /** 0=westward, 1=eastward */
    public ?float $eastward = null;
    /** 0=downward/below, 1=upward/above */
    public ?float $upward = null;
    /** 0=outward/external, 1=inward/internal */
    public ?float $inward = null;
    /** 0=backward/rearward, 1=frontward/forward */
    public ?float $frontward = null;
    /** 0=orthogonal/right-angle, 1=diagonal/oblique */
    public ?float $diagonal = null;
    /** 0=straight/linear path, 1=circular/looping path */
    public ?float $circularPath = null;
    /** 0=straight/flat, 1=spiral/helical */
    public ?float $spiral = null;
    /** 0=tangential/peripheral, 1=radial/center-outward */
    public ?float $radial = null;
    /** 0=radial/center-outward, 1=tangential/peripheral */
    public ?float $tangential = null;

    // ── Light & Optics ──────────────────────────────────────────────────────
    /** 0=non-refractive/straight, 1=refractive/bending light */
    public ?float $refractive = null;
    /** 0=absorptive/non-reflective, 1=reflective/mirror-like */
    public ?float $lightReflective = null;
    /** 0=reflective/non-absorptive, 1=absorptive/light-absorbing */
    public ?float $absorptive = null;
    /** 0=unpolarized, 1=polarized/directional light */
    public ?float $polarized = null;
    /** 0=focused/concentrated, 1=diffuse/scattered light */
    public ?float $diffuse = null;
    /** 0=diffuse/scattered, 1=focused/concentrated beam */
    public ?float $focused = null;
    /** 0=incoherent/mixed phase, 1=coherent/laser-like */
    public ?float $coherent = null;
    /** 0=monochromatic/single-wavelength, 1=spectral/full spectrum */
    public ?float $spectral = null;
    /** 0=visible/not infrared, 1=infrared/heat-emitting */
    public ?float $infrared = null;
    /** 0=visible/not ultraviolet, 1=ultraviolet/high-energy */
    public ?float $ultraviolet = null;

    // ── Electrical & Electronic ─────────────────────────────────────────────
    /** 0=low-voltage, 1=high-voltage */
    public ?float $highVoltage = null;
    /** 0=low-current, 1=high-current */
    public ?float $highCurrent = null;
    /** 0=conductive/low-resistance, 1=resistive/high-resistance */
    public ?float $resistive = null;
    /** 0=non-capacitive, 1=capacitive/charge-storing */
    public ?float $capacitive = null;
    /** 0=non-inductive, 1=inductive/magnetic-field */
    public ?float $inductive = null;
    /** 0=steady/DC, 1=oscillating/AC */
    public ?float $oscillating = null;
    /** 0=AC/alternating, 1=rectified/DC-converted */
    public ?float $rectified = null;
    /** 0=insulating/non-conducting, 1=semiconductive */
    public ?float $semiconductive = null;
    /** 0=non-piezoelectric, 1=piezoelectric/pressure-electric */
    public ?float $piezoelectric = null;
    /** 0=non-thermoelectric, 1=thermoelectric/heat-to-electricity */
    public ?float $thermoelectric = null;

    // ── Geological ──────────────────────────────────────────────────────────
    /** 0=igneous/volcanic, 1=sedimentary/layered */
    public ?float $sedimentary = null;
    /** 0=sedimentary/metamorphic, 1=igneous/volcanic */
    public ?float $igneous = null;
    /** 0=original/unaltered, 1=metamorphic/transformed by heat/pressure */
    public ?float $metamorphic = null;
    /** 0=unfossilized/recent, 1=fossilized/preserved ancient */
    public ?float $fossilized = null;
    /** 0=tectonically stable, 1=tectonic/plate-boundary */
    public ?float $tectonic = null;
    /** 0=resistant/uneroded, 1=erosive/weathered by elements */
    public ?float $erosive = null;
    /** 0=in-situ/bedrock, 1=alluvial/water-deposited */
    public ?float $alluvial = null;
    /** 0=non-karst/solid, 1=karst/dissolution-shaped */
    public ?float $karst = null;
    /** 0=temperate/non-frozen, 1=permafrost/permanently frozen */
    public ?float $permafrost = null;
    /** 0=non-geothermal, 1=geothermal/earth-heat */
    public ?float $geothermal = null;

    // ── Atmospheric ─────────────────────────────────────────────────────────
    /** 0=dry/arid, 1=humid/moist */
    public ?float $humid = null;
    /** 0=calm/still air, 1=windy/breezy */
    public ?float $windy = null;
    /** 0=calm/fair weather, 1=stormy/turbulent */
    public ?float $stormy = null;
    /** 0=clear/visibility high, 1=foggy/misty */
    public ?float $foggy = null;
    /** 0=clear/clean air, 1=hazy/smoggy */
    public ?float $hazy = null;
    /** 0=dry/no precipitation, 1=precipitating/rainy/snowy */
    public ?float $precipitating = null;
    /** 0=local/non-frontal, 1=frontal/weather-front */
    public ?float $frontal = null;
    /** 0=stratiform/layered, 1=convective/updraft */
    public ?float $convective = null;
    /** 0=well-mixed, 1=stratified/layered atmosphere */
    public ?float $stratified = null;
    /** 0=reliably rainy, 1=drought-prone/arid */
    public ?float $droughtProne = null;

    // ── Freshwater & Aquatic Features ──────────────────────────────────────
    /** 0=saline/marine, 1=freshwater */
    public ?float $freshwater = null;
    /** 0=freshwater/non-saline, 1=saline/salt-containing */
    public ?float $saline = null;
    /** 0=flowing/dynamic, 1=stagnant/still */
    public ?float $stagnant = null;
    /** 0=calm/still water, 1=turbulent/churning */
    public ?float $turbulent = null;
    /** 0=murky/opaque water, 1=clear/transparent water */
    public ?float $clearWater = null;
    /** 0=clear/transparent, 1=murky/turbid */
    public ?float $murky = null;
    /** 0=anoxic/oxygen-poor, 1=oxygenated/well-aerated */
    public ?float $oxygenated = null;
    /** 0=oligotrophic/nutrient-poor, 1=eutrophic/nutrient-rich */
    public ?float $eutrophic = null;
    /** 0=eutrophic/nutrient-rich, 1=oligotrophic/nutrient-poor */
    public ?float $oligotrophic = null;
    /** 0=riverine/flowing, 1=lacustrine/lake-related */
    public ?float $lacustrine = null;

    // ── Meteorological Events ───────────────────────────────────────────────
    /** 0=non-cyclonic/stable, 1=cyclonic/rotating storm */
    public ?float $cyclonic = null;
    /** 0=non-seismic/stable, 1=seismic/earthquake-related */
    public ?float $seismic = null;
    /** 0=non-volcanic/stable, 1=eruptive/volcanic */
    public ?float $eruptive = null;
    /** 0=avalanche-safe, 1=avalanche-prone */
    public ?float $avalancheProne = null;
    /** 0=flood-safe, 1=flood-prone */
    public ?float $floodProne = null;
    /** 0=quiet/non-thunderous, 1=thunderous/lightning */
    public ?float $thunderous = null;
    /** 0=tropical/non-blizzard, 1=blizzardous/severe snowstorm */
    public ?float $blizzardous = null;
    /** 0=non-tornadic, 1=tornadic/tornado-related */
    public ?float $tornadic = null;
    /** 0=rainy/wet, 1=drought-event/extreme dryness */
    public ?float $droughtEvent = null;
    /** 0=cool/temperate, 1=heatwave/extreme heat */
    public ?float $heatwave = null;

    // ── Philosophical Concepts ──────────────────────────────────────────────
    /** 0=non-dialectical, 1=dialectic/thesis-antithesis-synthesis */
    public ?float $dialectic = null;
    /** 0=pre-theoretical/naive, 1=epistemological/knowledge-theory */
    public ?float $epistemological = null;
    /** 0=physical/natural, 1=metaphysical/beyond physical */
    public ?float $metaphysical = null;
    /** 0=literal/text-only, 1=hermeneutical/interpretive */
    public ?float $hermeneutical = null;
    /** 0=theoretical/abstract, 1=phenomenological/experience-first */
    public ?float $phenomenological = null;
    /** 0=essence-focused/eternal, 1=existentialist/existence-first */
    public ?float $existentialist = null;
    /** 0=meaning-affirming, 1=absurdist/meaning-questioning */
    public ?float $absurdist = null;
    /** 0=meaning-affirming/idealist, 1=nihilistic/rejecting meaning */
    public ?float $nihilisticPhil = null;
    /** 0=idealist/mind-primary, 1=materialist/matter-primary */
    public ?float $materialist = null;
    /** 0=materialist/matter-primary, 1=idealist/mind-primary */
    public ?float $idealist = null;

    // ── Personality Traits ──────────────────────────────────────────────────
    /** 0=introverted/reserved, 1=extroverted/outgoing */
    public ?float $extroverted = null;
    /** 0=extroverted/outgoing, 1=introverted/reserved */
    public ?float $introverted = null;
    /** 0=emotionally stable, 1=neurotic/emotionally unstable */
    public ?float $neurotic = null;
    /** 0=disagreeable/antagonistic, 1=agreeable/cooperative */
    public ?float $agreeable = null;
    /** 0=impulsive/careless, 1=conscientious/careful */
    public ?float $conscientious = null;
    /** 0=closed-minded/rigid, 1=open-minded/curious */
    public ?float $openMinded = null;
    /** 0=flexible/yielding, 1=stubborn/unyielding */
    public ?float $stubborn = null;
    /** 0=apathetic/unfeeling, 1=empathetic/feeling with others */
    public ?float $empathetic = null;
    /** 0=unremarkable/plain, 1=charismatic/compelling */
    public ?float $charismatic = null;
    /** 0=passive/meek, 1=assertive/self-confident */
    public ?float $assertive = null;

    // ── Social Issues ───────────────────────────────────────────────────────
    /** 0=liberating/empowering, 1=oppressive/dominating */
    public ?float $oppressive = null;
    /** 0=constraining/oppressive, 1=liberatory/freeing */
    public ?float $liberatory = null;
    /** 0=fair/non-discriminatory, 1=discriminatory/biased */
    public ?float $discriminatory = null;
    /** 0=inequitable/hierarchical, 1=egalitarian/equal */
    public ?float $egalitarian = null;
    /** 0=exclusive/excluding, 1=inclusive/welcoming */
    public ?float $inclusive = null;
    /** 0=centered/privileged, 1=marginalized/sidelined */
    public ?float $marginalized = null;
    /** 0=disempowering, 1=empowering/enabling */
    public ?float $empowering = null;
    /** 0=passive/apolitical, 1=activist/engaged */
    public ?float $activist = null;
    /** 0=conservative/status-quo, 1=reformist/change-seeking */
    public ?float $reformist = null;
    /** 0=moderate/centrist, 1=radical/extreme change */
    public ?float $radical = null;

    // ── Economic Systems ────────────────────────────────────────────────────
    /** 0=non-capitalist/anti-market, 1=capitalist/market-driven */
    public ?float $capitalist = null;
    /** 0=capitalist/market-driven, 1=socialist/state-guided */
    public ?float $socialist = null;
    /** 0=capitalist/private, 1=communist/collective ownership */
    public ?float $communist = null;
    /** 0=modern/post-feudal, 1=feudal/lord-serf system */
    public ?float $feudal = null;
    /** 0=domestic/non-mercantilist, 1=mercantile/trade-focused */
    public ?float $mercantile = null;
    /** 0=regulatory/interventionist, 1=neoliberal/free-market */
    public ?float $neoliberal = null;
    /** 0=competitive/private, 1=cooperative/worker-owned */
    public ?float $cooperative = null;
    /** 0=concentrating/regressive, 1=redistributive/equalizing */
    public ?float $redistributive = null;
    /** 0=regenerative/additive, 1=extractive/depleting */
    public ?float $extractive = null;
    /** 0=commercial/surplus-oriented, 1=subsistence/self-sufficient */
    public ?float $subsistence = null;

    // ── Health & Wellness ───────────────────────────────────────────────────
    /** 0=poorly designed/straining, 1=ergonomic/body-friendly */
    public ?float $ergonomic = null;
    /** 0=reductionist/part-focused, 1=holistic/whole-person */
    public ?float $holistic = null;
    /** 0=reactive/curative, 1=preventative/proactive */
    public ?float $preventative = null;
    /** 0=symptomatic/treatment, 1=diagnostic/identification */
    public ?float $diagnostic = null;
    /** 0=palliative/comfort, 1=curative/disease-eliminating */
    public ?float $curative = null;
    /** 0=immunosuppressive, 1=immunogenic/immune-stimulating */
    public ?float $immunogenic = null;
    /** 0=pain-inducing/hyperalgesic, 1=analgesic/pain-relieving */
    public ?float $analgesic = null;
    /** 0=stimulating/activating, 1=sedative/calming */
    public ?float $sedative = null;
    /** 0=sedating/calming, 1=stimulant/activating */
    public ?float $stimulant = null;
    /** 0=non-psychoactive, 1=psychoactive/mind-altering */
    public ?float $psychoactive = null;

    // ── Craftsmanship ───────────────────────────────────────────────────────
    /** 0=machine-made/factory, 1=handmade/artisan */
    public ?float $handmade = null;
    /** 0=industrial/mass-produced, 1=artisan-crafted/bespoke */
    public ?float $artisanCraft = null;
    /** 0=rough/approximate, 1=precision-crafted/finely made */
    public ?float $precisionCraft = null;
    /** 0=plain/unengraved, 1=engraved/incised */
    public ?float $engraved = null;
    /** 0=unwoven/extruded, 1=woven/interlaced */
    public ?float $woven = null;
    /** 0=cast/molded, 1=sculpted/carved */
    public ?float $sculpted = null;
    /** 0=cast/poured, 1=forged/hammered */
    public ?float $forged = null;
    /** 0=metal/non-ceramic, 1=ceramic/fired clay */
    public ?float $ceramic = null;
    /** 0=unfinished/matte, 1=lacquered/high-gloss */
    public ?float $lacquered = null;
    /** 0=plain/unembroidered, 1=embroidered/needle-worked */
    public ?float $embroidered = null;

    // ── Academic Disciplines ────────────────────────────────────────────────
    /** relevance to mathematics discipline */
    public ?float $disciplineMath = null;
    /** relevance to natural sciences discipline */
    public ?float $disciplineScience = null;
    /** relevance to history discipline */
    public ?float $disciplineHistory = null;
    /** relevance to literature/literary studies */
    public ?float $disciplineLiterature = null;
    /** relevance to sociology discipline */
    public ?float $disciplineSociology = null;
    /** relevance to anthropology discipline */
    public ?float $disciplineAnthropology = null;
    /** relevance to psychology discipline */
    public ?float $disciplinePsychology = null;
    /** relevance to linguistics discipline */
    public ?float $disciplineLinguistics = null;
    /** relevance to theology discipline */
    public ?float $disciplineTheology = null;
    /** relevance to law/jurisprudence discipline */
    public ?float $disciplineLaw = null;

    // ── Visual Art Styles ───────────────────────────────────────────────────
    /** 0=graphic/flat, 1=painterly/brushwork-evident */
    public ?float $painterly = null;
    /** 0=flat/two-dimensional, 1=sculptural/three-dimensional */
    public ?float $sculptural = null;
    /** 0=painted/drawn, 1=photographic/lens-based */
    public ?float $photographic = null;
    /** 0=painterly/fine art, 1=graphic/designed */
    public ?float $graphic = null;
    /** 0=representational/figurative, 1=abstract/non-representational */
    public ?float $abstractArt = null;
    /** 0=maximalist/ornate, 1=minimalist/spare */
    public ?float $minimalist = null;
    /** 0=realist/rational, 1=surrealist/dreamlike */
    public ?float $surrealist = null;
    /** 0=sharp/precise, 1=impressionist/loose */
    public ?float $impressionist = null;
    /** 0=restrained/calm, 1=expressionist/emotionally intense */
    public ?float $expressionist = null;
    /** 0=sensory/formal, 1=conceptual/idea-based art */
    public ?float $conceptualArt = null;

    // ── Literary Forms ──────────────────────────────────────────────────────
    /** 0=prosaic/plain, 1=lyric/songlike */
    public ?float $lyric = null;
    /** 0=poetic/verse, 1=prosaic/prose */
    public ?float $prosaic = null;
    /** 0=non-fictional, 1=fictional/imagined */
    public ?float $fictional = null;
    /** 0=fictional/imagined, 1=non-fictional/factual */
    public ?float $nonfictional = null;
    /** 0=fictional/non-biographical, 1=biographical/life-story */
    public ?float $biographical = null;
    /** 0=third-person/omniscient, 1=epistolary/letter-form */
    public ?float $epistolary = null;
    /** 0=conventional/traditional form, 1=experimental/avant-garde */
    public ?float $experimentalLit = null;
    /** 0=apocryphal/non-canonical, 1=canonical/established classic */
    public ?float $canonicalLit = null;
    /** 0=elevated/literary language, 1=vernacular/everyday speech */
    public ?float $vernacular = null;
    /** 0=verbose/expanded, 1=aphoristic/concise wisdom */
    public ?float $aphoristic = null;

    // ── Film & Cinema ───────────────────────────────────────────────────────
    /** 0=non-cinematic/theatrical, 1=cinematic/film-specific */
    public ?float $cinematic = null;
    /** 0=fictional/narrative, 1=documentary/factual */
    public ?float $documentary = null;
    /** 0=live-action, 1=animated/drawn */
    public ?float $animated = null;
    /** 0=bright/idealistic, 1=noir/dark/cynical */
    public ?float $noir = null;
    /** 0=mainstream/conventional, 1=avant-garde/experimental */
    public ?float $avantGarde = null;
    /** 0=indie/art-house, 1=blockbuster/mass-market */
    public ?float $blockbuster = null;
    /** 0=studio/mainstream, 1=indie/independent production */
    public ?float $indie = null;
    /** 0=non-horror/safe, 1=horror/frightening */
    public ?float $horror = null;
    /** 0=serious/dramatic, 1=comedic/humorous */
    public ?float $comedic = null;
    /** 0=slow/contemplative, 1=thriller/fast-paced tension */
    public ?float $thriller = null;

    // ── Dance & Performing Arts ──────────────────────────────────────────────
    /** 0=unscripted/improvised, 1=choreographic/composed movement */
    public ?float $choreographic = null;
    /** 0=composed/scripted, 1=improvisational/spontaneous */
    public ?float $improvisational = null;
    /** 0=secular/non-ceremonial, 1=ceremonial dance/ritual */
    public ?float $ceremonialDance = null;
    /** 0=cinematic/non-theatrical, 1=theatrical/stage-based */
    public ?float $theatrical = null;
    /** 0=spoken/non-operatic, 1=operatic/sung drama */
    public ?float $operatic = null;
    /** 0=non-acrobatic/grounded, 1=acrobatic/gymnastic */
    public ?float $acrobatic = null;
    /** 0=folk/free-form, 1=balletic/classical technique */
    public ?float $balletic = null;
    /** 0=contemporary/non-folkloric, 1=folkloric dance/traditional */
    public ?float $folkloricDance = null;
    /** 0=classical/traditional, 1=contemporary dance/modern */
    public ?float $contemporaryDance = null;
    /** 0=verbal/spoken, 1=mime/silent performance */
    public ?float $mime = null;

    // ── Game Design ─────────────────────────────────────────────────────────
    /** 0=linear/guided, 1=sandbox/open-world */
    public ?float $sandbox = null;
    /** 0=open-world/sandbox, 1=linear/guided narrative */
    public ?float $linearGame = null;
    /** 0=action/twitch, 1=role-playing/character-driven */
    public ?float $rpg = null;
    /** 0=action-reflex, 1=strategy/planning-based */
    public ?float $strategyGame = null;
    /** 0=action/combat, 1=puzzle/problem-solving */
    public ?float $puzzle = null;
    /** 0=abstract, 1=simulation/realistic */
    public ?float $simulation = null;
    /** 0=slow/strategic, 1=action/fast-reflex */
    public ?float $actionGame = null;
    /** 0=gameplay-focused, 1=narrative/story-driven */
    public ?float $narrativeGame = null;
    /** 0=single-player/solo, 1=multiplayer/social */
    public ?float $multiplayer = null;
    /** 0=finite/completable, 1=endless/infinite play */
    public ?float $endlessGame = null;

    // ── Architecture Styles ─────────────────────────────────────────────────
    /** 0=modern/contemporary style, 1=gothic/medieval-inspired */
    public ?float $gothic = null;
    /** 0=simple/restrained, 1=baroque/ornate-theatrical */
    public ?float $baroque = null;
    /** 0=traditional/pre-modern, 1=modernist/functional */
    public ?float $modernist = null;
    /** 0=modernist/pure, 1=postmodernist/eclectic */
    public ?float $postmodernist = null;
    /** 0=imported/non-local, 1=vernacular/locally adapted */
    public ?float $vernacularArch = null;
    /** 0=ornate/decorated, 1=brutalist/raw concrete */
    public ?float $brutalist = null;
    /** 0=contemporary/non-classical, 1=neoclassical/antique-inspired */
    public ?float $neoclassical = null;
    /** 0=ordered/structured, 1=deconstructivist/fragmented */
    public ?float $deconstructivist = null;
    /** 0=ornate/complex, 1=minimalist/reduced */
    public ?float $minimalistArch = null;
    /** 0=rectilinear/geometric, 1=organic/biomorphic */
    public ?float $organicArch = null;

    // ── Social Media & Internet Culture ─────────────────────────────────────
    /** 0=niche/limited spread, 1=viral/widely spread online */
    public ?float $viralSocial = null;
    /** 0=evergreen/timeless, 1=trending/momentarily popular */
    public ?float $trending = null;
    /** 0=sincere/direct, 1=meme/ironic-remixed */
    public ?float $meme = null;
    /** 0=untagged/unsearchable, 1=hashtagged/categorized */
    public ?float $hashtagged = null;
    /** 0=private/unshared, 1=shareable/designed to spread */
    public ?float $shareable = null;
    /** 0=informative/accurate, 1=clickbait/sensational headline */
    public ?float $clickbait = null;
    /** 0=organic/non-algorithmic, 1=algorithmic/ranked */
    public ?float $algorithmic = null;
    /** 0=uncurated/raw, 1=curated/editorially selected */
    public ?float $curated = null;
    /** 0=top-down/produced, 1=crowdsourced/user-generated */
    public ?float $crowdsourced = null;
    /** 0=permanent/archived, 1=ephemeral/disappearing content */
    public ?float $ephemeralContent = null;

    // ── Physics ─────────────────────────────────────────────────────────────
    /** 0=Newtonian/classical, 1=relativistic/Einstein */
    public ?float $physicsRelativistic = null;
    /** 0=non-thermodynamic, 1=thermodynamic/heat-related */
    public ?float $thermodynamic = null;
    /** 0=non-electromagnetic, 1=electromagnetic/EM-related */
    public ?float $electromagnetic = null;
    /** 0=non-acoustic/vibrationless, 1=acoustic/sound-wave */
    public ?float $acoustic = null;
    /** 0=non-optical/non-light, 1=optical/light-related */
    public ?float $optical = null;
    /** 0=non-nuclear/chemical, 1=nuclear/atomic-core */
    public ?float $nuclearPhysics = null;
    /** 0=solid/liquid, 1=plasma/ionized gas */
    public ?float $plasma = null;
    /** 0=low-entropy/ordered, 1=entropic/disordered */
    public ?float $entropic = null;
    /** 0=potential/static energy, 1=kinetic/motion energy */
    public ?float $kinetic = null;
    /** 0=non-calorific, 1=calorific/heat-producing */
    public ?float $calorific = null;

    // ── Nutrition Science ───────────────────────────────────────────────────
    /** 0=micronutrient/trace, 1=macronutrient/bulk */
    public ?float $macronutrient = null;
    /** 0=macronutrient/bulk, 1=micronutrient/trace */
    public ?float $micronutrient = null;
    /** 0=antibiotic/non-probiotic, 1=probiotic/gut-beneficial */
    public ?float $probiotic = null;
    /** 0=non-prebiotic, 1=prebiotic/gut-microbiome feeding */
    public ?float $prebiotic = null;
    /** 0=pro-oxidant, 1=antioxidant/free-radical neutralizing */
    public ?float $antioxidant = null;
    /** 0=low-glycemic/stable blood sugar, 1=high-glycemic/spike */
    public ?float $glycemic = null;
    /** 0=anti-inflammatory, 1=inflammatory/pro-inflammatory */
    public ?float $inflammatory = null;
    /** 0=acidic/low-pH, 1=alkaline/high-pH diet */
    public ?float $alkaline = null;
    /** 0=accumulating/non-detox, 1=detoxifying/cleansing */
    public ?float $detoxifying = null;
    /** 0=poorly absorbed, 1=highly bioavailable/well-absorbed */
    public ?float $bioavailable = null;

    // ── Sleep & Consciousness ───────────────────────────────────────────────
    /** 0=alerting/stimulating, 1=soporific/sleep-inducing */
    public ?float $soporific = null;
    /** 0=sedating/calming, 1=stimulatory/activating */
    public ?float $stimulatory = null;
    /** 0=fully conscious, 1=hypnotic/trance-inducing */
    public ?float $hypnotic = null;
    /** 0=agitated/distracted, 1=meditative/deeply focused */
    public ?float $meditative = null;
    /** 0=reality-grounded, 1=hallucinatory/perception-altering */
    public ?float $hallucinatory = null;
    /** 0=waking/reality-based, 1=dreamlike/surreal */
    public ?float $dreamlike = null;
    /** 0=conscious/aware, 1=trance/altered state */
    public ?float $trance = null;
    /** 0=distracted/scattered, 1=mindful/present-aware */
    public ?float $mindful = null;
    /** 0=sleepy/fatigued, 1=alert/fully awake */
    public ?float $alert = null;
    /** 0=restless/disturbed, 1=restful/recuperative */
    public ?float $restful = null;

    // ── Figurative Language ──────────────────────────────────────────────────
    /** 0=literal/direct, 1=metaphorical/transferred meaning */
    public ?float $metaphorical = null;
    /** 0=direct/literal, 1=metonymic/associated substitution */
    public ?float $metonymic = null;
    /** 0=whole/complete reference, 1=synecdochic/part-for-whole */
    public ?float $synecdochic = null;
    /** 0=sincere/earnest, 1=ironic/opposite meaning */
    public ?float $ironic = null;
    /** 0=consistent/non-paradoxical, 1=paradoxical/self-contradicting */
    public ?float $paradoxical = null;
    /** 0=straightforward, 1=oxymoronic/contradictory pairing */
    public ?float $oxymoronic = null;
    /** 0=understated/litotic, 1=hyperbolic/extreme exaggeration */
    public ?float $hyperbolic = null;
    /** 0=direct/explicit, 1=litotic/understatement by negation */
    public ?float $litotic = null;
    /** 0=novel/non-proverbial, 1=proverbial/conventional wisdom */
    public ?float $proverbial = null;
    /** 0=verbose/expanded, 1=aphoristic/compact wisdom */
    public ?float $figurativeAphoristic = null;

    // ── Multisensory & Embodied ──────────────────────────────────────────────
    /** 0=single-sense, 1=synaesthetic/cross-sensory */
    public ?float $synaesthetic = null;
    /** 0=single-sense, 1=multisensory/engaging multiple senses */
    public ?float $multisensory = null;
    /** 0=distanced/detached, 1=immersive/enveloping */
    public ?float $immersive = null;
    /** 0=direct/unmediated, 1=mediated/filtered through medium */
    public ?float $mediated = null;
    /** 0=abstract/disembodied, 1=embodied/felt in body */
    public ?float $embodied = null;
    /** 0=non-spatial/abstract, 1=spatially experienced */
    public ?float $spatialSense = null;
    /** 0=non-temporal/timeless, 1=temporally experienced */
    public ?float $temporalSense = null;
    /** 0=externally perceived, 1=proprioceptive/body-position aware */
    public ?float $proprioceptive = null;
    /** 0=externally focused, 1=interoceptive/inner-body aware */
    public ?float $interoceptive = null;
    /** 0=interoceptive/inner, 1=exteroceptive/outer-world sensing */
    public ?float $exteroceptive = null;

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
