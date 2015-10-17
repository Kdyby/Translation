<?php

/**
 * Test: Kdyby\Translation\TranslationLoader.
 *
 * @testCase KdybyTests\Translation\TranslationLoaderNetteDbTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Kdyby\Translation\DI\Configuration;
use Kdyby\Translation\Loader\NetteDbLoader;
use Kdyby\Translation\MessageCatalogue;
use Kdyby\Translation\Resource\DatabaseResource;
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
class TranslationLoaderNetteDbTest extends TestCase
{

	/**
	 * @var \Nette\Database\Context
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
		$this->container = $this->createContainer('netteDb');
		$this->db = $this->container->getByType('Nette\Database\Context');
		Nette\Database\Helpers::loadFromFile($this->db->getConnection(), __DIR__ . '/../init.sql');
	}



	public function tearDown()
	{
		parent::tearDown();
		Nette\Database\Helpers::loadFromFile($this->db->getConnection(), __DIR__ . '/../clear.sql');
	}



	public function testAddLoaders()
	{
		$loader = new TranslationLoader();
		Assert::same(array(), $loader->getLoaders());

		$loader->addLoader('database', $dbLoader = new NetteDbLoader($this->db, new Configuration()));
		Assert::same(array('database' => $dbLoader), $loader->getLoaders());
	}



	public function testLoadResources()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('database', $dbLoader = new NetteDbLoader($this->db, new Configuration()));

		$catalogue = new MessageCatalogue('cs_CZ');
		$loader->loadResource('database', DatabaseResource::NETTE_DB, NULL, $catalogue);

		Assert::true($catalogue->defines('header', 'front'));
		Assert::true($catalogue->defines('hello', 'messages'));

		$catalogue = new MessageCatalogue('en');
		$loader->loadResource('database', DatabaseResource::NETTE_DB, NULL, $catalogue);

		Assert::true($catalogue->defines('header', 'front'));
		Assert::true($catalogue->defines('hello', 'messages'));
	}



	public function testLoadLocales()
	{
		$dbLoader = new NetteDbLoader($this->db, new Configuration());
		Assert::same(array('cs_CZ' => 'cs_CZ', 'en' => 'en'), $dbLoader->getLocales());
	}

}



\run(new TranslationLoaderNetteDbTest());
