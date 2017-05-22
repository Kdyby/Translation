<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Loader;

use Nette\Neon\Neon;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Loads translations from Neon files.
 */
class NeonFileLoader extends \Symfony\Component\Translation\Loader\ArrayLoader implements \Symfony\Component\Translation\Loader\LoaderInterface
{

	/**
	 * {@inheritdoc}
	 */
	public function load($resource, $locale, $domain = 'messages')
	{
		if (!stream_is_local($resource)) {
			throw new \Symfony\Component\Translation\Exception\InvalidResourceException(sprintf('This is not a local file "%s".', $resource));
		}

		if (!file_exists($resource)) {
			throw new \Symfony\Component\Translation\Exception\NotFoundResourceException(sprintf('File "%s" not found.', $resource));
		}

		try {
			$messages = Neon::decode(file_get_contents($resource));

		} catch (\Nette\Neon\Exception $e) {
			throw new \Symfony\Component\Translation\Exception\InvalidResourceException(sprintf('Error parsing Neon: %s', $e->getMessage()), 0, $e);
		}

		if (empty($messages)) {
			$messages = [];
		}

		if (!is_array($messages)) {
			throw new \Symfony\Component\Translation\Exception\InvalidResourceException(sprintf('The file "%s" must contain a Neon array.', $resource));
		}

		$catalogue = parent::load($messages, $locale, $domain);
		$catalogue->addResource(new FileResource($resource));

		return $catalogue;
	}

}
