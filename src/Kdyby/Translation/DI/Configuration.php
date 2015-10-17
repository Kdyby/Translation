<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\DI;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Configuration
{

	/**
	 * @var string
	 */
	private $table = 'translations';

	/**
	 * @var string
	 */
	private $key = 'key';

	/**
	 * @var string
	 */
	private $locale = 'locale';

	/**
	 * @var string
	 */
	private $message = 'message';

	/**
	 * @var string
	 */
	private $updatedAt = 'updated_at';



	/**
	 * @param string $table
	 */
	public function setTableName($table)
	{
		$this->table = $table;
	}



	/**
	 * @return string
	 */
	public function getTableName()
	{
		return $this->table;
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
	 * @return string
	 */
	public function getKeyColumn()
	{
		return $this->key;
	}



	/**
	 * @return string
	 */
	public function getLocaleColumn()
	{
		return $this->locale;
	}



	/**
	 * @return string
	 */
	public function getMessageColumn()
	{
		return $this->message;
	}



	/**
	 * @return string
	 */
	public function getUpdatedAtColumn()
	{
		return $this->updatedAt;
	}

}
