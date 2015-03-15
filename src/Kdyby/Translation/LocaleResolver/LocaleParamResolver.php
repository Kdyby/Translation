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
use Nette\Application\Application;
use Nette\Application\Request;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class LocaleParamResolver extends Nette\Object implements Kdyby\Translation\IUserLocaleResolver
{

	/**
	 * @var Nette\Application\Request
	 */
	private $request;

	/**
	 * @var Kdyby\Translation\Translator
	 */
	private $translator;



	public function setTranslator(Kdyby\Translation\Translator $translator)
	{
		$this->translator = $translator;
	}



	/**
	 * @param Application $sender
	 * @param Request $request
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
	public function resolve(Kdyby\Translation\Translator $translator)
	{
		if ($this->request === NULL) {
			return NULL;
		}

		$params = $this->request->getParameters();
		return !empty($params['locale']) ? $params['locale'] : NULL;
	}

}
