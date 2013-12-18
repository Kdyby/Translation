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
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslateMacrosTest extends TestCase
{

	public function testRender_translate()
	{
		$template = $this->buildTemplate()->setFile(__DIR__ . '/files/Homepage.default.latte');

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
missingKey.namedHelloCounting' . "\n", $template->__toString());
	}



	public function testRender_translate_prefixed()
	{
		$template = $this->buildTemplate()->setFile(__DIR__ . '/files/Order.default.latte');

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
missingKey.namedHelloCounting' . "\n", $template->__toString());
	}



	/**
	 * @return Nette\Templating\FileTemplate
	 */
	private function buildTemplate()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->getByType('Nette\Localization\ITranslator');
		$translator->setFallbackLocales(array('cs_CZ', 'cs'));
		$translator->setLocale('cs');

		$template = new Nette\Templating\FileTemplate();
		$template->registerHelperLoader(array($translator->createTemplateHelpers(), 'loader'));
		$template->registerFilter($engine = new Nette\Latte\Engine());
		Kdyby\Translation\Latte\TranslateMacros::install($engine->getCompiler());

		return $template;
	}

}

\run(new TranslateMacrosTest());
