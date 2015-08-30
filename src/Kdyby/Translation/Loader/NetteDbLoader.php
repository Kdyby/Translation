<?php

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

    /** @var Context */
    private $db;

    /**
     * @param Context $db
     */
    public function __construct(Context $db) {
        $this->db = $db;
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

    public function getTranslations($locale)
    {
        $stmt = $this->db->table($this->table)
            ->select("`$this->key` AS `key`, `$this->locale` AS locale, `$this->message` AS message")
            ->where('locale = ?', $locale);
        return $stmt->fetchAll();
    }

    /**
     * @param $locale
     * @return \DateTime
     */
    public function getLastUpdate($locale)
    {
        $stmt = $this->db->table($this->table)
            ->select("`$this->updatedAt` AS `updated_at`")
            ->where('locale = ?', $locale)
            ->order('updated_at DESC')
            ->limit(1);
        return $stmt->fetchField('updated_at');
    }

}
