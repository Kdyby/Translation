<?php

/**
 * Test: Kdyby\Translation\LatteExtractor.
 *
 * @testCase KdybyTests\Translation\LatteExtractorTest
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Extractors\LatteExtractor;
use Latte\Engine;
use Symfony\Component\Translation\MessageCatalogue;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class LatteExtractorTest extends \KdybyTests\Translation\TestCase
{

	public function testExtractDirectory()
	{
		$extractor = new LatteExtractor();

		$catalogue = new MessageCatalogue('cs_CZ');
		$extractor->extract(__DIR__ . '/data/extractor-files', $catalogue);
		if (!defined(Engine::class . '::VERSION_ID') || Engine::VERSION_ID < 20900) {
			$mess = [
				'messages' => [
					'Important title' => 'Important title',
					'Another important title' => 'Another important title',
					"\nInteresting article about interesting topic\n" => "\nInteresting article about interesting topic\n",
					'Chapter 2' => 'Chapter 2',
					'none|one|many' => 'none|one|many',
					'sample.identificator' => 'sample.identificator',
				],
			];
		} else {
			$mess = [
				'messages' => [
					"(\"Important title\")" => "(\"Important title\")",
					"('Another important title')" => "('Another important title')",
					"\nInteresting article about interesting topic\n" => "\nInteresting article about interesting topic\n",
					"('Chapter 2')" => "('Chapter 2')",
					'none|one|many' => 'none|one|many',
					'sample.identificator' => 'sample.identificator',
				],
			];
		}
		Assert::same($mess, $catalogue->all());
	}

	public function testExtractDirectoryWithPrefix()
	{
		$extractor = new LatteExtractor();
		$extractor->setPrefix('homepage');

		$catalogue = new MessageCatalogue('cs_CZ');
		$extractor->extract(__DIR__ . '/data/extractor-files', $catalogue);
		if (!defined(Engine::class . '::VERSION_ID') || Engine::VERSION_ID < 20900) {
			$mess = [
				'messages' => [
					'homepage.Important title' => 'Important title',
					'homepage.Another important title' => 'Another important title',
					"homepage.\nInteresting article about interesting topic\n" => "\nInteresting article about interesting topic\n",
					'homepage.Chapter 2' => 'Chapter 2',
					'homepage.none|one|many' => 'none|one|many',
					'homepage.sample.identificator' => 'sample.identificator',
				],
			];
		} else {
			$mess = [
				'messages' => [
					"homepage.(\"Important title\")" => "(\"Important title\")",
					"homepage.('Another important title')" => "('Another important title')",
					"homepage.\nInteresting article about interesting topic\n" => "\nInteresting article about interesting topic\n",
					"homepage.('Chapter 2')" => "('Chapter 2')",
					'homepage.none|one|many' => 'none|one|many',
					'homepage.sample.identificator' => 'sample.identificator',
				],
			];
		}
		Assert::same($mess, $catalogue->all());
	}

}

(new LatteExtractorTest())->run();
