<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'ml:use:ipa-predictor')]
class UseIpaPredictorModelCommand extends Command
{
    protected string $modelName;
    protected string $wordMappingPath;
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected TrainIpaPredictorModelCommand $trainIpaPredictorModelCommand
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Use IPA prediction model for a specific language and word')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageDetectionService::getLanguageCodes())
            )
            ->addOption('word', null, InputOption::VALUE_REQUIRED, 'Word to use for IPA prediction.')
            ->addOption('model_name', null, InputOption::VALUE_REQUIRED, 'Model name for IPA prediction.');
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
        $lang = $input->getOption('lang');
        $word = $input->getOption('word');

        if (!$lang || !$word) {
            $output->writeln("<error>No --lang and/or --word parameters provided.</error>");
            return Command::FAILURE;
        }

        $this->modelName = "ipa_predictor_dataset_{$lang}_model.pt";

        if (!file_exists(IpaPredictorConstants::getMlServiceModelsPath() . $this->modelName)) {
            $output->writeln("<error>Model for {$lang} not found! Train model first.</error>");
            return Command::FAILURE;
        }

        $this->wordMappingPath = "src/Models/CharMap/{$lang}.json";

        if (!file_exists($this->wordMappingPath)) {
            $output->writeln("<error>Word char map for {$lang} not found! Train model first.</error>");
            return Command::FAILURE;
        }

        $encodedWord = $this->trainIpaPredictorModelCommand->encodeWord($word);

        if (!$encodedWord) {
            $output->writeln("<error>Failed to encode word {$word}.</error>");
            return Command::FAILURE;
        }

        $response = $this->httpClient->request(
            'GET',
            'http://' . IpaPredictorConstants::getMlServiceHost() .
            ':' . IpaPredictorConstants::getMlServicePort() .
            '/' . IpaPredictorConstants::getMlServicePredictRoute() . '/',
            [
                'query' => [
                    'word' => $encodedWord,
                    'model_name' => $this->modelName,
                ],
            ]
        );

        $data = $response->toArray();
        $ipa = $data['ipa'];

        $output->writeln("Predicted IPA: {$ipa}");
        $decodedIpa = $this->trainIpaPredictorModelCommand->decodeIpa($ipa);
        $output->writeln("Predicted IPA decoded: {$decodedIpa}");

        return Command::SUCCESS;
    }

}