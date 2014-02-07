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
class TranslationLoader extends Nette\Object
{

	/**
	 * Loaders used for import.
	 *
	 * @var array|LoaderInterface[]
	 */
	private $loaders = array();
	/** @var  IMessagePreprocessor */
	private $messagePreprocessor;



	/**
	 * @param IMessagePreprocessor $messagePreprocessor
	 */
	public function setMessagePreprocessor(IMessagePreprocessor $messagePreprocessor)
	{
		$this->messagePreprocessor = $messagePreprocessor;
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
	 * Loads translation messages from a directory to the catalogue.
	 *
	 * @param string           $directory the directory to look into
	 * @param MessageCatalogue $catalogue the catalogue
	 */
	public function loadMessages($directory, MessageCatalogue $catalogue)
	{
		foreach ($this->loaders as $format => $loader) {
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
			throw new LoaderNotFoundException(sprintf('The "%s" translation loader is not registered.', $resource[0]));
		}

		$messageCatalogue = $this->loaders[$format]->load($resource, $catalogue->getLocale(), $domain);
		if ($this->messagePreprocessor) {
			$translations = $messageCatalogue->all();
			foreach ($translations['messages'] as $key => $message) {
				$messageCatalogue->set($key, $this->messagePreprocessor->process($message));
			}
		}
		$catalogue->addCatalogue($messageCatalogue);
	}

}
