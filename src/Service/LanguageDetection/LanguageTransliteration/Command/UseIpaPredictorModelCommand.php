<?php

namespace App\Service\LanguageDetection\LanguageTransliteration\Command;

use App\Constant\LanguageMappings;
use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use App\Service\LanguageDetection\LanguageTransliteration\UseIpaPredictorModelService;
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

#[AsCommand(name: 'ml:use:ipa-predictor')]
class UseIpaPredictorModelCommand extends Command
{
    public function __construct(
        protected UseIpaPredictorModelService $ipaPredictorModelService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Use IPA prediction model for a specific language and word')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageMappings::getLanguageCodes())
            )
            ->addOption('word', 'w', InputOption::VALUE_REQUIRED, 'Word to use for IPA prediction.');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console ml:use:ipa-predictor --lang latvian --word zivis

        $lang = $input->getOption('lang');
        $word = $input->getOption('word');

        if (!$lang || !$word) {
            $output->writeln("<error>No --lang and/or --word parameters provided.</error>");
            return Command::FAILURE;
        }

        if (!file_exists(IpaPredictorConstants::getMlServiceIpaModelsPath() . $lang.'_model.pt')) {
            $output->writeln("<error>Model for {$lang} not found! Train model first.</error>");
            return Command::FAILURE;
        }

        if (!file_exists(IpaPredictorConstants::getMlServiceDataPath() . $lang.'.csv')) {
            $output->writeln("<error>Data for {$lang} not found! Train model first.</error>");
            return Command::FAILURE;
        }

        $ipa = $this->ipaPredictorModelService->run($lang, $word);

        $output->writeln("Predicted IPA: {$ipa}");

        return Command::SUCCESS;
    }

}