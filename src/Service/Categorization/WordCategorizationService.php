<?php

namespace App\Service\Categorization;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordCategorizationService
{
    private const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL = 'claude-opus-4-6';
    private const MAX_TOKENS = 8096;

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
PROMPT;
    }
}
