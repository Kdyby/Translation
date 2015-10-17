<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Loader;

use Kdyby\Translation\Translator;
use Symfony\Component\Translation\Loader\LoaderInterface;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
interface IDatabaseLoader extends LoaderInterface
{

	/**
	 * @return array
	 */
	public function getLocales();



	/**
	 * @param Translator $translator
	 * @return void
	 */
	public function addResources(Translator $translator);



	/**
	 * Creates the schema in database.
	 *
	 * @param bool $createSchema Should it also execute the queries?
	 * @return array The queries that were (or would have been) executed
	 */
	public function setupDatabase($createSchema = FALSE);

}
