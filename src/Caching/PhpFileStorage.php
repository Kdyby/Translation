<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Caching;

use Nette\Caching\Cache;

/**
 * @internal
 */
class PhpFileStorage extends \Nette\Caching\Storages\FileStorage implements \Nette\Caching\IStorage
{

	/**
	 * @var string
	 */
	public $hint;

	/**
	 * Reads cache data from disk.
	 *
	 * @param array $meta
	 * @return mixed
	 */
	protected function readData(array $meta)
	{
		return [
			'file' => $meta['file'],
			'handle' => $meta['handle'],
		];
	}

	/**
	 * Returns file name.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function getCacheFile(string $key): string
	{
		$cacheKey = substr_replace(
			$key,
			trim(strtr($this->hint, '\\/@', '.._'), '.') . '-',
			strpos($key, Cache::NAMESPACE_SEPARATOR) + 1,
			0
		);

		return parent::getCacheFile($cacheKey) . '.php';
	}

}
