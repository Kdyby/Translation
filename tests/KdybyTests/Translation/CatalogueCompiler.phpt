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



	public function testLocaleEscaping()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->getByType('Nette\Localization\ITranslator');
		$translator->setFallbackLocales(array('fr_FR', 'fr.UTF8', 'cs_CZ', 'cs'));

		/** @var \Kdyby\Translation\CatalogueCompiler $compiler */
		$compiler = $container->getByType('Kdyby\Translation\CatalogueCompiler');

		$catalogues = array();
		$compiler->compile($translator, $catalogues, 'fr-FR.UTF8');

		$tempFiles = array_filter(get_included_files(), function ($path) {
			return stripos(realpath($path), realpath(TEMP_DIR)) !== FALSE && stripos($path, 'fr-FR.UTF8');
		});

		Assert::count(1, $tempFiles);

		$compiledCatalogue = call_user_func(function ($__file) use (&$__definedVariables) {
			$__definedVariables = get_defined_vars() + array('__compiled' => NULL);
			$__compiled = include $__file;
			$__definedVariables = array_diff_key(get_defined_vars(), $__definedVariables);

			return $__compiled;
		}, reset($tempFiles));

		Assert::type('Symfony\Component\Translation\MessageCatalogue', $compiledCatalogue);
		Assert::same(array(
			'catalogue',
			'catalogueFr_FR',
			'catalogueFr_UTF8',
			'catalogueCs_CZ',
			'catalogueCs'
		), array_keys($__definedVariables));
	}

}

\run(new CatalogueCompilerTest());
