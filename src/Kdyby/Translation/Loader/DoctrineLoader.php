<?php

namespace Kdyby\Translation\Loader;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Kdyby\Translation\Listener\TranslationMetadataListener;
use Kdyby\Translation\Resource\DatabaseResource;
use Tracy\Debugger;

class DoctrineLoader extends DatabaseLoader {

    /** @var Connection */
    private $conn;

//    /** @var EntityManager */
//    private $em;
//
//    /** @var AbstractSchemaManager */
//    private $sm;
//
//    /** @var SchemaTool */
//    private $schemaTool;

    /**
     * @param Connection $conn
     */
    public function __construct(Connection $conn) {
        $this->conn = $conn;
//        $this->setSchemaManager($schemaManager);
//        $this->schemaTool = $schemaTool;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        $qb = $this->conn->createQueryBuilder()
            ->addSelect("DISTINCT `$this->locale` AS locale")
            ->from("`$this->table`");
        $stmt = $qb->execute();
        $locales = array_column($stmt->fetchAll(), 'locale');
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

//    /**
//     * @param EntityManager $entityManager
//     */
//    public function setEntityManager(EntityManager $entityManager)
//    {
//        $this->em = $entityManager;
//    }

//    public function setSchemaManager(AbstractSchemaManager $schemaManager)
//    {
//        $this->sm = $schemaManager;
//        Debugger::barDump($this->sm, 'schema added in schemaManager setter');
////        if (!$this->sm->tablesExist($this->table)) {
////            $table = $this->sm->createSchema()
////                ->createTable($this->table);
////            $table->addColumn($this->key, Type::STRING);
////            $table->addColumn($this->locale, Type::STRING);
////            $table->addColumn($this->message, Type::TEXT);
////            $table->addColumn($this->updatedAt, Type::DATETIME);
////            $this->sm->createTable($table);
////        }
//        Debugger::barDump($this->sm, 'schema added in schemaManager setter');
//    }

//    public function insertTable()
//    {
//        Debugger::barDump($this->sm, 'insert table');
//        $metadata = new ClassMetadata(TranslationMetadataListener::FAKE_ENTITY_NAME);
//        $builder = new ClassMetadataBuilder($metadata);
//        $builder->addField("$this->key", 'string')
//            ->addField("$this->locale", 'string')
//            ->addField("$this->message", 'text')
//            ->addField("$this->updatedAt", 'datetime');
//        $metadata = $builder->getClassMetadata();
//        $metadata->setIdentifier(array(
//            $this->key => array('type' => 'string'),
//            $this->locale => array('type' => 'string'),
//        ));
//        Debugger::barDump($metadata, 'metadata');
//        $schema = $this->schemaTool->getSchemaFromMetadata([$metadata]);
//        $queries = $schema->toSql($this->conn->getDatabasePlatform());
////        $queries = $this->schemaTool->getCreateSchemaSql([$metadata]);
////        $queries = $this->sm->createSchema()->toSql($this->conn->getDatabasePlatform());
//        Debugger::barDump($queries, 'queries');
//    }

}
