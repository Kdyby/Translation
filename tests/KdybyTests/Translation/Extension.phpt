<?php

/**
 * Test: Kdyby\Translation\Extension.
 *
 * @testCase KdybyTests\Translation\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Symfony;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	public function testFunctionality()
	{
		$config = new Nette\Config\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('appDir' => __DIR__));
		Kdyby\Translation\DI\TranslationExtension::register($config);
		$container = $config->createContainer();
		/** @var \Nette\DI\Container|\SystemContainer $container */

		$translator = $container->getByType('Nette\Localization\ITranslator');
		/** @var Kdyby\Translation\Translator $translator */
		Assert::true($translator instanceof Nette\Localization\ITranslator);
		Assert::true($translator instanceof Kdyby\Translation\Translator);
		Assert::true($translator instanceof Symfony\Component\Translation\Translator);

		Assert::same("Ahoj světe", $translator->translate('homepage.hello', NULL, array(), 'front', 'cs'));
		Assert::same("Hello world", $translator->translate('homepage.hello', NULL, array(), 'front', 'en'));
	}

}

\run(new ExtensionTest());
