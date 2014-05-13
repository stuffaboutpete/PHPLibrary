<?php

namespace PO\Application\Bootstrap;

require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/../IBootstrap.php';
require_once dirname(__FILE__) . '/MagicGateway.php';
require_once dirname(__FILE__) . '/MagicGateway/DependencyFactory.php';
require_once dirname(__FILE__) . '/../../Exception.php';
require_once dirname(__FILE__) . '/MagicGateway/Exception.php';
require_once dirname(__FILE__) . '/../../Application.php';
require_once dirname(__FILE__) . '/../../IoCContainer.php';
require_once dirname(__FILE__) . '/../../IoCContainer/IContainment.php';
require_once dirname(__FILE__) . '/../../Model.php';
require_once dirname(__FILE__) . '/../../Gateway.php';
require_once dirname(__FILE__) . '/../../Gateway/IFactory.php';
require_once dirname(__FILE__) . '/../../Gateway/IQueryProvider.php';
require_once dirname(__FILE__) . '/../../Gateway/QueryProvider/Simple.php';
require_once dirname(__FILE__) . '/../../Gateway/Factory/Model.php';
require_once dirname(__FILE__) . '/../../Gateway/Factory/Model/IBuildMapContributor.php';
require_once dirname(__FILE__) . '/../../Gateway/Factory/Model/IDismantleContributor.php';

class MagicGatewayTest
extends \PHPUnit_Framework_TestCase {
	
	private $virtualDir;
	private $mGateway;
	private $mDependencyFactory;
	private $mModelFactory;
	private $mSimpleQueryProvider;
	private $mApplication;
	private $mIoCContainer;
	
	public function setUp()
	{
		$this->virtualDir = \vfsStream::setup('POApplicationBootstrapMagicGatewayTest', null, [
			'JustModel' => [
				'TestModel.php' => $this->writeModel('TestModel')
			],
			'TwoModels' => [
				'TestModelOne.php' => $this->writeModel('TestModelOne'),
				'TestModelTwo.php' => $this->writeModel('TestModelTwo')
			],
			'Factory' => [
				'TestModel.php' => $this->writeModel('FactoryTestModel'),
				'FactoryTestModel' => [
					'Factory.php' => $this->writeFactory('CustomFactory')
				]
			],
			'SingleBuildMap' => [
				'TestModel.php' => $this->writeModel('SingleBuildMapTestModel'),
				'SingleBuildMapTestModel' => [
					'BuildMap.php' => $this->writeBuildMap('SingleBuildMapBuildMap')
				]
			],
			'DualBuildMap' => [
				'TestModel.php' => $this->writeModel('DualBuildMapTestModel'),
				'DualBuildMapTestModel' => [
					'BuildMapOne.php' => $this->writeBuildMap('DualBuildMapBuildMapOne'),
					'BuildMapTwo.php' => $this->writeBuildMap('DualBuildMapBuildMapTwo')
				]
			],
			'SingleDismantler' => [
				'TestModel.php' => $this->writeModel('SingleDismantlerTestModel'),
				'SingleDismantlerTestModel' => [
					'Dismantler.php' => $this->writeDismantler('SingleDismantlerDismantler')
				]
			],
			'DualDismantler' => [
				'TestModel.php' => $this->writeModel('DualDismantlerTestModel'),
				'DualDismantlerTestModel' => [
					'DismantlerOne.php' => $this->writeDismantler('DualDismantlerDismantlerOne'),
					'DismantlerTwo.php' => $this->writeDismantler('DualDismantlerDismantlerTwo')
				]
			],
			'QueryProvider' => [
				'TestModel.php' => $this->writeModel('QueryProviderTestModel'),
				'QueryProviderTestModel' => [
					'QueryProvider.php' => $this->writeQueryProvider('CustomQueryProvider')
				]
			]
		]);
		$this->mGateway = $this->getMockBuilder('PO\Gateway')
			->disableOriginalConstructor()
			->getMock();
		$this->mDependencyFactory = $this->getMock(
			'PO\Application\Bootstrap\MagicGateway\DependencyFactory'
		);
		$this->mModelFactory = $this->getMockBuilder('PO\Gateway\Factory\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mSimpleQueryProvider = $this->getMockBuilder('PO\Gateway\QueryProvider\Simple')
			->disableOriginalConstructor()
			->getMock();
		$this->mApplication = $this->getMockBuilder('PO\Application')
			->setMethods(['extend', 'extensionExists', 'getIoC'])
			->disableOriginalConstructor()
			->getMock();
		$this->mIoCContainer = $this->getMock('PO\IoCContainer');
		$this->mDependencyFactory
			->expects($this->any())
			->method('getModelFactory')
			->will($this->returnValue($this->mModelFactory));
		$this->mDependencyFactory
			->expects($this->any())
			->method('getSimpleQueryProvider')
			->will($this->returnValue($this->mSimpleQueryProvider));
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->virtualDir);
		unset($this->mGateway);
		unset($this->mDependencyFactory);
		unset($this->mModelFactory);
		unset($this->mSimpleQueryProvider);
		unset($this->mApplication);
		unset($this->mIoCContainer);
		parent::tearDown();
	}
	
	public function testMagicGatewayBootstrapCanBeInstantiated()
	{
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest')
		);
		$this->assertInstanceOf('\PO\Application\Bootstrap\MagicGateway', $bootstrap);
	}
	
	public function testBootstrapRequiresGatewayObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new MagicGateway(
			null,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest')
		);
	}
	
	public function testBootstrapRequiresDependencyFactory()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new MagicGateway(
			$this->mGateway,
			null,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest')
		);
	}
	
	public function testBootstrapRequiresValidSearchDirectory()
	{
		$this->setExpectedException('\PO\Application\Bootstrap\MagicGateway\Exception');
		new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			__FILE__
		);
	}
	
	public function testApplicationIsExtendedWithGatewayWhenBootstrapIsRun()
	{
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest')
		);
		$this->mApplication
			->expects($this->once())
			->method('extend')
			->with(
				'gateway',
				$this->mGateway
			);
		$bootstrap->run($this->mApplication);
	}
	
	public function testGatewayIsRegisteredAsSingletonOnIoCContainerIfItExistsOnApplication()
	{
		$this->mApplication
			->expects($this->once())
			->method('extensionExists')
			->with('ioC')
			->will($this->returnValue(true));
		$this->mApplication
			->expects($this->once())
			->method('getIoC')
			->will($this->returnValue($this->mIoCContainer));
		$this->mIoCContainer
			->expects($this->once())
			->method('registerSingleton')
			->with($this->mGateway);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testNoIoCContainerSingletonIsRegisteredIfNoIoCContainerExistsOnApplication()
	{
		$this->mApplication
			->expects($this->once())
			->method('extensionExists')
			->with('ioC')
			->will($this->returnValue(false));
		$this->mApplication
			->expects($this->never())
			->method('getIoC');
		$this->mIoCContainer
			->expects($this->never())
			->method('registerSingleton');
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testTypeIsRegisteredAgainstGatewayIfModelExistsInSearchFolderUsingDefaults()
	{
		$this->mGateway
			->expects($this->once())
			->method('addType')
			->with(
				'TestNamespace\TestModel',
				$this->mModelFactory,
				$this->mSimpleQueryProvider
			);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\JustModel')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testReleventArgumentsArePassedToDependencyFactoryWhenRegisteringAType()
	{
		$this->mApplication
			->expects($this->any())
			->method('extensionExists')
			->with('ioC')
			->will($this->returnValue(true));
		$this->mApplication
			->expects($this->any())
			->method('getIoC')
			->will($this->returnValue($this->mIoCContainer));
		$this->mDependencyFactory
			->expects($this->at(0))
			->method('getModelFactory')
			->with(
				'TestNamespace\TestModel',
				null,
				null,
				$this->mIoCContainer
			);
		$this->mDependencyFactory
			->expects($this->at(1))
			->method('getSimpleQueryProvider')
			->with(
				'TestNamespace\TestModel',
				'test_model'
			);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\JustModel')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testMultipleTypesAreRegisteredIfMultipleModelsExist()
	{
		$this->mGateway
			->expects($this->at(0))
			->method('addType')
			->with(
				'TestNamespace\TestModelOne',
				$this->mModelFactory,
				$this->mSimpleQueryProvider
			);
		$this->mGateway
			->expects($this->at(1))
			->method('addType')
			->with(
				'TestNamespace\TestModelTwo',
				$this->mModelFactory,
				$this->mSimpleQueryProvider
			);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\TwoModels')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testCustomFactoryIsUsedInPlaceOfModelFactoryIfItExists()
	{
		$this->mGateway
			->expects($this->once())
			->method('addType')
			->with(
				'TestNamespace\FactoryTestModel',
				$this->isInstanceOf('TestNamespace\CustomFactory'),
				$this->mSimpleQueryProvider
			);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\Factory')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testCustomFactoryIsCreatedUsingIoCContainerIfAvailable()
	{
		$this->mApplication
			->expects($this->any())
			->method('extensionExists')
			->with('ioC')
			->will($this->returnValue(true));
		$this->mApplication
			->expects($this->any())
			->method('getIoC')
			->will($this->returnValue($this->mIoCContainer));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('TestNamespace\CustomFactory')
			->will($this->returnValue(new \TestNamespace\CustomFactory()));
		$this->mGateway
			->expects($this->once())
			->method('addType')
			->with(
				'TestNamespace\FactoryTestModel',
				$this->isInstanceOf('TestNamespace\CustomFactory'),
				$this->mSimpleQueryProvider
			);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\Factory')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testBuildMapContributorIsProvidedToModelFactoryIfItExists()
	{
		$this->mDependencyFactory
			->expects($this->at(0))
			->method('getModelFactory')
			->with(
				'TestNamespace\SingleBuildMapTestModel',
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					return (get_class($argument[0]) == 'TestNamespace\SingleBuildMapBuildMap');
				})
			)
			->will($this->returnValue($this->mModelFactory));
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\SingleBuildMap')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testMultipleBuildMapContributorsAreProvidedToModelFactoryIfTheyExist()
	{
		$this->mDependencyFactory
			->expects($this->at(0))
			->method('getModelFactory')
			->with(
				'TestNamespace\DualBuildMapTestModel',
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					if (count($argument) != 2) return false;
					if (get_class($argument[0]) != 'TestNamespace\DualBuildMapBuildMapOne') {
						return false;
					}
					if (get_class($argument[1]) != 'TestNamespace\DualBuildMapBuildMapTwo') {
						return false;
					}
					return true;
				})
			)
			->will($this->returnValue($this->mModelFactory));
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\DualBuildMap')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testBuildMapContributorsAreCreatedUsingIoCContainerIfAvailable()
	{
		$this->mApplication
			->expects($this->any())
			->method('extensionExists')
			->with('ioC')
			->will($this->returnValue(true));
		$this->mApplication
			->expects($this->any())
			->method('getIoC')
			->will($this->returnValue($this->mIoCContainer));
		$this->mIoCContainer
			->expects($this->at(1))
			->method('resolve')
			->with('TestNamespace\DualBuildMapBuildMapOne')
			->will($this->returnValue(new \TestNamespace\DualBuildMapBuildMapOne()));
		$this->mIoCContainer
			->expects($this->at(2))
			->method('resolve')
			->with('TestNamespace\DualBuildMapBuildMapTwo')
			->will($this->returnValue(new \TestNamespace\DualBuildMapBuildMapTwo()));
		$this->mDependencyFactory
			->expects($this->at(0))
			->method('getModelFactory')
			->with(
				'TestNamespace\DualBuildMapTestModel',
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					if (count($argument) != 2) return false;
					if (get_class($argument[0]) != 'TestNamespace\DualBuildMapBuildMapOne') {
						return false;
					}
					if (get_class($argument[1]) != 'TestNamespace\DualBuildMapBuildMapTwo') {
						return false;
					}
					return true;
				})
			)
			->will($this->returnValue($this->mModelFactory));
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\DualBuildMap')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testDismantleMapContributorIsProvidedToModelFactoryIfItExists()
	{
		$this->mDependencyFactory
			->expects($this->at(0))
			->method('getModelFactory')
			->with(
				'TestNamespace\SingleDismantlerTestModel',
				null,
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					return (get_class($argument[0]) == 'TestNamespace\SingleDismantlerDismantler');
				})
			)
			->will($this->returnValue($this->mModelFactory));
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\SingleDismantler')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testMultipleDismantleContributorsAreProvidedToModelFactoryIfTheyExist()
	{
		$this->mDependencyFactory
			->expects($this->at(0))
			->method('getModelFactory')
			->with(
				'TestNamespace\DualDismantlerTestModel',
				null,
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					if (count($argument) != 2) return false;
					if (get_class($argument[0]) != 'TestNamespace\DualDismantlerDismantlerOne') {
						return false;
					}
					if (get_class($argument[1]) != 'TestNamespace\DualDismantlerDismantlerTwo') {
						return false;
					}
					return true;
				})
			)
			->will($this->returnValue($this->mModelFactory));
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\DualDismantler')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testDismantleContributorsAreCreatedUsingIoCContainerIfAvailable()
	{
		$this->mApplication
			->expects($this->any())
			->method('extensionExists')
			->with('ioC')
			->will($this->returnValue(true));
		$this->mApplication
			->expects($this->any())
			->method('getIoC')
			->will($this->returnValue($this->mIoCContainer));
		$this->mIoCContainer
			->expects($this->at(1))
			->method('resolve')
			->with('TestNamespace\DualDismantlerDismantlerOne')
			->will($this->returnValue(new \TestNamespace\DualDismantlerDismantlerOne()));
		$this->mIoCContainer
			->expects($this->at(2))
			->method('resolve')
			->with('TestNamespace\DualDismantlerDismantlerTwo')
			->will($this->returnValue(new \TestNamespace\DualDismantlerDismantlerTwo()));
		$this->mDependencyFactory
			->expects($this->at(0))
			->method('getModelFactory')
			->with(
				'TestNamespace\DualDismantlerTestModel',
				null,
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					if (count($argument) != 2) return false;
					if (get_class($argument[0]) != 'TestNamespace\DualDismantlerDismantlerOne') {
						return false;
					}
					if (get_class($argument[1]) != 'TestNamespace\DualDismantlerDismantlerTwo') {
						return false;
					}
					return true;
				})
			)
			->will($this->returnValue($this->mModelFactory));
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\DualDismantler')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testCustomQueryProviderIsUsedInPlaceOfSimpleQueryProviderIfItExists()
	{
		$this->mGateway
			->expects($this->once())
			->method('addType')
			->with(
				'TestNamespace\QueryProviderTestModel',
				$this->mModelFactory,
				$this->isInstanceOf('TestNamespace\CustomQueryProvider')
			);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\QueryProvider')
		);
		$bootstrap->run($this->mApplication);
	}
	
	public function testCustomQueryProviderIsCreatedUsingIoCContainerIfAvailable()
	{
		$this->mApplication
			->expects($this->any())
			->method('extensionExists')
			->with('ioC')
			->will($this->returnValue(true));
		$this->mApplication
			->expects($this->any())
			->method('getIoC')
			->will($this->returnValue($this->mIoCContainer));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('TestNamespace\CustomQueryProvider')
			->will($this->returnValue(new \TestNamespace\CustomQueryProvider()));
		$this->mGateway
			->expects($this->once())
			->method('addType')
			->with(
				'TestNamespace\QueryProviderTestModel',
				$this->mModelFactory,
				$this->isInstanceOf('TestNamespace\CustomQueryProvider')
			);
		$bootstrap = new MagicGateway(
			$this->mGateway,
			$this->mDependencyFactory,
			\vfsStream::url('POApplicationBootstrapMagicGatewayTest\QueryProvider')
		);
		$bootstrap->run($this->mApplication);
	}
	
	private function writeModel($className)
	{
		return <<<CLASS
<?php
namespace TestNamespace;
		use PO\Model;
		class $className
		extends Model
		{
			public function __construct(){}
		}
CLASS;
	}
	
	private function writeFactory($className)
	{
		return <<<CLASS
<?php
namespace TestNamespace;
		use PO\Gateway\IFactory;
		class $className
		implements IFactory
		{
			public function approveClass(\$class){}
			public function build(array \$data){}
			public function dismantle(\$object){}
		}
CLASS;
	}
	
	private function writeBuildMap($className)
	{
		return <<<CLASS
<?php
namespace TestNamespace;
		use PO\Gateway\Factory\Model\IBuildMapContributor;
		class $className
		implements IBuildMapContributor
		{
			public function getMap(){}
		}
CLASS;
	}
	
	private function writeDismantler($className)
	{
		return <<<CLASS
<?php
namespace TestNamespace;
		use PO\Gateway\Factory\Model\IDismantleContributor;
		class $className
		implements IDismantleContributor
		{
			public function dismantle(array \$data){}
		}
CLASS;
	}
	
	private function writeQueryProvider($className)
	{
		return <<<CLASS
<?php
namespace TestNamespace;
		use PO\Gateway\IQueryProvider;
		class $className
		implements IQueryProvider
		{
			public function approveClass(\$class){}
			public function getSingleSelectPreparedStatements(\$keys){}
			public function getMultipleSelectPreparedStatements(\$keys){}
			public function getSavePreparedStatement(\$keys, \$allFields){}
			public function getDeletePreparedStatement(\$keys){}
		}
CLASS;
	}
	
}