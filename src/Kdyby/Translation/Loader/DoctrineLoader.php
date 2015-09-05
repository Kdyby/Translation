<?php

namespace Kdyby\Translation\Loader;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Kdyby\Translation\Listener\TranslationMetadataListener;
use Kdyby\Translation\Resource\DatabaseResource;
use Tracy\Debugger;

class DoctrineLoader extends DatabaseLoader
{

    /** @var Connection */
    private $conn;

    /**
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        $qb = $this->conn->createQueryBuilder()
            ->addSelect("DISTINCT `$this->locale` AS locale")
            ->from("`$this->table`");
        try {
            $stmt = $qb->execute();
            $locales = array_column($stmt->fetchAll(), 'locale');
        } catch(TableNotFoundException $e) {
            $locales = array();
        }
        return $locales;
    }


    public function getResourceName()
    {
        return DatabaseResource::DOCTRINE;
    }

    public function getTranslations($locale)
    {
        $qb = $this->conn->createQueryBuilder()
            ->addSelect("`$this->key` AS `key`")
            ->addSelect("`$this->locale` AS locale")
            ->addSelect("`$this->message` AS message")
            ->from("`$this->table`")
            ->where("locale = :locale")
            ->setParameter('locale', $locale);
        return $qb->execute()->fetchAll();
    }

    /**
     * @param $locale
     * @return \DateTime
     */
    public function getLastUpdate($locale)
    {
        $qb = $this->conn->createQueryBuilder()
            ->addSelect("`$this->updatedAt` AS `updated_at`")
            ->from("`$this->table`")
            ->where("locale = :locale")
            ->orderBy('updated_at', Criteria::DESC)
            ->setMaxResults(1)
            ->setParameter('locale', $locale);
        return new \DateTime($qb->execute()->fetchColumn());
    }

}
