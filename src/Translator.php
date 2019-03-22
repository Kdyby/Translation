<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Kdyby\Translation\Diagnostics\Panel;
use Latte\Runtime\IHtmlString as LatteHtmlString;
use Nette\Utils\IHtmlString as NetteHtmlString;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Formatter\ChoiceMessageFormatterInterface;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;

class Translator extends \Symfony\Component\Translation\Translator implements \Kdyby\Translation\ITranslator
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Kdyby\Translation\IUserLocaleResolver
	 */
	private $localeResolver;

	/**
	 * @var \Kdyby\Translation\CatalogueCompiler
	 */
	private $catalogueCompiler;

	/**
	 * @var \Kdyby\Translation\FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var \Kdyby\Translation\IResourceLoader
	 */
	private $translationsLoader;

	/**
	 * @var \Psr\Log\LoggerInterface|NULL
	 */
	private $psrLogger;

	/**
	 * @var \Kdyby\Translation\Diagnostics\Panel|NULL
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
	 * @var string|NULL
	 */
	private $localeWhitelist;

	/**
	 * @var \Symfony\Component\Translation\Formatter\MessageFormatterInterface
	 */
	private $formatter;

	/**
	 * @param \Kdyby\Translation\IUserLocaleResolver $localeResolver
	 * @param \Symfony\Component\Translation\Formatter\MessageFormatterInterface $formatter
	 * @param \Kdyby\Translation\CatalogueCompiler $catalogueCompiler
	 * @param \Kdyby\Translation\FallbackResolver $fallbackResolver
	 * @param \Kdyby\Translation\IResourceLoader $loader
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		IUserLocaleResolver $localeResolver,
		MessageFormatterInterface $formatter,
		CatalogueCompiler $catalogueCompiler,
		FallbackResolver $fallbackResolver,
		IResourceLoader $loader
	)
	{
		$this->localeResolver = $localeResolver;
		$this->formatter = $formatter;
		$this->catalogueCompiler = $catalogueCompiler;
		$this->fallbackResolver = $fallbackResolver;
		$this->translationsLoader = $loader;

		parent::__construct('', $formatter);
		$this->setLocale(NULL);
	}

	/**
	 * @internal
	 * @param \Kdyby\Translation\Diagnostics\Panel $panel
	 */
	public function injectPanel(Panel $panel)
	{
		$this->panel = $panel;
	}

	/**
	 * @param \Psr\Log\LoggerInterface|NULL $logger
	 */
	public function injectPsrLogger(LoggerInterface $logger = NULL)
	{
		$this->psrLogger = $logger;
	}

	/**
	 * Translates the given string.
	 *
	 * @param string|\Kdyby\Translation\Phrase|mixed $message The message id
	 * @param string|array|NULL $parameters An array of parameters for the message
	 * @throws \InvalidArgumentException
	 * @return string|\Nette\Utils\IHtmlString|\Latte\Runtime\IHtmlString
	 */
	public function translate($message, ...$parameters): string
	{
		if ($message instanceof Phrase) {
			return $message->translate($this);
		}

		$count = isset($parameters[0]) ? $parameters[0] : NULL;
		$params = isset($parameters[1]) ? $parameters[1] : [];
		$domain = isset($parameters[2]) ? $parameters[2] : NULL;
		$locale = isset($parameters[3]) ? $parameters[3] : NULL;

		if (is_array($count)) {
			$locale = ($domain !== NULL) ? (string) $domain : NULL;
			$domain = ($parameters !== NULL && !empty($parameters)) ? (string) $parameters : NULL;
			$params = $count;
			$count = NULL;
		}

		if (empty($message)) {
			return $message;

		} elseif ($message instanceof NetteHtmlString || $message instanceof LatteHtmlString) {
			$this->logMissingTranslation($message->__toString(), $domain, $locale);
			return $message; // what now?
		} elseif (is_int($message)) {
			$message = (string) $message;
		}

		if (!is_string($message)) {
			throw new \Kdyby\Translation\InvalidArgumentException(sprintf('Message id must be a string, %s was given', gettype($message)));
		}

		if (Strings::startsWith($message, '//')) {
			if ($domain !== NULL) {
				throw new \Kdyby\Translation\InvalidArgumentException(sprintf(
					'Providing domain "%s" while also having the message "%s" absolute is not supported',
					$domain,
					$message
				));
			}

			$message = Strings::substring($message, 2);
		}

		$tmp = [];
		foreach ($params as $key => $val) {
			$tmp['%' . trim($key, '%') . '%'] = $val;
		}
		$params = $tmp;

		if ($count !== NULL && is_scalar($count)) {
			return $this->transChoice($message, $count, $params + ['%count%' => $count], $domain, $locale);
		}

		return $this->trans($message, $params, $domain, $locale);
	}

	/**
	 * {@inheritdoc}
	 */
	public function trans($message, array $parameters = [], $domain = NULL, $locale = NULL)
	{
		if (is_int($message)) {
			$message = (string) $message;
		}

		if (!is_string($message)) {
			throw new \Kdyby\Translation\InvalidArgumentException(sprintf('Message id must be a string, %s was given', gettype($message)));
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
		if (is_int($message)) {
			$message = (string) $message;
		}

		if (!is_string($message)) {
			throw new \Kdyby\Translation\InvalidArgumentException(sprintf('Message id must be a string, %s was given', gettype($message)));
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
			if ($locale === NULL) {
				$result = strtr($message, $parameters);

			} else {
				if (!$this->formatter instanceof ChoiceMessageFormatterInterface) {
					$result = $id;
					if ($this->panel !== NULL) {
						$this->panel->choiceError(new \Symfony\Component\Translation\Exception\LogicException(sprintf('The formatter "%s" does not support plural translations.', get_class($this->formatter))), $domain);
					}
				} else {
					$result = $this->formatter->choiceFormat($message, (int) $number, $locale, $parameters);
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $format
	 * @param \Symfony\Component\Translation\Loader\LoaderInterface $loader
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
		if ($this->localeWhitelist !== NULL && !preg_match($this->localeWhitelist, $locale)) {
			if ($this->panel !== NULL) {
				$this->panel->addIgnoredResource($format, $resource, $locale, $domain);
			}
			return;
		}

		parent::addResource($format, $resource, $locale, $domain);
		$this->catalogueCompiler->addResource($format, $resource, $locale, $domain);
		$this->availableResourceLocales[$locale] = TRUE;

		if ($this->panel !== NULL) {
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
	 * Sets the current locale.
	 *
	 * @param string|NULL $locale The locale
	 *
	 * @throws \InvalidArgumentException If the locale contains invalid characters
	 */
	public function setLocale($locale)
	{
		parent::setLocale($locale);
	}

	/**
	 * Returns the current locale.
	 *
	 * @return string|NULL The locale
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
	 * @return \Kdyby\Translation\Translator
	 */
	public function setDefaultLocale($locale)
	{
		$this->assertValidLocale($locale);
		$this->defaultLocale = $locale;
		return $this;
	}

	/**
	 * @param string $messagePrefix
	 * @return \Kdyby\Translation\ITranslator
	 */
	public function domain($messagePrefix)
	{
		return new PrefixedTranslator($messagePrefix, $this);
	}

	/**
	 * @return \Kdyby\Translation\TemplateHelpers
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
			throw new \Kdyby\Translation\InvalidArgumentException('Invalid locale.');
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
	 * @param string|NULL $message
	 * @param string|NULL $domain
	 * @param string|NULL $locale
	 */
	protected function logMissingTranslation($message, $domain, $locale)
	{
		if ($message === NULL) {
			return;
		}

		if ($this->psrLogger !== NULL) {
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
	 * @param array|NULL $whitelist
	 * @return null|string
	 */
	public static function buildWhitelistRegexp($whitelist)
	{
		return ($whitelist !== NULL) ? '~^(' . implode('|', $whitelist) . ')~i' : NULL;
	}

}
