<?php

namespace Kdyby\Translation\Dumper;

use Kdyby\Translation\Helpers;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\MessageCatalogue;

abstract class DatabaseDumper implements DumperInterface
{

    /** @var string */
    protected $table = 'translations';

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
    public function setTableName($table)
    {
        $this->table = $table;
    }

    /**
     * @param string $key
     * @param string $locale
     * @param string $message
     * @param string $updatedAt
     */
    public function setColumnNames($key, $locale, $message, $updatedAt)
    {
        $this->key = $key;
        $this->locale = $locale;
        $this->message = $message;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Dumps the message catalogue.
     *
     * @param MessageCatalogue $messages The message catalogue
     * @param array $options Options that are used by the dumper
     */
    public function dump(MessageCatalogue $messages, $options = array())
    {
        $messagesArray = $messages->all();
        Helpers::flatten($messagesArray);
        $locale = $messages->getLocale();
        $keys = array_keys($messagesArray);

        $this->beginTransaction();
        $existingKeys = $this->getExistingKeys($keys, $locale);
        foreach ($messagesArray as $key => $message) {
            if (in_array($key, $existingKeys)) {
                $this->update($key, $locale, $message);
            } else {
                $this->insert($key, $locale, $message);
            }
        }
        $this->commit();
    }

    abstract public function getExistingKeys($keys, $locale);

    abstract public function beginTransaction();

    abstract public function commit();

    abstract public function rollBack();

    abstract public function insert($key, $locale, $message);

    abstract public function update($key, $locale, $message);

}
