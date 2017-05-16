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
use Nette\Utils\Finder;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;



/**
 * TranslationLoader loads translation messages from translation files.
 *
 * @author Michel Salib <michelsalib@hotmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslationLoader extends Nette\Object implements IResourceLoader
{

	/**
	 * Loaders used for import.
	 *
	 * @var array|LoaderInterface[]
	 */
	private $loaders = [];

	/**
	 * @var array
	 */
	private $serviceIds = [];

	/**
	 * @var Nette\DI\Container
	 */
	private $serviceLocator;



	/**
	 * @internal
	 */
	public function injectServiceIds($serviceIds, Nette\DI\Container $serviceLocator)
	{
		$this->serviceIds = $serviceIds;
		$this->serviceLocator = $serviceLocator;
	}



	/**
	 * Adds a loader to the translation extractor.
	 *
	 * @param string          $format The format of the loader
	 * @param LoaderInterface $loader
	 */
	public function addLoader($format, LoaderInterface $loader)
	{
		$this->loaders[$format] = $loader;
	}



	/**
	 * @return LoaderInterface[]
	 */
	public function getLoaders()
	{
		foreach ($this->serviceIds as $format => $loaderId) {
			$this->loaders[$format] = $this->serviceLocator->getService($loaderId);
		}
		$this->serviceIds = [];

		return $this->loaders;
	}



	/**
	 * Loads translation messages from a directory to the catalogue.
	 *
	 * @param string           $directory the directory to look into
	 * @param MessageCatalogue $catalogue the catalogue
	 */
	public function loadMessages($directory, MessageCatalogue $catalogue)
	{
		foreach ($this->getLoaders() as $format => $loader) {
			// load any existing translation files
			$extension = $catalogue->getLocale() . '.' . $format;
			foreach (Finder::findFiles('*.' . $extension)->from($directory) as $file) {
				/** @var \SplFileInfo $file */
				$domain = substr($file->getFileName(), 0, -1 * strlen($extension) - 1);
				$this->loadResource($format, $file->getPathname(), $domain, $catalogue);
			}
		}
	}



	/**
	 * @param string $format
	 * @param string $resource
	 * @param string $domain
	 * @param MessageCatalogue $catalogue
	 * @throws LoaderNotFoundException
	 */
	public function loadResource($format, $resource, $domain, MessageCatalogue $catalogue)
	{
		if (!isset($this->loaders[$format])) {
			if (!isset($this->serviceIds[$format])) {
				throw new LoaderNotFoundException(sprintf('The "%s" translation loader is not registered.', $resource[0]));
			}

			$this->loaders[$format] = $this->serviceLocator->getService($this->serviceIds[$format]);
			unset($this->serviceIds[$format]);
		}

		$catalogue->addCatalogue($this->loaders[$format]->load($resource, $catalogue->getLocale(), $domain));
	}

}
