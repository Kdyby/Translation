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

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslationLoaderTest extends TestCase
{

	protected function setUp()
	{
		parent::setUp();
		$container = $this->createContainer();

		/** @var Doctrine\DBAL\Connection $connection */
		$connection = $container->getByType('Doctrine\DBAL\Connection');

		$connection->executeUpdate(file_get_contents(__DIR__ . '/../init.sql'));
	}


	public function testAddLoaders()
	{
		$container = $this->createContainer();

		/** @var Doctrine\DBAL\Connection $connection */
		$connection = $container->getByType('Doctrine\DBAL\Connection');

		$loader = new TranslationLoader();
		Assert::same(array(), $loader->getLoaders());

		$loader->addLoader('neon', $neonLoader = new Kdyby\Translation\Loader\NeonFileLoader());
		$loader->addLoader('database', $dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($connection));
		Assert::same(array('neon' => $neonLoader, 'database' => $dbLoader), $loader->getLoaders());
	}



	public function loadResource()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('neon', new Kdyby\Translation\Loader\NeonFileLoader());

		$catalogue = new Kdyby\Translation\MessageCatalogue('cs_CZ');
		$loader->loadResource('neon', __DIR__ . '/lang/front.cs_CZ.neon', 'front', $catalogue);
		$loader->loadResource('database', 'database', NULL, $catalogue);

		Assert::true($catalogue->defines('front.homepage.hello'));
		Assert::true($catalogue->defines('front.header'));

		$catalogue = new Kdyby\Translation\MessageCatalogue('en');
		$loader->loadResource('database', 'database', NULL, $catalogue);

		Assert::true($catalogue->defines('front.header'));
	}

	public function testLoadLocales()
	{
		$container = $this->createContainer();

		/** @var Doctrine\DBAL\Connection $connection */
		$dbLoader = $container->getByType('Kdyby\Translation\Loader\DatabaseLoader');

		Assert::same(array('cs', 'en'), $dbLoader->getLocales());
	}

	protected function tearDown()
	{
		parent::tearDown();
		$container = $this->createContainer();

		/** @var Doctrine\DBAL\Connection $connection */
		$connection = $container->getByType('Doctrine\DBAL\Connection');

		$connection->executeUpdate(file_get_contents(__DIR__ . '/../clear.sql'));
	}


}

\run(new TranslationLoaderTest());
