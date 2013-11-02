<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Kdyby;
use Nette;
use Nette\DI\Container;
use Symfony\Component\Translation\Loader\LoaderInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class LoadersInitializer extends Nette\Object
{

	/**
	 * @var \Nette\DI\Container
	 */
	private $container;

	/**
	 * @var array
	 */
	private $loaderIds;

	/**
	 * @var array
	 */
	private $initialized = array();



	/**
	 * @param Container $container A ContainerInterface instance
	 * @param array $loaderIds An array of loader Ids
	 */
	public function __construct($loaderIds = array(), Container $container)
	{
		$this->loaderIds = $loaderIds;
		$this->container = $container;
	}



	public function initialize(Translator $translator)
	{
		if (isset($this->initialized[$oid = spl_object_hash($translator)])) {
			return;
		}

		foreach ($this->loaderIds as $serviceId => $aliases) {
			foreach ($aliases as $alias) {
				/** @var LoaderInterface $loader */
				$loader = $this->container->getService($serviceId);

				$translator->addLoader($alias, $loader);
			}
		}

		$this->initialized[$oid] = TRUE;
	}

}
