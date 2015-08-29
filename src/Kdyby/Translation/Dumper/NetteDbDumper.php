<?php
/**
 * Created by PhpStorm.
 * User: Azathoth
 * Date: 24. 8. 2015
 * Time: 12:50
 */

namespace Kdyby\Translation\Dumper;

use Doctrine\DBAL\Connection;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\MessageCatalogue;
use Nette\Database\Context;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Tracy\Debugger;

class NetteDbDumper implements IDatabaseDumper {

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


    /**
     * Dumps the message catalogue.
     *
     * @param \Symfony\Component\Translation\MessageCatalogue $messages The message catalogue
     * @param array $options Options that are used by the dumper
     */
    public function dump(\Symfony\Component\Translation\MessageCatalogue $messages, $options = array())
    {
        $messagesArray = $messages->all();
        $this->flatten($messagesArray);
        $locale = $messages->getLocale();
        $keys = array_keys($messagesArray);

        $this->db->beginTransaction();
        $stmt = $this->db->table($this->table)
            ->select("`$this->key` AS `key`")
            ->where("locale  = ?", $locale)
            ->where("`key` IN (?)", $keys);
        $existingTranslations = array_column($stmt->fetchAll(), 'key'); //to get only one dimensional array of keys
        Debugger::barDump($existingTranslations, 'existing translations');
        foreach ($messagesArray as $key => $translation) {
            if (in_array($key, $existingTranslations)) {
                $this->db->table($this->table)
                    ->where("$this->key = ?", $key)
                    ->where("$this->locale = ?", $locale)
                    ->update([$this->translation => $translation]);
            } else {
                $this->db->table($this->table)
                    ->insert([
                        $this->key => $key,
                        $this->locale => $locale,
                        $this->translation => $translation
                    ]);
            }
        }

        $this->db->commit();

    }

    private function flatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode) {
            $subnode = &$messages;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path.'.'.$key : $key;
                $this->flatten($messages, $value, $nodePath);
                if (null === $path) {
                    unset($messages[$key]);
                }
            } elseif (null !== $path) {
                $messages[$path.'.'.$key] = $value;
            }
        }
    }

}
