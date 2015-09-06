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
            if( !function_exists( 'array_column' ) ) {                  //just because of PHP 5.4, where function array_column is not present. Fuck you, PHP 5.4
                function array_column( array $input, $column_key, $index_key = null ) {
                    $result = array();
                    foreach( $input as $k => $v )
                        $result[ $index_key ? $v[ $index_key ] : $k ] = $v[ $column_key ];
                    return $result;
                }
            }
        } catch(TableNotFoundException $e) {
            $locales = array();
        }
        return $locales;
    }


    protected function getResourceName()
    {
        return DatabaseResource::DOCTRINE;
    }

    protected function getTranslations($locale)
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
    protected function getLastUpdate($locale)
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
