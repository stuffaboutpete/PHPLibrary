<?php

namespace PO\Application\Dispatchable\Mvc\ControllerIdentifier;

require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/../IControllerIdentifier.php';
require_once dirname(__FILE__) . '/../../../../Exception.php';
require_once dirname(__FILE__) . '/FolderStructure/Exception.php';
require_once dirname(__FILE__) . '/FolderStructure.php';

class FolderStructureTest
extends \PHPUnit_Framework_TestCase {
	
	private $virtualDir;
	
	public static function setUpBeforeClass()
	{
		spl_autoload_register(function($class){
			if (!preg_match('/Index/', $class)) return;
			$classParts = explode('\\', $class);
			$path = \vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/' .
				'IndexesRoot/' .
				implode('/', $classParts) .
				'.php'
			);
			if (file_exists($path)) include_once $path;
		});
		spl_autoload_register(function($class){
			$class = ltrim($class, '\\');
			$classParts = explode('\\', $class);
			$path = \vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/' .
				implode('/', $classParts) .
				'.php'
			);
			if (file_exists($path)) include_once $path;
		});
	}
	
	public function setUp()
	{
		$this->virtualDir = \vfsStream::setup(
			'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest',
			null,
			array(
				'Test.php' => '<?php class Test {}',
				'Test' => array(
					'Nested' => array(
						'Twice.php' => '<?php namespace Test\Nested; class Twice {}'
					)
				),
				'Nestedconflict.php' => '<?php class Nestedconflict {}',
				'Nestedconflict' => array(
					'Index.php' => '<?php namespace Nestedconflict; class Index {}'
				),
				'IndexesRoot' => array(
					'Index.php' => '<?php class Index {}',
					'Nested' => array(
						'Index.php' => '<?php namespace Nested; class Index {}'
					)
				),
				'MyNamespace' => array(
					'Test.php' => '<?php namespace MyNamespace; class Test {}',
					'Test' => array(
						'Nested' => array(
							'Index.php'
								=> '<?php namespace MyNamespace\Test\Nested; class Index {}',
							'Twice.php'
								=> '<?php namespace MyNamespace\Test\Nested; class Twice {}'
						)
					)
				),
				'CamelCased' => array(
					'Index.php' => '<?php namespace CamelCased; class Index {}',
					'Index.phtml' => ''
				),
				'Templates' => array(
					'Test.phtml' => '',
					'Test' => array(
						'Nested' => array(
							'Twice.phtml' => ''
						)
					),
					'Index.phtml' => '',
					'Nested' => array(
						'Index.phtml' => ''
					)
				),
				'JustTemplate' => array(
					'Index.phtml' => ''
				)
			)
		);
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->virtualDir = null;
		parent::tearDown();
	}
	
	public function testIdentifierCanBeInstantiated()
	{
		$identifier = new FolderStructure();
		$this->assertInstanceOf(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure',
			$identifier
		);
	}
	
	public function testMatchesSimpleClass()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test');
		$this->assertEquals('\Test', $identifier->getControllerClass());
	}
	
	public function testDoesNotMatchNonExistentSimpleClass()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/nonexistent');
		$this->assertEquals(null, $identifier->getControllerClass());
	}
	
	public function testMatchesNestedClass()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test/nested/twice');
		$this->assertEquals('\Test\Nested\Twice', $identifier->getControllerClass());
	}
	
	public function testDoesNotMatchNonExistentNestedClass()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test/nonexistent');
		$this->assertEquals(null, $identifier->getControllerClass());
	}
	
	public function testMatchesPartialNestedClassIfOneExistsAndRemainingPartsIsEven()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test/key/value');
		$this->assertEquals('\Test', $identifier->getControllerClass());
	}
	
	public function testDoesNotMatchPartialNestedClassIfOneExistsButRemainingPartsIsOdd()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test/value');
		$this->assertEquals(null, $identifier->getControllerClass());
	}
	
	public function testIndexClassIsReturnedIfPathIsEmpty()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/');
		$this->assertEquals('\Index', $identifier->getControllerClass());
	}
	
	public function testNestedIndexClassCanBeFound()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/nested');
		$this->assertEquals('\Nested\Index', $identifier->getControllerClass());
	}
	
	public function testErrorIsThrownIfBothClassFileAndRelatedIndexClassExist()
	{
		$this->setExpectedException(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure\Exception'
		);
		$identifier = new FolderStructure();
		$identifier->receivePath('/nestedconflict');
		$identifier->getControllerClass();
	}
	
	public function MyNamespaceCanBeProvidedToConstructorWhichIsUsedToFindBasicClass()
	{
		$identifier = new FolderStructure('\MyNamespace');
		$identifier->receivePath('/test');
		$this->assertEquals('\MyNamespace\Test', $identifier->getControllerClass());
	}
	
	public function MyNamespaceCanBeProvidedToConstructorWhichIsUsedToFindNestedClass()
	{
		$identifier = new FolderStructure('\MyNamespace');
		$identifier->receivePath('/test/nested/twice');
		$this->assertEquals(
			'\MyNamespace\Test\Nested\Twice',
			$identifier->getControllerClass()
		);
	}
	
	public function MyNamespaceCanBeProvidedToConstructorWhichIsUsedToFindBasicIndex()
	{
		$identifier = new FolderStructure('\MyNamespace');
		$identifier->receivePath('/');
		$this->assertEquals('\MyNamespace\Index', $identifier->getControllerClass());
	}
	
	public function MyNamespaceCanBeProvidedToConstructorWhichIsUsedToFindNestededIndex()
	{
		$identifier = new FolderStructure('\MyNamespace');
		$identifier->receivePath('/test/nested');
		$this->assertEquals(
			'\MyNamespace\Test\Nested\Index',
			$identifier->getControllerClass()
		);
	}
	
	public function testNoTemplatePathIsReturnedIfNoDirectoryIsProvided()
	{
		$identifier = new FolderStructure();
		$this->assertEquals(null, $identifier->getTemplatePath());
	}
	
	public function testDirectoryCanBePassedToConstructor()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest'
			)
		);
		$this->assertInstanceOf(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure',
			$identifier
		);
	}
	
	public function testNonExistentDirectoryTriggersError()
	{
		$this->setExpectedException(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure\Exception'
		);
		$identifier = new FolderStructure(
			null,
			\vfsStream::url('nonexistent')
		);
	}
	
	public function testMatchesSimpleTemplate()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/test');
		$this->assertEquals(
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest' .
				'/Templates/Test.phtml'
			),
			$identifier->getTemplatePath()
		);
	}
	
	public function testDoesNotMatchNonExistentSimpleTemplate()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/nonexistent');
		$this->assertEquals(null, $identifier->getTemplatePath());
	}
	
	public function testMatchesNestedTemplate()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/test/nested/twice');
		$this->assertEquals(
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest' .
				'/Templates/Test/Nested/Twice.phtml'
			),
			$identifier->getTemplatePath()
		);
	}
	
	public function testDoesNotMatchNonExistentNestedTemplate()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/test/nonexistent');
		$this->assertEquals(null, $identifier->getTemplatePath());
	}
	
	public function testMatchesPartialNestedTemplateIfOneExistsAndRemainingPartsIsEven()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/test/key/value');
		$this->assertEquals(
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest' .
				'/Templates/Test.phtml'
			),
			$identifier->getTemplatePath()
		);
	}
	
	public function testDoesNotMatchPartialNestedTemplateIfOneExistsButRemainingPartsIsOdd()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/test/value');
		$this->assertEquals(null, $identifier->getTemplatePath());
	}
	
	public function testIndexTemplateIsReturnedIfPathIsEmpty()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/');
		$this->assertEquals(
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest' .
				'/Templates/Index.phtml'
			),
			$identifier->getTemplatePath()
		);
	}
	
	public function testIndexTemplateIsReturnedIfIndexControllerDoesNotExist()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/JustTemplate'
			)
		);
		$identifier->receivePath('/');
		$this->assertEquals(
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest' .
				'/JustTemplate/Index.phtml'
			),
			$identifier->getTemplatePath()
		);
		// @todo Test cannot be run as it looks for the
		// existence of a class \Index - for the test to
		// work, this should not exist however it does
		// exist due to other tests in this class
		//$this->assertEquals(null, $identifier->getControllerClass());
	}
	
	public function testNestedIndexTemplateCanBeFound()
	{
		$identifier = new FolderStructure(
			null,
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest/Templates'
			)
		);
		$identifier->receivePath('/nested');
		$this->assertEquals(
			\vfsStream::url(
				'POApplicationDispatchableMvcControllerIdentifierFolderStructureTest' .
				'/Templates/Nested/Index.phtml'
			),
			$identifier->getTemplatePath()
		);
	}
	
	public function testErrorIsThrownIfBothTemplateFileAndRelatedIndexTemplateExist()
	{
		$this->setExpectedException(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure\Exception'
		);
		$identifier = new FolderStructure();
		$identifier->receivePath('/nestedconflict');
		$identifier->getControllerClass();
	}
	
	public function testPathVariablesReturnAsEmptyArrayIfNoData()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test');
		$this->assertEquals([], $identifier->getPathVariables());
	}
	
	public function testPathVariablesReturnAsAssociativeArrayIfDataIsInPath()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test/key/value');
		$this->assertEquals(['key' => 'value'], $identifier->getPathVariables());
	}
	
	public function testMultipleKeyValuesCanBeReturnedFromPathData()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test/key1/value1/key2/value2');
		$this->assertEquals([
			'key1' => 'value1',
			'key2' => 'value2'
		], $identifier->getPathVariables());
	}
	
	public function testGetDataIsIncludedAsPathData()
	{
		$identifier = new FolderStructure();
		$identifier->receivePath('/test/key1/value1/key2/value2?key3=value3');
		$this->assertEquals([
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3'
		], $identifier->getPathVariables());
	}
	
	public function testExceptionIsThrownIfReceivePathIsNotCalledBeforeGetControllerClass()
	{
		$this->setExpectedException(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure\Exception'
		);
		$identifier = new FolderStructure();
		$identifier->getControllerClass();
	}
	
	public function testExceptionIsThrownIfReceivePathIsNotCalledBeforeGetTemplatePath()
	{
		$this->setExpectedException(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure\Exception'
		);
		$identifier = new FolderStructure(null, '/path');
		$identifier->getTemplatePath();
	}
	
	public function testExceptionIsThrownIfReceivePathIsNotCalledBeforeGetPathVariables()
	{
		$this->setExpectedException(
			'\PO\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure\Exception'
		);
		$identifier = new FolderStructure();
		$identifier->getPathVariables();
	}
	
	// @todo Get classes/files case-insensitively
	// @todo something/key/value should match something/index.phtml (and/or class) with key=value
	
	private function writeClass($className)
	{
		
		return <<<CLASS
<?php
namespace MyNamespace;
		use \PO\Application;
		use \PO\Application\Dispatchable\Mvc\Controller;
		class $className
		extends Controller
		{
			public function dispatch(Application \$application, \$pathVariables = null)
			{
				if (\$application->hasExtension('outputApplicationClass')) {
					echo get_class(\$application);
					return;
				}
				if (is_array(\$pathVariables)) {
					foreach (\$pathVariables as \$key => \$value) echo "\$key => \$value";
					return;
				}
				echo 'I\'m in \MyNamespace\$className';
			}
			public function getTemplateVariables(){
				return [ 'content' => 'Test value' ];
			}
		}
		
CLASS;
	
	}
	
}