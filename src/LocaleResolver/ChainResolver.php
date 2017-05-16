<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\LocaleResolver;

use Kdyby;
use Kdyby\Translation\IUserLocaleResolver;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ChainResolver extends Nette\Object implements IUserLocaleResolver
{

	/**
	 * @var array|IUserLocaleResolver[]
	 */
	private $resolvers = [];



	/**
	 * @param IUserLocaleResolver $resolver
	 */
	public function addResolver(IUserLocaleResolver $resolver)
	{
		array_unshift($this->resolvers, $resolver); // first the newer
	}



	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @return string|NULL
	 */
	public function resolve(Kdyby\Translation\Translator $translator)
	{
		foreach ($this->resolvers as $resolver) {
			if (($locale = $resolver->resolve($translator)) !== NULL) {
				return $locale;
			}
		}

		return $translator->getDefaultLocale();
	}

}
