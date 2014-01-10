<?php

/**
 * Test: Kdyby\Translation\Phase.
 *
 * @testCase KdybyTests\Translation\PhaseTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Kdyby\Translation\Phrase;
use Nette;
use Nette\Application\UI\Form;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PhraseTest extends TestCase
{

	public function testCheckboxCaption()
	{
		Tester\Environment::skip('Nette\Forms\Controls\BaseControl::translate() line 5: phase must not be serialised to string before passing to translator');

		$translator = $this->createTranslator();

		$form = new Form();
		$form->setTranslator($translator);

		$check = $form->addCheckbox('useCredits', new Phrase('front.orderForm.useCredits', $credits = 10));

		$label = method_exists($check, 'getLabelPart') ? $check->getLabelPart() : $check->getLabel();
		Assert::same('Use 10 credits', $label->getText());
	}



	public function testValidationMessage()
	{
		$translator = $this->createTranslator();

		$form = new Form();
		$form->setTranslator($translator);

		$check = $form->addCheckbox('useCredits');

		$check->addRule(Form::FILLED, new Phrase('front.orderForm.useCredits', $credits = 10));
		$form->validate(array($check));

		Assert::same(array(
			'Use 10 credits'
		), $check->getErrors());

		$control = method_exists($check, 'getControlPart') ? $check->getControlPart() : $check->getControl();
		$rules = isset($control->attrs['data-nette-rules']) ? $control->attrs['data-nette-rules'] : $control->attrs['data']['nette-rules'];

		Assert::match('%A%Use 10 credits%A%', $rules);
	}

}

\run(new PhraseTest());
