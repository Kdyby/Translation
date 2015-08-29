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

abstract class DatabaseLoader implements IDatabaseLoader {


    abstract function load($resource, $locale, $domain = NULL);

    /**
     * @return array
     */
    abstract public function getLocales();

    public function addResources(Translator $translator)
    {
        Debugger::barDump(get_called_class(), 'add resources');
        foreach ($this->getLocales() as $locale) {
//            $translator->addResource('doctrine', new DatabaseResource('doctrine'), $locale);
            $translator->addResource($this->getResourceName(), $this->getResourceName(), $locale);
        }
    }

    abstract public function getResourceName();
}
