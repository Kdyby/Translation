<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Symfony;



/**
 * @author Jan Langer <jan.langer@gmail.com>
 */
class MessageCatalogue extends Symfony\Component\Translation\MessageCatalogue
{

	/**
	 * {@inheritdoc}
	 */
	public function get($id, $domain = 'messages')
	{
		if ($this->defines($id, $domain)) {
			return parent::get($id, $domain);
		}

		if ($this->getFallbackCatalogue() !== NULL) {
			return $this->getFallbackCatalogue()->get($id, $domain);
		}

		return "\x01";
	}

}
