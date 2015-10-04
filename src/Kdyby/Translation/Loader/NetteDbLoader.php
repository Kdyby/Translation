<?php

namespace Kdyby\Translation\Loader;

use Kdyby\Translation\Resource\DatabaseResource;
use Nette\Database\Context;
use Nette\Database\DriverException;

class NetteDbLoader extends DatabaseLoader
{

	/** @var Context */
	private $db;

	/**
	 * @param Context $db
	 */
	public function __construct(Context $db)
	{
		$this->db = $db;
	}

	/**
	 * @return array
	 */
	public function getLocales()
	{
		try {
			$stmt = $this->db->query('SELECT DISTINCT '.$this->delimite($this->locale).' as locale FROM '.$this->delimite($this->table));
			$locales = $stmt->fetchPairs('locale', 'locale');
		} catch(DriverException $e) {
			$locales = array();
		}
		return $locales;
	}

	protected function getResourceName()
	{
		return DatabaseResource::NETTE_DB;
	}

	protected function getTranslations($locale)
	{
		$stmt = $this->db->table($this->table)
			->select($this->delimite($this->key).' AS '.$this->delimite('key').', '.$this->delimite($this->locale).' AS locale, '.$this->delimite($this->message).' AS message')
			->where("$this->locale = ?", $locale);
		return $stmt->fetchAll();
	}

	/**
	 * @param $locale
	 * @return \DateTime
	 */
	protected function getLastUpdate($locale)
	{
		$updatedAt = $this->db->table($this->table)
			->select($this->delimite($this->updatedAt).' AS '.$this->delimite('updated_at'))
			->where($this->delimite($this->locale).' = ?', $locale)
			->order('updated_at DESC')
			->limit(1)
			->fetchField('updated_at');
		if ($updatedAt === NULL) {
			$updatedAt = new \DateTime();
			$updatedAt->setTimestamp(0);
		}
		return $updatedAt;
	}

	private function delimite($name)
	{
		return $this->db->getConnection()->getSupplementalDriver()->delimite($name);
	}
}
