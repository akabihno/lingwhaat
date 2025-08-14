<?php

namespace App\Service\LanguageDetection;

use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use App\Service\LanguageDetection\LanguageTransliteration\TrainIpaPredictorModelCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'ml:train:word-predictor')]
class TrainWordPredictorModelCommand extends Command
{
    protected string $trainingDataPath;

    public function __construct(
        protected HttpClientInterface $httpClient,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Train word based on IPA prediction model for a specific language')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageDetectionService::getLanguageCodes())
            );
    }

    /**
     * @note this command expects that TrainIpaPredictorModelCommand has been already ran
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws TransportExceptionInterface
     * @see TrainIpaPredictorModelCommand
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console ml:train:word-predictor --lang lv

        $lang = $input->getOption('lang');

        $this->trainingDataPath = realpath(".") . "/ml_service/data/{$lang}.csv";

        if (!file_exists($this->trainingDataPath)) {
            $output->writeln('<error>No valid data found for training. Use ml:train:ipa-predictor first.</error>');
            return Command::FAILURE;
        }

        $file = new File($this->trainingDataPath);

        $response = $this->httpClient->request('POST', 'http://'.IpaPredictorConstants::getMlServiceHost().':'.IpaPredictorConstants::getMlServicePort().'/'.IpaPredictorConstants::getMlServiceTrainWordRoute().'/', [
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

        $output->writeln("Training complete on dataset {$this->trainingDataPath} with status {$statusCode}");


        return Command::SUCCESS;
    }

}