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
use Kdyby\Translation\Translator;
use Nette;



/**
 * When you don't want to use the param resolver,
 * you simply won't use the parameter `locale` in router
 * and will implement a signal that will call `setLocale` on this class.
 *
 * When you set the locale to this resolver, it will be stored in session
 * and forced on all other requests of the visitor, because this resolver has the highest priority.
 *
 * Get this class using autowire, but beware, use only Kdyby\Translation\LocaleResolver\SessionResolver,
 * do not try to autowire Kdyby\Translation\IUserLocaleResolver, it will fail.
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class SessionResolver extends Nette\Object implements Kdyby\Translation\IUserLocaleResolver
{

	/**
	 * @var \Nette\Http\SessionSection|\stdClass
	 */
	private $localeSession;

	/**
	 * @var \Nette\Http\IResponse
	 */
	private $httpResponse;

	/**
	 * @var \Nette\Http\Session
	 */
	private $session;



	public function __construct(Nette\Http\Session $session, Nette\Http\IResponse $httpResponse)
	{
		$this->localeSession = $session->getSection(get_class($this));
		$this->httpResponse = $httpResponse;
		$this->session = $session;
	}



	/**
	 * @param string $locale
	 */
	public function setLocale($locale = NULL)
	{
		$this->localeSession->locale = $locale;
	}



	/**
	 * @param Translator $translator
	 * @return string|NULL
	 */
	public function resolve(Translator $translator)
	{
		if (!$this->session->isStarted() && $this->httpResponse->isSent()) {
			trigger_error(
				"The advice of session locale resolver is required but the session has not been started and headers had been already sent. " .
				"Either start your sessions earlier or disabled the SessionResolver.",
				E_USER_WARNING
			);
			return NULL;
		}

		if (empty($this->localeSession->locale)) {
			return NULL;
		}

		$short = array_map(function ($locale) {
			return substr($locale, 0, 2);
		}, $translator->getAvailableLocales());

		if (!in_array(substr($this->localeSession->locale, 0, 2), $short, TRUE)) {
			return NULL;
		}

		return $this->localeSession->locale;
	}

}
