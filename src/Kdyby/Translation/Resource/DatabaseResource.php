<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Resource;

use Symfony\Component\Config\Resource\ResourceInterface;



/**
 * @author Azathoth <memnarch@seznam.cz>
 */
class DatabaseResource implements ResourceInterface
{

	const DOCTRINE = 'doctrine';
	const NETTE_DB = 'nettedb';

	/**
	 * @var string|boolean
	 */
	private $resource;

	/**
	 * @var int
	 */
	private $lastUpdated;



	/**
	 * @param string $resource The file path to the resource
	 * @param int $lastUpdated timestamp
	 */
	public function __construct($resource, $lastUpdated)
	{
		$this->resource = $resource;
		$this->lastUpdated = $lastUpdated;
	}



	/**
	 * {@inheritdoc}
	 */
	public function getResource()
	{
		return $this->resource;
	}



	/**
	 * {@inheritdoc}
	 */
	public function isFresh($timestamp)
	{
		return $this->lastUpdated <= $timestamp;
	}



	/**
	 * {@inheritdoc}
	 */
	public function __toString()
	{
		return (string) $this->resource;
	}

}
