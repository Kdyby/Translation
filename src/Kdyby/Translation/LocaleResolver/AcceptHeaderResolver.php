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
use Symfony\Component\Locale\Locale;



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
	 * @return string
	 */
	public function resolve()
	{
//		Locale::acceptFromHttp($this->httpRequest->getHeader('Accept-Language'));
		// $this->locale = $this->httpRequest->detectLanguage(array_merge(array(), array($this->setFallbackLocale())));
		return $this->httpRequest->detectLanguage(array('cs_CZ'));
	}

}
