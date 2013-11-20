<?php

/**
 * Test: Kdyby\Translation\TemplateHelpers.
 *
 * @testCase KdybyTests\Translation\TemplateHelpersTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TemplateHelpersTest extends Tester\TestCase
{

	public function testTranslate()
	{
		$translator = $this->createTranslator();
		$helper = new Kdyby\Translation\TemplateHelpers($translator);

		Assert::same("Ahoj %name%", $helper->translate('front.homepage.namedHello', 3, NULL, NULL, 'cs'));
		Assert::same("Ahoj Peter", $helper->translate('front.homepage.namedHello', 3, array('name' => 'Peter'), NULL, 'cs'));
		Assert::same("Ahoj Peter", $helper->translate('front.homepage.namedHello', array('name' => 'Peter'), NULL, 'cs'));

		Assert::same("Helloes %name%", $helper->translate('front.homepage.namedHelloCounting', 3, NULL, NULL, 'en'));
		Assert::same("Helloes Peter", $helper->translate('front.homepage.namedHelloCounting', 3, array('name' => 'Peter'), NULL, 'en'));
		Assert::same("Hello Peter|Helloes Peter", $helper->translate('front.homepage.namedHelloCounting', array('name' => 'Peter'), NULL, 'en'));
	}



	private function createTranslator()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('appDir' => __DIR__));
		Kdyby\Translation\DI\TranslationExtension::register($config);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$container = $config->createContainer();
		/** @var \Nette\DI\Container|\SystemContainer $container */

		$translator = $container->getByType('Nette\Localization\ITranslator');
		/** @var Kdyby\Translation\Translator $translator */

		return $translator;
	}

}

\run(new TemplateHelpersTest());
