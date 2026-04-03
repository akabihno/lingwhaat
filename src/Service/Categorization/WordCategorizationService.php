<?php

namespace App\Service\Categorization;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordCategorizationService
{
    private const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL = 'claude-opus-4-6';
    private const MAX_TOKENS = 16384;

    private string $systemPrompt;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $anthropicApiKey
    ) {
        $this->systemPrompt = $this->buildSystemPrompt();
    }

    /**
     * Categorize a batch of words.
     *
     * @param string[] $words
     * @return array<string, array<string, float>>  word => [category => score]
     */
    public function categorize(array $words): array
    {
        if (empty($words)) {
            return [];
        }

        $wordList = implode(', ', $words);

        $response = $this->httpClient->request('POST', self::CLAUDE_API_URL, [
            'headers' => [
                'x-api-key' => $this->anthropicApiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ],
            'json' => [
                'model' => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'system' => $this->systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Categorize these words: $wordList",
                    ],
                ],
            ],
        ]);

        $data = $response->toArray();
        $content = $data['content'][0]['text'] ?? '';

        return $this->parseResponse($content);
    }

    private function parseResponse(string $content): array
    {
        // Extract JSON object from the response (may be wrapped in markdown)
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/s', $content, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/(\{[\s\S]*\})/s', $content, $matches)) {
            $json = $matches[1];
        } else {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        // Normalise values to floats in [0.0, 1.0]
        $result = [];
        foreach ($decoded as $word => $categories) {
            if (!is_array($categories)) {
                continue;
            }
            $normalized = [];
            foreach ($categories as $key => $value) {
                if (is_numeric($value)) {
                    $normalized[$key] = max(0.0, min(1.0, (float) $value));
                }
            }
            $result[(string) $word] = $normalized;
        }

        return $result;
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a semantic word categorizer for a multilingual dictionary system.

Given a list of words, return a JSON object where each key is a word and each value is an object of category scores.
Scores are floats from 0.0 to 1.0. Only include dimensions you are confident about — omit uncertain ones.
Respond with ONLY the JSON object, no explanation.

Category dimensions (name: description of scale):
livingBeing: 0=non-living, 1=living being
animate: 0=inanimate, 1=animate/self-moving
human: 0=non-human, 1=human
animal: 0=non-animal, 1=animal
plant: 0=non-plant, 1=plant
naturalOrigin: 0=artificial, 1=natural/wild
physical: 0=abstract/intangible, 1=physical/tangible
organic: 0=inorganic, 1=organic/biological
discrete: 0=collective concept, 1=singular discrete entity
complete: 0=partial/fragment, 1=whole/complete
size: 0=tiny, 1=huge
weight: 0=lightweight, 1=heavy
length: 0=short, 1=long
age: 0=new/young, 1=old/ancient
quantity: 0=singular/few, 1=many/numerous
intensity: 0=mild/weak, 1=intense/strong
complexity: 0=simple, 1=complex
density: 0=sparse, 1=dense
depth: 0=shallow, 1=deep/profound
scope: 0=narrow/specific, 1=broad/general
motion: 0=still/static, 1=moving/dynamic
speed: 0=slow, 1=fast
directed: 0=random/omnidirectional, 1=directed/purposeful
volatile: 0=stable/permanent, 1=volatile/transient
gradual: 0=sudden/abrupt, 1=gradual/incremental
cyclic: 0=linear/one-time, 1=cyclic/recurring
growth: 0=shrinking/decreasing, 1=growing/increasing
continuous: 0=discrete/intermittent, 1=continuous/unbroken
reversible: 0=irreversible, 1=reversible
transformative: 0=preserving, 1=transformative/disruptive
temperature: 0=cold, 1=hot
brightness: 0=dark, 1=bright/luminous
loudness: 0=silent, 1=loud/noisy
hardness: 0=soft, 1=hard/rigid
wetness: 0=dry, 1=wet/moist
roughness: 0=smooth, 1=rough/coarse
sharpness: 0=blunt/dull, 1=sharp/pointed
fragility: 0=robust/durable, 1=fragile/delicate
transparency: 0=opaque, 1=transparent
flexibility: 0=rigid, 1=flexible/pliable
literal: 0=metaphorical, 1=literal/denotative
concrete: 0=abstract, 1=concrete/observable
positiveValence: 0=negative sentiment, 1=positive sentiment
formal: 0=informal/casual, 1=formal/official
technical: 0=colloquial, 1=technical/specialized
universal: 0=culture-specific, 1=universal
precise: 0=vague, 1=precise/exact
objective: 0=subjective, 1=objective/factual
definite: 0=indefinite/uncertain, 1=definite/certain
essential: 0=incidental, 1=essential/fundamental
emotional: 0=neutral/cognitive, 1=emotional/affective
arousal: 0=calming, 1=arousing/exciting
social: 0=solitary/individual, 1=social/communal
familiar: 0=foreign/alien, 1=familiar/domestic
peaceful: 0=violent/aggressive, 1=peaceful/calm
pleasurable: 0=painful/unpleasant, 1=pleasurable/pleasant
fearful: 0=safe/reassuring, 1=fear-inducing/threatening
desirable: 0=aversive/unwanted, 1=desirable/wanted
trustworthy: 0=deceptive, 1=trustworthy/reliable
humble: 0=proud/arrogant, 1=humble/modest
nounLikelihood: 0=unlikely noun, 1=primarily noun
verbLikelihood: 0=unlikely verb, 1=primarily verb
adjectiveLikelihood: 0=unlikely adjective, 1=primarily adjective
adverbLikelihood: 0=unlikely adverb, 1=primarily adverb
properNoun: 0=common noun, 1=proper noun
countable: 0=uncountable/mass, 1=countable
transitive: 0=intransitive, 1=transitive
compound: 0=simple word, 1=compound/derived
borrowed: 0=native, 1=loanword/borrowed
onomatopoeic: 0=arbitrary, 1=onomatopoeic
natureDomain: relevance to nature/environment domain
scienceDomain: relevance to science/technology domain
artDomain: relevance to art/culture domain
socialDomain: relevance to social/interpersonal domain
economicDomain: relevance to economic/commercial domain
politicalDomain: relevance to political/governance domain
religiousDomain: relevance to religious/spiritual domain
activityDomain: relevance to sports/physical activity
foodDomain: relevance to food/nourishment domain
timeDomain: relevance to time/temporal concepts
childlike: 0=adult-associated, 1=child-associated
gendered: 0=gender-neutral, 1=gender-marked
taboo: 0=standard/safe, 1=taboo/offensive
humorous: 0=serious, 1=humorous/playful
archaic: 0=contemporary, 1=archaic/obsolete
slang: 0=standard, 1=slang/very informal
geographic: 0=neutral, 1=geographically specific
seasonal: 0=year-round, 1=seasonal/periodic
ritual: 0=mundane, 1=ritual/ceremonial
mythological: 0=factual, 1=mythological/legendary
partOf: 0=independent whole, 1=part/component of something
container: 0=non-container, 1=container/vessel
tool: 0=non-tool, 1=tool/instrument
agent: 0=patient/object acted upon, 1=agent/actor
location: 0=non-locational, 1=location/place
process: 0=static state/object, 1=process/event
causal: 0=consequential, 1=causal/initiating
relational: 0=standalone concept, 1=relational/connective
edible: 0=inedible, 1=edible/consumable
domestic: 0=wild/external, 1=domestic/household
reasoning: 0=instinctive/automatic, 1=deliberate/logical
conscious: 0=unconscious/automatic, 1=conscious/self-aware
intentional: 0=accidental/unintended, 1=intentional/purposeful
creative: 0=conventional/standard, 1=creative/novel
analytical: 0=holistic/intuitive, 1=analytical/systematic
learned: 0=innate/instinctive, 1=learned/acquired
memorable: 0=forgettable, 1=salient/memorable
cognitive: 0=purely physical/sensory, 1=cognitively demanding
reflective: 0=impulsive/reactive, 1=reflective/contemplative
imaginative: 0=realistic/literal-minded, 1=imaginative/fantastical
dimensionality: 0=one-dimensional/linear, 1=three-dimensional/volumetric
symmetry: 0=asymmetric/irregular, 1=symmetric/regular
enclosed: 0=open/exposed, 1=enclosed/contained
elevated: 0=low/ground-level, 1=high/elevated
centered: 0=peripheral/marginal, 1=central/focal
bounded: 0=unbounded/infinite extent, 1=bounded/finite
oriented: 0=omnidirectional, 1=directionally oriented
proximate: 0=distant/far, 1=near/proximate
vertical: 0=horizontal/flat, 1=vertical/upright
curved: 0=straight/angular, 1=curved/rounded
duration: 0=momentary/fleeting, 1=long-lasting/enduring
instantaneous: 0=extended/prolonged, 1=instantaneous/immediate
scheduled: 0=spontaneous/unscheduled, 1=scheduled/planned
simultaneous: 0=sequential/one-at-a-time, 1=simultaneous/concurrent
futureOriented: 0=past-oriented/backward-looking, 1=future-oriented
presentDay: 0=timeless/eternal, 1=present-day/contemporary
urgency: 0=leisurely/unhurried, 1=urgent/time-critical
sequential: 0=parallel/concurrent, 1=sequential/step-by-step
epochal: 0=ordinary/routine, 1=epochal/historically significant
rhythmic: 0=arrhythmic/irregular, 1=rhythmic/metered
metallic: 0=non-metallic, 1=metallic
crystalline: 0=amorphous/disordered, 1=crystalline/lattice-structured
fluid: 0=solid/rigid state, 1=fluid/liquid or gaseous
porous: 0=impermeable/solid, 1=porous/permeable
elastic: 0=inelastic/brittle, 1=elastic/springy
combustible: 0=fireproof/incombustible, 1=flammable/combustible
conductive: 0=insulating/non-conductive, 1=conductive
magnetic: 0=non-magnetic, 1=magnetic
soluble: 0=insoluble, 1=soluble/dissolvable
synthetic: 0=natural/organic origin, 1=synthetic/manufactured
cellular: 0=non-cellular/abiotic, 1=cellular/biological unit
reproductive: 0=non-reproductive/sterile, 1=reproductive/generative
parasitic: 0=mutualistic/symbiotic, 1=parasitic/exploitative
predatory: 0=prey/passive organism, 1=predatory/hunter
migratory: 0=sedentary/stationary, 1=migratory/seasonally mobile
aquatic: 0=terrestrial/land-based, 1=aquatic/water-dwelling
nocturnal: 0=diurnal/daytime-active, 1=nocturnal/night-active
domesticatedAnimal: 0=wild/feral, 1=domesticated/tamed animal
endangered: 0=abundant/thriving species, 1=rare/endangered
venomous: 0=harmless/non-toxic, 1=venomous/toxic
digital: 0=analog/physical medium, 1=digital/virtual
automated: 0=manual/handmade, 1=automated/mechanized
networked: 0=standalone/isolated, 1=networked/connected
programmable: 0=fixed/hardwired, 1=programmable/configurable
interactive: 0=passive/static, 1=interactive/responsive
scalable: 0=fixed-capacity/limited, 1=scalable/expandable
realtime: 0=delayed/asynchronous, 1=real-time/synchronous
encrypted: 0=plaintext/unprotected, 1=encrypted/secured
portable: 0=fixed/stationary device, 1=portable/mobile
renewable: 0=depleting/finite resource, 1=renewable/self-replenishing
tradable: 0=non-tradable/personal, 1=tradable/marketable
scarce: 0=abundant/plentiful, 1=scarce/rare commodity
valuable: 0=worthless/low-value, 1=highly valuable/precious
productive: 0=unproductive/wasteful, 1=productive/efficient
profitable: 0=loss-making/costly, 1=profitable/lucrative
competitive: 0=monopolistic/unique offering, 1=competitive/contested market
investable: 0=consumable/expensed, 1=investable/capital asset
liquid: 0=illiquid/locked asset, 1=liquid/easily exchangeable
regulated: 0=unregulated/free market, 1=heavily regulated/controlled
taxable: 0=tax-exempt/untaxed, 1=taxable/assessed
democratic: 0=authoritarian/top-down, 1=democratic/participatory
centralized: 0=decentralized/distributed, 1=centralized/unified
sovereign: 0=subject/subordinate entity, 1=sovereign/independent
publicAccess: 0=private/exclusive, 1=public/open to all
legislative: 0=executive/administrative, 1=legislative/rule-making
enforceable: 0=advisory/optional, 1=enforceable/mandatory
elected: 0=appointed/hereditary, 1=elected/chosen by vote
representative: 0=direct/unmediated, 1=representative/delegated
constitutional: 0=arbitrary/discretionary, 1=constitutional/rule-bound
ideological: 0=pragmatic/neutral, 1=ideologically driven
verbal: 0=non-verbal/gestural, 1=verbal/spoken
written: 0=oral/unwritten tradition, 1=written/textual
polysemous: 0=monosemous/single meaning, 1=polysemous/multiple meanings
euphemistic: 0=direct/blunt expression, 1=euphemistic/softened
persuasive: 0=informational/neutral, 1=persuasive/rhetorical
narratorial: 0=expository/descriptive mode, 1=narrative/story-based
interrogative: 0=declarative/stating, 1=interrogative/questioning
imperative: 0=suggestive/optional, 1=imperative/commanding
performative: 0=descriptive/representational, 1=performative/enactive
idiomatic: 0=compositional/transparent meaning, 1=idiomatic/non-compositional
beautiful: 0=ugly/unaesthetic, 1=beautiful/attractive
ornate: 0=plain/minimalist, 1=ornate/heavily decorated
harmonious: 0=discordant/jarring, 1=harmonious/consonant
expressive: 0=restrained/contained, 1=expressive/emotive
stylized: 0=naturalistic/realistic, 1=stylized/abstracted
refined: 0=crude/raw, 1=refined/polished
innovative: 0=traditional/conventional, 1=innovative/avant-garde
dramatic: 0=understated/subtle, 1=dramatic/theatrical
elegant: 0=clumsy/awkward, 1=elegant/graceful
sublime: 0=ordinary/mundane in impact, 1=sublime/transcendent
quantifiable: 0=unquantifiable/qualitative, 1=quantifiable/measurable
linearScale: 0=non-linear/complex relationship, 1=linear/proportional
recursive: 0=finite/non-recursive, 1=recursive/self-referential
deterministic: 0=stochastic/random outcome, 1=deterministic/predictable
infinite: 0=finite/bounded quantity, 1=infinite/unbounded
cardinal: 0=ordinal/relative, 1=cardinal/absolute count
binary: 0=continuous/graduated, 1=binary/either-or
algebraic: 0=geometric/spatial, 1=algebraic/symbolic
convergent: 0=divergent/spreading, 1=convergent/narrowing
axiomatic: 0=empirical/informal, 1=axiomatic/proof-based
ecosystemic: 0=isolated/independent organism, 1=deeply embedded in ecosystem
invasive: 0=native/indigenous species, 1=invasive/introduced
polluting: 0=clean/purifying, 1=polluting/contaminating
biodegradable: 0=persistent/non-degradable, 1=biodegradable/decomposing
endemic: 0=cosmopolitan/widespread, 1=endemic/locally specific
sustainable: 0=depleting/unsustainable, 1=sustainable/regenerative
weathered: 0=protected/sheltered, 1=weathered/exposed to elements
riparian: 0=inland/terrestrial, 1=water-adjacent/riparian
forested: 0=open/barren landscape, 1=forested/wooded
climatic: 0=climate-independent, 1=climate-dependent/sensitive
authoritative: 0=subordinate/submissive, 1=authoritative/dominant
hierarchical: 0=egalitarian/flat structure, 1=hierarchical/ranked
prestigious: 0=low-status/stigmatized, 1=high-status/prestigious
exclusive: 0=inclusive/open, 1=exclusive/restricted access
privileged: 0=underprivileged/marginalized, 1=privileged/advantaged
influential: 0=powerless/insignificant, 1=influential/powerful
institutional: 0=informal/personal, 1=institutional/official
collective: 0=individual/personal, 1=collective/group-based
rivalrous: 0=cooperative/collaborative, 1=rivalrous/competitive
inherited: 0=achieved/earned status, 1=inherited/ascribed
moral: 0=immoral/unethical, 1=moral/virtuous
honest: 0=deceptive/dishonest, 1=honest/truthful
just: 0=unjust/unfair, 1=just/equitable
compassionate: 0=indifferent/callous, 1=compassionate/caring
courageous: 0=cowardly/timid, 1=courageous/brave
responsible: 0=irresponsible/negligent, 1=responsible/accountable
altruistic: 0=selfish/self-serving, 1=altruistic/generous
principled: 0=opportunistic/unprincipled, 1=principled/consistent
merciful: 0=harsh/punitive, 1=merciful/forgiving
virtuous: 0=vicious/corrupt, 1=virtuous/morally excellent
educational: 0=non-educational/trivial, 1=educational/instructive
specialized: 0=general/common knowledge, 1=highly specialized/expert
empirical: 0=theoretical/speculative, 1=empirically grounded
documented: 0=undocumented/oral tradition, 1=documented/recorded
teachable: 0=intuitive/unteachable skill, 1=teachable/transferable
experiential: 0=book-learned/theoretical, 1=experiential/hands-on
academic: 0=practical/applied, 1=academic/theoretical
interdisciplinary: 0=narrowly disciplinary, 1=interdisciplinary/cross-domain
foundational: 0=peripheral/derivative knowledge, 1=foundational/prerequisite
cumulative: 0=standalone knowledge, 1=builds on prior knowledge
structural: 0=decorative/non-structural, 1=load-bearing/structural
mechanical: 0=non-mechanical/biological, 1=mechanical/engineered
modular: 0=monolithic/integrated, 1=modular/component-based
articulated: 0=rigid/fixed joint, 1=articulated/jointed
reinforced: 0=unprotected/bare, 1=reinforced/strengthened
balanced: 0=unbalanced/lopsided, 1=balanced/stable
assembled: 0=grown/organic formation, 1=assembled/constructed
repairable: 0=disposable/throwaway, 1=repairable/maintainable
lubricated: 0=friction-heavy/dry mechanism, 1=lubricated/smooth-running
interlocking: 0=independent/non-fitting parts, 1=interlocking/fitted together
acidic: 0=alkaline/basic, 1=acidic
oxidizing: 0=reducing/anti-oxidizing, 1=oxidizing/reactive with oxygen
evaporative: 0=non-volatile/stable at room temp, 1=volatile/evaporating
reactive: 0=inert/chemically stable, 1=chemically reactive
radioactive: 0=stable/non-radioactive, 1=radioactive
ionized: 0=electrically neutral, 1=ionized/charged
crystallized: 0=amorphous solid, 1=crystallized/structured
polymerized: 0=monomeric/simple molecule, 1=polymerized/complex chain
catalytic: 0=inert/non-catalytic, 1=catalytic/accelerating reactions
saturated: 0=unsaturated/reactive bonds, 1=saturated/stable bonds
visible: 0=invisible/hidden, 1=visible/observable
audible: 0=silent/inaudible, 1=audible/hearable
tactile: 0=untouchable/intangible, 1=tactile/touchable
olfactory: 0=odorless/scent-free, 1=strongly olfactory/scented
gustatory: 0=tasteless/bland, 1=flavorful/gustatory
kinesthetic: 0=still/passive, 1=involving body movement/kinesthetic
vestibular: 0=balance-stable/grounded, 1=disorienting/vertiginous
thermal: 0=thermally neutral, 1=thermally distinct/extreme
painful: 0=painless/comfortable, 1=painful/nociceptive
fragrant: 0=unpleasant/malodorous, 1=fragrant/pleasant-smelling
nutritious: 0=non-nutritious/empty calories, 1=nutritious/nourishing
caloric: 0=low-calorie/light, 1=high-calorie/energy-dense
proteinRich: 0=protein-poor, 1=protein-rich
fermented: 0=fresh/unfermented, 1=fermented/aged
cooked: 0=raw/unprocessed, 1=cooked/heat-prepared
spicy: 0=mild/bland in heat, 1=spicy/pungent
sweet: 0=savory/salty, 1=sweet/sugary
perishable: 0=shelf-stable/long-lasting, 1=perishable/short shelf life
culinary: 0=raw ingredient, 1=culinary preparation/dish
processed: 0=whole/minimally processed, 1=highly processed/refined
pathogenic: 0=beneficial/probiotic, 1=pathogenic/disease-causing
therapeutic: 0=harmful/detrimental to health, 1=therapeutic/healing
chronic: 0=acute/sudden onset, 1=chronic/long-term
systemic: 0=local/topical effect, 1=systemic/body-wide
contagious: 0=non-contagious/non-spreading, 1=contagious/infectious
symptomatic: 0=asymptomatic/silent, 1=symptomatic/obvious signs
surgical: 0=non-invasive/conservative, 1=surgical/invasive
preventive: 0=reactive/curative only, 1=preventive/prophylactic
rehabilitative: 0=acute treatment only, 1=rehabilitative/restorative
palliative: 0=curative/restorative aim, 1=palliative/comfort-focused
inhabitable: 0=uninhabitable/hostile space, 1=inhabitable/livable
supportive: 0=decorative/non-structural, 1=supportive/load-bearing
ornamental: 0=utilitarian/plain, 1=ornamental/decorative
accessible: 0=inaccessible/exclusive space, 1=accessible/open
insulated: 0=exposed/uninsulated, 1=thermally insulated
fireproof: 0=combustible/fire-prone, 1=fireproof/fire-resistant
weatherproof: 0=porous/leaky, 1=weatherproof/sealed
multistory: 0=single-level/ground floor, 1=multi-story/vertical
monumental: 0=modest/small-scale, 1=monumental/grand
civic: 0=private/residential, 1=civic/public function
motorized: 0=non-motorized/manual, 1=motorized/engine-powered
airborne: 0=ground-based, 1=airborne/flying
seafaring: 0=land-based, 1=seafaring/nautical
passenger: 0=cargo/freight transport, 1=passenger/people-carrying
selfPropelled: 0=externally propelled/pushed, 1=self-propelled/autonomous
railTracked: 0=wheeled/unguided, 1=rail-tracked/guided
commuter: 0=long-distance/intercity, 1=commuter/local transport
fuelBurning: 0=electric/non-combustion, 1=fuel-burning/combustion engine
expeditionary: 0=local/short-range, 1=long-range/expeditionary
sharedTransport: 0=private/personal vehicle, 1=shared/public transport
broadcast: 0=point-to-point/private, 1=broadcast/one-to-many
multicast: 0=unicast/one-to-one, 1=multicast/one-to-many
printed: 0=digital/screen-based medium, 1=printed/physical medium
recorded: 0=live/ephemeral, 1=recorded/archived
censored: 0=uncensored/free expression, 1=censored/restricted content
viral: 0=niche/limited reach, 1=viral/widely shared
participatory: 0=passive/consumable media, 1=participatory/interactive
credible: 0=unreliable/dubious source, 1=credible/trustworthy source
sensational: 0=measured/factual reporting, 1=sensational/dramatic
serialized: 0=standalone/one-shot, 1=serialized/episodic
sacred: 0=profane/secular, 1=sacred/holy
transcendent: 0=immanent/worldly, 1=transcendent/otherworldly
dogmatic: 0=open/pluralistic, 1=dogmatic/orthodox
mystical: 0=rational/logical, 1=mystical/ineffable
devotional: 0=casual/incidental, 1=devotional/worship-oriented
eschatological: 0=present-focused/immanent, 1=eschatological/afterlife-focused
prophetic: 0=retrospective/historical, 1=prophetic/forward-looking
congregational: 0=solitary/individual practice, 1=congregational/communal worship
canonical: 0=apocryphal/non-canonical, 1=canonical/scriptural
intercessory: 0=direct/unmediated access, 1=mediated/intercessory
folkloric: 0=historical/documented, 1=folkloric/traditional tale
supernatural: 0=natural/mundane, 1=supernatural/magical
totemic: 0=non-symbolic/literal object, 1=totemic/symbolically charged
heroic: 0=ordinary/common, 1=heroic/exceptional
trickster: 0=straightforward/honest, 1=trickster/deceptive-playful
cosmogonic: 0=local/temporal, 1=cosmogonic/creation-related
chthonic: 0=celestial/sky-related, 1=chthonic/underworld-related
animistic: 0=inanimate/spiritless, 1=animistic/spirit-inhabited
oracular: 0=mundane/ordinary, 1=oracular/prophetic
liminal: 0=fixed/stable state, 1=liminal/transitional threshold
coastal: 0=inland/landlocked, 1=coastal/littoral
mountainous: 0=flat/lowland, 1=mountainous/highland
tropical: 0=polar/arctic climate, 1=tropical/equatorial
arid: 0=humid/wet, 1=arid/desert-like
subterranean: 0=surface/above-ground, 1=subterranean/underground
volcanic: 0=tectonically stable, 1=volcanic/seismically active
glacial: 0=warm/non-glacial, 1=glacial/ice-covered
fluvial: 0=non-riverine, 1=river/fluvial system
peninsular: 0=continental/inland, 1=peninsular/island
deltaic: 0=upland/source area, 1=deltaic/estuarine
stellar: 0=planetary/substellar, 1=stellar/star-like
galactic: 0=local/small-scale, 1=galactic/cosmic-scale
orbital: 0=free-floating, 1=orbital/in regular orbit
radiant: 0=absorbing/dark body, 1=radiant/emitting energy
gravitational: 0=low-gravity/lightweight, 1=high-gravity/massive
nebular: 0=solid/compact body, 1=nebular/diffuse cloud
paired: 0=solitary/single body, 1=paired/binary system
primordial: 0=recently formed, 1=primordial/ancient cosmic
expansive: 0=contracting/imploding, 1=expansive/growing
eccentric: 0=circular orbit, 1=highly elliptical/eccentric orbit
melodic: 0=atonal/non-melodic, 1=melodic/tuneful
percussive: 0=sustained/held tone, 1=percussive/struck
harmonic: 0=dissonant/clashing, 1=harmonic/consonant
tonal: 0=atonal/pantonal, 1=tonal/key-centered
lyrical: 0=instrumental/wordless, 1=lyrical/vocal
improvised: 0=composed/notated, 1=improvised/spontaneous
amplified: 0=acoustic/unamplified, 1=electrically amplified
tempo: 0=slow/largo tempo, 1=fast/presto tempo
polyphonic: 0=monophonic/single voice, 1=polyphonic/multi-voice
danceable: 0=concert/seated listening, 1=dance/movement-inducing
chromatic: 0=achromatic/grayscale, 1=chromatic/colorful
warmColor: 0=cool/blue-toned, 1=warm/red-yellow-toned
vibrant: 0=muted/dull, 1=vibrant/vivid
contrasting: 0=low contrast/blended, 1=high contrast/stark
patterned: 0=uniform/solid, 1=patterned/textured
monochromatic: 0=multi-colored, 1=monochromatic/single-hue
luminescent: 0=non-luminescent, 1=luminescent/glowing
pictorial: 0=abstract/non-representational, 1=pictorial/figurative
panoramic: 0=close-up/limited view, 1=panoramic/wide-angle
kaleidoscopic: 0=simple/uniform, 1=kaleidoscopic/complex varied
grippy: 0=slippery/frictionless, 1=grippy/high-friction
cushioned: 0=hard/unpadded, 1=cushioned/padded
viscous: 0=fluid/thin, 1=viscous/thick/sticky
granular: 0=smooth/fine-grained, 1=granular/gritty
springy: 0=inelastic/dead feel, 1=springy/resilient
clinging: 0=non-adhesive/releasing, 1=clinging/adhesive
prickly: 0=smooth/harmless to touch, 1=prickly/sharp to touch
velvety: 0=rough/coarse texture, 1=velvety/ultra-smooth
slick: 0=rough/matte surface, 1=slick/polished surface
fibrous: 0=non-fibrous/uniform, 1=fibrous/stringy
fragrantSmell: 0=odorless, 1=strongly fragrant/perfumed
putrid: 0=fresh/clean scent, 1=putrid/rotting smell
savory: 0=sweet/sugary taste, 1=savory/umami
bitter: 0=sweet/mild taste, 1=bitter/acrid
sour: 0=neutral/non-acidic taste, 1=sour/acidic
salty: 0=unsalted/bland, 1=salty/briny
umami: 0=non-savory/plain, 1=umami/meaty/savory-rich
aromatic: 0=inodorous/scent-free, 1=aromatic/spiced
astringent: 0=non-astringent, 1=astringent/drying in mouth
pungent: 0=mild/subtle, 1=pungent/sharp/strong-smelling
nostalgic: 0=future-looking, 1=nostalgic/past-evoking
melancholic: 0=cheerful/upbeat, 1=melancholic/wistful
euphoric: 0=subdued/muted feeling, 1=euphoric/elated
anxious: 0=calm/composed, 1=anxiety-inducing/tense
cathartic: 0=emotionally neutral, 1=cathartic/releasing
empathic: 0=alienating/cold, 1=empathy-inducing/warm
awesome: 0=unremarkable/ordinary, 1=awe-inspiring/magnificent
grief: 0=joyful/celebratory, 1=grief/sorrow-related
longing: 0=satisfied/complete, 1=longing/yearning
whimsical: 0=serious/grave, 1=whimsical/fanciful
kinship: 0=non-kinship/stranger, 1=kinship/family-related
friendly: 0=adversarial/hostile, 1=friendly/amicable
romantic: 0=platonic/non-romantic, 1=romantic/intimate
professional: 0=personal/private relationship, 1=professional/work-related
mentoring: 0=peer/equal relationship, 1=mentor/guide relationship
neighborly: 0=isolated/anonymous, 1=neighborly/community-oriented
transactional: 0=relational/emotional bond, 1=transactional/exchange-based
deferential: 0=assertive/equal standing, 1=deferential/respectful-upward
reciprocal: 0=one-way/unilateral, 1=reciprocal/mutual
intimate: 0=distant/formal, 1=intimate/close
barter: 0=monetary/abstract value, 1=barter/direct exchange
gifted: 0=purchased/transactional, 1=gifted/freely given
leased: 0=owned outright, 1=leased/rented/borrowed
subsidized: 0=market-priced/full cost, 1=subsidized/artificially cheap
speculative: 0=stable/non-speculative, 1=speculative/uncertain value
fungible: 0=unique/non-fungible, 1=fungible/interchangeable
reusable: 0=single-use/disposable, 1=reusable/long-lasting
wholesale: 0=retail/consumer-scale, 1=wholesale/bulk
imported: 0=locally produced, 1=imported/foreign
branded: 0=generic/unbranded, 1=branded/trademarked
binding: 0=non-binding/advisory, 1=legally binding/obligatory
patented: 0=public domain/unprotected, 1=patented/IP-protected
licensed: 0=unregulated/free use, 1=licensed/regulated
criminal: 0=legal/permitted, 1=criminal/prohibited
contractual: 0=informal/handshake agreement, 1=contractual/formally agreed
jurisdictional: 0=universal/borderless, 1=jurisdictionally specific
precedential: 0=novel/unprecedented, 1=precedential/case-law based
punitive: 0=rehabilitative/restorative, 1=punitive/penalty-based
remedial: 0=punitive/preventive, 1=remedial/corrective
statutory: 0=common law/customary, 1=statutory/codified
respiratory: 0=non-respiratory, 1=respiratory/breathing-related
digestive: 0=non-digestive, 1=digestive/metabolic
circulatory: 0=non-circulatory, 1=circulatory/blood-flow
neural: 0=non-neural, 1=neural/nerve-related
hormonal: 0=non-hormonal, 1=hormonal/endocrine
immune: 0=non-immune, 1=immune/defensive response
muscular: 0=non-muscular/passive, 1=muscular/movement-producing
skeletal: 0=soft-bodied/no skeleton, 1=skeletal/bone-related
perceptory: 0=non-sensory, 1=sensory organ/perception-related
excretory: 0=absorbing/retaining, 1=excretory/waste-eliminating
infantile: 0=mature/adult, 1=infantile/neonatal
juvenile: 0=adult/mature, 1=juvenile/young
adolescent: 0=childhood/adulthood stage, 1=adolescent/transitional
mature: 0=immature/developing, 1=mature/fully developed
geriatric: 0=young/early-stage, 1=geriatric/late-stage
larval: 0=adult form/post-metamorphic, 1=larval/immature stage
embryonic: 0=post-natal/developed, 1=embryonic/earliest stage
pubescent: 0=pre-/post-pubescent, 1=pubescent/sexually maturing
senescent: 0=youthful/vigorous, 1=senescent/aging/declining
posthumous: 0=living/current, 1=posthumous/after death
primitive: 0=derived/evolved form, 1=primitive/ancestral form
vestigial: 0=functional/active trait, 1=vestigial/remnant
analogous: 0=homologous/shared ancestry, 1=analogous/independently evolved
relict: 0=widespread/common, 1=relict/surviving remnant
specialist: 0=generalist/flexible, 1=specialist/narrow adaptation
adaptive: 0=non-adaptive/fixed, 1=highly adaptive/plastic
symbiotic: 0=independent/asocial, 1=symbiotic/mutualistic
apex: 0=prey/lower trophic level, 1=apex/top of food chain
cryptic: 0=conspicuous/visible, 1=cryptic/camouflaged
colonizing: 0=established/endemic, 1=colonizing/pioneering
compressible: 0=incompressible/maximal entropy, 1=compressible/reducible
searchable: 0=unsearchable/opaque, 1=searchable/indexed
schematic: 0=unstructured/freeform, 1=structured/schematic
queryable: 0=static/non-queryable, 1=queryable/dynamic
versioned: 0=unversioned/single-state, 1=versioned/tracked history
distributed: 0=centralized/local, 1=distributed/decentralized
cached: 0=computed on demand, 1=cached/precomputed
streamed: 0=batch/all-at-once, 1=streamed/continuous flow
mutable: 0=immutable/read-only, 1=mutable/changeable
probabilistic: 0=deterministic/exact, 1=probabilistic/uncertain
ontological: 0=conventional/nominal, 1=ontologically real/substantial
phenomenal: 0=noumenal/thing-in-itself, 1=phenomenal/as-experienced
contingent: 0=necessary/inevitable, 1=contingent/could-be-otherwise
emergent: 0=reducible/simple, 1=emergent/arising from complexity
teleological: 0=non-purposive/blind process, 1=teleological/goal-directed
dialectical: 0=static/fixed, 1=dialectical/containing opposites
reductive: 0=holistic/irreducible, 1=reductive/explained by parts
normative: 0=descriptive/value-neutral, 1=normative/prescriptive
epistemic: 0=empirical/sense-based, 1=epistemic/knowledge-theory focus
existential: 0=trivial/superficial, 1=existentially significant
apprehensive: 0=confident/assured, 1=apprehensive/worried
depressive: 0=elated/manic, 1=depressive/sad/low
obsessive: 0=flexible/indifferent, 1=obsessive/compulsive
phobic: 0=comfortable/neutral, 1=phobia-related/fear trigger
manic: 0=depressed/low energy, 1=manic/high energy
dissociative: 0=grounded/present, 1=dissociative/detached
paranoid: 0=trusting/open, 1=paranoid/suspicious
narcissistic: 0=self-effacing/humble, 1=narcissistic/self-centered
prosocial: 0=antisocial/withdrawn, 1=prosocial/other-focused
resilient: 0=fragile/easily overwhelmed, 1=resilient/stress-resistant
habitual: 0=novel/unprecedented, 1=habitual/routine
impulsive: 0=deliberate/planned, 1=impulsive/spontaneous
compulsive: 0=voluntary/chosen, 1=compulsive/driven
ritualistic: 0=improvised/flexible, 1=ritualistic/formulaic
addictive: 0=non-addictive, 1=addictive/habit-forming
conformist: 0=non-conformist/deviant, 1=conformist/rule-following
riskTaking: 0=risk-averse/cautious, 1=risk-taking/bold
tenacious: 0=giving up easily, 1=tenacious/persistent
submissive: 0=dominant/assertive, 1=submissive/yielding
versatile: 0=rigid/fixed behavior, 1=versatile/adaptable
protagonist: 0=antagonist/villain, 1=protagonist/hero
tragic: 0=comedic/uplifting ending, 1=tragic/sorrowful ending
comic: 0=serious/humorless, 1=comic/funny
epic: 0=small-scale/intimate, 1=epic/grand scope
didactic: 0=entertaining/pure pleasure, 1=didactic/lesson-teaching
suspenseful: 0=predictable/unsurprising, 1=suspenseful/tension-filled
satirical: 0=sincere/earnest, 1=satirical/ironic
sentimental: 0=unsentimental/dry, 1=sentimental/emotionally warm
mythic: 0=realistic/grounded, 1=mythic/archetypal
allegorical: 0=literal/surface-level, 1=allegorical/symbolic meaning
athletic: 0=sedentary/non-physical, 1=athletic/physically demanding
strategic: 0=luck-based/random, 1=strategic/skill-based
teamBased: 0=individual/solo, 1=team-based/cooperative
scored: 0=unscored/non-competitive, 1=scored/competitive
timed: 0=untimed/open-ended, 1=timed/time-limited
recreational: 0=elite/professional, 1=recreational/casual
indoor: 0=outdoor/open air, 1=indoor/enclosed
contact: 0=non-contact/safe, 1=contact/physical
endurance: 0=sprint/explosive, 1=endurance/long-duration
gambling: 0=non-gambling, 1=gambling/wagering element
fashionable: 0=unfashionable/dated, 1=fashionable/trendy
wearable: 0=impractical/decorative, 1=practical/wearable
modest: 0=revealing/exposed, 1=modest/covered
luxurious: 0=basic/budget, 1=luxurious/premium
fitted: 0=loose/oversized, 1=form-fitting/tailored
embellished: 0=plain/unadorned, 1=embellished/decorated
vintage: 0=modern/contemporary fashion, 1=vintage/retro-styled
androgynous: 0=strongly gendered, 1=gender-neutral/androgynous
dressy: 0=casual/everyday attire, 1=formal/dressy attire
iconic: 0=generic/nondescript, 1=iconic/recognizable
farmed: 0=wild/uncultivated, 1=farmed/cultivated
harvested: 0=perennial/unharvested, 1=seasonally harvested
irrigated: 0=rain-fed/dryland, 1=irrigated/water-managed
grazed: 0=forested/non-pastoral, 1=grazed/pastoral
fertilized: 0=organic/unfertilized, 1=fertilized/enriched
pesticideTreated: 0=organic/pesticide-free, 1=pesticide-treated
monoculture: 0=polyculture/diverse, 1=monoculture/single-crop
industrialFarmed: 0=artisanal/small-scale, 1=industrial/large-scale farming
heirloom: 0=hybrid/modern variety, 1=heirloom/traditional variety
grafted: 0=seed-grown/natural, 1=grafted/propagated
metropolitan: 0=rural/provincial, 1=metropolitan/cosmopolitan
gentrified: 0=working-class/traditional, 1=gentrified/upscale
mixedUse: 0=single-use/zoned, 1=mixed-use/diverse
transitOriented: 0=car-dependent, 1=transit-oriented/walkable
highRise: 0=low-rise/horizontal, 1=high-rise/vertical
commercialArea: 0=residential/non-commercial, 1=commercial/business
pedestrian: 0=vehicular/car-centric, 1=pedestrian/foot-friendly
nightlife: 0=quiet/residential, 1=vibrant nightlife/entertainment
multicultural: 0=monocultural/homogeneous, 1=multicultural/diverse
plannedCity: 0=organic/unplanned growth, 1=planned/designed city
marine: 0=terrestrial/land, 1=marine/ocean-based
tidal: 0=non-tidal/inland, 1=tidal/influenced by tides
deepSea: 0=shallow/littoral, 1=deep-sea/abyssal
brackish: 0=freshwater/saltwater extreme, 1=brackish/mixed-salinity
buoyant: 0=sinking/dense, 1=buoyant/floating
nautical: 0=non-nautical, 1=nautical/seafaring
pelagic: 0=benthic/bottom-dwelling, 1=pelagic/open-water
estuarine: 0=open-ocean, 1=estuarine/delta/river-mouth
coralReef: 0=non-reef/open water, 1=coral reef/reef-associated
anadromous: 0=purely marine or freshwater, 1=migrating between fresh/salt water
offensive: 0=defensive/protective, 1=offensive/attacking
lethal: 0=non-lethal/incapacitating, 1=lethal/deadly
covert: 0=overt/open, 1=covert/clandestine
tactical: 0=strategic/long-range planning, 1=tactical/immediate action
armored: 0=unarmored/vulnerable, 1=armored/protected
ranged: 0=melee/close-combat, 1=ranged/long-distance combat
explosive: 0=non-explosive, 1=explosive/blast-based
psychological: 0=physical/kinetic, 1=psychological/information warfare
mobilized: 0=static/fortified, 1=mobile/maneuver-based
disciplined: 0=chaotic/unorganized, 1=disciplined/structured
experimental: 0=observational/theoretical, 1=experimental/interventional
reproducible: 0=unreproducible/one-off, 1=reproducible/repeatable
falsifiable: 0=unfalsifiable, 1=falsifiable/testable
quantitative: 0=qualitative/descriptive, 1=quantitative/numerical
longitudinal: 0=cross-sectional/snapshot, 1=longitudinal/over-time
controlled: 0=uncontrolled/naturalistic, 1=controlled/laboratory
peerReviewed: 0=anecdotal/unrefereed, 1=peer-reviewed/validated
translational: 0=basic/pure research, 1=translational/applied
hypothesisDriven: 0=exploratory/descriptive, 1=hypothesis-driven
replicable: 0=unique/non-replicable, 1=replicable/standard method
ceremonial: 0=informal/casual practice, 1=ceremonial/formal rite
indigenous: 0=global/universal practice, 1=indigenous/culture-specific
transmitted: 0=invented/modern, 1=transmitted/culturally inherited
communalRite: 0=private/individual practice, 1=communal/shared rite
medicinal: 0=recreational/non-therapeutic, 1=medicinal/healing practice
forbidden: 0=permitted/culturally approved, 1=forbidden/taboo culturally
artisanal: 0=industrial/mass-produced, 1=artisanal/handcrafted
festive: 0=everyday/mundane, 1=festive/celebratory
mourning: 0=celebratory/joyful, 1=mourning/grief-related
initiatory: 0=ongoing/non-transitional, 1=initiatory/rite of passage
disruptive: 0=incremental/evolutionary, 1=disruptive/revolutionary
pioneering: 0=follower/derivative, 1=pioneering/first-of-its-kind
prototypical: 0=mature/refined, 1=prototypical/early-stage
crossDisciplinary: 0=single-field/specialized, 1=cross-disciplinary
patentable: 0=obvious/prior-art, 1=novel/patentable
replicatable: 0=one-off/artisanal, 1=replicatable/scalable
openSource: 0=proprietary/closed, 1=open/freely shared
iterative: 0=waterfall/big-bang, 1=iterative/agile
frugal: 0=resource-intensive, 1=frugal/minimal-resource
grassroots: 0=top-down/institutional, 1=grassroots/bottom-up
polluted: 0=pristine/clean, 1=polluted/contaminated
deforested: 0=forested/wooded, 1=deforested/cleared
desertified: 0=fertile/vegetated, 1=desertified/barren
overfished: 0=sustainably fished, 1=overfished/depleted
threatened: 0=abundant/secure, 1=threatened/at risk
carbonFootprint: 0=carbon-neutral/negative, 1=high carbon footprint
toxic: 0=non-toxic/benign, 1=toxic/hazardous
recycled: 0=virgin/new material, 1=recycled/reclaimed
regenerative: 0=extractive/depleting, 1=regenerative/restoring
climateVulnerable: 0=climate-stable, 1=climate-affected/vulnerable
medieval: 0=modern/contemporary, 1=medieval/pre-modern
colonial: 0=pre/post-colonial, 1=colonial-era/imperial
watershed: 0=ordinary/unremarkable, 1=watershed/turning-point event
prehistoric: 0=historical/recorded, 1=prehistoric/pre-literate
antique: 0=modern/new, 1=antique/very old
current: 0=historical/past, 1=current/present-day
nascent: 0=established/mature, 1=nascent/just emerging
recurrent: 0=unique/unprecedented, 1=recurrent/historically repeated
chronicled: 0=unrecorded/oral, 1=chronicled/documented
seminal: 0=derivative/minor, 1=seminal/highly influential
populous: 0=sparse/uninhabited, 1=populous/densely inhabited
heterogeneous: 0=homogeneous/uniform, 1=heterogeneous/diverse
aging: 0=youthful/young population, 1=aging/older demographic
migrant: 0=settled/native population, 1=migrant/mobile population
urbanized: 0=rural/agricultural, 1=urbanized/city-dwelling
literate: 0=illiterate/uneducated, 1=literate/educated
affluent: 0=impoverished/low-income, 1=affluent/wealthy
devout: 0=secular/irreligious, 1=devout/religious
nuclear: 0=extended/communal family, 1=nuclear/small family unit
diaspora: 0=indigenous/homeland, 1=diaspora/dispersed population
subatomic: 0=macroscopic/visible, 1=subatomic/quantum-scale
molecular: 0=bulk/macroscopic, 1=molecular/nanoscale
microscopic: 0=macroscopic/visible to eye, 1=microscopic/only under lens
latticed: 0=amorphous/disordered arrangement, 1=crystalline/lattice-structured
quantum: 0=classical/macroscopic behavior, 1=quantum/wave-particle
isotopic: 0=standard/common isotope, 1=isotopically distinct
proteinaceous: 0=non-protein, 1=protein/amino-acid-based
enzymatic: 0=non-enzymatic, 1=enzyme-mediated/catalytic
viralMicro: 0=non-viral, 1=viral/pathogenic microorganism
bacterial: 0=sterile/non-bacterial, 1=bacterial/prokaryotic
connected: 0=isolated/disconnected, 1=highly connected/networked
hub: 0=peripheral/leaf node, 1=hub/central node
redundant: 0=single-path/fragile, 1=redundant/robust
lowLatency: 0=high-latency/slow, 1=low-latency/fast
wireless: 0=wired/physical connection, 1=wireless/over-the-air
authenticated: 0=anonymous/unauthenticated, 1=authenticated/verified
decentralized: 0=centralized/single-point, 1=decentralized/distributed
protocol: 0=proprietary/closed protocol, 1=standardized/open protocol
bandwidth: 0=low-bandwidth/limited, 1=high-bandwidth/fast throughput
peerToPeer: 0=client-server/hierarchical, 1=peer-to-peer/equal
hazardous: 0=safe/benign, 1=hazardous/dangerous
monitored: 0=unmonitored/private, 1=monitored/surveilled
fortified: 0=unprotected/vulnerable, 1=fortified/hardened
stealthy: 0=visible/detectable, 1=stealthy/hidden
alarmed: 0=unalarmed/silent, 1=alarmed/alerting
insured: 0=uninsured/unprotected, 1=insured/covered
escapable: 0=inescapable/trapped, 1=escapable/exit available
injurious: 0=harmless, 1=injury-causing
surveilled: 0=private/unobserved, 1=under surveillance
failsafe: 0=no backup/single point of failure, 1=failsafe/backup
private: 0=public/open, 1=private/confidential
anonymous: 0=identified/traceable, 1=anonymous/untraceable
obfuscated: 0=transparent/clear, 1=obfuscated/hidden
secret: 0=disclosed/public, 1=secret/undisclosed
consensual: 0=non-consensual/forced, 1=consensual/agreed
personal: 0=shared/collective, 1=personal/individual
sensitive: 0=benign/non-sensitive, 1=sensitive/requiring protection
proprietary: 0=public domain, 1=proprietary/owned
redacted: 0=fully disclosed, 1=redacted/partially hidden
surveillanceFree: 0=under surveillance, 1=surveillance-free/private
reliable: 0=unreliable/inconsistent, 1=reliable/dependable
legitimate: 0=illegitimate/unauthorized, 1=legitimate/properly authorized
expertLevel: 0=novice/layperson, 1=expert/highly skilled
accountable: 0=unaccountable, 1=accountable/responsible
verifiable: 0=unverifiable/opaque, 1=verifiable/provable
mandated: 0=optional/voluntary, 1=mandated/required
endorsed: 0=unofficial/unendorsed, 1=officially endorsed
audited: 0=unaudited/unverified, 1=audited/checked
delegated: 0=self-directed, 1=delegated/authorized by higher authority
ratified: 0=informal/unratified, 1=ratified/formally approved
gourmet: 0=home cooking/peasant food, 1=haute cuisine/gourmet
agedFood: 0=fresh/new food, 1=aged/matured food
streetFood: 0=fine dining, 1=street food/casual
vegetarian: 0=omnivorous/carnivorous, 1=plant-based/vegetarian
exoticFood: 0=familiar/common ingredient, 1=exotic/unusual ingredient
preserved: 0=fresh/unprocessed, 1=preserved/long-shelf
fusion: 0=traditional/authentic cuisine, 1=fusion/cross-cultural
ritualFood: 0=everyday food, 1=ritual/ceremonial food
comfortFood: 0=sophisticated/challenging dish, 1=comforting/familiar food
rawFood: 0=cooked/processed, 1=raw/uncooked
utilitarian: 0=deontological/rule-based, 1=utilitarian/outcome-based
deontological: 0=consequentialist/outcome-focused, 1=deontological/duty-based
consequentialist: 0=non-consequentialist, 1=consequentialist/results-focused
hedonistic: 0=ascetic/pain-embracing, 1=hedonistic/pleasure-seeking
stoic: 0=epicurean/pleasure-seeking, 1=stoic/endurance-focused
nihilistic: 0=meaning-affirming, 1=nihilistic/meaning-denying
pragmaticEth: 0=idealistic/theoretical, 1=pragmatic/practical
relativistic: 0=absolutist/universal, 1=relativistic/context-dependent
absolutist: 0=relativistic/flexible, 1=absolutist/universal rules
pluralistic: 0=monistic/single-view, 1=pluralistic/multiple-view
biased: 0=unbiased/neutral, 1=biased/slanted
stereotyped: 0=individual/nuanced, 1=stereotyped/overgeneralized
prejudiced: 0=unprejudiced/fair, 1=prejudiced/discriminatory
idealized: 0=realistic/accurate, 1=idealized/unrealistically positive
projected: 0=internalized/self-attributed, 1=projected/attributed to others
rationalized: 0=accepted/honest, 1=rationalized/post-hoc justified
scapegoated: 0=self-attributed, 1=scapegoated/blame-shifted
fallacious: 0=sound/valid reasoning, 1=fallacious/invalid reasoning
heuristic: 0=systematic/methodical, 1=heuristic/rule-of-thumb
assumptive: 0=well-founded/verified, 1=assumptive/taken for granted
colloquial: 0=formal/standard, 1=colloquial/everyday
dialectal: 0=standard/accent-neutral, 1=dialectal/regional
jargon: 0=general/lay, 1=jargon/field-specific
pidgin: 0=established/monolingual, 1=pidgin/contact language
creolized: 0=pidgin/simplified, 1=creolized/nativized
standardized: 0=non-standard/dialectal, 1=standardized/prescriptive
elevatedRegister: 0=plain/vernacular, 1=elevated/high register
vulgar: 0=refined/polite, 1=vulgar/crude language
poetic: 0=prosaic/everyday language, 1=poetic/heightened language
rhetorical: 0=plain/straightforward, 1=rhetorical/oratorical
northward: 0=southward/equatorward, 1=northward/poleward
eastward: 0=westward, 1=eastward
upward: 0=downward/below, 1=upward/above
inward: 0=outward/external, 1=inward/internal
frontward: 0=backward/rearward, 1=frontward/forward
diagonal: 0=orthogonal/right-angle, 1=diagonal/oblique
circularPath: 0=straight/linear path, 1=circular/looping path
spiral: 0=straight/flat, 1=spiral/helical
radial: 0=tangential/peripheral, 1=radial/center-outward
tangential: 0=radial/center-outward, 1=tangential/peripheral
refractive: 0=non-refractive/straight, 1=refractive/bending light
lightReflective: 0=absorptive/non-reflective, 1=reflective/mirror-like
absorptive: 0=reflective/non-absorptive, 1=absorptive/light-absorbing
polarized: 0=unpolarized, 1=polarized/directional light
diffuse: 0=focused/concentrated, 1=diffuse/scattered light
focused: 0=diffuse/scattered, 1=focused/concentrated beam
coherent: 0=incoherent/mixed phase, 1=coherent/laser-like
spectral: 0=monochromatic/single-wavelength, 1=spectral/full spectrum
infrared: 0=visible/not infrared, 1=infrared/heat-emitting
ultraviolet: 0=visible/not ultraviolet, 1=ultraviolet/high-energy
highVoltage: 0=low-voltage, 1=high-voltage
highCurrent: 0=low-current, 1=high-current
resistive: 0=conductive/low-resistance, 1=resistive/high-resistance
capacitive: 0=non-capacitive, 1=capacitive/charge-storing
inductive: 0=non-inductive, 1=inductive/magnetic-field
oscillating: 0=steady/DC, 1=oscillating/AC
rectified: 0=AC/alternating, 1=rectified/DC-converted
semiconductive: 0=insulating/non-conducting, 1=semiconductive
piezoelectric: 0=non-piezoelectric, 1=piezoelectric/pressure-electric
thermoelectric: 0=non-thermoelectric, 1=thermoelectric/heat-to-electricity
sedimentary: 0=igneous/volcanic, 1=sedimentary/layered
igneous: 0=sedimentary/metamorphic, 1=igneous/volcanic
metamorphic: 0=original/unaltered, 1=metamorphic/transformed by heat/pressure
fossilized: 0=unfossilized/recent, 1=fossilized/preserved ancient
tectonic: 0=tectonically stable, 1=tectonic/plate-boundary
erosive: 0=resistant/uneroded, 1=erosive/weathered by elements
alluvial: 0=in-situ/bedrock, 1=alluvial/water-deposited
karst: 0=non-karst/solid, 1=karst/dissolution-shaped
permafrost: 0=temperate/non-frozen, 1=permafrost/permanently frozen
geothermal: 0=non-geothermal, 1=geothermal/earth-heat
humid: 0=dry/arid, 1=humid/moist
windy: 0=calm/still air, 1=windy/breezy
stormy: 0=calm/fair weather, 1=stormy/turbulent
foggy: 0=clear/visibility high, 1=foggy/misty
hazy: 0=clear/clean air, 1=hazy/smoggy
precipitating: 0=dry/no precipitation, 1=precipitating/rainy/snowy
frontal: 0=local/non-frontal, 1=frontal/weather-front
convective: 0=stratiform/layered, 1=convective/updraft
stratified: 0=well-mixed, 1=stratified/layered atmosphere
droughtProne: 0=reliably rainy, 1=drought-prone/arid
freshwater: 0=saline/marine, 1=freshwater
saline: 0=freshwater/non-saline, 1=saline/salt-containing
stagnant: 0=flowing/dynamic, 1=stagnant/still
turbulent: 0=calm/still water, 1=turbulent/churning
clearWater: 0=murky/opaque water, 1=clear/transparent water
murky: 0=clear/transparent, 1=murky/turbid
oxygenated: 0=anoxic/oxygen-poor, 1=oxygenated/well-aerated
eutrophic: 0=oligotrophic/nutrient-poor, 1=eutrophic/nutrient-rich
oligotrophic: 0=eutrophic/nutrient-rich, 1=oligotrophic/nutrient-poor
lacustrine: 0=riverine/flowing, 1=lacustrine/lake-related
cyclonic: 0=non-cyclonic/stable, 1=cyclonic/rotating storm
seismic: 0=non-seismic/stable, 1=seismic/earthquake-related
eruptive: 0=non-volcanic/stable, 1=eruptive/volcanic
avalancheProne: 0=avalanche-safe, 1=avalanche-prone
floodProne: 0=flood-safe, 1=flood-prone
thunderous: 0=quiet/non-thunderous, 1=thunderous/lightning
blizzardous: 0=tropical/non-blizzard, 1=blizzardous/severe snowstorm
tornadic: 0=non-tornadic, 1=tornadic/tornado-related
droughtEvent: 0=rainy/wet, 1=drought-event/extreme dryness
heatwave: 0=cool/temperate, 1=heatwave/extreme heat
dialectic: 0=non-dialectical, 1=dialectic/thesis-antithesis-synthesis
epistemological: 0=pre-theoretical/naive, 1=epistemological/knowledge-theory
metaphysical: 0=physical/natural, 1=metaphysical/beyond physical
hermeneutical: 0=literal/text-only, 1=hermeneutical/interpretive
phenomenological: 0=theoretical/abstract, 1=phenomenological/experience-first
existentialist: 0=essence-focused/eternal, 1=existentialist/existence-first
absurdist: 0=meaning-affirming, 1=absurdist/meaning-questioning
nihilisticPhil: 0=meaning-affirming/idealist, 1=nihilistic/rejecting meaning
materialist: 0=idealist/mind-primary, 1=materialist/matter-primary
idealist: 0=materialist/matter-primary, 1=idealist/mind-primary
extroverted: 0=introverted/reserved, 1=extroverted/outgoing
introverted: 0=extroverted/outgoing, 1=introverted/reserved
neurotic: 0=emotionally stable, 1=neurotic/emotionally unstable
agreeable: 0=disagreeable/antagonistic, 1=agreeable/cooperative
conscientious: 0=impulsive/careless, 1=conscientious/careful
openMinded: 0=closed-minded/rigid, 1=open-minded/curious
stubborn: 0=flexible/yielding, 1=stubborn/unyielding
empathetic: 0=apathetic/unfeeling, 1=empathetic/feeling with others
charismatic: 0=unremarkable/plain, 1=charismatic/compelling
assertive: 0=passive/meek, 1=assertive/self-confident
oppressive: 0=liberating/empowering, 1=oppressive/dominating
liberatory: 0=constraining/oppressive, 1=liberatory/freeing
discriminatory: 0=fair/non-discriminatory, 1=discriminatory/biased
egalitarian: 0=inequitable/hierarchical, 1=egalitarian/equal
inclusive: 0=exclusive/excluding, 1=inclusive/welcoming
marginalized: 0=centered/privileged, 1=marginalized/sidelined
empowering: 0=disempowering, 1=empowering/enabling
activist: 0=passive/apolitical, 1=activist/engaged
reformist: 0=conservative/status-quo, 1=reformist/change-seeking
radical: 0=moderate/centrist, 1=radical/extreme change
capitalist: 0=non-capitalist/anti-market, 1=capitalist/market-driven
socialist: 0=capitalist/market-driven, 1=socialist/state-guided
communist: 0=capitalist/private, 1=communist/collective ownership
feudal: 0=modern/post-feudal, 1=feudal/lord-serf system
mercantile: 0=domestic/non-mercantilist, 1=mercantile/trade-focused
neoliberal: 0=regulatory/interventionist, 1=neoliberal/free-market
cooperative: 0=competitive/private, 1=cooperative/worker-owned
redistributive: 0=concentrating/regressive, 1=redistributive/equalizing
extractive: 0=regenerative/additive, 1=extractive/depleting
subsistence: 0=commercial/surplus-oriented, 1=subsistence/self-sufficient
ergonomic: 0=poorly designed/straining, 1=ergonomic/body-friendly
holistic: 0=reductionist/part-focused, 1=holistic/whole-person
preventative: 0=reactive/curative, 1=preventative/proactive
diagnostic: 0=symptomatic/treatment, 1=diagnostic/identification
curative: 0=palliative/comfort, 1=curative/disease-eliminating
immunogenic: 0=immunosuppressive, 1=immunogenic/immune-stimulating
analgesic: 0=pain-inducing/hyperalgesic, 1=analgesic/pain-relieving
sedative: 0=stimulating/activating, 1=sedative/calming
stimulant: 0=sedating/calming, 1=stimulant/activating
psychoactive: 0=non-psychoactive, 1=psychoactive/mind-altering
handmade: 0=machine-made/factory, 1=handmade/artisan
artisanCraft: 0=industrial/mass-produced, 1=artisan-crafted/bespoke
precisionCraft: 0=rough/approximate, 1=precision-crafted/finely made
engraved: 0=plain/unengraved, 1=engraved/incised
woven: 0=unwoven/extruded, 1=woven/interlaced
sculpted: 0=cast/molded, 1=sculpted/carved
forged: 0=cast/poured, 1=forged/hammered
ceramic: 0=metal/non-ceramic, 1=ceramic/fired clay
lacquered: 0=unfinished/matte, 1=lacquered/high-gloss
embroidered: 0=plain/unembroidered, 1=embroidered/needle-worked
disciplineMath: relevance to mathematics discipline
disciplineScience: relevance to natural sciences discipline
disciplineHistory: relevance to history discipline
disciplineLiterature: relevance to literature/literary studies
disciplineSociology: relevance to sociology discipline
disciplineAnthropology: relevance to anthropology discipline
disciplinePsychology: relevance to psychology discipline
disciplineLinguistics: relevance to linguistics discipline
disciplineTheology: relevance to theology discipline
disciplineLaw: relevance to law/jurisprudence discipline
painterly: 0=graphic/flat, 1=painterly/brushwork-evident
sculptural: 0=flat/two-dimensional, 1=sculptural/three-dimensional
photographic: 0=painted/drawn, 1=photographic/lens-based
graphic: 0=painterly/fine art, 1=graphic/designed
abstractArt: 0=representational/figurative, 1=abstract/non-representational
minimalist: 0=maximalist/ornate, 1=minimalist/spare
surrealist: 0=realist/rational, 1=surrealist/dreamlike
impressionist: 0=sharp/precise, 1=impressionist/loose
expressionist: 0=restrained/calm, 1=expressionist/emotionally intense
conceptualArt: 0=sensory/formal, 1=conceptual/idea-based art
lyric: 0=prosaic/plain, 1=lyric/songlike
prosaic: 0=poetic/verse, 1=prosaic/prose
fictional: 0=non-fictional, 1=fictional/imagined
nonfictional: 0=fictional/imagined, 1=non-fictional/factual
biographical: 0=fictional/non-biographical, 1=biographical/life-story
epistolary: 0=third-person/omniscient, 1=epistolary/letter-form
experimentalLit: 0=conventional/traditional form, 1=experimental/avant-garde
canonicalLit: 0=apocryphal/non-canonical, 1=canonical/established classic
vernacular: 0=elevated/literary language, 1=vernacular/everyday speech
aphoristic: 0=verbose/expanded, 1=aphoristic/concise wisdom
cinematic: 0=non-cinematic/theatrical, 1=cinematic/film-specific
documentary: 0=fictional/narrative, 1=documentary/factual
animated: 0=live-action, 1=animated/drawn
noir: 0=bright/idealistic, 1=noir/dark/cynical
avantGarde: 0=mainstream/conventional, 1=avant-garde/experimental
blockbuster: 0=indie/art-house, 1=blockbuster/mass-market
indie: 0=studio/mainstream, 1=indie/independent production
horror: 0=non-horror/safe, 1=horror/frightening
comedic: 0=serious/dramatic, 1=comedic/humorous
thriller: 0=slow/contemplative, 1=thriller/fast-paced tension
choreographic: 0=unscripted/improvised, 1=choreographic/composed movement
improvisational: 0=composed/scripted, 1=improvisational/spontaneous
ceremonialDance: 0=secular/non-ceremonial, 1=ceremonial dance/ritual
theatrical: 0=cinematic/non-theatrical, 1=theatrical/stage-based
operatic: 0=spoken/non-operatic, 1=operatic/sung drama
acrobatic: 0=non-acrobatic/grounded, 1=acrobatic/gymnastic
balletic: 0=folk/free-form, 1=balletic/classical technique
folkloricDance: 0=contemporary/non-folkloric, 1=folkloric dance/traditional
contemporaryDance: 0=classical/traditional, 1=contemporary dance/modern
mime: 0=verbal/spoken, 1=mime/silent performance
sandbox: 0=linear/guided, 1=sandbox/open-world
linearGame: 0=open-world/sandbox, 1=linear/guided narrative
rpg: 0=action/twitch, 1=role-playing/character-driven
strategyGame: 0=action-reflex, 1=strategy/planning-based
puzzle: 0=action/combat, 1=puzzle/problem-solving
simulation: 0=abstract, 1=simulation/realistic
actionGame: 0=slow/strategic, 1=action/fast-reflex
narrativeGame: 0=gameplay-focused, 1=narrative/story-driven
multiplayer: 0=single-player/solo, 1=multiplayer/social
endlessGame: 0=finite/completable, 1=endless/infinite play
gothic: 0=modern/contemporary style, 1=gothic/medieval-inspired
baroque: 0=simple/restrained, 1=baroque/ornate-theatrical
modernist: 0=traditional/pre-modern, 1=modernist/functional
postmodernist: 0=modernist/pure, 1=postmodernist/eclectic
vernacularArch: 0=imported/non-local, 1=vernacular/locally adapted
brutalist: 0=ornate/decorated, 1=brutalist/raw concrete
neoclassical: 0=contemporary/non-classical, 1=neoclassical/antique-inspired
deconstructivist: 0=ordered/structured, 1=deconstructivist/fragmented
minimalistArch: 0=ornate/complex, 1=minimalist/reduced
organicArch: 0=rectilinear/geometric, 1=organic/biomorphic
viralSocial: 0=niche/limited spread, 1=viral/widely spread online
trending: 0=evergreen/timeless, 1=trending/momentarily popular
meme: 0=sincere/direct, 1=meme/ironic-remixed
hashtagged: 0=untagged/unsearchable, 1=hashtagged/categorized
shareable: 0=private/unshared, 1=shareable/designed to spread
clickbait: 0=informative/accurate, 1=clickbait/sensational headline
algorithmic: 0=organic/non-algorithmic, 1=algorithmic/ranked
curated: 0=uncurated/raw, 1=curated/editorially selected
crowdsourced: 0=top-down/produced, 1=crowdsourced/user-generated
ephemeralContent: 0=permanent/archived, 1=ephemeral/disappearing content
physicsRelativistic: 0=Newtonian/classical, 1=relativistic/Einstein
thermodynamic: 0=non-thermodynamic, 1=thermodynamic/heat-related
electromagnetic: 0=non-electromagnetic, 1=electromagnetic/EM-related
acoustic: 0=non-acoustic/vibrationless, 1=acoustic/sound-wave
optical: 0=non-optical/non-light, 1=optical/light-related
nuclearPhysics: 0=non-nuclear/chemical, 1=nuclear/atomic-core
plasma: 0=solid/liquid, 1=plasma/ionized gas
entropic: 0=low-entropy/ordered, 1=entropic/disordered
kinetic: 0=potential/static energy, 1=kinetic/motion energy
calorific: 0=non-calorific, 1=calorific/heat-producing
macronutrient: 0=micronutrient/trace, 1=macronutrient/bulk
micronutrient: 0=macronutrient/bulk, 1=micronutrient/trace
probiotic: 0=antibiotic/non-probiotic, 1=probiotic/gut-beneficial
prebiotic: 0=non-prebiotic, 1=prebiotic/gut-microbiome feeding
antioxidant: 0=pro-oxidant, 1=antioxidant/free-radical neutralizing
glycemic: 0=low-glycemic/stable blood sugar, 1=high-glycemic/spike
inflammatory: 0=anti-inflammatory, 1=inflammatory/pro-inflammatory
alkaline: 0=acidic/low-pH, 1=alkaline/high-pH diet
detoxifying: 0=accumulating/non-detox, 1=detoxifying/cleansing
bioavailable: 0=poorly absorbed, 1=highly bioavailable/well-absorbed
soporific: 0=alerting/stimulating, 1=soporific/sleep-inducing
stimulatory: 0=sedating/calming, 1=stimulatory/activating
hypnotic: 0=fully conscious, 1=hypnotic/trance-inducing
meditative: 0=agitated/distracted, 1=meditative/deeply focused
hallucinatory: 0=reality-grounded, 1=hallucinatory/perception-altering
dreamlike: 0=waking/reality-based, 1=dreamlike/surreal
trance: 0=conscious/aware, 1=trance/altered state
mindful: 0=distracted/scattered, 1=mindful/present-aware
alert: 0=sleepy/fatigued, 1=alert/fully awake
restful: 0=restless/disturbed, 1=restful/recuperative
metaphorical: 0=literal/direct, 1=metaphorical/transferred meaning
metonymic: 0=direct/literal, 1=metonymic/associated substitution
synecdochic: 0=whole/complete reference, 1=synecdochic/part-for-whole
ironic: 0=sincere/earnest, 1=ironic/opposite meaning
paradoxical: 0=consistent/non-paradoxical, 1=paradoxical/self-contradicting
oxymoronic: 0=straightforward, 1=oxymoronic/contradictory pairing
hyperbolic: 0=understated/litotic, 1=hyperbolic/extreme exaggeration
litotic: 0=direct/explicit, 1=litotic/understatement by negation
proverbial: 0=novel/non-proverbial, 1=proverbial/conventional wisdom
figurativeAphoristic: 0=verbose/expanded, 1=aphoristic/compact wisdom
synaesthetic: 0=single-sense, 1=synaesthetic/cross-sensory
multisensory: 0=single-sense, 1=multisensory/engaging multiple senses
immersive: 0=distanced/detached, 1=immersive/enveloping
mediated: 0=direct/unmediated, 1=mediated/filtered through medium
embodied: 0=abstract/disembodied, 1=embodied/felt in body
spatialSense: 0=non-spatial/abstract, 1=spatially experienced
temporalSense: 0=non-temporal/timeless, 1=temporally experienced
proprioceptive: 0=externally perceived, 1=proprioceptive/body-position aware
interoceptive: 0=externally focused, 1=interoceptive/inner-body aware
exteroceptive: 0=interoceptive/inner, 1=exteroceptive/outer-world sensing
PROMPT;
    }
}
