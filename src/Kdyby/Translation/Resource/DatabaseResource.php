<?php

namespace Kdyby\Translation\Resource;


use Symfony\Component\Config\Resource\ResourceInterface;

class DatabaseResource implements ResourceInterface
{

    const DOCTRINE = 'doctrine';
    const NETTE_DB = 'nettedb';

    /**
     * @var string|false
     */
    private $resource;

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
    public function __toString()
    {
        return (string) $this->resource;
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

}
