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
class LocaleParamResolver extends Nette\Object implements Kdyby\Translation\IUserLocaleResolver, Kdyby\Events\Subscriber
{

	/**
	 * @var Nette\Application\Request
	 */
	private $request;



	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			'Nette\\Application\\Application::onRequest'
		);
	}



	/**
	 * @param Application $sender
	 * @param Request $request
	 */
	public function onRequest(Application $sender, Request $request)
	{
		$this->request = $request;
	}



	/**
	 * @return string|NULL
	 */
	public function resolve(Kdyby\Translation\Translator $translator)
	{
		if ($this->request === NULL) {
			return NULL;
		}

		$params = $this->request->getParameters();
		return isset($params['locale']) ? $params['locale'] : NULL;
	}

}

