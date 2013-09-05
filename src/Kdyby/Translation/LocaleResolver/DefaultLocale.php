<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\LocaleResolver;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DefaultLocale extends Nette\Object implements Kdyby\Translation\IUserLocaleResolver
{

	/**
	 * @var string
	 */
	private $default;



	/**
	 * @param string $default
	 */
	public function __construct($default)
	{
		$this->default = $default;
	}



	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @return string|NULL
	 */
	public function resolve(Kdyby\Translation\Translator $translator)
	{
		return $this->default;
	}

}
