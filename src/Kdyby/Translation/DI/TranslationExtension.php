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
use Nette\DI\Statement;
use Nette\PhpGenerator as Code;
use Nette\Reflection;
use Nette\Utils\Arrays;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Tracy;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslationExtension extends Nette\DI\CompilerExtension
{

	const LOADER_TAG = 'translation.loader';
	const DUMPER_TAG = 'translation.dumper';
	const EXTRACTOR_TAG = 'translation.extractor';

	const RESOLVER_REQUEST = 'request';
	const RESOLVER_HEADER = 'header';
	const RESOLVER_SESSION = 'session';

	/**
	 * @var array
	 */
	public $defaults = array(
		// 'whitelist' => array('cs', 'en'),
		'default' => 'en',
		// 'fallback' => array('en_US', 'en'), // using custom merge strategy becase Nette's config merger appends lists of values
		'dirs' => array('%appDir%/lang', '%appDir%/locale'),
		'cache' => 'Kdyby\Translation\Caching\PhpFileStorage',
		'debugger' => '%debugMode%',
		'resolvers' => array(
			self::RESOLVER_SESSION => FALSE,
			self::RESOLVER_REQUEST => TRUE,
			self::RESOLVER_HEADER => TRUE,
		),
	);

	/**
	 * @var array
	 */
	private $loaders;



	public function __construct()
	{
		$this->defaults['cache'] = new Statement($this->defaults['cache'], array('%tempDir%/cache'));
	}



	public function loadConfiguration()
	{
		$this->loaders = array();

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$translator = $builder->addDefinition($this->prefix('default'))
			->setClass('Kdyby\Translation\Translator', array($this->prefix('@userLocaleResolver')))
			->addSetup('?->setTranslator(?)', array($this->prefix('@userLocaleResolver.param'), '@self'))
			->setInject(FALSE);

		Validators::assertField($config, 'fallback', 'list');
		$translator->addSetup('setFallbackLocales', array($config['fallback']));

		$catalogueCompiler = $builder->addDefinition($this->prefix('catalogueCompiler'))
			->setClass('Kdyby\Translation\CatalogueCompiler', self::filterArgs($config['cache']))
			->setInject(FALSE);

		if ($config['debugger'] && interface_exists('Tracy\IBarPanel')) {
			$builder->addDefinition($this->prefix('panel'))
				->setClass('Kdyby\Translation\Diagnostics\Panel', array(dirname($builder->expand('%appDir%'))))
				->addSetup('setResourceWhitelist', array($config['whitelist']));

			$translator->addSetup('?->register(?)', array($this->prefix('@panel'), '@self'));
			$catalogueCompiler->addSetup('enableDebugMode');
		}

		$this->loadLocaleResolver($config);

		$builder->addDefinition($this->prefix('helpers'))
			->setClass('Kdyby\Translation\TemplateHelpers')
			->setFactory($this->prefix('@default') . '::createTemplateHelpers')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('fallbackResolver'))
			->setClass('Kdyby\Translation\FallbackResolver')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('catalogueFactory'))
			->setClass('Kdyby\Translation\CatalogueFactory')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('selector'))
			->setClass('Symfony\Component\Translation\MessageSelector')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('loadersInitializer'))
			->setClass('Kdyby\Translation\LoadersInitializer')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('extractor'))
			->setClass('Symfony\Component\Translation\Extractor\ChainExtractor')
			->setInject(FALSE);

		$this->loadExtractors();

		$builder->addDefinition($this->prefix('writer'))
			->setClass('Symfony\Component\Translation\Writer\TranslationWriter')
			->setInject(FALSE);

		$this->loadDumpers();

		$builder->addDefinition($this->prefix('loader'))
			->setClass('Kdyby\Translation\TranslationLoader')
			->setInject(FALSE);

		$this->loadLoaders();

		if ($this->isRegisteredConsoleExtension()) {
			$this->loadConsole($config);
		}

		$latteFactory = $builder->hasDefinition('nette.latteFactory')
			? $builder->getDefinition('nette.latteFactory')
			: $builder->getDefinition('nette.latte');

		$latteFactory
			->addSetup('Kdyby\Translation\Latte\TranslateMacros::install(?->getCompiler())', array('@self'))
			->addSetup('addFilter', array('translate', array($this->prefix('@helpers'), 'translate')))
			->addSetup('addFilter', array('getTranslator', array($this->prefix('@helpers'), 'getTranslator')));
	}



	protected function loadLocaleResolver(array $config)
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('userLocaleResolver.param'))
			->setClass('Kdyby\Translation\LocaleResolver\LocaleParamResolver')
			->setAutowired(FALSE)
			->setInject(FALSE);

		$builder->getDefinition('application')
			->addSetup('$service->onRequest[] = ?', array(array($this->prefix('@userLocaleResolver.param'), 'onRequest')));

		$builder->addDefinition($this->prefix('userLocaleResolver.acceptHeader'))
			->setClass('Kdyby\Translation\LocaleResolver\AcceptHeaderResolver')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('userLocaleResolver.session'))
			->setClass('Kdyby\Translation\LocaleResolver\SessionResolver')
			->setInject(FALSE);

		$chain = $builder->addDefinition($this->prefix('userLocaleResolver'))
			->setClass('Kdyby\Translation\IUserLocaleResolver')
			->setFactory('Kdyby\Translation\LocaleResolver\ChainResolver')
			->addSetup('addResolver', array(new Statement('Kdyby\Translation\LocaleResolver\DefaultLocale', array($config['default']))))
			->setInject(FALSE);

		$resolvers = array(
			new Statement('Kdyby\Translation\LocaleResolver\DefaultLocale', array($config['default']))
		);

		if ($config['resolvers'][self::RESOLVER_HEADER]) {
			$resolvers[] = $this->prefix('@userLocaleResolver.acceptHeader');
			$chain->addSetup('addResolver', array($this->prefix('@userLocaleResolver.acceptHeader')));
		}

		if ($config['resolvers'][self::RESOLVER_REQUEST]) {
			$resolvers[] = $this->prefix('@userLocaleResolver.param');
			$chain->addSetup('addResolver', array($this->prefix('@userLocaleResolver.param')));
		}

		if ($config['resolvers'][self::RESOLVER_SESSION]) {
			$resolvers[] = $this->prefix('@userLocaleResolver.session');
			$chain->addSetup('addResolver', array($this->prefix('@userLocaleResolver.session')));
		}

		if ($config['debugger'] && interface_exists('Tracy\IBarPanel')) {
			$builder->getDefinition($this->prefix('panel'))
				->addSetup('setLocaleResolvers', array(array_reverse($resolvers)));

			$builder->getDefinition('application')
				->addSetup('$self = $this; $service->onStartup[] = function () use ($self) { $self->getService(?); }', array($this->prefix('default')))
				->addSetup('$service->onRequest[] = ?', array(array($this->prefix('@panel'), 'onRequest')));
		}
	}



	protected function loadConsole(array $config)
	{
		$builder = $this->getContainerBuilder();

		Validators::assertField($config, 'dirs', 'list');
		$builder->addDefinition($this->prefix('console.extract'))
			->setClass('Kdyby\Translation\Console\ExtractCommand')
			->addSetup('$defaultOutputDir', array(reset($config['dirs'])))
			->setInject(FALSE)
			->addTag('kdyby.console.command', 'latte');
	}



	protected function loadDumpers()
	{
		$builder = $this->getContainerBuilder();

		foreach ($this->loadFromFile(__DIR__ . '/config/dumpers.neon') as $format => $class) {
			$builder->addDefinition($this->prefix('dumper.' . $format))
				->setClass($class)
				->addTag(self::DUMPER_TAG, $format);
		}
	}



	protected function loadLoaders()
	{
		$builder = $this->getContainerBuilder();

		foreach ($this->loadFromFile(__DIR__ . '/config/loaders.neon') as $format => $class) {
			$builder->addDefinition($this->prefix('loader.' . $format))
				->setClass($class)
				->addTag(self::LOADER_TAG, $format);
		}
	}



	protected function loadExtractors()
	{
		$builder = $this->getContainerBuilder();

		foreach ($this->loadFromFile(__DIR__ . '/config/extractors.neon') as $format => $class) {
			$builder->addDefinition($this->prefix('extractor.' . $format))
				->setClass($class)
				->addTag(self::EXTRACTOR_TAG, $format);
		}
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		Kdyby\Translation\Diagnostics\Panel::registerBluescreen();

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

		$builder->getDefinition($this->prefix('loadersInitializer'))
			->setArguments(array($this->loaders))
			->setInject(FALSE);

		foreach ($this->compiler->getExtensions() as $extension) {
			if (!$extension instanceof ITranslationProvider) {
				continue;
			}

			$config['dirs'] = array_merge($config['dirs'], array_values($extension->getTranslationResources()));
		}

		if ($dirs = array_values(array_filter($config['dirs'], callback('is_dir')))) {
			foreach ($dirs as $dir) {
				$builder->addDependency($dir);
			}

			$translator = $builder->getDefinition($this->prefix('default'));

			foreach (Arrays::flatten($this->loaders) as $format) {
				foreach (Finder::findFiles('*.*.' . $format)->from($dirs) as $file) {
					/** @var \SplFileInfo $file */
					if ($m = Strings::match($file->getFilename(), '~^(?P<domain>.*?)\.(?P<locale>[^\.]+)\.' . preg_quote($format) . '$~')) {
						if (!in_array(substr($m['locale'], 0, 2), $config['whitelist'])) {
							if ($config['debugger']) {
								$builder->getDefinition($this->prefix('panel'))
									->addSetup('addIgnoredResource', array($format, $file->getPathname(), $m['locale'], $m['domain']));
							}
							continue; // ignore
						}

						$this->validateResource($format, $file->getPathname(), $m['locale'], $m['domain']);
						$translator->addSetup('addResource', array($format, $file->getPathname(), $m['locale'], $m['domain']));
						$builder->addDependency($file->getPathname());

						if ($config['debugger']) {
							$builder->getDefinition($this->prefix('panel'))
								->addSetup('addResource', array($format, $file->getPathname(), $m['locale'], $m['domain']));
						}
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



	public function afterCompile(Code\ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		$initialize->addBody('Kdyby\Translation\Diagnostics\Panel::registerBluescreen();');
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
		return parent::getConfig($this->defaults) + array('fallback' => array('en_US'), 'whitelist' => array('cs', 'en'));
	}



	/**
	 * @param string|\stdClass $statement
	 * @return Nette\DI\Statement[]
	 */
	public static function filterArgs($statement)
	{
		return Nette\DI\Compiler::filterArguments(array(is_string($statement) ? new Nette\DI\Statement($statement) : $statement));
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
