<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\LocaleResolver;

use Kdyby\Translation\IUserLocaleResolver;
use Kdyby\Translation\Translator;

class ChainResolver implements \Kdyby\Translation\IUserLocaleResolver
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var array|\Kdyby\Translation\IUserLocaleResolver[]
	 */
	private $resolvers = [];

	/**
	 * @param \Kdyby\Translation\IUserLocaleResolver $resolver
	 */
	public function addResolver(IUserLocaleResolver $resolver)
	{
		array_unshift($this->resolvers, $resolver); // first the newer
	}

	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @return string|NULL
	 */
	public function resolve(Translator $translator)
	{
		foreach ($this->resolvers as $resolver) {
			$locale = $resolver->resolve($translator);
			if ($locale !== NULL) {
				return $locale;
			}
		}

		return $translator->getDefaultLocale();
	}

}
