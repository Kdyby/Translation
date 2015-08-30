<?php

namespace Kdyby\Translation\Dumper;

use Doctrine\DBAL\Connection;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Helpers;

class DoctrineDumper extends DatabaseDumper {

    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    public function getExistingKeys($keys, $locale)
    {
        $conn = $this->em->getConnection();
        $qb = $conn->createQueryBuilder()
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
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
    }

    public function commit()
    {
        $conn = $this->em->getConnection();
        $conn->commit();
    }

    public function insert($key, $locale, $message)
    {
        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->insert($this->table)
            ->values([
                "`$this->key`" => ":key",
                "`$this->locale`" => ":locale",
                "`$this->message`" => ":message"
            ])
            ->setParameters([
                'key' => $key,
                'locale' => $locale,
                'message' => $message
            ]);
        $qb->execute();
    }

    public function update($key, $locale, $message)
    {
        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->update($this->table, 't')
            ->set("t.$this->message", ':message')
            ->andWhere("t.$this->key = :key")
            ->andWhere("t.$this->locale = :locale")
            ->setParameters([
                'key' => $key,
                'locale' => $locale,
                'message' => $message
            ]);
        $qb->execute();
    }
}
