<?php

namespace App\Service\Logging\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'logging:setup-elasticsearch-ilm',
    description: 'Set up Elasticsearch ILM policy for application logs with 14-day retention'
)]
class SetupElasticsearchIlmCommand extends Command
{
    private const string POLICY_NAME = 'application-logs-policy';
    private const string INDEX_PATTERN = 'application-logs-*';
    private string $elasticsearchUrl;

    public function __construct(private HttpClientInterface $httpClient)
    {
        parent::__construct();
        $host = $_ENV['ELASTICSEARCH_HOST'] ?? 'localhost';
        $port = $_ENV['ELASTICSEARCH_PORT'] ?? 9200;
        $this->elasticsearchUrl = "http://{$host}:{$port}";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Setting up Elasticsearch ILM for Application Logs');

        try {
            $this->createIlmPolicy($io);

            $this->createIndexTemplate($io);

            $io->success('Elasticsearch ILM policy has been successfully configured!');
            $io->note([
                'Logs will now be stored in daily indices: application-logs-YYYY.MM.DD',
                'Indices older than 14 days will be automatically deleted',
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed to set up ILM policy: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createIlmPolicy(SymfonyStyle $io): void
    {
        $io->section('Creating ILM Policy');

        $policy = [
            'policy' => [
                'phases' => [
                    'hot' => [
                        'min_age' => '0ms',
                        'actions' => [
                            'rollover' => [
                                'max_age' => '1d',
                                'max_primary_shard_size' => '50gb',
                            ],
                        ],
                    ],
                    'delete' => [
                        'min_age' => '14d',
                        'actions' => [
                            'delete' => new \stdClass(),
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->httpClient->request(
            'PUT',
            "{$this->elasticsearchUrl}/_ilm/policy/" . self::POLICY_NAME,
            [
                'json' => $policy,
                'headers' => ['Content-Type' => 'application/json'],
            ]
        );

        if ($response->getStatusCode() === 200) {
            $io->success('ILM policy "' . self::POLICY_NAME . '" created successfully');
        } else {
            throw new \RuntimeException('Failed to create ILM policy: ' . $response->getContent(false));
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function createIndexTemplate(SymfonyStyle $io): void
    {
        $io->section('Creating Index Template');

        $template = [
            'index_patterns' => [self::INDEX_PATTERN],
            'template' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'index.lifecycle.name' => self::POLICY_NAME,
                    'index.lifecycle.rollover_alias' => 'application-logs',
                ],
                'mappings' => [
                    'properties' => [
                        'timestamp' => [
                            'type' => 'date',
                        ],
                        'level' => [
                            'type' => 'keyword',
                        ],
                        'message' => [
                            'type' => 'text',
                        ],
                        'context' => [
                            'type' => 'object',
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
            'priority' => 200,
        ];

        $response = $this->httpClient->request(
            'PUT',
            "{$this->elasticsearchUrl}/_index_template/application-logs-template",
            [
                'json' => $template,
                'headers' => ['Content-Type' => 'application/json'],
            ]
        );

        if ($response->getStatusCode() === 200) {
            $io->success('Index template "application-logs-template" created successfully');
        } else {
            throw new \RuntimeException('Failed to create index template: ' . $response->getContent(false));
        }
    }
}
