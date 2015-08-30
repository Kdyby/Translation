<?php

namespace Kdyby\Translation\Dumper;

use Nette\Database\Context;
use Nette\Utils\DateTime;

class NetteDbDumper extends DatabaseDumper
{

    /** @var Context */
    private $db;

    /**
     * @param Context $db
     */
    public function __construct(Context $db)
    {
        $this->db = $db;
    }


    public function getExistingKeys($keys, $locale)
    {
        $stmt = $this->db->table($this->table)
            ->select("`$this->key` AS `key`")
            ->where("locale  = ?", $locale)
            ->where("`key` IN (?)", $keys);
        return $stmt->fetchPairs('key', 'key'); //to get only one dimensional array of keys
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function insert($key, $locale, $message)
    {
        $this->db->table($this->table)
            ->insert([
                $this->key => $key,
                $this->locale => $locale,
                $this->message => $message,
                $this->updatedAt => new \DateTime()
            ]);
    }

    public function update($key, $locale, $message)
    {
        $this->db->table($this->table)
            ->where("$this->key = ?", $key)
            ->where("$this->locale = ?", $locale)
            ->update([
                $this->message => $message,
                $this->updatedAt => new \DateTime()
            ]);
    }

    public function createTable()
    {
        $this->db->query("CREATE TABLE `$this->table` (
                          `$this->key` varchar(50) NOT NULL,
                          `$this->locale` varchar(50) NOT NULL,
                          `message` longtext,
                          `updated_at` datetime NOT NULL,
                          PRIMARY KEY (`$this->key`,`$this->locale`)
                        );");
    }
}
