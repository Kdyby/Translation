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



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslationExtension extends Nette\DI\CompilerExtension
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

		$services = $this->loadFromFile(__DIR__ . '/services.neon');
		$this->compiler->parseServices($builder, $services, $this->name);

		$builder->addDefinition($this->prefix('userLocaleResolver'))
			->setClass('Kdyby\Translation\IUserLocaleResolver')
			->setFactory('Kdyby\Translation\LocaleResolver\ChainResolver')
			->addSetup('addResolver', array(
				new Nette\DI\Statement('Kdyby\Translation\LocaleResolver\DefaultLocale', array($config['default'])),
			))
			->addSetup('addResolver', array(
				new Nette\DI\Statement('Kdyby\Translation\LocaleResolver\AcceptHeaderResolver'),
			))
			->addSetup('addResolver', array($this->prefix('@userLocaleResolver.param')));

		$translator = $builder->getDefinition($this->prefix('default'));
		$translator->factory->arguments[3] = new Nette\DI\Statement($config['cache']);

		Validators::assertField($config, 'fallback', 'list');
		$translator->addSetup('setFallbackLocale', array($config['fallback']));

		if ($builder->parameters['debugMode']) {
			$translator->addSetup('enableDebugMode');
			$translator->addSetup('Kdyby\Translation\Diagnostics\Panel::register');
		}

		if ($this->isRegisteredConsoleExtension()) {
			Validators::assertField($config, 'dirs', 'list');
			$builder->addDefinition($this->prefix('console.extract'))
				->setClass('Kdyby\Translation\Console\ExtractCommand')
				->addSetup('$defaultOutputDir', array(reset($config['dirs'])))
				->addTag('kdyby.console.command', 'latte');
		}

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

			$builder->getDefinition($extractorId)->setAutowired(FALSE);
		}

		$writer = $builder->getDefinition($this->prefix('writer'));
		foreach ($builder->findByTag(self::DUMPER_TAG) as $dumperId => $meta) {
			Validators::assert($meta, 'string:2..');

			$writer->addSetup('addDumper', array($meta, '@' . $dumperId));

			$builder->getDefinition($dumperId)->setAutowired(FALSE);
		}

		$this->loaders = array();
		$loader = $builder->getDefinition($this->prefix('loader'));
		foreach ($builder->findByTag(self::LOADER_TAG) as $loaderId => $meta) {
			Validators::assert($meta, 'string:2..');

			$this->loaders[$loaderId][] = $meta;
			$loader->addSetup('addLoader', array($meta, '@' . $loaderId));

			$builder->getDefinition($loaderId)->setAutowired(FALSE);
		}

		$translator = $builder->getDefinition($this->prefix('default'));
		$translator->factory->arguments[4] = $this->loaders;

		if ($dirs = array_filter($config['dirs'], callback('is_dir'))) {
			foreach ($dirs as $dir) {
				$builder->addDependency($dir);
			}
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



	private function isRegisteredConsoleExtension()
	{
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof Kdyby\Console\DI\ConsoleExtension) {
				return TRUE;
			}
		}

		return FALSE;
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
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('translation', new TranslationExtension());
		};
	}

}
