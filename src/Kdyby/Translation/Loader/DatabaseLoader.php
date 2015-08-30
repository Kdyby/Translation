<?php

namespace Kdyby\Translation\Loader;

use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\ITranslator;
use Kdyby\Translation\MessageCatalogue;
use Kdyby\Translation\Resource\DatabaseResource;
use Kdyby\Translation\Translator;
use Nette\Utils\Strings;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Tracy\Debugger;

abstract class DatabaseLoader implements IDatabaseLoader {

    /** @var string */
    protected $table = 'translation';

    /** @var string */
    protected $key = 'key';

    /** @var string */
    protected $locale = 'locale';

    /** @var string */
    protected $message = 'message';

    /** @var string */
    protected $updatedAt = 'updated_at';

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @param string $key
     * @param string $locale
     * @param string $message
     * @param string $updatedAt
     */
    public function setColumns($key, $locale, $message, $updatedAt)
    {
        $this->key = $key;
        $this->locale = $locale;
        $this->message = $message;
        $this->updatedAt = $updatedAt;
    }

    function load($resource, $locale, $domain = NULL) {
        $catalogue = new MessageCatalogue($locale);

        $translations = $this->getTranslations($locale);
        foreach($translations as $translation) {
            if ($domain === NULL) {
                $key = $translation['key'];
                if (Strings::contains($key, '.')) {
                    $prefix = Strings::substring($key, 0, strpos($key, '.'));
                    $key = Strings::substring($key, Strings::length($prefix) + 1);  //plus one because of dot
                } else {
                    $prefix = $domain;
                }
                $catalogue->set($key, $translation['message'], $prefix);
            } else {
                $catalogue->set($translation['key'], $translation['message'], $domain);
            }
        }

        Debugger::barDump($this->getLastUpdate($locale), 'last update');
        $catalogue->addResource(new DatabaseResource($resource, $this->getLastUpdate($locale)->getTimestamp()));

        return $catalogue;
    }

    /**
     * @return array
     */
    abstract public function getLocales();

    /**
     * @param $locale
     * @return \DateTime
     */
    abstract public function getLastUpdate($locale);

    public function addResources(Translator $translator)
    {
        foreach ($this->getLocales() as $locale) {
            $translator->addResource('database', $this->getResourceName(), $locale);
        }
    }

    abstract public function getResourceName();

    abstract public function getTranslations($locale);
}
