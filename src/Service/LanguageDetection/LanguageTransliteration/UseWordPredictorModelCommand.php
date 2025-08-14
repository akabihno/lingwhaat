<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'ml:use:word-predictor')]
class UseWordPredictorModelCommand extends Command
{
    protected string $modelName;
    protected string $dataPath;
    public function __construct(
        protected HttpClientInterface $httpClient,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Use word prediction model for a specific language and IPA')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageDetectionService::getLanguageCodes())
            )
            ->addOption('ipa', 'i', InputOption::VALUE_REQUIRED, 'IPA to use for word prediction.');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console ml:use:word-predictor --lang ru --ipa [ˈpot͡ɕvə]

        $lang = $input->getOption('lang');
        $ipa = $input->getOption('ipa');

        if (!$lang || !$ipa) {
            $output->writeln("<error>No --lang and/or --ipa parameters provided.</error>");
            return Command::FAILURE;
        }

        $this->modelName = "{$lang}_model.pt";

        if (!file_exists(IpaPredictorConstants::getMlServiceWordModelsPath() . $this->modelName)) {
            $output->writeln("<error>Model for {$lang} not found! Train model first.</error>");
            return Command::FAILURE;
        }

        $this->dataPath = "{$lang}.csv";

        if (!file_exists(IpaPredictorConstants::getMlServiceDataPath() . $this->dataPath)) {
            $output->writeln("<error>Data for {$lang} not found! Train model first.</error>");
            return Command::FAILURE;
        }

        $response = $this->httpClient->request(
            'GET',
            'http://' . IpaPredictorConstants::getMlServiceHost() .
            ':' . IpaPredictorConstants::getMlServicePort() .
            '/' . IpaPredictorConstants::getMlServicePredictWordRoute() . '/',
            [
                'query' => [
                    'ipa' => $ipa,
                    'model_name' => $this->modelName,
                    'file' => $this->dataPath,
                ],
            ]
        );

        $data = $response->toArray();
        $word = $data['word'];

        $output->writeln("Predicted word: {$word}");

        return Command::SUCCESS;
    }

}