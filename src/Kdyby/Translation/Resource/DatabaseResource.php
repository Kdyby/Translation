<?php
/**
 * Created by PhpStorm.
 * User: Azathoth
 * Date: 28. 8. 2015
 * Time: 16:30
 */

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

    /**
     * Constructor.
     *
     * @param string $resource The file path to the resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
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
        return false;
    }

}