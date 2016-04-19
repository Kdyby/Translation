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
use Kdyby\Translation\Diagnostics\Panel;
use Nette;
use Nette\Utils\ObjectMixin;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator as BaseTranslator;



/**
 * Translator.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class Translator extends BaseTranslator implements ITranslator
{

	/**
	 * @var IUserLocaleResolver
	 */
	private $localeResolver;

	/**
	 * @var CatalogueCompiler
	 */
	private $catalogueCompiler;

	/**
	 * @var FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var IResourceLoader
	 */
	private $translationsLoader;

	/**
	 * @var LoggerInterface
	 */
	private $psrLogger;

	/**
	 * @var Panel
	 */
	private $panel;

	/**
	 * @var array
	 */
	private $availableResourceLocales = [];

	/**
	 * @var string
	 */
	private $defaultLocale;

	/**
	 * @var string
	 */
	private $localeWhitelist;



	/**
	 * @param IUserLocaleResolver $localeResolver
	 * @param MessageSelector $selector The message selector for pluralization
	 * @param CatalogueCompiler $catalogueCompiler
	 * @param FallbackResolver $fallbackResolver
	 * @param IResourceLoader $loader
	 */
	public function __construct(IUserLocaleResolver $localeResolver, MessageSelector $selector,
		CatalogueCompiler $catalogueCompiler, FallbackResolver $fallbackResolver, IResourceLoader $loader)
	{
		$this->localeResolver = $localeResolver;
		$this->catalogueCompiler = $catalogueCompiler;
		$this->fallbackResolver = $fallbackResolver;
		$this->translationsLoader = $loader;

		parent::__construct(NULL, $selector);
	}



	/**
	 * @internal
	 * @param Panel $panel
	 */
	public function injectPanel(Panel $panel)
	{
		$this->panel = $panel;
	}



	/**
	 * @param LoggerInterface|NULL $logger
	 */
	public function injectPsrLogger(LoggerInterface $logger = NULL)
	{
		$this->psrLogger = $logger;
	}



	/**
	 * Translates the given string.
	 *
	 * @param string  $message    The message id
	 * @param integer $count      The number to use to find the indice of the message
	 * @param array   $parameters An array of parameters for the message
	 * @param string  $domain     The domain for the message
	 * @param string  $locale     The locale
	 *
	 * @return string
	 */
	public function translate($message, $count = NULL, $parameters = [], $domain = NULL, $locale = NULL)
	{
		if ($message instanceof Phrase) {
			return $message->translate($this);
		}

		if (is_array($count)) {
			$locale = $domain ?: NULL;
			$domain = $parameters ?: NULL;
			$parameters = $count;
			$count = NULL;
		}

		if (empty($message)) {
			return $message;

		} elseif ($message instanceof Nette\Utils\Html) {
			if ($this->panel) {
				$this->panel->markUntranslated($message, $domain);
			}
			return $message; // todo: what now?
		}

		$tmp = [];
		foreach ($parameters as $key => $val) {
			$tmp['%' . trim($key, '%') . '%'] = $val;
		}
		$parameters = $tmp;

		if ($count !== NULL && is_scalar($count)) {
			return $this->transChoice($message, $count, $parameters + ['%count%' => $count], $domain, $locale);
		}

		return $this->trans($message, $parameters, $domain, $locale);
	}



	/**
	 * {@inheritdoc}
	 */
	public function trans($message, array $parameters = [], $domain = NULL, $locale = NULL)
	{
		if ($message instanceof Phrase) {
			return $message->translate($this);
		}

		if ($domain === NULL) {
			list($domain, $id) = $this->extractMessageDomain($message);

		} else {
			$id = $message;
		}

		$result = parent::trans($id, $parameters, $domain, $locale);
		if ($result === "\x01") {
			$this->logMissingTranslation($message, $domain, $locale);
			if ($this->panel !== NULL) {
				$this->panel->markUntranslated($message, $domain);
			}
			$result = strtr($message, $parameters);
		}

		return $result;
	}



	/**
	 * {@inheritdoc}
	 */
	public function transChoice($message, $number, array $parameters = [], $domain = NULL, $locale = NULL)
	{
		if ($message instanceof Phrase) {
			return $message->translate($this);
		}

		if ($domain === NULL) {
			list($domain, $id) = $this->extractMessageDomain($message);

		} else {
			$id = $message;
		}

		try {
			$result = parent::transChoice($id, $number, $parameters, $domain, $locale);

		} catch (\Exception $e) {
			$result = $id;
			if ($this->panel !== NULL) {
				$this->panel->choiceError($e, $domain);
			}
		}

		if ($result === "\x01") {
			$this->logMissingTranslation($message, $domain, $locale);
			if ($this->panel !== NULL) {
				$this->panel->markUntranslated($message, $domain);
			}
			$result = strtr($message, $parameters);
		}

		return $result;
	}



	/**
	 * @param string $format
	 * @param LoaderInterface $loader
	 */
	public function addLoader($format, LoaderInterface $loader)
	{
		parent::addLoader($format, $loader);
		$this->translationsLoader->addLoader($format, $loader);
	}



	/**
	 * @return \Symfony\Component\Translation\Loader\LoaderInterface[]
	 */
	protected function getLoaders()
	{
		return $this->translationsLoader->getLoaders();
	}



	/**
	 * @param array $whitelist
	 * @return Translator
	 */
	public function setLocaleWhitelist(array $whitelist = NULL)
	{
		$this->localeWhitelist = self::buildWhitelistRegexp($whitelist);
	}



	/**
	 * {@inheritdoc}
	 */
	public function addResource($format, $resource, $locale, $domain = NULL)
	{
		if ($this->localeWhitelist && !preg_match($this->localeWhitelist, $locale)) {
			if ($this->panel) {
				$this->panel->addIgnoredResource($format, $resource, $locale, $domain);
			}
			return;
		}

		parent::addResource($format, $resource, $locale, $domain);
		$this->catalogueCompiler->addResource($format, $resource, $locale, $domain);
		$this->availableResourceLocales[$locale] = TRUE;

		if ($this->panel) {
			$this->panel->addResource($format, $resource, $locale, $domain);
		}
	}



	/**
	 * {@inheritdoc}
	 */
	public function setFallbackLocales(array $locales)
	{
		parent::setFallbackLocales($locales);
		$this->fallbackResolver->setFallbackLocales($locales);
	}



	/**
	 * Returns array of locales from given resources
	 *
	 * @return array
	 */
	public function getAvailableLocales()
	{
		$locales = array_keys($this->availableResourceLocales);
		sort($locales);
		return $locales;
	}



	/**
	 * {@inheritdoc}
	 */
	public function getLocale()
	{
		if ($this->locale === NULL) {
			$this->setLocale($this->localeResolver->resolve($this));
		}

		return $this->locale;
	}



	/**
	 * @return string
	 */
	public function getDefaultLocale()
	{
		return $this->defaultLocale;
	}



	/**
	 * @param string $locale
	 * @return Translator
	 */
	public function setDefaultLocale($locale)
	{
		$this->assertValidLocale($locale);
		$this->defaultLocale = $locale;
		return $this;
	}



	/**
	 * @param string $messagePrefix
	 * @return ITranslator
	 */
	public function domain($messagePrefix)
	{
		return new PrefixedTranslator($messagePrefix, $this);
	}



	/**
	 * @return TemplateHelpers
	 */
	public function createTemplateHelpers()
	{
		return new TemplateHelpers($this);
	}



	/**
	 * {@inheritdoc}
	 */
	protected function loadCatalogue($locale)
	{
		if (empty($locale)) {
			throw new InvalidArgumentException("Invalid locale.");
		}

		if (isset($this->catalogues[$locale])) {
			return;
		}

		$this->catalogues = $this->catalogueCompiler->compile($this, $this->catalogues, $locale);
	}



	/**
	 * {@inheritdoc}
	 */
	protected function computeFallbackLocales($locale)
	{
		return $this->fallbackResolver->compute($this, $locale);
	}



	/**
	 * Asserts that the locale is valid, throws an Exception if not.
	 *
	 * @param string $locale Locale to tests
	 * @throws \InvalidArgumentException If the locale contains invalid characters
	 */
	protected function assertValidLocale($locale)
	{
		if (preg_match('~^[a-z0-9@_\\.\\-]*\z~i', $locale) !== 1) {
			throw new \InvalidArgumentException(sprintf('Invalid "%s" locale.', $locale));
		}
	}



	/**
	 * @param string $message
	 * @return array
	 */
	private function extractMessageDomain($message)
	{
		if (strpos($message, '.') !== FALSE && strpos($message, ' ') === FALSE) {
			list($domain, $message) = explode('.', $message, 2);

		} else {
			$domain = 'messages';
		}

		return [$domain, $message];
	}



	/**
	 * @param string $message
	 * @param string $domain
	 * @param string $locale
	 */
	protected function logMissingTranslation($message, $domain, $locale)
	{
		if ($this->psrLogger) {
			$this->psrLogger->notice('Missing translation', [
				'message' => $message,
				'domain' => $domain,
				'locale' => $locale ?: $this->getLocale(),
			]);
		}
	}



	/**
	 * @param null|string $whitelist
	 * @return null|string
	 */
	public static function buildWhitelistRegexp($whitelist)
	{
		return $whitelist ? '~^(' . implode('|', $whitelist) . ')~i' : NULL;
	}



	/*************************** Nette\Object ***************************/



	/**
	 * Access to reflection.
	 * @return \Nette\Reflection\ClassType
	 */
	public static function getReflection()
	{
		return new Nette\Reflection\ClassType(get_called_class());
	}



	/**
	 * Call to undefined method.
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return ObjectMixin::call($this, $name, $args);
	}



	/**
	 * Call to undefined static method.
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		return ObjectMixin::callStatic(get_called_class(), $name, $args);
	}



	/**
	 * Adding method to class.
	 *
	 * @param $name
	 * @param null $callback
	 *
	 * @throws \Nette\MemberAccessException
	 * @return callable|null
	 */
	public static function extensionMethod($name, $callback = NULL)
	{
		if (strpos($name, '::') === FALSE) {
			$class = get_called_class();
		} else {
			list($class, $name) = explode('::', $name);
		}
		if ($callback === NULL) {
			return ObjectMixin::getExtensionMethod($class, $name);
		} else {
			ObjectMixin::setExtensionMethod($class, $name, $callback);
		}
	}



	/**
	 * Returns property value. Do not call directly.
	 *
	 * @param string $name
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public function &__get($name)
	{
		return ObjectMixin::get($this, $name);
	}



	/**
	 * Sets value of a property. Do not call directly.
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @throws \Nette\MemberAccessException
	 * @return void
	 */
	public function __set($name, $value)
	{
		ObjectMixin::set($this, $name, $value);
	}



	/**
	 * Is property defined?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return ObjectMixin::has($this, $name);
	}



	/**
	 * Access to undeclared property.
	 *
	 * @param string $name
	 *
	 * @throws \Nette\MemberAccessException
	 * @return void
	 */
	public function __unset($name)
	{
		ObjectMixin::remove($this, $name);
	}

}
