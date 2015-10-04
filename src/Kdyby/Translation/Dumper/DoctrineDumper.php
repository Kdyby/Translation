<?php

namespace Kdyby\Translation\Dumper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

class DoctrineDumper extends DatabaseDumper
{

    /** @var Connection */
    private $connection;

    /**
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->connection = $conn;
    }

    protected function getExistingKeys($keys, $locale)
    {
        $qb = $this->connection->createQueryBuilder()
            ->addSelect($this->connection->quoteIdentifier($this->key).' AS '.$this->connection->quoteIdentifier('key'))
            ->from($this->connection->quoteIdentifier($this->table))
            ->andWhere($this->connection->quoteIdentifier($this->locale).' = :locale')
            ->andWhere($this->connection->quoteIdentifier($this->key).' IN (:keys)')
            ->setParameter('locale', $locale)
            ->setParameter('keys', $keys, Connection::PARAM_STR_ARRAY);
        $stmt = $qb->execute();
        return array_column($stmt->fetchAll(), 'key'); //to get only one dimensional array of keys

    }

    protected function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    protected function commit()
    {
        $this->connection->commit();
    }

    protected function insert($key, $locale, $message)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->insert($this->table)
            ->values([
                $this->connection->quoteIdentifier($this->key) => ":key",
                $this->connection->quoteIdentifier($this->locale) => ":locale",
                $this->connection->quoteIdentifier($this->message) => ":message",
                $this->connection->quoteIdentifier($this->updatedAt) => ":updatedAt"
            ])
            ->setParameters([
                'key' => $key,
                'locale' => $locale,
                'message' => $message,
                'updatedAt' => new \DateTime()
            ], [
                'key' => Type::STRING,
                'locale' => Type::STRING,
                'message' => Type::STRING,
                'updatedAt' => Type::DATETIME
            ]);
        $qb->execute();
    }

    protected function update($key, $locale, $message)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update($this->table, 't')
            ->set('t.'.$this->connection->quoteIdentifier($this->message), ':message')
            ->set('t.'.$this->connection->quoteIdentifier($this->updatedAt), ':updatedAt')
            ->andWhere('t.'.$this->connection->quoteIdentifier($this->key).' = :key')
            ->andWhere('t.'.$this->connection->quoteIdentifier($this->locale).' = :locale')
            ->setParameters([
                'key' => $key,
                'locale' => $locale,
                'message' => $message,
                'updatedAt' => new \DateTime()
            ], [
                'key' => Type::STRING,
                'locale' => Type::STRING,
                'message' => Type::STRING,
                'updatedAt' => Type::DATETIME
            ]);
        $qb->execute();
    }

    protected function rollBack()
    {
        $this->connection->rollBack();
    }
}
