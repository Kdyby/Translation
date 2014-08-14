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
 *
 * @method translate($message, $count = NULL, $parameters = array(), $domain = NULL, $locale = NULL);
 */
interface ITranslator extends Nette\Localization\ITranslator
{

	// function translate($message, $count = NULL, array $parameters = array(), $domain = NULL, $locale = NULL);

}
