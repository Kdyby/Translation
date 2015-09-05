<?php

namespace Kdyby\Translation\Dumper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Tracy\Debugger;

class DoctrineDumper extends DatabaseDumper
{

    /** @var Connection */
    private $conn;

    /** @var AbstractSchemaManager */
    private $sm;

    /**
     * @param Connection $conn
     * @param AbstractSchemaManager $sm
     */
    public function __construct(Connection $conn, AbstractSchemaManager $sm)
    {
        $this->conn = $conn;
        $this->sm = $sm;
    }

    public function getExistingKeys($keys, $locale)
    {
        $qb = $this->conn->createQueryBuilder()
            ->addSelect("`$this->key` AS `key`")
            ->from("`$this->table`")
            ->andWhere("locale  = :locale")
            ->andWhere("`key` IN (:keys)")
            ->setParameter('locale', $locale)
            ->setParameter('keys', $keys, Connection::PARAM_STR_ARRAY);
        $stmt = $qb->execute();
        return array_column($stmt->fetchAll(), 'key'); //to get only one dimensional array of keys

    }

    public function beginTransaction()
    {
        $this->conn->beginTransaction();
    }

    public function commit()
    {
        $this->conn->commit();
    }

    public function insert($key, $locale, $message)
    {
        $qb = $this->conn->createQueryBuilder();
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
                'key' => PDOConnection::PARAM_STR,
                'locale' => PDOConnection::PARAM_STR,
                'message' => PDOConnection::PARAM_STR,
                'updatedAt' => 'datetime'
            ]);
        $qb->execute();
    }

    public function update($key, $locale, $message)
    {
        $qb = $this->conn->createQueryBuilder();
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
                'key' => PDOConnection::PARAM_STR,
                'locale' => PDOConnection::PARAM_STR,
                'message' => PDOConnection::PARAM_STR,
                'updatedAt' => 'datetime'
            ]);
        $qb->execute();
    }

}
