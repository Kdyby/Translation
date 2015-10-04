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
class TranslationDumperTest extends TestCase
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
		$this->connection->createQueryBuilder()->select('*')->from('translations')->execute(); //just to check table creating
	}


	public function testChangeTranslations()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('database', $dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($this->connection));

		$catalogue = new Kdyby\Translation\MessageCatalogue('cs_CZ');
		$loader->loadResource('database', Kdyby\Translation\Resource\DatabaseResource::DOCTRINE, NULL, $catalogue);

		Assert::true($catalogue->defines('header', 'front'));
		Assert::true($catalogue->defines('hello', 'messages'));
		Assert::same('záhlaví', $catalogue->get('header', 'front'));
		Assert::same('ahoj', $catalogue->get('hello', 'messages'));

		$container = $this->createContainer();
		/** @var Symfony\Component\Translation\Writer\TranslationWriter $writer */
		$writer = $container->getByType('Symfony\Component\Translation\Writer\TranslationWriter');
		$catalogue->set('header', 'úvodka', 'front');
		$catalogue->set('hello', 'nazdar', 'messages');
		$writer->writeTranslations($catalogue, 'database');

		Assert::same('úvodka', $catalogue->get('header', 'front'));
		Assert::same('nazdar', $catalogue->get('hello', 'messages'));
	}



	public function testAddTranslations()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('database', $dbLoader = new Kdyby\Translation\Loader\DoctrineLoader($this->connection));

		$catalogue = new Kdyby\Translation\MessageCatalogue('cs_CZ');
		$loader->loadResource('database', Kdyby\Translation\Resource\DatabaseResource::DOCTRINE, NULL, $catalogue);

		Assert::false($catalogue->defines('footer', 'messages'));
		Assert::false($catalogue->defines('farewell', 'front'));


		$container = $this->createContainer();
		/** @var Symfony\Component\Translation\Writer\TranslationWriter $writer */
		$writer = $container->getByType('Symfony\Component\Translation\Writer\TranslationWriter');
		$catalogue->add(array('farewell' => 'Sbohem'), 'front');
		$catalogue->add(array('footer' => 'Zápatí'), 'messages');
		$writer->writeTranslations($catalogue, 'database');

		Assert::true($catalogue->defines('footer', 'messages'));
		Assert::true($catalogue->defines('farewell', 'front'));
	}

	public function tearDown()
	{
		parent::tearDown();

		$this->connection->executeUpdate(file_get_contents(__DIR__ . '/../clear.sql'));
	}

}

\run(new TranslationDumperTest());
