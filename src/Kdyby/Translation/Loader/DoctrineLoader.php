<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Loader;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Types\Type;
use Kdyby\Translation\DatabaseException;
use Kdyby\Translation\DI\Configuration;
use Kdyby\Translation\Helpers;
use Kdyby\Translation\Resource\DatabaseResource;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
class DoctrineLoader extends DatabaseLoader
{

	/**
	 * @var Connection
	 */
	private $db;



	public function __construct(Connection $conn, Configuration $config)
	{
		parent::__construct($config);
		$this->db = $conn;
	}



	/**
	 * @return array
	 */
	public function getLocales()
	{
		try {
			$qb = $this->db->createQueryBuilder()
				->addSelect('DISTINCT ' . $this->db->quoteIdentifier($this->config->getLocaleColumn()) . ' AS locale')
				->from($this->db->quoteIdentifier($this->config->getTableName()));
			return Helpers::arrayColumn($qb->execute()->fetchAll(), 'locale', 'locale');

		} catch (TableNotFoundException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	/**
	 * {@inheritdoc}
	 */
	public function setupDatabase($createSchema = FALSE)
	{
		$schemaManager = $this->db->getSchemaManager();
		$platform = $this->db->getDatabasePlatform();

		if (!$schemaManager->tablesExist($this->config->getTableName())) {
			$table = $schemaManager->createSchema()->createTable($this->config->getTableName());
			$table->addColumn($this->config->getKeyColumn(), Type::STRING);
			$table->addColumn($this->config->getLocaleColumn(), Type::STRING);
			$table->addColumn($this->config->getMessageColumn(), Type::TEXT);
			$table->addColumn($this->config->getUpdatedAtColumn(), Type::DATETIME);
			$table->setPrimaryKey(array($this->config->getKeyColumn(), $this->config->getLocaleColumn()));
			$table->addIndex(array($this->config->getUpdatedAtColumn()));

		} else {
			$table = $schemaManager->createSchema()->getTable($this->config->getTableName());
		}

		if ($createSchema === TRUE) {
			try {
				$schemaManager->createTable($table);

			} catch (DriverException $e) {
				throw new DatabaseException($e->getMessage(), 0, $e);
			}
		}

		return $platform->getCreateTableSQL($table);
	}



	protected function getTranslations($locale)
	{
		try {
			$qb = $this->db->createQueryBuilder()
				->addSelect($this->db->quoteIdentifier($this->config->getKeyColumn()) . ' AS ' . $this->db->quoteIdentifier('key'))
				->addSelect($this->db->quoteIdentifier($this->config->getLocaleColumn()) . ' AS locale')
				->addSelect($this->db->quoteIdentifier($this->config->getMessageColumn()) . ' AS message')
				->from($this->db->quoteIdentifier($this->config->getTableName()))
				->where($this->db->quoteIdentifier($this->config->getLocaleColumn()) . " = :locale")
				->setParameter('locale', $locale);

			return $qb->execute()->fetchAll();

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	/**
	 * @param string $locale
	 * @return \DateTime
	 */
	protected function getLastUpdate($locale)
	{
		try {
			$updatedAt = $this->db->createQueryBuilder()
				->addSelect($this->db->quoteIdentifier($this->config->getUpdatedAtColumn()) . ' AS ' . $this->db->quoteIdentifier('updated_at'))
				->from($this->db->quoteIdentifier($this->config->getTableName()))
				->where($this->db->quoteIdentifier($this->config->getLocaleColumn()) . ' = :locale')
				->orderBy('updated_at', Criteria::DESC)
				->setMaxResults(1)
				->setParameter('locale', $locale)
				->execute()->fetchColumn();

			$dateTime = new \DateTime($updatedAt);
			if ($updatedAt === NULL) {
				$dateTime->setTimestamp(0);
			}

			return $dateTime;

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	protected function getResourceName()
	{
		return DatabaseResource::DOCTRINE;
	}

}
