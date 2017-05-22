<?php

/**
 * Test: Kdyby\Translation\TemplateHelpers.
 *
 * @testCase
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\TemplateHelpers;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class TemplateHelpersTest extends \KdybyTests\Translation\TestCase
{

	public function testTranslate()
	{
		$translator = $this->createTranslator();
		$helper = new TemplateHelpers($translator);

		Assert::same('Ahoj %name%', $helper->translate('front.homepage.namedHello', 3, NULL, NULL, 'cs'));
		Assert::same('Ahoj Peter', $helper->translate('front.homepage.namedHello', 3, ['name' => 'Peter'], NULL, 'cs'));
		Assert::same('Ahoj Peter', $helper->translate('front.homepage.namedHello', ['name' => 'Peter'], NULL, 'cs'));

		Assert::same('front.missingKey.namedHello', $helper->translate('front.missingKey.namedHello', 3, NULL, NULL, 'cs'));
		Assert::same('front.missingKey.namedHello', $helper->translate('front.missingKey.namedHello', 3, ['name' => 'Peter'], NULL, 'cs'));
		Assert::same('front.missingKey.namedHello', $helper->translate('front.missingKey.namedHello', ['name' => 'Peter'], NULL, 'cs'));

		Assert::same('Helloes %name%', $helper->translate('front.homepage.namedHelloCounting', 3, NULL, NULL, 'en'));
		Assert::same('Helloes Peter', $helper->translate('front.homepage.namedHelloCounting', 3, ['name' => 'Peter'], NULL, 'en'));
		Assert::same('Hello Peter|Helloes Peter', $helper->translate('front.homepage.namedHelloCounting', ['name' => 'Peter'], NULL, 'en'));

		Assert::same('front.missingKey.namedHelloCounting', $helper->translate('front.missingKey.namedHelloCounting', 3, NULL, NULL, 'en'));
		Assert::same('front.missingKey.namedHelloCounting', $helper->translate('front.missingKey.namedHelloCounting', 3, ['name' => 'Peter'], NULL, 'en'));
		Assert::same('front.missingKey.namedHelloCounting', $helper->translate('front.missingKey.namedHelloCounting', ['name' => 'Peter'], NULL, 'en'));
	}

}

(new TemplateHelpersTest())->run();
