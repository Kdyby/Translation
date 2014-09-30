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
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\MessageCatalogueInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CatalogueFactory extends Nette\Object
{

	/**
	 * @var FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var TranslationLoader
	 */
	private $loader;

	/**
	 * @var array
	 */
	private $resources = array();



	public function __construct(FallbackResolver $fallbackResolver, TranslationLoader $loader)
	{
		$this->fallbackResolver = $fallbackResolver;
		$this->loader = $loader;
	}



	/**
	 * {@inheritdoc}
	 */
	public function addResource($format, $resource, $locale, $domain = 'messages')
	{
		$this->resources[$locale][] = array($format, $resource, $domain);
	}



	/**
	 * @param Translator $translator
	 * @param MessageCatalogueInterface[] $availableCatalogues
	 * @param string $locale
	 * @throws NotFoundResourceException
	 * @return MessageCatalogueInterface
	 */
	public function createCatalogue(Translator $translator, array &$availableCatalogues, $locale)
	{
		try {
			$this->doLoadCatalogue($availableCatalogues, $locale);

		} catch (NotFoundResourceException $e) {
			if (!$this->fallbackResolver->compute($translator, $locale)) {
				throw $e;
			}
		}

		$current = $availableCatalogues[$locale];

		foreach ($this->fallbackResolver->compute($translator, $locale) as $fallback) {
			if (!isset($availableCatalogues[$fallback])) {
				$this->doLoadCatalogue($availableCatalogues, $fallback);
			}

			$current->addFallbackCatalogue($availableCatalogues[$fallback]);
			$current = $availableCatalogues[$fallback];
		}

		return $availableCatalogues[$locale];
	}



	private function doLoadCatalogue(array &$availableCatalogues, $locale)
	{
		$availableCatalogues[$locale] = $catalogue = new MessageCatalogue($locale);

		if (isset($this->resources[$locale])) {
			foreach ($this->resources[$locale] as $resource) {
				$this->loader->loadResource($resource[0], $resource[1], $resource[2], $catalogue);
			}
		}

		return $catalogue;
	}

}
