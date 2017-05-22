<?php

/**
 * Test: Kdyby\Translation\TranslationLoader.
 *
 * @testCase
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Loader\NeonFileLoader;
use Kdyby\Translation\MessageCatalogue;
use Kdyby\Translation\TranslationLoader;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class TranslationLoaderTest extends \KdybyTests\Translation\TestCase
{

	public function testAddLoaders()
	{
		$loader = new TranslationLoader();
		Assert::same([], $loader->getLoaders());

		$neonLoader = new NeonFileLoader();
		$loader->addLoader('neon', $neonLoader);
		Assert::same(['neon' => $neonLoader], $loader->getLoaders());
	}

	public function testLoadResource()
	{
		$loader = new TranslationLoader();
		$loader->addLoader('neon', new NeonFileLoader());

		$catalogue = new MessageCatalogue('cs_CZ');
		$loader->loadResource('neon', __DIR__ . '/lang/front.cs_CZ.neon', 'front', $catalogue);

		Assert::true($catalogue->defines('homepage.hello', 'front'));
	}

}

(new TranslationLoaderTest())->run();
