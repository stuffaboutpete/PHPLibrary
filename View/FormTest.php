<?php

namespace PO\View;

require_once dirname(__FILE__) . '/Form.php';

class FormTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testFormCanBeInstantiated()
	{
		$form = new Form([Form::TEXT]);
		$this->assertInstanceOf('PO\View\Form', $form);
	}
	
	public function testFormCanBeRendered()
	{
		$form = new Form([Form::TEXT]);
		$this->assertRegExp('/^<form/', $form->__toString());
		$this->assertRegExp('/<input[\s]+type="text"[\s]+>/', $form->__toString());
		$this->assertRegExp('/<\/form>$/', $form->__toString());
	}
	
	public function testExceptionIsThrownIfNoElementsAreProvided()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::NO_ELEMENT_PROVIDED
		);
		$form = new Form([]);
	}
	
	public function testFormMethodDefaultsToPost()
	{
		$form = new Form([Form::TEXT]);
		$this->assertContains('method="post"', $form->__toString());
	}
	
	public function testMethodCanBeSetToGet()
	{
		$form = new Form([Form::TEXT], 'get');
		$this->assertContains('method="get"', $form->__toString());
	}
	
	public function testExceptionIsThrownIfMethodIsNotPostOrGet()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::INVALID_METHOD_PROVIDED
		);
		new Form([Form::TEXT], 'invalid');
	}
	
	public function testNoTargetIsSetByDefault()
	{
		$form = new Form([Form::TEXT], 'post');
		$this->assertNotContains('target', $form->__toString());
	}
	
	public function testFormTargetCanBeSet()
	{
		$form = new Form([Form::TEXT], 'post', '/example/path');
		$this->assertContains('target="/example/path"', $form->__toString());
	}
	
	public function testExceptionIsThrownIfProvidedTargetIsNotAString()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::NON_STRING_PROVIDED_AS_TARGET
		);
		new Form([Form::TEXT], 'post', true);
	}
	
	public function testMultipleElementsCanBeProvidedAndAreRendered()
	{
		$form = new Form([
			Form::TEXT,
			Form::SUBMIT
		]);
		$this->assertRegExp(
			'/<input(?:[\s]+[^\s>]*)*?[\s]+type="text"(?:[\s]+[^\s>]*)*?>/',
			$form->__toString()
		);
		$this->assertRegExp(
			'/<input(?:[\s]+[^\s>]*)*?[\s]+type="submit"(?:[\s]+[^\s>]*)*?>/',
			$form->__toString()
		);
	}
	
	public function testElementKeyMustBeEqualToAClassConstant()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::INVALID_ELEMENT_KEY_PROVIDED
		);
		new Form([
			Form::TEXT,
			999
		]);
	}
	
	public function testElementKeyCanBeProvidedInsideArray()
	{
		$form = new Form([
			['element' => Form::TEXT]
		]);
		$this->assertRegExp(
			'/<input(?:[\s]+[^\s>]*)*?[\s]+type="text"(?:[\s]+[^\s>]*)*?>/',
			$form->__toString()
		);
	}
	
	public function testArrayElementDefinitionMustIncludeElementKey()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::INVALID_ELEMENT_DEFINITION
		);
		new Form([
			['notElement' => Form::TEXT]
		]);
	}
	
	public function testExceptionIsThrownIfElementKeyIsNotAnIntegerOrArray()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::INVALID_ELEMENT_DEFINITION
		);
		new Form([
			Form::TEXT => true
		]);
	}
	
	public function testLabelCanBeCreatedForElementAndForAttributeIsSetCorrectly()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'label'		=> 'My Label',
				'id'		=> 'my-element'
			]
		]);
		$this->assertRegExp(
			'/<input(?:[\s]+[^\s>]*)*?[\s]+(?:type="text"[\s]+id="my-element"|'.
			'id="my-element"[\s]+type="text")(?:[\s]+[^\s>]*)*?>/',
			$form->__toString()
		);
		$this->assertRegExp(
			'/<label(?:[\s]+[^\s>]*)*?[\s]+for="my-element"'.
			'(?:[\s]+[^\s>]*)*?>[\s]*My Label[\s]*<\/label>/',
			$form->__toString()
		);
	}
	
	public function testLabelIsShownDirectlyBeforeRelatedInput()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'label'		=> 'My Label',
				'id'		=> 'my-element'
			]
		]);
		$this->assertRegExp(
			'/<label[^>]+>[^<]+<\/label>[\s]*<input[^>]+>/',
			$form->__toString()
		);
	}
	
	public function testExceptionIsThrownIfLabelIsDeclaredButNoIDIsDeclared()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::MISSING_VALUE_IN_ELEMENT_DECLARATION
		);
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'label'		=> 'My Label'
			]
		]);
	}
	
	public function testExceptionIsThrownIfLabelIsNotAString()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::INVALID_VALUE_IN_ELEMENT_DECLARATION
		);
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'label'		=> ['My Label'],
				'id'		=> 'my-element'
			]
		]);
	}
	
	public function testExceptionIsThrownIfContentIsProvidedForASelfClosingElementType()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::CONTENT_PROVIDED_FOR_SELF_CLOSING_TAG
		);
		new Form([
			[
				'element'	=> Form::TEXT,
				'content'	=> 'I\'m the content'
			]
		]);
	}
	
	public function testElementCanBeURL()
	{
		$form = new Form([Form::URL]);
		$this->assertRegExp('/<input[\s]+type="url"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeTelephoneNumber()
	{
		$form = new Form([Form::TEL]);
		$this->assertRegExp('/<input[\s]+type="tel"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeEmailAddress()
	{
		$form = new Form([Form::EMAIL]);
		$this->assertRegExp('/<input[\s]+type="email"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeSearchField()
	{
		$form = new Form([Form::SEARCH]);
		$this->assertRegExp('/<input[\s]+type="search"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeNumber()
	{
		$form = new Form([Form::NUMBER]);
		$this->assertRegExp('/<input[\s]+type="number"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeRange()
	{
		$form = new Form([Form::RANGE]);
		$this->assertRegExp('/<input[\s]+type="range"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeDateTimeLocal()
	{
		$form = new Form([Form::DATE_TIME_LOCAL]);
		$this->assertRegExp('/<input[\s]+type="datetime-local"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeDate()
	{
		$form = new Form([Form::DATE]);
		$this->assertRegExp('/<input[\s]+type="date"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeTime()
	{
		$form = new Form([Form::TIME]);
		$this->assertRegExp('/<input[\s]+type="time"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeWeek()
	{
		$form = new Form([Form::WEEK]);
		$this->assertRegExp('/<input[\s]+type="week"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeMonth()
	{
		$form = new Form([Form::MONTH]);
		$this->assertRegExp('/<input[\s]+type="month"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeColor()
	{
		$form = new Form([Form::COLOR]);
		$this->assertRegExp('/<input[\s]+type="color"[\s]+>/', $form->__toString());
	}
	
	public function testElementCanBeTextArea()
	{
		$form = new Form([Form::TEXTAREA]);
		$this->assertRegExp('/<textarea[\s]*>[\s]*<\/textarea>/', $form->__toString());
	}
	
	public function testTextAreaElementCanContainContent()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXTAREA,
				'content'	=> 'I\'m the content'
			]
		]);
		$this->assertRegExp('/<textarea[\s]*>[\s]*I\'m the content[\s]*<\/textarea>/', $form->__toString());
	}
	
	public function testElementCanBeSelectAndOptionsAreRendered()
	{
		$form = new Form([
			[
				'element'	=> Form::SELECT,
				'options'	=> ['One', 'Two']
			]
		]);
		$this->assertRegExp(
			'/<select[\s]*>[\s]*<option[\s]*>[\s]*One[\s]*<\/option>[\s]*'.
			'<option[\s]*>[\s]*Two[\s]*<\/option>[\s]*<\/select>/',
			$form->__toString()
		);
	}
	
	public function testSelectElementCanRenderOptionsWithProvidedValues()
	{
		$form = new Form([
			[
				'element'	=> Form::SELECT,
				'options'	=> [
					'one' => 'Uno',
					'two' => 'Dos'
				]
			]
		]);
		$this->assertRegExp(
			'/<select[\s]*>[\s]*<option[\s]*value="one"[\s]*>[\s]*Uno[\s]*<\/option>[\s]*'.
			'<option[\s]*value="two"[\s]*>[\s]*Dos[\s]*<\/option>[\s]*<\/select>/',
			$form->__toString()
		);
	}
	
	public function testDataListCanBeRenderedForInputElementAndIdAndListAttributesAreIncluded()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'datalist' => [
					'id'		=> 'my-list',
					'options'	=> ['One', 'Two']
				]
			]
		]);
		$this->assertRegExp(
			'/<input[\s]+(?:list="my-list"[\s]+type="text"|type="text"[\s]+list="my-list")[\s]*>/',
			$form->__toString()
		);
		$this->assertRegExp(
			'/<datalist[\s]+id="my-list"[\s]*>[\s]*<option[\s]+value="One"[\s]*>[\s]*'.
			'<option[\s]+value="Two"[\s]*>[\s]*<\/datalist>/',
			$form->__toString()
		);
	}
	
	public function testDataListCanBeProvidedWithNoIdAndARandomOneIsGenerated()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'datalist'	=> ['One', 'Two']
			]
		]);
		$this->assertRegExp(
			'/<input[\s]+(?:list="list-[a-z]{6}"[\s]+type="text"|type="text"[\s]+' .
			'list="list-[a-z]{6}")[\s]*>/',
			$form->__toString()
		);
		$this->assertRegExp(
			'/<datalist[\s]+id="list-[a-z]{6}"[\s]*>[\s]*<option[\s]+value="One"[\s]*>[\s]*' .
			'<option[\s]+value="Two"[\s]*>[\s]*<\/datalist>/',
			$form->__toString()
		);
		preg_match(
			'/<input[\s]+(?:list="(list-[a-z]{6})"[\s]+type="text"|type="text"[\s]+' .
			'list="(list-[a-z]{6})")[\s]*>/',
			$form,
			$inputMatch
		);
		preg_match(
			'/<datalist[\s]+id="(list-[a-z]{6})"[\s]*>[\s]*<option[\s]+value="One"[\s]*>[\s]*' .
			'<option[\s]+value="Two"[\s]*>[\s]*<\/datalist>/',
			$form,
			$dataListMatch
		);
		$this->assertTrue(
			$dataListMatch[1] == $inputMatch[1] ||
			$dataListMatch[1] == $inputMatch[2]
		);
	}
	
	public function testElementCanBeDeclaredWithAutoFocus()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'autofocus' => true
			]
		]);
		$this->assertRegExp(
			'/<input[\s]+(?:type="text"[\s]+autofocus|autofocus[\s]*type="text")[\s]*>/',
			$form->__toString()
		);
	}
	
	public function testOnlyOneElementCanBeDeclaredAutoFocus()
	{
		$this->setExpectedException(
			Form\Exception::Class,
			'',
			Form\Exception::DUPLICATE_AUTOFOCUS
		);
		new Form([
			Form::TEXT,
			[
				'element'	=> Form::TEXT,
				'autofocus' => true
			],
			[
				'element'	=> Form::TEXT,
				'autofocus' => true
			]
		]);
	}
	
	public function testUnrecognisedDefinitionKeyIsUsedAsAttribute()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'required'	=> true,
				'anything'	=> true
			]
		]);
		$this->assertRegExp(
			'/<input[\s]+[\sa-z="-]*required(?:[\s]+[\sa-z="-]*)?>/',
			$form->__toString()
		);
		$this->assertRegExp(
			'/<input[\s]+[\sa-z="-]*anything(?:[\s]+[\sa-z="-]*)?>/',
			$form->__toString()
		);
	}
	
	public function testUnrecognisedAttributeCanContainSingleValue()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'class'		=> 'some-class'
			]
		]);
		$this->assertRegExp(
			'/<input[\s]+[\sa-z="-]*class="some-class"(?:[\s]+[\sa-z="-]*)?>/',
			$form->__toString()
		);
	}
	
	public function testUnrecognisedAttributeCanContainMultipleValues()
	{
		$form = new Form([
			[
				'element'	=> Form::TEXT,
				'class'		=> ['class-one', 'class-two']
			]
		]);
		$this->assertRegExp(
			'/<input[\s]+[\sa-z="-]*class="(?:class-one class-two|class-two class-one)' .
			'"(?:[\s]+[\sa-z="-]*)?>/',
			$form->__toString()
		);
	}
	
	// @todo button - http://reference.sitepoint.com/html/button
	// @todo enctype - http://reference.sitepoint.com/html/form
	
}
