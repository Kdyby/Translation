<?php
/**
 * Created by PhpStorm.
 * User: Azathoth
 * Date: 24. 8. 2015
 * Time: 12:50
 */

namespace Kdyby\Translation\Loader;

use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\ITranslator;
use Kdyby\Translation\MessageCatalogue;
use Kdyby\Translation\Resource\DatabaseResource;
use Kdyby\Translation\Translator;
use Nette\Database\Context;
use Nette\Utils\Strings;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Tracy\Debugger;

class NetteDbLoader extends DatabaseLoader {


    /** @var string */
    private $table;

    /** @var string */
    private $key;

    /** @var string */
    private $locale;

    /** @var string */
    private $translation;

    /** @var Context */
    private $db;

    /**
     * @param $config
     * @param Context $db
     */
    public function __construct($config, Context $db) {
        $this->table = $config['table'];
        $this->key = $config['columns']['key'];
        $this->locale = $config['columns']['locale'];
        $this->translation = $config['columns']['translation'];
        $this->db = $db;
    }

    function load($resource, $locale, $domain = NULL) {
        $catalogue = new MessageCatalogue($locale);

        $stmt = $this->db->table($this->table)
            ->select("`$this->key` AS `key`, `$this->locale` AS locale, `$this->translation` AS translation")
            ->where('locale = ?', $locale);
        $translations = $stmt->fetchAll();
        foreach($translations as $translation) {
            if ($domain === NULL) {
                $key = $translation['key'];
                if (Strings::contains($key, '.')) {
                    $prefix = Strings::substring($key, 0, strpos($key, '.'));
                    $key = Strings::substring($key, Strings::length($prefix) + 1);  //plus one because of dot
                } else {
                    $prefix = $domain;
                }
                $catalogue->set($key, $translation['translation'], $prefix);
            } else {
                $catalogue->set($translation['key'], $translation['translation'], $domain);
            }
        }

        $catalogue->addResource(new DatabaseResource($resource));

        return $catalogue;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        $stmt = $this->db->query("SELECT DISTINCT `$this->locale` as locale FROM $this->table");
        $locales = $stmt->fetchPairs('locale', 'locale');
        return $locales;
    }

    public function getResourceName()
    {
        return DatabaseResource::NETTE_DB;
    }

}
