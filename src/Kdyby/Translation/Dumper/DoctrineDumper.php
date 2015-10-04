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
		if( !function_exists( 'array_column' ) ) {                  //just because of PHP 5.4, where function array_column is not present. Fuck you, PHP 5.4
			function array_column( array $input, $column_key, $index_key = NULL ) {
				$result = array();
				foreach( $input as $k => $v )
					$result[ $index_key ? $v[ $index_key ] : $k ] = $v[ $column_key ];
				return $result;
			}
		}

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
		$qb->update($this->connection->quoteIdentifier($this->table))
			->set($this->connection->quoteIdentifier($this->message), ':message')
			->set($this->connection->quoteIdentifier($this->updatedAt), ':updatedAt')
			->andWhere($this->connection->quoteIdentifier($this->key).' = :key')
			->andWhere($this->connection->quoteIdentifier($this->locale).' = :locale')
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
