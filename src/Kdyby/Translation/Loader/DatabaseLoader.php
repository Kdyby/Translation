<?php

namespace Kdyby\Translation\Loader;

use Kdyby\Translation\MessageCatalogue;
use Kdyby\Translation\Resource\DatabaseResource;
use Kdyby\Translation\Translator;
use Nette\Utils\Strings;

abstract class DatabaseLoader implements IDatabaseLoader
{

	/** @var string */
	protected $table = 'translations';

	/** @var string */
	protected $key = 'key';

	/** @var string */
	protected $locale = 'locale';

	/** @var string */
	protected $message = 'message';

	/** @var string */
	protected $updatedAt = 'updated_at';

	/**
	 * @param string $table
	 * @return $this
	 */
	public function setTableName($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @param string $key
	 * @param string $locale
	 * @param string $message
	 * @param string $updatedAt
	 * @return $this
	 */
	public function setColumnNames($key, $locale, $message, $updatedAt)
	{
		$this->key = $key;
		$this->locale = $locale;
		$this->message = $message;
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function load($resource, $locale, $domain = NULL)
	{
		$catalogue = new MessageCatalogue($locale);

		$translations = $this->getTranslations($locale);
		foreach($translations as $translation) {
			if ($domain === NULL) {
				$key = $translation['key'];
				if (Strings::contains($key, '.')) {
					if (function_exists('mb_strpos')) {
						$prefix = Strings::substring($key, 0, mb_strpos($key, '.'));
					} else {
						$prefix = Strings::substring($key, 0, strpos($key, '.'));
					}
					$key = Strings::substring($key, Strings::length($prefix) + 1);  //plus one because of dot
				} else {
					$prefix = $domain;
				}
				$catalogue->set($key, $translation['message'], $prefix);
			} else {
				$catalogue->set($translation['key'], $translation['message'], $domain);
			}
		}

		$catalogue->addResource(new DatabaseResource($resource, $this->getLastUpdate($locale)->getTimestamp()));

		return $catalogue;
	}

	/**
	 * @return array
	 */
	abstract public function getLocales();

	/**
	 * @param $locale
	 * @return \DateTime
	 */
	abstract protected function getLastUpdate($locale);

	public function addResources(Translator $translator)
	{
		foreach ($this->getLocales() as $locale) {
			$translator->addResource('database', $this->getResourceName(), $locale);
		}
	}

	abstract protected function getResourceName();

	abstract protected function getTranslations($locale);
}
