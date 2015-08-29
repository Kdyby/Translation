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
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Tracy\Debugger;

class DoctrineDumper implements IDatabaseDumper {

    /** @var string */
    private $table;

    /** @var string */
    private $key;

    /** @var string */
    private $locale;

    /** @var string */
    private $translation;

    /** @var EntityManager */
    private $em;

    /**
     * @param $config
     * @param EntityManager $em
     */
    public function __construct($config, EntityManager $em) {
        $this->table = $config['table'];
        $this->key = $config['columns']['key'];
        $this->locale = $config['columns']['locale'];
        $this->translation = $config['columns']['translation'];
        $this->em = $em;
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

        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        $qb = $conn->createQueryBuilder()
            ->addSelect("`$this->key` AS `key`")
            ->from("`$this->table`")
            ->andWhere("locale  = :locale")
            ->andWhere("`key` IN (:keys)")
            ->setParameter('locale', $locale)
            ->setParameter('keys', $keys, Connection::PARAM_STR_ARRAY);
        $stmt = $qb->execute();
        $existingTranslations = array_column($stmt->fetchAll(), 'key'); //to get only one dimensional array of keys
        Debugger::barDump($existingTranslations, 'existing translations');
        foreach ($messagesArray as $key => $translation) {
            $qb = $conn->createQueryBuilder();
            if (in_array($key, $existingTranslations)) {
                $qb->update($this->table, 't')
                    ->set("t.$this->translation", ':translation')
                    ->andWhere("t.$this->key = :key")
                    ->andWhere("t.$this->locale = :locale")
                    ->setParameters([
                        'key' => $key,
                        'locale' => $locale,
                        'translation' => $translation
                    ]);
            } else {
                $qb->insert($this->table)
                    ->values([
                        "`$this->key`" => ":key",
                        "`$this->locale`" => ":locale",
                        "`$this->translation`" => ":translation"
                    ])
                    ->setParameters([
                        'key' => $key,
                        'locale' => $locale,
                        'translation' => $translation
                    ]);
            }
            $qb->execute();
        }

        $conn->commit();

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