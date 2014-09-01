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
class TemplateHelpersTest extends TestCase
{

	public function testTranslate()
	{
		$translator = $this->createTranslator();
		$helper = new Kdyby\Translation\TemplateHelpers($translator);

		Assert::equal(Nette\Utils\Html::el()->setHtml("Ahoj %name%"), $helper->translate('front.homepage.namedHello', 3, NULL, NULL, 'cs'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("Ahoj Peter"), $helper->translate('front.homepage.namedHello', 3, array('name' => 'Peter'), NULL, 'cs'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("Ahoj Peter"), $helper->translate('front.homepage.namedHello', array('name' => 'Peter'), NULL, 'cs'));

		Assert::equal(Nette\Utils\Html::el()->setHtml("missingKey.namedHello"), $helper->translate('front.missingKey.namedHello', 3, NULL, NULL, 'cs'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("missingKey.namedHello"), $helper->translate('front.missingKey.namedHello', 3, array('name' => 'Peter'), NULL, 'cs'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("missingKey.namedHello"), $helper->translate('front.missingKey.namedHello', array('name' => 'Peter'), NULL, 'cs'));

		Assert::equal(Nette\Utils\Html::el()->setHtml("Helloes %name%"), $helper->translate('front.homepage.namedHelloCounting', 3, NULL, NULL, 'en'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("Helloes Peter"), $helper->translate('front.homepage.namedHelloCounting', 3, array('name' => 'Peter'), NULL, 'en'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("Hello Peter|Helloes Peter"), $helper->translate('front.homepage.namedHelloCounting', array('name' => 'Peter'), NULL, 'en'));

		Assert::equal(Nette\Utils\Html::el()->setHtml("missingKey.namedHelloCounting"), $helper->translate('front.missingKey.namedHelloCounting', 3, NULL, NULL, 'en'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("missingKey.namedHelloCounting"), $helper->translate('front.missingKey.namedHelloCounting', 3, array('name' => 'Peter'), NULL, 'en'));
		Assert::equal(Nette\Utils\Html::el()->setHtml("missingKey.namedHelloCounting"), $helper->translate('front.missingKey.namedHelloCounting', array('name' => 'Peter'), NULL, 'en'));
	}

}

\run(new TemplateHelpersTest());
