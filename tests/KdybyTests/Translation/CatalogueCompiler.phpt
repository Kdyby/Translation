<?php
/**
 * Test: Kdyby\Translation\CatalogueCompiler.
 *
 * @testCase KdybyTests\Translation\CatalogueCompilerTest
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
class CatalogueCompilerTest extends TestCase
{

    public function testFallbacks()
    {
        $container = $this->createContainer();

        /** @var Kdyby\Translation\Translator $translator */
        $translator = $container->getByType('Nette\Localization\ITranslator');
        $translator->setFallbackLocales(array('cs_CZ', 'cs'));

        Assert::same("Ahoj světe", $translator->translate('homepage.hello', NULL, array(), 'front', 'fr'));

        $translator->setFallbackLocales(array());
        Assert::same("homepage.hello", $translator->translate('homepage.hello', NULL, array(), 'front', 'fr'));

        $translator->setFallbackLocales(array('en_US', 'en'));
        Assert::same("Hello world", $translator->translate('homepage.hello', NULL, array(), 'front', 'fr'));

    }

}

\run(new CatalogueCompilerTest());
