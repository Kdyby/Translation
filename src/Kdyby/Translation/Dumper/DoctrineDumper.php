<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Dumper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Types\Type;
use Kdyby\Translation\DatabaseException;
use Kdyby\Translation\DI\Configuration;
use Kdyby\Translation\Helpers;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
class DoctrineDumper extends DatabaseDumper
{

	/**
	 * @var Connection
	 */
	private $db;



	public function __construct(Connection $db, Configuration $config)
	{
		parent::__construct($config);
		$this->db = $db;
	}



	protected function getExistingKeys($keys, $locale)
	{
		try {
			$qb = $this->db->createQueryBuilder()
				->addSelect($this->db->quoteIdentifier($this->config->getKeyColumn()) . ' AS ' . $this->db->quoteIdentifier('key'))
				->from($this->db->quoteIdentifier($this->config->getTableName()))
				->andWhere($this->db->quoteIdentifier($this->config->getLocaleColumn()) . ' = :locale')
				->andWhere($this->db->quoteIdentifier($this->config->getKeyColumn()) . ' IN (:keys)')
				->setParameter('locale', $locale)
				->setParameter('keys', $keys, Connection::PARAM_STR_ARRAY);

			return Helpers::arrayColumn($qb->execute()->fetchAll(), 'key'); //to get only one dimensional array of keys

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	protected function insert($key, $locale, $message)
	{
		try {
			$qb = $this->db->createQueryBuilder();
			$qb->insert($this->db->quoteIdentifier($this->config->getTableName()))
				->values([
					$this->db->quoteIdentifier($this->config->getKeyColumn()) => ":key",
					$this->db->quoteIdentifier($this->config->getLocaleColumn()) => ":locale",
					$this->db->quoteIdentifier($this->config->getMessageColumn()) => ":message",
					$this->db->quoteIdentifier($this->config->getUpdatedAtColumn()) => ":updatedAt",
				]);

			$qb->setParameters([
				'key' => $key,
				'locale' => $locale,
				'message' => $message,
				'updatedAt' => new \DateTime(),
			], [
				'updatedAt' => Type::DATETIME,
			]);

			$qb->execute();

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	protected function update($key, $locale, $message)
	{
		try {
			$qb = $this->db->createQueryBuilder();
			$qb->update($this->db->quoteIdentifier($this->config->getTableName()))
				->set($this->db->quoteIdentifier($this->config->getMessageColumn()), ':message')
				->set($this->db->quoteIdentifier($this->config->getUpdatedAtColumn()), ':updatedAt')
				->andWhere($this->db->quoteIdentifier($this->config->getKeyColumn()) . ' = :key')
				->andWhere($this->db->quoteIdentifier($this->config->getLocaleColumn()) . ' = :locale');

			$qb->setParameters([
				'key' => $key,
				'locale' => $locale,
				'message' => $message,
				'updatedAt' => new \DateTime(),
			], [
				'updatedAt' => Type::DATETIME,
			]);

			$qb->execute();

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	protected function beginTransaction()
	{
		$this->db->beginTransaction();
	}



	protected function commit()
	{
		$this->db->commit();
	}



	protected function rollBack()
	{
		$this->db->rollBack();
	}
}
