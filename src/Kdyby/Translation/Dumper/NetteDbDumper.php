<?php

namespace Kdyby\Translation\Dumper;

use Nette\Database\Context;

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


    protected function getExistingKeys($keys, $locale)
    {
        return $this->db->table($this->table)
            ->select("`$this->key` AS `key`")
            ->where("`$this->locale` = ?", $locale)
            ->where("`key` IN (?)", $keys)
            ->fetchPairs('key', 'key'); //to get only one dimensional array of keys
    }

    protected function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    protected function commit()
    {
        $this->db->commit();
    }

    protected function insert($key, $locale, $message)
    {
        $this->db->table($this->table)
            ->insert([
                $this->key => $key,
                $this->locale => $locale,
                $this->message => $message,
                $this->updatedAt => new \DateTime()
            ]);
    }

    protected function update($key, $locale, $message)
    {
        $this->db->table($this->table)
            ->where("$this->key = ?", $key)
            ->where("$this->locale = ?", $locale)
            ->update([
                $this->message => $message,
                $this->updatedAt => new \DateTime()
            ]);
    }

    protected function rollBack()
    {
        $this->db->rollBack();
    }
}
