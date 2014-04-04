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
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator as BaseTranslator;



/**
 * Translator.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class Translator extends BaseTranslator implements Nette\Localization\ITranslator
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
	 * @var CatalogueFactory
	 */
	private $catalogueFactory;

	/**
	 * @var Panel
	 */
	private $panel;

	/**
	 * @var array
	 */
	private $availableResourceLocales = array();



	/**
	 * @param IUserLocaleResolver $localeResolver
	 * @param MessageSelector $selector The message selector for pluralization
	 * @param CatalogueCompiler $catalogueCompiler
	 * @param CatalogueFactory $catalogueFactory
	 * @param FallbackResolver $fallbackResolver
	 */
	public function __construct(IUserLocaleResolver $localeResolver, MessageSelector $selector, CatalogueCompiler $catalogueCompiler,
		CatalogueFactory $catalogueFactory, FallbackResolver $fallbackResolver)
	{
		$this->localeResolver = $localeResolver;
		$this->catalogueCompiler = $catalogueCompiler;
		$this->catalogueFactory = $catalogueFactory;
		$this->fallbackResolver = $fallbackResolver;

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
	public function translate($message, $count = NULL, array $parameters = array(), $domain = NULL, $locale = NULL)
	{
		if ($message instanceof Phrase) {
			return $message->translate($this);
		}

		if (empty($message)) {
			return $message;

		} elseif ($message instanceof Nette\Utils\Html) {
			if ($this->panel) {
				$this->panel->markUntranslated($message);
			}
			return $message; // todo: what now?
		}

		if ($domain === NULL) {
			if (strpos($message, '.') !== FALSE && strpos($message, ' ') === FALSE) {
				list($domain, $message) = explode('.', $message, 2);

			} else {
				$domain = 'messages';
			}
		}

		$tmp = array();
		foreach ($parameters as $key => $val) {
			$tmp['%' . trim($key, '%') . '%'] = $val;
		}
		$parameters = $tmp;

		if ($count !== NULL && is_scalar($count)) {
			return $this->transChoice($message, preg_replace('~[^0-9,.]~', '', $count), $parameters + array('%count%' => $count), $domain, $locale);
		}

		return $this->trans($message, $parameters, $domain, $locale);
	}



	/**
	 * {@inheritdoc}
	 */
	public function trans($id, array $parameters = array(), $domain = NULL, $locale = NULL)
	{
		if ($id instanceof Phrase) {
			return $id->translate($this);
		}

		$result = parent::trans($id, $parameters, $domain, $locale);
		if ($this->panel !== NULL && $id === $result) { // probably untranslated
			$this->panel->markUntranslated($id);
		}

		return $result;
	}



	/**
	 * {@inheritdoc}
	 */
	public function transChoice($id, $number, array $parameters = array(), $domain = NULL, $locale = NULL)
	{
		if ($id instanceof Phrase) {
			return $id->translate($this);
		}

		try {
			$result = parent::transChoice($id, $number, $parameters, $domain, $locale);

		} catch (\Exception $e) {
			$result = $id;
			if ($this->panel !== NULL) {
				$this->panel->choiceError($e);
			}
		}

		if ($this->panel !== NULL && $id === $result) { // probably untranslated
			$this->panel->markUntranslated($id);
		}

		return $result;
	}



	/**
	 * {@inheritdoc}
	 */
	public function addResource($format, $resource, $locale, $domain = NULL)
	{
		$this->catalogueFactory->addResource($format, $resource, $locale, $domain);
		parent::addResource($format, $resource, $locale, $domain);
		$this->availableResourceLocales[$locale] = TRUE;
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
		return array_keys($this->availableResourceLocales);
	}



	/**
	 * {@inheritdoc}
	 */
	public function getLocale()
	{
		if ($this->locale === NULL) {
			$this->locale = $this->localeResolver->resolve($this);
		}

		return $this->locale;
	}



	/**
	 * @param string $messagePrefix
	 * @return Nette\Localization\ITranslator
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
		return Nette\ObjectMixin::call($this, $name, $args);
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
		return Nette\ObjectMixin::callStatic(get_called_class(), $name, $args);
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
			return Nette\ObjectMixin::getExtensionMethod($class, $name);
		} else {
			Nette\ObjectMixin::setExtensionMethod($class, $name, $callback);
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
		return Nette\ObjectMixin::get($this, $name);
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
		Nette\ObjectMixin::set($this, $name, $value);
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
		return Nette\ObjectMixin::has($this, $name);
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
		Nette\ObjectMixin::remove($this, $name);
	}

}
