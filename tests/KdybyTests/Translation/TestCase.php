<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Translation;

use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Monolog\DI\MonologExtension;
use Kdyby\Translation\DI\TranslationExtension;
use Nette\Configurator;
use Nette\Localization\ITranslator;

abstract class TestCase extends \Tester\TestCase
{

	/**
	 * @param string|NULL $configName
	 * @return \Nette\DI\Container
	 */
	protected function createContainer($configName = NULL)
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['appDir' => __DIR__]);
		TranslationExtension::register($config);
		MonologExtension::register($config);
		ConsoleExtension::register($config);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		if ($configName) {
			$config->addConfig(__DIR__ . '/config/' . $configName . '.neon');
		}

		return $config->createContainer();
	}

	/**
	 * @param string|NULL $configName
	 * @return \Kdyby\Translation\Translator
	 */
	protected function createTranslator($configName = NULL)
	{
		$container = $this->createContainer($configName);
		/** @var \Kdyby\Translation\Translator $translator */
		$translator = $container->getByType(ITranslator::class);
		// type hacking
		return $translator;
	}

}
