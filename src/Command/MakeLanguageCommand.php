<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'make:language',
    description: 'Creates a new Language Entity and Repository',
)]
class MakeLanguageCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('language', InputArgument::REQUIRED, 'Language name (e.g., Norwegian, Danish)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $language = $input->getArgument('language');

        $languageClass = ucfirst($language);
        $tableName = 'pronunciation_' . strtolower($language) . '_language';

        $entityPath = __DIR__ . '/../Entity/' . $languageClass . 'LanguageEntity.php';
        $entityContent = $this->generateEntityTemplate($languageClass, $tableName);

        if (file_exists($entityPath)) {
            $io->error("Entity already exists: {$entityPath}");
            return Command::FAILURE;
        }

        file_put_contents($entityPath, $entityContent);
        $io->success("Created: src/Entity/{$languageClass}LanguageEntity.php");

        $repositoryPath = __DIR__ . '/../Repository/' . $languageClass . 'LanguageRepository.php';
        $repositoryContent = $this->generateRepositoryTemplate($languageClass);

        if (file_exists($repositoryPath)) {
            $io->error("Repository already exists: {$repositoryPath}");
            return Command::FAILURE;
        }

        file_put_contents($repositoryPath, $repositoryContent);
        $io->success("Created: src/Repository/{$languageClass}LanguageRepository.php");

        $io->note('Next steps:');
        $io->listing([
            'Run: php bin/console make:migration',
            'Run: php bin/console doctrine:migrations:migrate',
        ]);

        return Command::SUCCESS;
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
