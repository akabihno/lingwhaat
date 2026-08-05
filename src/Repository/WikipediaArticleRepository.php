<?php

namespace App\Repository;

use App\Entity\WikipediaArticleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WikipediaArticleEntity>
 *
 * @method WikipediaArticleEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method WikipediaArticleEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method WikipediaArticleEntity[]    findAll()
 * @method WikipediaArticleEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WikipediaArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WikipediaArticleEntity::class);
    }

    /**
     * @return WikipediaArticleEntity[]
     */
    public function findByLanguageCodePaginated(string $languageCode, int $limit = 100, int $offset = 0): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Keyset (seek) pagination: fetch the next $limit articles for a language whose id is greater
     * than $afterId, in ascending id order. Pass 0 to start from the beginning, then feed the id of
     * the last returned row back in as $afterId for the next page. Unlike OFFSET-based paging this
     * is a single index range seek on (language_code, id) regardless of how deep into the corpus we
     * are, so cost stays flat instead of growing with the offset.
     *
     * @return array<int, array{id:int, text:string}>
     */
    public function findIdAndTextByLanguageCodeAfterId(
        string $languageCode,
        int $limit = 100,
        int $afterId = 0
    ): array {
        // FORCE INDEX (language_code, id): with ORDER BY id LIMIT, MySQL's optimizer otherwise
        // picks a PRIMARY-key scan and reads millions of rows — fetching every LONGTEXT — to find a
        // sparse language's matches, which is catastrophic from a low cursor (EXPLAIN: key=PRIMARY
        // rows=~3M vs forced key=i_lang_id rows=1). DQL can't express index hints, so use native SQL.
        // MAX_EXECUTION_TIME caps this SELECT at 30s server-side: a safety net so a bad plan or
        // lock can never again run for thousands of seconds and hang the worker (with FORCE INDEX
        // the query is sub-second, so this only ever fires on a real regression). MySQL aborts the
        // statement with an error, which fails the message for a normal retry.
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT /*+ MAX_EXECUTION_TIME(30000) */ id, text FROM wikipedia_article FORCE INDEX (i_lang_id)
             WHERE language_code = :languageCode AND id > :afterId
             ORDER BY id ASC
             LIMIT :limit',
            ['languageCode' => $languageCode, 'afterId' => $afterId, 'limit' => $limit],
            ['languageCode' => \PDO::PARAM_STR, 'afterId' => \PDO::PARAM_INT, 'limit' => \PDO::PARAM_INT],
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'text' => (string) $row['text'],
            ],
            $rows
        );
    }

    /**
     * Offset-paginated articles for a language, ascending by id.
     *
     * FORCE INDEX (language_code, id) for the same reason as
     * findIdAndTextByLanguageCodeAfterId(): with ORDER BY id LIMIT the optimizer otherwise picks a
     * PRIMARY-key scan and reads millions of LONGTEXT rows to find a sparse language's matches.
     *
     * @return array<int, array{id:int, wikipediaLink:string, text:string, tsCreated:string}>
     */
    public function findByLanguageCodePaginatedOrdered(
        string $languageCode,
        int $limit = 20,
        int $offset = 0
    ): array {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT /*+ MAX_EXECUTION_TIME(30000) */ id, wikipedia_link, text, ts_created
             FROM wikipedia_article FORCE INDEX (i_lang_id)
             WHERE language_code = :languageCode
             ORDER BY id ASC
             LIMIT :limit OFFSET :offset',
            ['languageCode' => $languageCode, 'limit' => $limit, 'offset' => $offset],
            ['languageCode' => \PDO::PARAM_STR, 'limit' => \PDO::PARAM_INT, 'offset' => \PDO::PARAM_INT],
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'wikipediaLink' => (string) $row['wikipedia_link'],
                'text' => (string) $row['text'],
                'tsCreated' => (string) $row['ts_created'],
            ],
            $rows
        );
    }

    /**
     * Keyset-paginated articles for a language, ascending by id, starting strictly after
     * $afterId. Same shape as findByLanguageCodePaginatedOrdered() but a single index range
     * seek on (language_code, id) instead of an OFFSET skip, so cost stays flat no matter how
     * deep into the corpus $afterId is — see findIdAndTextByLanguageCodeAfterId() for why
     * OFFSET degrades and FORCE INDEX is needed here too.
     *
     * @return array<int, array{id:int, wikipediaLink:string, text:string, tsCreated:string}>
     */
    public function findByLanguageCodeAfterIdOrdered(
        string $languageCode,
        int $limit = 20,
        int $afterId = 0
    ): array {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT /*+ MAX_EXECUTION_TIME(30000) */ id, wikipedia_link, text, ts_created
             FROM wikipedia_article FORCE INDEX (i_lang_id)
             WHERE language_code = :languageCode AND id > :afterId
             ORDER BY id ASC
             LIMIT :limit',
            ['languageCode' => $languageCode, 'afterId' => $afterId, 'limit' => $limit],
            ['languageCode' => \PDO::PARAM_STR, 'afterId' => \PDO::PARAM_INT, 'limit' => \PDO::PARAM_INT],
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'wikipediaLink' => (string) $row['wikipedia_link'],
                'text' => (string) $row['text'],
                'tsCreated' => (string) $row['ts_created'],
            ],
            $rows
        );
    }

    /**
     * Whether this language already holds an article with this link. Backed by i_lang_link
     * (language_code, wikipedia_link(191)) — a prefix index, so MySQL narrows on the prefix and
     * then verifies the full value, which keeps the result exact for links longer than 191 chars.
     */
    public function existsByLanguageCodeAndLink(string $languageCode, string $wikipediaLink): bool
    {
        $id = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT id FROM wikipedia_article
             WHERE language_code = :languageCode AND wikipedia_link = :wikipediaLink
             LIMIT 1',
            ['languageCode' => $languageCode, 'wikipediaLink' => $wikipediaLink],
            ['languageCode' => \PDO::PARAM_STR, 'wikipediaLink' => \PDO::PARAM_STR],
        );

        return $id !== false;
    }

    public function countByLanguageCode(string $languageCode): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return string[]
     */
    public function getDistinctLanguageCodes(): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('DISTINCT w.languageCode')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'languageCode');
    }

    /**
     * Language codes that have at least one article, ordered by article count descending so the
     * busiest corpora are dispatched first.
     *
     * @return string[]
     */
    public function getLanguageCodesByArticleCountDesc(): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('w.languageCode')
            ->groupBy('w.languageCode')
            ->orderBy('COUNT(w.id)', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'languageCode');
    }
}
