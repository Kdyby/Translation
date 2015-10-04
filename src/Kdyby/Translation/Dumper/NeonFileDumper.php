<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Dumper;

use Nette\Neon\Neon;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;



/**
 * Generates Neon files from a message catalogue.
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class NeonFileDumper extends FileDumper
{

	/**
	 * {@inheritDoc}
	 */
	protected function format(MessageCatalogue $messages, $domain)
	{
		return Neon::encode($messages->all($domain), Neon::BLOCK);
	}



	/**
	 * {@inheritDoc}
	 */
	protected function getExtension()
	{
		return 'neon';
	}

}
