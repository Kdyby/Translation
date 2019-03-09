<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

interface IResourceLoader
{

	/**
	 * Adds a loader to the translation extractor.
	 *
	 * @param string $format The format of the loader
	 * @param \Symfony\Component\Translation\Loader\LoaderInterface $loader
	 */
	public function addLoader(string $format, LoaderInterface $loader): void;

	/**
	 * @return \Symfony\Component\Translation\Loader\LoaderInterface[]
	 */
	public function getLoaders(): array;

	/**
	 * @param string $format
	 * @param string $resource
	 * @param string $domain
	 * @param \Symfony\Component\Translation\MessageCatalogue $catalogue
	 * @throws \Kdyby\Translation\LoaderNotFoundException
	 */
	public function loadResource(string $format, string $resource, string $domain, MessageCatalogue $catalogue): void;

}
