<?php

namespace App\Command;

use App\Entity\LanguageParseScheduleEntity;
use App\Message\ParseWiktionaryLanguagesMessage;
use App\Service\WiktionaryArticlesCategoriesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'make:language',
    description: 'Creates a new Language Entity and Repository, runs migrations, updates mappings, and grants DB access',
)]
class MakeLanguageCommand extends Command
{
    public function __construct(
        #[Autowire(env: 'DB_HOST')] private readonly string $dbHost,
        #[Autowire(env: 'MYSQL_PORT')] private readonly string $dbPort,
        #[Autowire(env: 'MYSQL_DATABASE')] private readonly string $dbName,
        #[Autowire(env: 'MYSQL_ROOT_PASSWORD')] private readonly string $dbRootPassword,
        #[Autowire(env: 'MYSQL_WEB_USER')] private readonly string $dbWebUser,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
        private readonly WiktionaryArticlesCategoriesService $categoriesService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('language', InputArgument::REQUIRED, 'Language name, e.g. Korean, Wolof (single word, capitalised)')
            ->addArgument('code', InputArgument::REQUIRED, 'ISO language code, e.g. ko, wo')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $language = ucfirst(strtolower($input->getArgument('language')));
        $code = strtolower($input->getArgument('code'));
        $tableName = 'pronunciation_' . strtolower($language) . '_language';
        $linksTableName = strtolower($language) . '_links';

        // 1. Create Entity
        $entityPath = __DIR__ . '/../Entity/' . $language . 'LanguageEntity.php';
        if (file_exists($entityPath)) {
            $io->error("Entity already exists: {$entityPath}");
            return Command::FAILURE;
        }
        file_put_contents($entityPath, $this->generateEntityTemplate($language, $tableName));
        $io->success("Created: src/Entity/{$language}LanguageEntity.php");

        // 2. Create Repository
        $repositoryPath = __DIR__ . '/../Repository/' . $language . 'LanguageRepository.php';
        if (file_exists($repositoryPath)) {
            $io->error("Repository already exists: {$repositoryPath}");
            return Command::FAILURE;
        }
        file_put_contents($repositoryPath, $this->generateRepositoryTemplate($language));
        $io->success("Created: src/Repository/{$language}LanguageRepository.php");

        // 3. Update LanguageMappings.php
        $this->updateLanguageMappings($language, $code);
        $io->success('Updated src/Constant/LanguageMappings.php');

        // 4. Update create_web_user.sql
        $this->updateCreateWebUserSql($tableName, $linksTableName);
        $io->success('Updated imports/create_web_user.sql');

        // 5. Update README.md
        $this->updateReadme($language);
        $io->success('Updated README.md');

        // 6. Generate migration — capture output to extract the version class name
        $io->section('Generating migration...');
        $diffOutput = new BufferedOutput();
        $exitCode = $this->getApplication()->find('doctrine:migrations:diff')->run(
            new ArrayInput(['--no-interaction' => true]),
            $diffOutput
        );
        $diffText = $diffOutput->fetch();
        $output->write($this->suppressMigrationWarnings($diffText));
        if (!preg_match('/Generated new migration class to "([^"]+)"/', $diffText, $matches)) {
            $io->error($exitCode !== Command::SUCCESS
                ? 'Migration generation failed.'
                : 'Could not parse migration file path from output.'
            );
            return Command::FAILURE;
        }
        $migrationFile = $matches[1];
        $migrationVersion = 'DoctrineMigrations' . '\\' . basename($migrationFile, '.php');

        // 7. Run the migration in a separate process — Doctrine's class registry is built at DI
        // container init time, so any migration file created mid-process is invisible to the
        // current process regardless of require_once.
        $io->section('Running migration...');
        $process = new \Symfony\Component\Process\Process([
            PHP_BINARY,
            __DIR__ . '/../../bin/console',
            'doctrine:migrations:execute',
            '--up',
            '--no-interaction',
            $migrationVersion,
        ]);
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($this->suppressMigrationWarnings($buffer));
        });
        if (!$process->isSuccessful()) {
            $io->error('Migration execution failed.');
            return Command::FAILURE;
        }

        // 8. Create links table and grant web user access on the fly
        $this->createLinksTableAndGrant($tableName, $linksTableName);
        $io->success("Granted web user SELECT,INSERT,UPDATE on {$tableName} and {$linksTableName}");

        // 9. Fetch word list from Wiktionary categories
        $io->section('Fetching word list from Wiktionary...');
        $this->categoriesService->getArticlesByCategory(strtolower($language));
        $io->success("Word list fetched for '{$language}'");

        // 10. Register language in parse schedule and kick off processing immediately
        $scheduleEntry = (new LanguageParseScheduleEntity())->setLanguageName(strtolower($language));
        $this->entityManager->persist($scheduleEntry);
        $this->entityManager->flush();
        $this->bus->dispatch(new ParseWiktionaryLanguagesMessage());
        $io->success("Registered '{$language}' in language_parse_schedule and dispatched IPA parse");

        $io->success("Language '{$language}' ({$code}) added successfully!");
        return Command::SUCCESS;
    }

    private function updateLanguageMappings(string $language, string $code): void
    {
        $path = __DIR__ . '/../Constant/LanguageMappings.php';
        $content = file_get_contents($path);
        $constPrefix = strtoupper($language);

        // Constants block: insert CODE + NAME pair
        $content = $this->insertAlphabetically(
            $content,
            "    public const string {$constPrefix}_LANGUAGE_CODE = '{$code}';\n"
            . "    public const string {$constPrefix}_LANGUAGE_NAME = '{$language}';",
            '/^    public const string ([A-Z_]+)_LANGUAGE_CODE = /m',
            $language,
            true
        );

        // getLanguageCodes()
        $content = $this->insertAlphabetically(
            $content,
            "            self::{$constPrefix}_LANGUAGE_CODE,",
            '/^            self::([A-Z_]+)_LANGUAGE_CODE,$/m',
            $language,
            true
        );

        // getEntityClassByLanguageCode()
        $content = $this->insertAlphabetically(
            $content,
            "            self::{$constPrefix}_LANGUAGE_CODE => 'App\\Entity\\{$language}LanguageEntity',",
            "/^            self::([A-Z_]+)_LANGUAGE_CODE => 'App/m",
            $language,
            true
        );

        // detectLanguageCodeFromEntity()
        $content = $this->insertAlphabetically(
            $content,
            "            '{$language}LanguageEntity' => self::{$constPrefix}_LANGUAGE_CODE,",
            "/^            '([A-Za-z]+)LanguageEntity' => self::/m",
            $language,
            false
        );

        // getLanguageCodeByName()
        $content = $this->insertAlphabetically(
            $content,
            "            self::{$constPrefix}_LANGUAGE_NAME => self::{$constPrefix}_LANGUAGE_CODE,",
            '/^            self::([A-Z_]+)_LANGUAGE_NAME => self::/m',
            $language,
            true
        );

        file_put_contents($path, $content);
    }

    /**
     * Insert $newLine alphabetically among lines matching $linePattern.
     * When $upperToTitle is true, the captured group is converted with ucfirst(strtolower()) before comparison.
     * Multi-line $newLine values (containing \n) are inserted as multiple lines.
     */
    private function insertAlphabetically(
        string $content,
        string $newLine,
        string $linePattern,
        string $newLanguage,
        bool $upperToTitle
    ): string {
        $lines = explode("\n", $content);
        $insertAt = null;
        $lastMatchAt = null;

        foreach ($lines as $i => $line) {
            if (!preg_match($linePattern, $line, $m)) {
                continue;
            }
            $existingLang = $upperToTitle ? ucfirst(strtolower($m[1])) : $m[1];
            $lastMatchAt = $i;
            if (strcasecmp($newLanguage, $existingLang) < 0) {
                $insertAt = $i;
                break;
            }
        }

        if ($insertAt === null && $lastMatchAt !== null) {
            $insertAt = $lastMatchAt + 1;
        }

        if ($insertAt !== null) {
            array_splice($lines, $insertAt, 0, explode("\n", $newLine));
        }

        return implode("\n", $lines);
    }

    private function updateCreateWebUserSql(string $tableName, string $linksTableName): void
    {
        $path = __DIR__ . '/../../imports/create_web_user.sql';
        $content = file_get_contents($path);
        $newGrants = "GRANT SELECT,INSERT,UPDATE ON lingwhaat.{$tableName} TO '\${MYSQL_WEB_USER}'@'%';\n"
            . "GRANT SELECT,INSERT,UPDATE ON lingwhaat.{$linksTableName} TO '\${MYSQL_WEB_USER}'@'%';";
        $content = str_replace('FLUSH PRIVILEGES;', $newGrants . "\nFLUSH PRIVILEGES;", $content);
        file_put_contents($path, $content);
    }

    private function updateReadme(string $language): void
    {
        $path = __DIR__ . '/../../README.md';
        $content = file_get_contents($path);

        // Find the table and parse existing language names from data rows (skip header + separator)
        preg_match('/(\|[^\n]+\|\n\|[-|]+\|\n(?:\|[^\n]+\|\n?)*)/', $content, $tableMatch);
        $tableText = $tableMatch[1];
        $tableLines = explode("\n", trim($tableText));

        $languages = [];
        foreach (array_slice($tableLines, 2) as $row) {
            foreach (array_map('trim', explode('|', $row)) as $cell) {
                if ($cell !== '') {
                    $languages[] = $cell;
                }
            }
        }

        $languages[] = $language;
        $languages = array_unique($languages);
        usort($languages, fn($a, $b) => strcasecmp(rtrim($a, '*'), rtrim($b, '*')));

        // Build new table with per-column widths
        $colCount = 4;
        $chunks = array_chunk($languages, $colCount);
        $colWidths = array_fill(0, $colCount, 0);
        foreach ($chunks as $row) {
            foreach ($row as $j => $cell) {
                $colWidths[$j] = max($colWidths[$j], strlen($cell));
            }
        }

        $formatRow = function (array $cells) use ($colCount, $colWidths): string {
            $parts = [];
            for ($j = 0; $j < $colCount; $j++) {
                $cell = $cells[$j] ?? '';
                $parts[] = ' ' . str_pad($cell, $colWidths[$j]) . ' ';
            }
            return '|' . implode('|', $parts) . '|';
        };

        $headerRow = $formatRow(array_fill(0, $colCount, ''));
        $separatorRow = '|' . implode('|', array_map(fn($w) => str_repeat('-', $w + 2), $colWidths)) . '|';
        $tableRows = array_merge([$headerRow, $separatorRow], array_map($formatRow, $chunks));
        $newTable = implode("\n", $tableRows);

        $content = preg_replace('/\|[^\n]+\|\n\|[-|]+\|\n(?:\|[^\n]+\|\n?)*/', $newTable . "\n", $content);

        $content = preg_replace(
            '/- Support for \d+ languages/',
            '- Support for ' . count($languages) . ' languages',
            $content
        );

        file_put_contents($path, $content);
    }

    private function createLinksTableAndGrant(string $tableName, string $linksTableName): void
    {
        $pdo = new \PDO(
            "mysql:host={$this->dbHost};port={$this->dbPort};dbname={$this->dbName}",
            'root',
            $this->dbRootPassword,
        );

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$linksTableName}` (
                `id` int NOT NULL AUTO_INCREMENT,
                `name` varchar(256) DEFAULT '',
                `link` varchar(2048) DEFAULT '',
                `ts_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        $pdo->exec("GRANT SELECT,INSERT,UPDATE ON `{$this->dbName}`.`{$tableName}` TO '{$this->dbWebUser}'@'%'");
        $pdo->exec("GRANT SELECT,INSERT,UPDATE ON `{$this->dbName}`.`{$linksTableName}` TO '{$this->dbWebUser}'@'%'");
        $pdo->exec('FLUSH PRIVILEGES');
    }

    private function suppressMigrationWarnings(string $output): string
    {
        return preg_replace('/^\s*!\s*\[WARNING\][^\n]*previously executed migrations[^\n]*\n?/m', '', $output);
    }

    private function generateEntityTemplate(string $languageClass, string $tableName): string
    {
        return <<<PHP
<?php

namespace App\Entity;

use App\Repository\\{$languageClass}LanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: {$languageClass}LanguageRepository::class)]
#[ORM\Table(
    name: "{$tableName}",
    indexes: [
        new ORM\Index(name: 'idx_name', columns: ['name']),
    ]
)]
class {$languageClass}LanguageEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int \$id;

    #[ORM\Column(length: 256)]
    private string \$name;

    #[ORM\Column(length: 255)]
    private string \$ipa;

    #[ORM\Column(name: 'ts_created', length: 255)]
    private string \$tsCreated;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int \$score;

    public function getId(): int
    {
        return \$this->id;
    }

    public function setId(int \$id): {$languageClass}LanguageEntity
    {
        \$this->id = \$id;
        return \$this;
    }

    public function getName(): string
    {
        return \$this->name;
    }

    public function setName(string \$name): {$languageClass}LanguageEntity
    {
        \$this->name = \$name;
        return \$this;
    }

    public function getIpa(): string
    {
        return \$this->ipa;
    }

    public function setIpa(string \$ipa): {$languageClass}LanguageEntity
    {
        \$this->ipa = \$ipa;
        return \$this;
    }

    public function getTsCreated(): string
    {
        return \$this->tsCreated;
    }

    public function setTsCreated(string \$tsCreated): {$languageClass}LanguageEntity
    {
        \$this->tsCreated = \$tsCreated;
        return \$this;
    }

    public function getScore(): int
    {
        return \$this->score;
    }

    public function setScore(int \$score): {$languageClass}LanguageEntity
    {
        \$this->score = \$score;
        return \$this;
    }

}

PHP;
    }

    private function generateRepositoryTemplate(string $languageClass): string
    {
        return <<<PHP
<?php

namespace App\Repository;

use App\Entity\\{$languageClass}LanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class {$languageClass}LanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry \$registry)
    {
        parent::__construct(\$registry, {$languageClass}LanguageEntity::class);
    }

}

PHP;
    }
}
