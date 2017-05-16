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
use Latte\Engine;
use Latte\Runtime\FilterInfo;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TemplateHelpers extends Nette\Object
{

	/**
	 * @var ITranslator
	 */
	private $translator;



	public function __construct(ITranslator $translator)
	{
		$this->translator = $translator;
	}



	public function register(Engine $engine)
	{
		if (class_exists('Latte\Runtime\FilterInfo')) {
			$engine->addFilter('translate', [$this, 'translateFilterAware']);
		} else {
			$engine->addFilter('translate', [$this, 'translate']);
		}
		$engine->addFilter('getTranslator', [$this, 'getTranslator']);
	}



	/**
	 * @return ITranslator
	 */
	public function getTranslator()
	{
		return $this->translator;
	}



	public function translate($message, $count = NULL, $parameters = [], $domain = NULL, $locale = NULL)
	{
		if (is_array($count)) {
			$locale = $domain ?: NULL;
			$domain = $parameters ?: NULL;
			$parameters = $count ?: [];
			$count = NULL;
		}

		return $this->translator->translate($message, $count, (array) $parameters, $domain, $locale);
	}



	public function translateFilterAware(FilterInfo $filterInfo, $message, $count = NULL, $parameters = [], $domain = NULL, $locale = NULL)
	{
		return $this->translate($message, $count, $parameters, $domain, $locale);
	}



	/**
	 * @deprecated
	 */
	public function loader($method)
	{
		if (method_exists($this, $method) && strtolower($method) !== 'register') {
			return [$this, $method];
		}
	}

}
