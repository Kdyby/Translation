<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

/**
 * Object wrapper for message that can store default parameters and related information for translation.
 */
class Phrase
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var string
	 */
	public $message;

	/**
	 * @var int|NULL
	 */
	public $count;

	/**
	 * @var array
	 */
	public $parameters;

	/**
	 * @var string|NULL
	 */
	public $domain;

	/**
	 * @var string|NULL
	 */
	public $locale;

	/**
	 * @var \Kdyby\Translation\Translator|NULL
	 */
	private $translator;

	/**
	 * @param string $message
	 * @param int|array|NULL $count
	 * @param string|array|NULL $parameters
	 * @param string|NULL $domain
	 * @param string|NULL $locale
	 */
	public function __construct($message, $count = NULL, $parameters = NULL, $domain = NULL, $locale = NULL)
	{
		$this->message = $message;

		if (is_array($count)) {
			/** @var string $stringParameters */
			$stringParameters = $parameters;
			$locale = ($domain !== NULL) ? (string) $domain : NULL;
			$domain = ($parameters !== NULL) ? (string) $stringParameters : NULL;
			$parameters = $count;
			$count = NULL;
		}

		$this->count = $count !== NULL ? (int) $count : NULL;
		$this->parameters = (array) $parameters;
		$this->domain = $domain;
		$this->locale = $locale;
	}

	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @param int|NULL $count
	 * @param array $parameters
	 * @param string|NULL $domain
	 * @param string|NULL $locale
	 * @return string|\Nette\Utils\IHtmlString|\Latte\Runtime\IHtmlString
	 */
	public function translate(Translator $translator, $count = NULL, array $parameters = [], $domain = NULL, $locale = NULL)
	{
		if (!is_string($this->message)) {
			throw new \Kdyby\Translation\InvalidStateException('Message is not a string, type ' . gettype($this->message) . ' given.');
		}

		$count = ($count !== NULL) ? (int) $count : $this->count;
		$parameters = !empty($parameters) ? $parameters : $this->parameters;
		$domain = ($domain !== NULL) ? $domain : $this->domain;
		$locale = ($locale !== NULL) ? $locale : $this->locale;

		return $translator->translate($this->message, $count, (array) $parameters, $domain, $locale);
	}

	/**
	 * @internal
	 * @param \Kdyby\Translation\Translator $translator
	 */
	public function setTranslator(Translator $translator)
	{
		$this->translator = $translator;
	}

	public function __toString()
	{
		if ($this->translator === NULL) {
			return $this->message;
		}

		try {
			return (string) $this->translate($this->translator);

		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
		}

		return '';
	}

	public function __sleep()
	{
		$this->translator = NULL;
		return ['message', 'count', 'parameters', 'domain', 'locale'];
	}

}
