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
     */
    public function setColumns($key, $locale, $message)
    {
        $this->key = $key;
        $this->locale = $locale;
        $this->message = $message;
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

        $catalogue->addResource(new DatabaseResource($resource));

        return $catalogue;
    }

    /**
     * @return array
     */
    abstract public function getLocales();

    public function addResources(Translator $translator)
    {
        foreach ($this->getLocales() as $locale) {
            $translator->addResource($this->getResourceName(), $this->getResourceName(), $locale);
        }
    }

    abstract public function getResourceName();

    abstract public function getTranslations($locale);
}
