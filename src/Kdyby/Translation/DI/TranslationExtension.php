<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\DI;

use Kdyby;
use Nette;
use Nette\Utils\Arrays;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Nette\Utils\Validators;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslationExtension extends Nette\Config\CompilerExtension
{

	const LOADER_TAG = 'translation.loader';
	const DUMPER_TAG = 'translation.dumper';
	const EXTRACTOR_TAG = 'translation.extractor';

	/**
	 * @var array
	 */
	public $defaults = array(
		'default' => 'en',
		// 'fallback' => array('en_US', 'en'),
		// 'dirs' => array('%appDir%/lang'),
		'cache' => '@nette.templateCacheStorage',
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$services = $this->loadFromFile(__DIR__ . '/services.neon');
		$this->compiler->parseServices($builder, $services, $this->name);

		$translator = $builder->getDefinition($this->prefix('default'));
		$translator->factory->arguments[3] = new Nette\DI\Statement($config['cache']);

		Validators::assertField($config, 'fallback', 'list');
		$translator->addSetup('setFallbackLocale', array($config['fallback']));

		if ($builder->parameters['debugMode']) {
			$translator->addSetup('enableDebugMode');
			$translator->addSetup('Kdyby\Translation\Diagnostics\Panel::register');
		}

		Validators::assertField($config, 'dirs', 'list');
		$builder->getDefinition($this->prefix('console.extract'))
			->addSetup('$defaultOutputDir', array(reset($config['dirs'])));

		$builder->parameters['translation'] = array('defaultLocale' => $config['default']);

		$builder->getDefinition('nette.latte')
			->addSetup('Kdyby\Translation\Latte\TranslateMacros::install(?->compiler)', array('@self'));
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$extractor = $builder->getDefinition($this->prefix('extractor'));
		foreach ($builder->findByTag(self::EXTRACTOR_TAG) as $extractorId => $meta) {
			Validators::assert($meta, 'string:2..');

			$extractor->addSetup('addExtractor', array($meta, '@' . $extractorId));

			$builder->getDefinition($extractorId)->setAutowired(FALSE)->setInject(FALSE);
		}

		$writer = $builder->getDefinition($this->prefix('writer'));
		foreach ($builder->findByTag(self::DUMPER_TAG) as $dumperId => $meta) {
			Validators::assert($meta, 'string:2..');

			$writer->addSetup('addDumper', array($meta, '@' . $dumperId));

			$builder->getDefinition($dumperId)->setAutowired(FALSE)->setInject(FALSE);
		}

		$loaders = array();
		$loader = $builder->getDefinition($this->prefix('loader'));
		foreach ($builder->findByTag(self::LOADER_TAG) as $loaderId => $meta) {
			Validators::assert($meta, 'string:2..');

			$loaders[$loaderId][] = $meta;
			$loader->addSetup('addLoader', array($meta, '@' . $loaderId));

			$builder->getDefinition($loaderId)->setAutowired(FALSE)->setInject(FALSE);
		}

		$translator = $builder->getDefinition($this->prefix('default'));
		$translator->factory->arguments[4] = $loaders;

		if ($dirs = array_filter($config['dirs'], callback('is_dir'))) {
			foreach (Arrays::flatten($loaders) as $format) {
				foreach (Finder::findFiles('*.*.' . $format)->from($dirs) as $file) {
					/** @var \SplFileInfo $file */
					if ($m = Strings::match($file->getFilename(), '~^(?P<domain>.*?)\.(?P<locale>[^\.]+)\.' . preg_quote($format) . '$~')) {
						$translator->addSetup('addResource', array($format, $file->getPathname(), $m ['locale'], $m ['domain']));
					}
				}
			}
		}
	}



	/**
	 * {@inheritdoc}
	 */
	public function getConfig(array $defaults = NULL, $expand = TRUE)
	{
		return parent::getConfig($this->defaults) + $this->compiler->getContainerBuilder()
				->expand(array('fallback' => array('en_US', 'en'), 'dirs' => array('%appDir%/lang')));
	}



	/**
	 * @param \Nette\Config\Configurator $configurator
	 */
	public static function register(Nette\Config\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\Config\Compiler $compiler) {
			$compiler->addExtension('translation', new TranslationExtension());
		};
	}

}

