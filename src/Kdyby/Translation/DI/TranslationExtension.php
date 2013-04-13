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
use Kdyby\Translation\InvalidResourceException;
use Nette;
use Nette\Reflection;
use Nette\Utils\Arrays;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Symfony\Component\Translation\Loader\LoaderInterface;



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

	/**
	 * @var array
	 */
	private $loaders;



	public function loadConfiguration()
	{
		$this->loaders = array();

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();
		$builder->parameters['translation'] = array('defaultLocale' => $config['default']);

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

		$builder->getDefinition('application')
			->addSetup('$service->onRequest[] = $this->getService(?)->onRequest', array($this->prefix('userLocaleResolver.param')));
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

		$this->loaders = array();
		$loader = $builder->getDefinition($this->prefix('loader'));
		foreach ($builder->findByTag(self::LOADER_TAG) as $loaderId => $meta) {
			Validators::assert($meta, 'string:2..');

			$this->loaders[$loaderId][] = $meta;
			$loader->addSetup('addLoader', array($meta, '@' . $loaderId));

			$builder->getDefinition($loaderId)->setAutowired(FALSE)->setInject(FALSE);
		}

		$translator = $builder->getDefinition($this->prefix('default'));
		$translator->setInject(FALSE);
		$translator->factory->arguments[4] = $this->loaders;

		if ($dirs = array_filter($config['dirs'], callback('is_dir'))) {
			foreach (Arrays::flatten($this->loaders) as $format) {
				foreach (Finder::findFiles('*.*.' . $format)->from($dirs) as $file) {
					/** @var \SplFileInfo $file */
					if ($m = Strings::match($file->getFilename(), '~^(?P<domain>.*?)\.(?P<locale>[^\.]+)\.' . preg_quote($format) . '$~')) {
						$this->validateResource($format, $file->getPathname(), $m['locale'], $m['domain']);
						$translator->addSetup('addResource', array($format, $file->getPathname(), $m['locale'], $m['domain']));
						$builder->addDependency($file->getPathname());
					}
				}
			}
		}
	}



	protected function validateResource($format, $file, $locale, $domain)
	{
		$builder = $this->getContainerBuilder();

		foreach ($this->loaders as $id => $knownFormats) {
			if (!in_array($format, $knownFormats, TRUE)) {
				continue;
			}

			try {
				$def = $builder->getDefinition($id);
				$refl = Reflection\ClassType::from($def->factory ? $def->factory->entity : $def->class);
				if (($method = $refl->getConstructor()) && $method->getNumberOfRequiredParameters() > 1) {
					continue;
				}

				$loader = $refl->newInstance();
				if (!$loader instanceof LoaderInterface) {
					continue;
				}

			} catch (\ReflectionException $e) {
				continue;
			}

			try {
				$loader->load($file, $locale, $domain);

			} catch (\Exception $e) {
				throw new InvalidResourceException("Resource $file is not valid and cannot be loaded.", 0, $e);
			}

			break;
		}
	}



	/**
	 * {@inheritdoc}
	 */
	public function getConfig(array $defaults = NULL, $expand = TRUE)
	{
		return parent::getConfig($this->defaults) + $this->compiler->getContainerBuilder()
				->expand(array('fallback' => array('en_US'), 'dirs' => array('%appDir%/lang')));
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
