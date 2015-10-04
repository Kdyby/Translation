<?php

/**
 * Test: Kdyby\Translation\TranslationLoader.
 *
 * @testCase KdybyTests\Translation\TranslationLoaderTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Kdyby\Translation\TranslationLoader;
use Nette;
use Symfony;
use Tester;
use Tester\Assert;
use Doctrine;
use Tracy\Debugger;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslationLoaderTest extends TestCase
{
	/** @var Doctrine\DBAL\Connection $connection */
	private $connection;

	public function __construct()
	{
		Tester\Environment::lock('db', dirname(TEMP_DIR));
	}

	protected function setUp()
	{
		parent::setUp();
		$container = $this->createContainer();
		$this->connection = $container->getByType('Doctrine\DBAL\Connection');
		$this->connection->executeUpdate(file_get_contents(__DIR__ . '/../init.sql'));
	}


	public function testAddLoaders()
	{

		$loader = new TranslationLoader();
		Assert::same(array(), $loader->getLoaders());

		$loader->addLoader('neon', $neonLoader = new Kdyby\Translation\Loader\NeonFileLoader());
		$loader->addLoader('database', $dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($this->connection));
		Assert::same(array('neon' => $neonLoader, 'database' => $dbLoader), $loader->getLoaders());
	}



	public function testLoadResources()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('neon', new Kdyby\Translation\Loader\NeonFileLoader());
		$loader->addLoader('database', $dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($this->connection));

		$catalogue = new Kdyby\Translation\MessageCatalogue('cs_CZ');
		$loader->loadResource('neon', __DIR__ . '/lang/front.cs_CZ.neon', 'front', $catalogue);
		$loader->loadResource('database', Kdyby\Translation\Resource\DatabaseResource::DOCTRINE, NULL, $catalogue);

		Assert::true($catalogue->defines('homepage.hello', 'front'));
		Assert::true($catalogue->defines('header', 'front'));
		Assert::true($catalogue->defines('hello', 'messages'));

		$catalogue = new Kdyby\Translation\MessageCatalogue('en');
		$loader->loadResource('database', Kdyby\Translation\Resource\DatabaseResource::DOCTRINE, NULL, $catalogue);
//
		Assert::true($catalogue->defines('header', 'front'));
		Assert::true($catalogue->defines('hello', 'messages'));

	}

	public function testLoadLocales()
	{
		$dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($this->connection);

		$result = $this->connection->executeQuery($this->connection->getDatabasePlatform()->getListTablesSQL())->fetchAll();
		Kdyby\Translation\Helpers::flatten($result);
		Debugger::log($result);

		Assert::same(array('cs_CZ', 'en'), $dbLoader->getLocales());
	}

	public function rearDown()
	{
		parent::tearDown();
		$this->connection->executeUpdate(file_get_contents(__DIR__ . '/../clear.sql'));
	}

}

\run(new TranslationLoaderTest());
