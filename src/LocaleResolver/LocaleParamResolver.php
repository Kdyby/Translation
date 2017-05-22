<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\LocaleResolver;

use Kdyby\Translation\Translator;
use Nette\Application\Application;
use Nette\Application\Request;

class LocaleParamResolver implements \Kdyby\Translation\IUserLocaleResolver
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Nette\Application\Request
	 */
	private $request;

	/**
	 * @var \Kdyby\Translation\Translator
	 */
	private $translator;

	public function setTranslator(Translator $translator)
	{
		$this->translator = $translator;
	}

	/**
	 * @param \Nette\Application\Application $sender
	 * @param \Nette\Application\Request $request
	 */
	public function onRequest(Application $sender, Request $request)
	{
		$params = $request->getParameters();
		if ($request->getMethod() === Request::FORWARD && empty($params['locale'])) {
			return;
		}

		$this->request = $request;

		if (!$this->translator) {
			return;
		}

		$this->translator->setLocale(NULL);
		$this->translator->getLocale(); // invoke resolver
	}

	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @return string|NULL
	 */
	public function resolve(Translator $translator)
	{
		if ($this->request === NULL) {
			return NULL;
		}

		$params = $this->request->getParameters();
		return !empty($params['locale']) ? $params['locale'] : NULL;
	}

}
