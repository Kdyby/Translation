<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Latte\Engine;
use Latte\Runtime\FilterInfo;

class TemplateHelpers
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Kdyby\Translation\ITranslator|\Kdyby\Translation\Translator|\Kdyby\Translation\PrefixedTranslator
	 */
	private $translator;

	public function __construct(ITranslator $translator)
	{
		if (!$translator instanceof Translator && !$translator instanceof PrefixedTranslator) {
			throw new \Kdyby\Translation\InvalidArgumentException(sprintf(
				'The given translator must be instance of %s or %s, bug %s was given',
				Translator::class,
				PrefixedTranslator::class,
				get_class($translator)
			));
		}

		$this->translator = $translator;
	}

	public function register(Engine $engine): void
	{
		if (class_exists(FilterInfo::class)) {
			$engine->addFilter('translate', [$this, 'translateFilterAware']);
		} else {
			$engine->addFilter('translate', [$this, 'translate']);
		}
		$engine->addFilter('getTranslator', [$this, 'getTranslator']);
	}

	/**
	 * @return \Kdyby\Translation\ITranslator|\Kdyby\Translation\Translator|\Kdyby\Translation\PrefixedTranslator
	 */
	public function getTranslator(): ITranslator
	{
		return $this->translator;
	}

	/**
	 * @param string $message
	 * @param int|array|NULL $count
	 * @param string|array|NULL $parameters
	 * @param string|NULL $domain
	 * @param string|NULL $locale
	 * @return string
	 */
	public function translate(string $message, $count = NULL, $parameters = [], ?string $domain = NULL, ?string $locale = NULL): string
	{
		if (is_array($count)) {
			$locale = ($domain !== NULL) ? (string) $domain : NULL;
			$domain = ($parameters !== NULL && !empty($parameters)) ? (string) $parameters : NULL;
			$parameters = $count;
			$count = NULL;
		}

		return $this->translator->translate(
			$message,
			($count !== NULL) ? (int) $count : NULL,
			(array) $parameters,
			$domain,
			$locale
		);
	}

	/**
	 * @param \Latte\Runtime\FilterInfo $filterInfo
	 * @param string $message
	 * @param int|array|NULL $count
	 * @param string|array|NULL $parameters
	 * @param string|NULL $domain
	 * @param string|NULL $locale
	 * @return string
	 */
	public function translateFilterAware(FilterInfo $filterInfo, string $message, $count = NULL, $parameters = [], ?string $domain = NULL, ?string $locale = NULL): string
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
