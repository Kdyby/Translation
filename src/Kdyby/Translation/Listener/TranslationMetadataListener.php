<?php

namespace Kdyby\Translation\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Kdyby\Doctrine\Events;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Tracy\Debugger;

class TranslationMetadataListener extends Object implements Subscriber {

    const FAKE_ENTITY_NAME = 'Translation';

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
    public function setColumns($key, $locale, $message, $updatedAt)
    {
        $this->key = $key;
        $this->locale = $locale;
        $this->message = $message;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(Events::loadClassMetadata => 'loadClassMetadata');
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $factory = $eventArgs->getEntityManager()->getMetadataFactory();
        if (!$factory->hasMetadataFor(self::FAKE_ENTITY_NAME)) {
            $metadata = new ClassMetadata(self::FAKE_ENTITY_NAME);
            $builder = new ClassMetadataBuilder($metadata);
            $builder->addField("$this->key", 'string')
                ->addField("$this->locale", 'string')
                ->addField("$this->message", 'text')
                ->addField("$this->updatedAt", 'datetime');
            $metadata->setIdentifier(array(
                $this->key => array('type' => 'string'),
                $this->locale => array('type' => 'string'),
            ));

            $factory->setMetadataFor(self::FAKE_ENTITY_NAME, $metadata);
            Debugger::barDump($factory->getAllMetadata(), 'all metadata');
        }
        Debugger::barDump('adding metadata in event');
    }
}
