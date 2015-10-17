<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Dumper;

use Kdyby\Translation\DatabaseException;
use Kdyby\Translation\DI\Configuration;
use Nette\Database\Context;
use Nette\Database\DriverException;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
class NetteDbDumper extends DatabaseDumper
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



	protected function getExistingKeys($keys, $locale)
	{
		try {
			return $this->db->table($this->config->getTableName())
				->select($this->delimite($this->config->getKeyColumn()) . ' AS ' . $this->delimite('key'))
				->where($this->delimite($this->config->getLocaleColumn()) . ' = ?', $locale)
				->where($this->delimite($this->config->getKeyColumn()) . ' IN (?)', $keys)
				->fetchPairs('key', 'key'); //to get only one dimensional array of keys

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	protected function insert($key, $locale, $message)
	{
		try {
			$this->db->table($this->config->getTableName())->insert([
				$this->config->getKeyColumn() => $key,
				$this->config->getLocaleColumn() => $locale,
				$this->config->getMessageColumn() => $message,
				$this->config->getUpdatedAtColumn() => new \DateTime(),
			]);

		} catch (DriverException $e) {
			throw new DatabaseException($e->getMessage(), 0, $e);
		}
	}



	protected function update($key, $locale, $message)
	{
		try {
			$this->db->table($this->config->getTableName())
				->where($this->config->getKeyColumn() . " = ?", $key)
				->where($this->config->getLocaleColumn() . " = ?", $locale)
				->update([
					$this->config->getMessageColumn() => $message,
					$this->config->getUpdatedAtColumn() => new \DateTime(),
				]);

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



	private function delimite($name)
	{
		return $this->db->getConnection()->getSupplementalDriver()->delimite($name);
	}
}
