<?php

namespace App\Service\LanguageDetection\LanguageTransliteration\Command;

use App\Repository\AbstractLanguageRepository;
use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'ml:train:ipa-predictor')]
class TrainIpaPredictorModelCommand extends Command
{
    protected string $trainingDataPath;

    public function __construct(
        protected ContainerInterface $container,
        protected HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Train IPA prediction model for a specific language')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language name (e.g., latvian, norwegian, dutch)'
            )->addOption('prepare', 'p', InputOption::VALUE_OPTIONAL,
                'Optional argument to update existing dataset in CSV file (can be used if data in DB was populated)');
    }

    /**
     * Get a repository for a language by name
     */
    protected function getLanguageRepository(string $languageName): ?AbstractLanguageRepository
    {
        $languageClass = ucfirst(strtolower($languageName));
        $repositoryServiceId = 'App\\Repository\\' . $languageClass . 'LanguageRepository';

        if (!$this->container->has($repositoryServiceId)) {
            return null;
        }

        $repository = $this->container->get($repositoryServiceId);

        if (!$repository instanceof AbstractLanguageRepository) {
            return null;
        }

        return $repository;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console ml:train:ipa-predictor --lang latvian

        $languageName = $input->getOption('lang');
        $prepare = $input->getOption('prepare');

        if (!$languageName) {
            $output->writeln('<error>No language provided in --lang parameter.</error>');
            return Command::FAILURE;
        }

        $repository = $this->getLanguageRepository($languageName);

        if (!$repository) {
            $output->writeln("<error>Language '{$languageName}' is not supported or repository not found.</error>");
            $output->writeln('<info>Make sure the language name matches an existing repository (e.g., latvian, norwegian, dutch).</info>');
            return Command::FAILURE;
        }

        $langFileName = strtolower($languageName);
        $this->trainingDataPath = realpath(".")."/ml_service/data/{$langFileName}.csv";

        $trainingDatasetArray = $repository->findAllNamesAndIpa();

        if (!$trainingDatasetArray) {
            $output->writeln('<error>No valid data found for training.</error>');
            return Command::FAILURE;
        }

        if (!file_exists($this->trainingDataPath) || $prepare) {
            $csvHandle = fopen($this->trainingDataPath, 'w');
            fputcsv($csvHandle, ['word', 'ipa']);

            foreach ($trainingDatasetArray as $datasetRow) {
                $word = $datasetRow['name'];
                $ipa = $datasetRow['ipa'];
                if ($ipa && $word) {
                    fputcsv($csvHandle, [$word, $ipa]);
                }
            }

            fclose($csvHandle);
            $output->writeln("Training dataset for {$languageName} saved to {$this->trainingDataPath}");
        }

        $file = new File($this->trainingDataPath);

        $response = $this->httpClient->request('POST', 'http://'.IpaPredictorConstants::getMlServiceHost().':'.IpaPredictorConstants::getMlServicePort().'/'.IpaPredictorConstants::getMlServiceTrainIpaRoute().'/', [
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
            'body' => [
                'file' => fopen($file->getRealPath(), 'r'),
            ],
            'timeout' => 600,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent();

        $output->writeln("Training scheduled on dataset {$this->trainingDataPath} with status {$statusCode}");

        return Command::SUCCESS;
    }

}