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
use Nette\Utils\Strings;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Tracy\Debugger;

class DoctrineLoader extends DatabaseLoader {

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

    function load($resource, $locale, $domain = NULL) {
        $catalogue = new MessageCatalogue($locale);

        $conn = $this->em->getConnection();
        $qb = $conn->createQueryBuilder()
            ->addSelect("`$this->key` AS `key`")
            ->addSelect("`$this->locale` AS locale")
            ->addSelect("`$this->translation` AS translation")
            ->from("`$this->table`")
            ->where("locale = :locale")
            ->setParameter('locale', $locale);
        $stmt = $qb->execute();
        $translations = $stmt->fetchAll();
        foreach($translations as $translation) {
            if ($domain === NULL) {
                $key = $translation['key'];
                if (Strings::contains($key, '.')) {
                    $prefix = Strings::substring($key, 0, Strings::indexOf($key, '.'));
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
        $conn = $this->em->getConnection();
        $qb = $conn->createQueryBuilder()
            ->addSelect("DISTINCT `$this->locale` AS locale")
            ->from("`$this->table`");
        $stmt = $qb->execute();
        $locales = array_column($stmt->fetchAll(), 'locale');
        return $locales;
    }


    public function getResourceName()
    {
        return DatabaseResource::DOCTRINE;
    }

}
