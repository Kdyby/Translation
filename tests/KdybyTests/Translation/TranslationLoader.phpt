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

		Assert::true($catalogue->defines('front.homepage.hello'));
	}

}

\run(new TranslationLoaderTest());
