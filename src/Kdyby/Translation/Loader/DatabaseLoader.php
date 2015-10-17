<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Loader;

use Kdyby\Translation\DI\Configuration;
use Kdyby\Translation\Helpers;
use Kdyby\Translation\MessageCatalogue;
use Kdyby\Translation\Resource\DatabaseResource;
use Kdyby\Translation\Translator;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
abstract class DatabaseLoader implements IDatabaseLoader
{

	/**
	 * @var Configuration
	 */
	protected $config;



	public function __construct(Configuration $config)
	{
		$this->config = $config;
	}



	/**
	 * {@inheritdoc}
	 */
	public function load($resource, $locale, $domain = NULL)
	{
		$catalogue = new MessageCatalogue($locale);

		$translations = $this->getTranslations($locale);
		foreach ($translations as $translation) {
			if ($domain !== NULL) {
				$catalogue->set($translation['key'], $translation['message'], $domain);

			} else {
				list($prefix, $key) = Helpers::extractMessageDomain($translation['key']);
				$catalogue->set($key, $translation['message'], $prefix);
			}
		}

		$catalogue->addResource(new DatabaseResource($resource, $this->getLastUpdate($locale)->getTimestamp()));

		return $catalogue;
	}



	/**
	 * @inheritdoc
	 */
	public function addResources(Translator $translator)
	{
		foreach ($this->getLocales() as $locale) {
			$translator->addResource('database', $this->getResourceName(), $locale);
		}
	}



	/**
	 * @param $locale
	 * @return \DateTime
	 */
	abstract protected function getLastUpdate($locale);



	/**
	 * @return string
	 */
	abstract protected function getResourceName();



	/**
	 * @param string $locale
	 * @return array
	 */
	abstract protected function getTranslations($locale);

}
