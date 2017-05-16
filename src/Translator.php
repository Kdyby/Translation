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
use Latte;
use Nette;
use Nette\Utils\Strings;
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

	use Kdyby\StrictObjects\Scream;

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
	 * @var MessageSelector
	 */
	private $selector;

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
		$this->selector = $selector;
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

		if (Strings::startsWith($message, '//')) {
			if ($domain !== NULL) {
				throw new InvalidArgumentException(sprintf(
					'Providing domain "%s" while also having the message "%s" absolute is not supported',
					$domain,
					$message
				));
			}

			$message = Strings::substring($message, 2);
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
			if ($locale === NULL) {
				$locale = $this->getLocale();
			}
			$result = strtr($this->selector->choose($message, (int) $number, $locale), $parameters);
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
		if (parent::getLocale() === NULL) {
			$this->setLocale($this->localeResolver->resolve($this));
		}

		return parent::getLocale();
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

		if ($this->panel !== NULL) {
			$this->panel->markUntranslated($message, $domain);
		}
	}



	/**
	 * @param array $whitelist
	 * @return null|string
	 */
	public static function buildWhitelistRegexp($whitelist)
	{
		return $whitelist ? '~^(' . implode('|', $whitelist) . ')~i' : NULL;
	}

}
