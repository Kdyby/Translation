<?php

/**
 * Test: Kdyby\Translation\TranslateMacros.
 *
 * @testCase KdybyTests\Translation\TranslateMacrosTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Latte;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



class ControlMock extends Nette\Application\UI\Control
{

}

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslateMacrosTest extends TestCase
{

	public function testRender_translate()
	{
		$template = $this->buildTemplate();

		Assert::same('Ahoj %name%
Ahoj Peter
Ahoj Peter

Ahoj %name%
Ahoj Peter
Ahoj Peter

missingKey.namedHello
missingKey.namedHello
missingKey.namedHello

Helloes %name%
Helloes Peter
Hello Peter|Helloes Peter

missingKey.namedHelloCounting
missingKey.namedHelloCounting
missingKey.namedHelloCounting' . "\n", (string) $template->setFile(__DIR__ . '/files/Homepage.default.latte'));
	}



	public function testRender_translate_prefixed()
	{
		$template = $this->buildTemplate();

		Assert::same('
Ahoj %name%
Ahoj Peter
Ahoj Peter

Ahoj %name%
Ahoj Peter
Ahoj Peter



missingKey.namedHello
missingKey.namedHello
missingKey.namedHello



Helloes %name%
Helloes Peter
Hello Peter|Helloes Peter



missingKey.namedHelloCounting
missingKey.namedHelloCounting
missingKey.namedHelloCounting' . "\n", (string) $template->setFile(__DIR__ . '/files/Order.default.latte'));
	}



	/**
	 * @return \Nette\Bridges\ApplicationLatte\Template
	 */
	private function buildTemplate()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->getByType('Nette\Localization\ITranslator');
		$translator->setFallbackLocales(array('cs_CZ', 'cs'));
		$translator->setLocale('cs');

		return $container->getByType('Nette\Application\UI\ITemplateFactory')->createTemplate(new ControlMock());
	}

}

\run(new TranslateMacrosTest());
