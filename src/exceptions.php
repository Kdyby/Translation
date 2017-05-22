<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

interface Exception
{

}

class InvalidArgumentException extends \InvalidArgumentException implements \Kdyby\Translation\Exception
{

}

class InvalidStateException extends \RuntimeException implements \Kdyby\Translation\Exception
{

}

class InvalidResourceException extends \UnexpectedValueException implements \Kdyby\Translation\Exception
{

}

class LoaderNotFoundException extends \RuntimeException implements \Kdyby\Translation\Exception
{

}
