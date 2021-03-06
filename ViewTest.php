<?php

namespace PO;

require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/View.php';
require_once dirname(__FILE__) . '/Exception.php';
require_once dirname(__FILE__) . '/View/Exception.php';
require_once dirname(__FILE__) . '/Helper/ArrayType.php';

class ViewTest
extends \PHPUnit_Framework_TestCase {
	
	private $virtualDir;
	
	public static function setUpBeforeClass()
	{
		spl_autoload_register(function($class){
			$classParts = explode('\\', $class);
			$path = \vfsStream::url(implode('/', $classParts) . '.php');
			if (file_exists($path)) include_once $path;
		});
	}
	
	public function setUp()
	{
		$this->virtualDir = \vfsStream::setup('POViewTest', null, array(
			'Test.php' => '<?php namespace POViewTest; class Test extends \PO\View {}',
			'Test.phtml' => 'I\'m in Test.phtml<?php if (isset($key)) echo " ($key)"; ?>',
			'Other.phtml' => 'I\'m in Other.phtml',
			'TestViewCreator.php' => '<?php namespace POViewTest; class TestViewCreator {' .
				'public static function makeView($path) {' .
					'return new \PO\View($path);' .
				'}' .
			'}',
			'Nested' => array(
				'Test.phtml' => 'I\'m in Nested/Test.phtml'
			),
			'NoTemplate.php' => '<?php namespace POViewTest;' .
				'class NoTemplate extends \PO\View {}',
			'Child' => array(
				'Test.php' => '<?php namespace POViewTest\Child; ' .
					'class Test extends \POViewTest\Test {}',
				'Test.phtml' => 'I\'m in Child\Test.phtml',
				'NoTemplate.php' => '<?php namespace POViewTest\Child; ' .
					'class NoTemplate extends \POViewTest\Test {}',
				'Child' => array(
					'Test.php' => '<?php namespace POViewTest\Child\Child; ' .
						'class Test extends \POViewTest\Child\Test {}',
					'Test.phtml' => 'I\'m in Child\Child\Test.phtml',
				)
			),
			'Container.php' => '<?php namespace POViewTest; class Container extends \PO\View { ' .
				'public function setContent($content){ ' .
					'$this->addTemplateVariable(\'content\', $content); ' .
				'}' .
			'}',
			'Container.phtml' => 'Container (<?= $content; ?>)',
			'Content.php' => '<?php namespace POViewTest; class Content extends \PO\View {}',
			'Content.phtml' => 'Content'
		));
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->virtualDir = null;
		parent::tearDown();
	}
	
	public function testViewCanBeInstantiated()
	{
		$view = new View(\vfsStream::url('POViewTest/Test.phtml'));
		$this->assertInstanceOf('PO\View', $view);
	}
	
	public function testPathToTemplateCanBeProvidedAndItIsRenderedByToStringMethod()
	{
		$view = new View(\vfsStream::url('POViewTest/Test.phtml'));
		$this->assertEquals('I\'m in Test.phtml', $view->__toString());
	}
	
	public function testExceptionIsThrownIfNonStringPathIsProvided()
	{
		$this->setExpectedException('\PO\View\Exception');
		$view = new View([]);
	}
	
	public function testExceptionIsThrownIfEmptyStringPathIsProvided()
	{
		$this->setExpectedException('\PO\View\Exception');
		$view = new View('');
	}
	
	public function testTemplatePathCanOmitExtensionAndPhtmlIsAssumed()
	{
		$view = new View(\vfsStream::url('POViewTest/Test'));
		$this->assertEquals('I\'m in Test.phtml', $view->__toString());
	}
	
	public function testExceptionIsThrownIfAbsolutePathToTemplateIsInvalid()
	{
		$this->setExpectedException('\PO\View\Exception');
		$view = new View(\vfsStream::url('POViewTest/InvalidPath.phtml'));
	}
	
	public function testPathToTemplateCanBeNextToCallingCodeFile()
	{
		$view = \POViewTest\TestViewCreator::makeView('Test.phtml');
		$this->assertEquals('I\'m in Test.phtml', $view->__toString());
	}
	
	public function testPathToTemplateCanBeRelativeToCallingCodeFile()
	{
		$view = \POViewTest\TestViewCreator::makeView('Nested/Test.phtml');
		$this->assertEquals('I\'m in Nested/Test.phtml', $view->__toString());
	}
	
	public function testExceptionIsThrownIfRelativePathToTemplateIsInvalid()
	{
		$this->setExpectedException('\PO\View\Exception');
		$view = \POViewTest\TestViewCreator::makeView('InvalidPath.phtml');
	}
	
	public function testInheritingClassesCanOmitPathAndTemplateWithSameNameIsUsed()
	{
		$view = new \POViewTest\Test();
		$this->assertEquals('I\'m in Test.phtml', $view->__toString());
	}
	
	public function testExceptionIsThrownIfClassNameTemplateCannotBeFound()
	{
		$this->setExpectedException('\PO\View\Exception');
		$view = new \POViewTest\NoTemplate();
	}
	
	public function testInheritingClassCanPassPathWhichWillBeUsed()
	{
		$view = new \POViewTest\Test('Other');
		$this->assertEquals('I\'m in Other.phtml', $view->__toString());
	}
	
	public function testTemplateVariablesCanBeAddedAndUsedInTemplate()
	{
		$view = new View(\vfsStream::url('POViewTest/Test.phtml'));
		$class = new \ReflectionClass($view);
		$method = $class->getMethod('addTemplateVariable');
		$method->setAccessible(true);
		$method->invokeArgs($view, ['key', 'value']);
		$this->assertEquals('I\'m in Test.phtml (value)', $view->__toString());
	}
	
	public function testTemplateVariablesCanBePassedToConstructor()
	{
		$view = new View(\vfsStream::url('POViewTest/Test.phtml'), ['key' => 'value']);
		$this->assertEquals('I\'m in Test.phtml (value)', $view->__toString());
	}
	
	public function testExceptionIsThrownIfConstructorTemplateVariablesAreNotAssociativeArray()
	{
		$this->setExpectedException('\PO\View\Exception');
		$view = new View(\vfsStream::url('POViewTest/Test.phtml'), ['value 1', 'value 2']);
	}
	
	public function testInheritingViewIsUsedToIdentifyTemplate()
	{
		$view = new \POViewTest\Child\Test();
		$this->assertEquals('I\'m in Child\Test.phtml', $view->__toString());
	}
	
	public function testParentViewIsUsedToIdentifyTemplateIfNoChildTemplateExists()
	{
		$view = new \POViewTest\Child\NoTemplate();
		$this->assertEquals('I\'m in Test.phtml', $view->__toString());
	}
	
	public function testChildViewCanNominateToUseAParentView()
	{
		$view = new \POViewTest\Child\Child\Test();
		$this->assertEquals('I\'m in Child\Child\Test.phtml', $view->__toString());
		$view = new \POViewTest\Child\Child\Test();
		$class = new \ReflectionClass($view);
		$method = $class->getMethod('useAncestorTemplate');
		$method->setAccessible(true);
		$method->invokeArgs($view, [\POViewTest\Child\Test::Class]);
		$this->assertEquals('I\'m in Child\Test.phtml', $view->__toString());
	}
	
	public function testExceptionIsThrownIfAncestorClassDoesNotExist()
	{
		$this->setExpectedException(
			'\PO\View\Exception',
			'',
			View\Exception::ANCESTOR_CLASS_DOES_NOT_EXIST
		);
		$view = new \POViewTest\Child\Child\Test();
		$class = new \ReflectionClass($view);
		$method = $class->getMethod('useAncestorTemplate');
		$method->setAccessible(true);
		$method->invokeArgs($view, ['InvalidClassName']);
	}
	
	public function testExceptionIsThrownIfAncestorClassIsNotInInheritanceChain()
	{
		$this->setExpectedException(
			'\PO\View\Exception',
			'',
			View\Exception::ANCESTOR_CLASS_NOT_ANCESTOR
		);
		$view = new \POViewTest\Child\Child\Test();
		$class = new \ReflectionClass($view);
		$method = $class->getMethod('useAncestorTemplate');
		$method->setAccessible(true);
		$method->invokeArgs($view, ['stdClass']);
	}
	
	public function testCanBeRenderedIntoDifferentViewByPassingOutputToSpecifiedMethod()
	{
		$view = new \POViewTest\Content();
		$class = new \ReflectionClass($view);
		$method = $class->getMethod('renderInto');
		$method->setAccessible(true);
		$method->invokeArgs($view, [new \POViewTest\Container(), 'setContent']);
		$this->assertEquals('Container (Content)', $view->__toString());
	}
	
	public function testExceptionIsThrownIfMethodSpecifiedInRenderIntoDoesNotExist()
	{
		$this->setExpectedException(
			View\Exception::Class,
			'',
			View\Exception::RENDER_INTO_METHOD_DOES_NOT_EXIST
		);
		$view = new \POViewTest\Content();
		$class = new \ReflectionClass($view);
		$method = $class->getMethod('renderInto');
		$method->setAccessible(true);
		$method->invokeArgs($view, [new \POViewTest\Container(), 'invalidMethod']);
	}
	
}