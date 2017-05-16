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
use Nette;



/**
 * Object wrapper for message that can store default parameters and related information for translation.
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class Phrase extends Nette\Object
{

	/**
	 * @var string
	 */
	public $message;

	/**
	 * @var int
	 */
	public $count;

	/**
	 * @var array
	 */
	public $parameters;

	/**
	 * @var string
	 */
	public $domain;

	/**
	 * @var string
	 */
	public $locale;

	/**
	 * @var Translator
	 */
	private $translator;



	public function __construct($message, $count = NULL, $parameters = NULL, $domain = NULL, $locale = NULL)
	{
		$this->message = $message;

		if (is_array($count)) {
			$locale = $domain;
			$domain = $parameters;
			$parameters = $count;
			$count = NULL;
		}

		$this->count = $count;
		$this->parameters = (array) $parameters;
		$this->domain = $domain;
		$this->locale = $locale;
	}



	public function translate(Translator $translator, $count = NULL, array $parameters = [], $domain = NULL, $locale = NULL)
	{
		if (!is_string($this->message)) {
			throw new InvalidStateException("Message is not a string, type " . gettype($this->message) . ' given.');
		}

		$count = $count !== NULL ? $count : $this->count;
		$parameters = !empty($parameters) ? $parameters : $this->parameters;
		$domain = $domain !== NULL ? $domain : $this->domain;
		$locale = $locale !== NULL ? $locale : $this->locale;

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
		if (!$this->translator) {
			return $this->message;
		}

		try {
			return $this->translate($this->translator);

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
