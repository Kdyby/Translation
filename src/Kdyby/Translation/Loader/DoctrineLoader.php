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

class DoctrineLoader extends DatabaseLoader {

    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em) {
        $this->em = $em;
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

    public function getTranslations($locale)
    {
        $conn = $this->em->getConnection();
        $qb = $conn->createQueryBuilder()
            ->addSelect("`$this->key` AS `key`")
            ->addSelect("`$this->locale` AS locale")
            ->addSelect("`$this->message` AS message")
            ->from("`$this->table`")
            ->where("locale = :locale")
            ->setParameter('locale', $locale);
        return $qb->execute();
    }
}
