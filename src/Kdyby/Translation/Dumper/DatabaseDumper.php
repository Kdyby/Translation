<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Dumper;

use Kdyby\Translation\DI\Configuration;
use Kdyby\Translation\Helpers;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\MessageCatalogue;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
abstract class DatabaseDumper implements DumperInterface
{

	/**
	 * @var Configuration
	 */
	protected $config;



	public function __construct(Configuration $config)
	{
		$this->config = $config;
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
		if (isset($messagesArray[NULL]) && is_array($messagesArray[NULL])) { //hack for translations without domain
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

		} catch (\Exception $e) {
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
