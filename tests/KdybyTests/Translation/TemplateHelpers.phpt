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

	public function dataTranslate()
	{
		return array(
			array("Ahoj %name%", 'front.homepage.namedHello', 3, NULL, NULL, 'cs'),
			array("Ahoj Peter", 'front.homepage.namedHello', 3, array('name' => 'Peter'), NULL, 'cs'),
			array("Ahoj Peter", 'front.homepage.namedHello', array('name' => 'Peter'), NULL, 'cs'),

			array("missingKey.namedHello", 'front.missingKey.namedHello', 3, NULL, NULL, 'cs'),
			array("missingKey.namedHello", 'front.missingKey.namedHello', 3, array('name' => 'Peter'), NULL, 'cs'),
			array("missingKey.namedHello", 'front.missingKey.namedHello', array('name' => 'Peter'), NULL, 'cs'),

			array("Helloes %name%", 'front.homepage.namedHelloCounting', 3, NULL, NULL, 'en'),
			array("Helloes Peter", 'front.homepage.namedHelloCounting', 3, array('name' => 'Peter'), NULL, 'en'),
			array("Hello Peter|Helloes Peter", 'front.homepage.namedHelloCounting', array('name' => 'Peter'), NULL, 'en'),

			array("missingKey.namedHelloCounting", 'front.missingKey.namedHelloCounting', 3, NULL, NULL, 'en'),
			array("missingKey.namedHelloCounting", 'front.missingKey.namedHelloCounting', 3, array('name' => 'Peter'), NULL, 'en'),
			array("missingKey.namedHelloCounting", 'front.missingKey.namedHelloCounting', array('name' => 'Peter'), NULL, 'en'),
		);
	}



	/**
	 * @dataProvider dataTranslate
	 */
	public function testTranslate($expected, $message, $count = NULL, $parameters = array(), $domain = NULL, $locale = NULL)
	{
		$translator = $this->createTranslator();
		$helper = new Kdyby\Translation\TemplateHelpers($translator);

		Assert::equal($expected, $helper->translate($message, $count, $parameters, $domain, $locale));
	}



	/**
	 * @dataProvider dataTranslate
	 */
	public function testTranslate_WrappedInHtml($expected, $message, $count = NULL, $parameters = array(), $domain = NULL, $locale = NULL)
	{
		$translator = $this->createTranslator();
		$helper = new Kdyby\Translation\TemplateHelpers($translator);
		$helper->setWrapInHtmlObject(TRUE);

		Assert::equal(Nette\Utils\Html::el()->setHtml($expected), $helper->translate($message, $count, $parameters, $domain, $locale));
	}

}

\run(new TemplateHelpersTest());
