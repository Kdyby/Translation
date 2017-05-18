<?php

/**
 * Test: Kdyby\Translation\LatteExtractor.
 *
 * @testCase KdybyTests\Translation\LatteExtractorTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Extractors\LatteExtractor;
use Nette;
use Symfony\Component\Translation\MessageCatalogue;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class LatteExtractorTest extends TestCase
{

	public function testExtractDirectory()
	{
		$extractor = new LatteExtractor();

		$catalogue = new MessageCatalogue('cs_CZ');
		$extractor->extract(__DIR__ . '/extractor-files', $catalogue);

		Assert::same([
			'messages' => [
				'Important title' => 'Important title',
				'Another important title' => 'Another important title',
				"\nInteresting article about interesting topic\n" => "\nInteresting article about interesting topic\n",
				'Chapter 2' => 'Chapter 2',
				'none|one|many' => 'none|one|many',
				'sample.identificator' => 'sample.identificator',
			]
		], $catalogue->all());
	}



	public function testExtractDirectory_withPrefix()
	{
		$extractor = new LatteExtractor();
		$extractor->setPrefix('homepage');

		$catalogue = new MessageCatalogue('cs_CZ');
		$extractor->extract(__DIR__ . '/extractor-files', $catalogue);

		Assert::same([
			'messages' => [
				'homepage.Important title' => 'Important title',
				'homepage.Another important title' => 'Another important title',
				"homepage.\nInteresting article about interesting topic\n" => "\nInteresting article about interesting topic\n",
				'homepage.Chapter 2' => 'Chapter 2',
				'homepage.none|one|many' => 'none|one|many',
				'homepage.sample.identificator' => 'sample.identificator',
			]
		], $catalogue->all());
	}

}

(new LatteExtractorTest())->run();
