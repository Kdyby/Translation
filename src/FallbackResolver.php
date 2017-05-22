<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

class FallbackResolver
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var array
	 */
	private $fallbackLocales = [];

	/**
	 * @param array $fallbackLocales
	 */
	public function setFallbackLocales(array $fallbackLocales)
	{
		$this->fallbackLocales = $fallbackLocales;
	}

	public function compute(Translator $translator, $locale)
	{
		$locales = [];
		foreach ($this->fallbackLocales as $fallback) {
			if ($fallback === $locale) {
				continue;
			}

			$locales[] = $fallback;
		}

		if (strrchr($locale, '_') !== FALSE) {
			array_unshift($locales, substr($locale, 0, -strlen(strrchr($locale, '_'))));
		}

		foreach ($translator->getAvailableLocales() as $available) {
			if ($available === $locale) {
				continue;
			}

			if (substr($available, 0, 2) === substr($locale, 0, 2)) {
				array_unshift($locales, $available);
				break;
			}
		}

		return array_unique($locales);
	}

}
