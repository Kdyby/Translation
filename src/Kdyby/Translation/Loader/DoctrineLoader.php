<?php

namespace Kdyby\Translation\Loader;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Kdyby\Translation\Resource\DatabaseResource;

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
		if( !function_exists( 'array_column' ) ) {                  //just because of PHP 5.4, where function array_column is not present. Fuck you, PHP 5.4
			function array_column( array $input, $column_key, $index_key = NULL ) {
				$result = array();
				foreach( $input as $k => $v )
					$result[ $index_key ? $v[ $index_key ] : $k ] = $v[ $column_key ];
				return $result;
			}
		}

		$qb = $this->conn->createQueryBuilder()
			->addSelect('DISTINCT '.$this->conn->quoteIdentifier($this->locale).' AS locale')
			->from($this->conn->quoteIdentifier($this->table));
		try {
			$stmt = $qb->execute();
			$locales = array_column($stmt->fetchAll(), 'locale');
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
			->addSelect($this->conn->quoteIdentifier($this->key).' AS '.$this->conn->quoteIdentifier('key'))
			->addSelect($this->conn->quoteIdentifier($this->locale).' AS locale')
			->addSelect($this->conn->quoteIdentifier($this->message).' AS message')
			->from($this->conn->quoteIdentifier($this->table))
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
			->addSelect($this->conn->quoteIdentifier($this->updatedAt).' AS '.$this->conn->quoteIdentifier('updated_at'))
			->from($this->conn->quoteIdentifier($this->table))
			->where($this->conn->quoteIdentifier($this->locale).' = :locale')
			->orderBy('updated_at', Criteria::DESC)
			->setMaxResults(1)
			->setParameter('locale', $locale);
		$updatedAt = $qb->execute()->fetchColumn();
		$dateTime = new \DateTime($updatedAt);
		if ($updatedAt === NULL) {
			$dateTime->setTimestamp(0);
		}
		return $dateTime;
	}

}
