<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Texy;



class TexyMessagePreprocessor implements IMessagePreprocessor
{

	/** @var  Texy */
	protected $texy;

	function __construct(Texy $texy)
	{
		$this->texy = $texy;
	}

	/**
	 * @param string $message
	 * @return string
	 */
	function process($message)
	{
		return $this->texy->process($message, TRUE);
	}
}
