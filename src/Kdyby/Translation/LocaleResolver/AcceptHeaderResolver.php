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
class AcceptHeaderResolver extends Nette\Object implements Kdyby\Translation\IUserLocaleResolver
{

	/**
	 * @var \Nette\Http\Request
	 */
	private $httpRequest;



	/**
	 * @param Nette\Http\Request $httpRequest
	 */
	public function __construct(Nette\Http\Request $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}



	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @return string|NULL
	 */
	public function resolve(Kdyby\Translation\Translator $translator)
	{
		$short = array_map(function ($locale) {
			return substr($locale, 0, 2);
		}, $translator->getAvailableLocales());

		return $this->httpRequest->detectLanguage($short) ?: NULL;
	}

}
