<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Tester;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class TestCase extends Tester\TestCase
{

	protected function createContainer()
	{
		$config = new Nette\Config\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('appDir' => __DIR__));
		Kdyby\Translation\DI\TranslationExtension::register($config);
		$config->addConfig(__DIR__ . '/../nette-reset.neon', $config::NONE);

		$container = $config->createContainer();
		/** @var \Nette\DI\Container|\SystemContainer $container */

		return $container;
	}



	protected function createTranslator()
	{
		$container = $this->createContainer();

		$translator = $container->getByType('Nette\Localization\ITranslator');
		/** @var Kdyby\Translation\Translator $translator */

		return $translator;
	}

}
