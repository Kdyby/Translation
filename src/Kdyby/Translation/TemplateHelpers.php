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
 * @author Filip Procházka <filip@prochazka.su>
 */
class TemplateHelpers extends Nette\Object
{

	/**
	 * @var Translator
	 */
	private $translator;



	public function __construct(Translator $translator)
	{
		$this->translator = $translator;
	}



	public function loader($method)
	{
		if (method_exists($this, $method)) {
			return callback($this, $method);
		}
	}



	/**
	 * @return \Kdyby\Translation\Translator
	 */
	public function getTranslator()
	{
		return $this->translator;
	}



	public function translate($message, $count = NULL, $parameters = array(), $domain = NULL, $locale = NULL)
	{
		if (is_array($count)) {
			$locale = $domain ?: NULL;
			$domain = $parameters ?: NULL;
			$parameters = $count ?: array();
			$count = NULL;
		}

		return $this->translator->translate($message, $count, (array) $parameters, $domain, $locale);
	}

}
