<?php

/**
 * Test: Kdyby\Translation\TranslationDumper.
 *
 * @testCase KdybyTests\Translation\TranslationLoaderTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Kdyby\Translation\DI\Configuration;
use Kdyby\Translation\TranslationLoader;
use Nette;
use Symfony;
use Tester;
use Tester\Assert;
use Doctrine;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslationDumperDoctrineTest extends TestCase
{

	/**
	 * @var Doctrine\DBAL\Connection
	 */
	private $db;

	/**
	 * @var \Nette\DI\Container
	 */
	private $container;



	protected function setUp()
	{
		parent::setUp();
		Tester\Environment::lock('db', dirname(TEMP_DIR));
		$this->container = $this->createContainer('doctrine');
		$this->db = $this->container->getByType('Doctrine\DBAL\Connection');
		$this->db->executeUpdate(file_get_contents(__DIR__ . '/../init.sql'));
	}



	public function tearDown()
	{
		parent::tearDown();
		$this->db->executeUpdate(file_get_contents(__DIR__ . '/../clear.sql'));
	}



	public function testChangeTranslations()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('database', $dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($this->db, new Configuration()));

		$catalogue = new Kdyby\Translation\MessageCatalogue('cs_CZ');
		$loader->loadResource('database', Kdyby\Translation\Resource\DatabaseResource::DOCTRINE, NULL, $catalogue);

		Assert::true($catalogue->defines('header', 'front'));
		Assert::true($catalogue->defines('hello', 'messages'));
		Assert::same('záhlaví', $catalogue->get('header', 'front'));
		Assert::same('ahoj', $catalogue->get('hello', 'messages'));

		$writer = $this->container->getByType('Symfony\Component\Translation\Writer\TranslationWriter');
		$catalogue->set('header', 'úvodka', 'front');
		$catalogue->set('hello', 'nazdar', 'messages');
		$writer->writeTranslations($catalogue, 'database');

		Assert::same('úvodka', $catalogue->get('header', 'front'));
		Assert::same('nazdar', $catalogue->get('hello', 'messages'));
	}



	public function testAddTranslations()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('database', $dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($this->db, new Configuration()));

		$catalogue = new Kdyby\Translation\MessageCatalogue('cs_CZ');
		$loader->loadResource('database', Kdyby\Translation\Resource\DatabaseResource::DOCTRINE, NULL, $catalogue);

		Assert::false($catalogue->defines('footer', 'messages'));
		Assert::false($catalogue->defines('farewell', 'front'));

		$writer = $this->container->getByType('Symfony\Component\Translation\Writer\TranslationWriter');
		$catalogue->add(array('farewell' => 'Sbohem'), 'front');
		$catalogue->add(array('footer' => 'Zápatí'), 'messages');
		$writer->writeTranslations($catalogue, 'database');

		Assert::true($catalogue->defines('footer', 'messages'));
		Assert::true($catalogue->defines('farewell', 'front'));
	}

}



\run(new TranslationDumperDoctrineTest());
