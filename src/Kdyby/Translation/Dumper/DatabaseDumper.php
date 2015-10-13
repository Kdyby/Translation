<?php

namespace Kdyby\Translation\Dumper;

use Kdyby\Translation\Helpers;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\MessageCatalogue;

abstract class DatabaseDumper implements DumperInterface
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
	 */
	public function setTableName($table)
	{
		$this->table = $table;
	}

	/**
	 * @param string $key
	 * @param string $locale
	 * @param string $message
	 * @param string $updatedAt
	 */
	public function setColumnNames($key, $locale, $message, $updatedAt)
	{
		$this->key = $key;
		$this->locale = $locale;
		$this->message = $message;
		$this->updatedAt = $updatedAt;
	}

	/**
	 * Dumps the message catalogue.
	 *
	 * @param MessageCatalogue $messages The message catalogue
	 * @param array $options Options that are used by the dumper
	 */
	public function dump(MessageCatalogue $messages, $options = array())
	{
		$messagesArray = $messages->all();
		if (isset($messagesArray[NULL]) && is_array($messagesArray[NULL])) {    //bugfix for translations without domain
			$messagesArray += $messagesArray[NULL];
			unset($messagesArray[NULL]);
		}
		Helpers::flatten($messagesArray);
		$locale = $messages->getLocale();
		$keys = array_keys($messagesArray);

		$this->beginTransaction();
		try {
			$existingKeys = $this->getExistingKeys($keys, $locale);
			foreach ($messagesArray as $key => $message) {
				if ($message !== NULL) {
					if (in_array($key, $existingKeys)) {
						$this->update($key, $locale, $message);
					} else {
						$this->insert($key, $locale, $message);
					}
				}
			}
			$this->commit();
		} catch(\Exception $e) {
			$this->rollBack();
			throw $e;
		}
	}

	abstract protected function getExistingKeys($keys, $locale);

	abstract protected function beginTransaction();

	abstract protected function commit();

	abstract protected function rollBack();

	abstract protected function insert($key, $locale, $message);

	abstract protected function update($key, $locale, $message);

}
