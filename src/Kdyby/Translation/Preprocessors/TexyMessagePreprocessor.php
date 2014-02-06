<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Preprocessors;

use Kdyby\Translation\IMessagePreprocessor;
use Texy;



class TexyMessagePreprocessor implements IMessagePreprocessor
{

	/**
	 * @var Texy
	 */
	protected $texy;



	/**
	 * @param \Texy $texy
	 * @return TexyMessagePreprocessor
	 */
	public function setTexy($texy)
	{
		$this->texy = $texy;
	}



	/**
	 * @return \Texy
	 */
	public function getTexy()
	{
		if ($this->texy === NULL) {
			$this->texy = new Texy();
			\Texy::$advertisingNotice = FALSE;
			// todo: disable link/email conversion
		}

		return $this->texy;
	}



	/**
	 * @param string $message
	 * @return string
	 */
	public function process($message)
	{
		$texy = $this->getTexy();
		$texy->allowed['longwords'] = FALSE;

		return $texy->process($message, TRUE);
	}

}
