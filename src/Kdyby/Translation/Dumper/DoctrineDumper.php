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
            ->addSelect("`$this->key` AS `key`")
            ->from("`$this->table`")
            ->andWhere("locale  = :locale")
            ->andWhere("`key` IN (:keys)")
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
                "`$this->key`" => ":key",
                "`$this->locale`" => ":locale",
                "`$this->message`" => ":message",
                "`$this->updatedAt`" => ":updatedAt"
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
            ->set("t.$this->message", ':message')
            ->set("t.$this->updatedAt", ':updatedAt')
            ->andWhere("t.$this->key = :key")
            ->andWhere("t.$this->locale = :locale")
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
