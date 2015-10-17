<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Loader;

use Kdyby\Translation\DatabaseException;
use Kdyby\Translation\DI\Configuration;
use Kdyby\Translation\NotImplementedException;
use Kdyby\Translation\Resource\DatabaseResource;
use Nette\Database\Context;
use Nette\Database\DriverException;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
class NetteDbLoader extends DatabaseLoader
{

	/**
	 * @var Context
	 */
	private $db;



	public function __construct(Context $db, Configuration $config)
	{
		parent::__construct($config);
		$this->db = $db;
	}



	/**
	 * @return array
	 */
	public function getLocales()
	{
		try {
			return $this->db
				->query('SELECT DISTINCT ' . $this->delimite($this->config->getLocaleColumn()) . ' as locale FROM ' . $this->delimite($this->config->getTableName()))
				->fetchPairs('locale', 'locale');

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	/**
	 * {@inheritdoc}
	 */
	public function setupDatabase($createSchema = FALSE)
	{
		throw new NotImplementedException;
	}



	/**
	 * @param string $locale
	 * @return array|\Nette\Database\Table\IRow[]
	 */
	protected function getTranslations($locale)
	{
		try {
			return $this->db->table($this->config->getTableName())
				->select($this->delimite($this->config->getKeyColumn()) . ' AS ' . $this->delimite('key') . ', ' .
					$this->delimite($this->config->getLocaleColumn()) . ' AS locale, ' .
					$this->delimite($this->config->getMessageColumn()) . ' AS message')
				->where($this->config->getLocaleColumn() . ' = ?', $locale)
				->fetchAll();

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
			$row = $this->db->table($this->config->getTableName())
				->select($this->delimite($this->config->getUpdatedAtColumn()) . ' AS ' . $this->delimite('updated_at'))
				->where($this->delimite($this->config->getLocaleColumn()) . ' = ?', $locale)
				->order('updated_at DESC')
				->limit(1)
				->fetch();

			$updatedAt = $row['updated_at'];
			if (empty($updatedAt)) {
				$updatedAt = new \DateTime();
				$updatedAt->setTimestamp(0);
			}

			return $updatedAt;

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	/**
	 * @return string
	 */
	protected function getResourceName()
	{
		return DatabaseResource::NETTE_DB;
	}



	private function delimite($name)
	{
		return $this->db->getConnection()->getSupplementalDriver()->delimite($name);
	}

}
