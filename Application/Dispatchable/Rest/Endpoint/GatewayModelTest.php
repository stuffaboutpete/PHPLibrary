<?php

namespace PO\Application\Dispatchable\Rest\Endpoint;

use PO\Application\Dispatchable\Rest\Endpoint\GatewayModel\Exception;
use PO\Gateway;

require_once dirname(__FILE__) . '/GatewayModel.php';
require_once dirname(__FILE__) . '/../../../../Gateway.php';

class GatewayModelTest
extends \PHPUnit_Framework_TestCase {
	
	private $mRouteVariables;
	private $mGateway;
	private $mFactory;
	private $mResponse;
	private $mCollection;
	private $mRequest;
	
	public function setUp()
	{
		$this->mRouteVariables = $this->getMockBuilder(
			'\PO\Application\Dispatchable\Rest\RouteVariables'
		)->disableOriginalConstructor()
			->getMock();
		$this->mGateway = $this->getMockBuilder('\PO\Gateway')
			->disableOriginalConstructor()
			->getMock();
		$this->mGateway
			->expects($this->any())
			->method('typeIsRegistered')
			->will($this->returnValue(true));
		$this->mFactory = $this->getMock('\PO\Gateway\IFactory');
		$this->mFactory
			->expects($this->any())
			->method('approveClass')
			->will($this->returnValue(true));
		$this->mResponse = $this->getMock('\PO\Http\Response');
		$this->mCollection = $this->getMockBuilder('\PO\Gateway\Collection')
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest = $this->getMock('\PO\Http\Request');
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->mRouteVariables);
		unset($this->mGateway);
		unset($this->mFactory);
		unset($this->mResponse);
		unset($this->mCollection);
		unset($this->mRequest);
		parent::tearDown();
	}
	
	public function testEndpointCanBeInstantiated()
	{
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$this->assertInstanceOf(
			'\PO\Application\Dispatchable\Rest\Endpoint\GatewayModel',
			$endpoint
		);
	}
	
	public function testEndpointRequiresGatewayObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new GatewayModel(
			null,
			'PO\Model'
		);
	}
	
	public function testEndpointRequiresClassNameThatIsRegisteredWithGateway()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::TARGET_CLASS_MUST_BE_REGISTERED_WITH_PROVIDED_GATEWAY
		);
		$mGateway = $this->getMockBuilder('\PO\Gateway')
			->disableOriginalConstructor()
			->getMock();
		$mGateway
			->expects($this->once())
			->method('typeIsRegistered')
			->will($this->returnValue(false));
		new GatewayModel(
			$mGateway,
			\stdClass::Class
		);
	}
	
	public function testEndpointDispatchMethodRequiresResponseObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch();
	}
	
	public function testObjectCanBeSelectedFromGatewayAndDismantledDataIsReturnedOnDispatch()
	{
		$stdObject = new \stdClass();
		$stdData = ['property' => 'value'];
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($stdData);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['stdClass']
			)
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($stdObject)
			->will($this->returnValue($stdData));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testResponseIsSetTo404IfNoDataIsFound()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404')
			->with(null);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model']
			)
			->will($this->throwException(new Gateway\Exception(Gateway\Exception::NO_DATA_FOUND)));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfNonObjectIsReturnedFromGateway()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::NON_OBJECT_RETURNED_FROM_GATEWAY
		);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model']
			);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfNonTargetClassObjectIsReturnedFromGateway()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::INVALID_CLASS_RETURNED_FROM_GATEWAY
		);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model']
			)
			->will($this->returnValue(new \stdClass()));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testRouteVariableCanBeProvidedToGateway()
	{
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('value'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(false));
		$stdObject = new \stdClass();
		$stdData = ['property' => 'value'];
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($stdData);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				$this->callback(function($argument){
					if (count($argument) != 2) return false;
					if ($argument[0] != 'stdClass') return false;
					if ($argument[1] != 'value') return false;
					return true;
				})
			)
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($stdObject)
			->will($this->returnValue($stdData));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testMultipleRouteVariablesCanBeProvidedToGateway()
	{
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('one'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(3))
			->method('offsetGet')
			->with('key_2')
			->will($this->returnValue('two'));
		$mRouteVariables
			->expects($this->at(4))
			->method('offsetExists')
			->with('key_3')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(5))
			->method('offsetGet')
			->with('key_3')
			->will($this->returnValue('three'));
		$mRouteVariables
			->expects($this->at(6))
			->method('offsetExists')
			->with('key_4')
			->will($this->returnValue(false));
		$stdObject = new \stdClass();
		$stdData = ['property' => 'value'];
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($stdData);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				$this->callback(function($argument){
					if (count($argument) != 4) return false;
					if ($argument[0] != 'stdClass') return false;
					if ($argument[1] != 'one') return false;
					if ($argument[2] != 'two') return false;
					if ($argument[3] != 'three') return false;
					return true;
				})
			)
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($stdObject)
			->will($this->returnValue($stdData));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testFetchKeyCanBeProvidedAsARouteVariable()
	{
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('value'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(false));
		$mRouteVariables
			->expects($this->at(3))
			->method('offsetExists')
			->with('fetch_key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(4))
			->method('offsetGet')
			->with('fetch_key')
			->will($this->returnValue('by_some_method'));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetchBySomeMethod',
				['stdClass', 'value']
			)
			->will($this->returnValue(new \stdClass()));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue([]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testElementCanBeRemovedFromDataUsingSelectMap()
	{
		$stdObject = new \stdClass();
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with(['include_me' => 'value 1']);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['stdClass']
			)
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($stdObject)
			->will($this->returnValue([
				'include_me' => 'value 1',
				'exclude_me' => 'value 2'
			]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass',
			[
				'-exclude_me'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testMultipleElementsCanBeRemovedFromDataUsingSelectMap()
	{
		$stdObject = new \stdClass();
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'include_me' => 'value 1',
				'include_me_also' => 'value 2'
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['stdClass']
			)
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($stdObject)
			->will($this->returnValue([
				'include_me' => 'value 1',
				'include_me_also' => 'value 2',
				'exclude_me' => 'value 3',
				'exclude_me_also' => 'value 4'
			]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass',
			[
				'-exclude_me',
				'-exclude_me_also'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testSelectMapCanBeAppliedToNestedData()
	{
		$stdObject = new \stdClass();
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'property_1' => 'value 1',
				'property_2' => [
					'sub_property_2' => 'sub value 2',
					'sub_property_3' => [
						'sub_sub_property_1' => 'sub sub value 1'
					]
				]
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($stdObject)
			->will($this->returnValue([
				'property_1' => 'value 1',
				'property_2' => [
					'sub_property_1' => 'sub value 1',
					'sub_property_2' => 'sub value 2',
					'sub_property_3' => [
						'sub_sub_property_1' => 'sub sub value 1',
						'sub_sub_property_2' => 'sub sub value 2'
					]
				]
			]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass',
			[
				'property_2' => [
					'-sub_property_1',
					'sub_property_3' => [
						'-sub_sub_property_2'
					]
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfDataContainsObjectForWhichThereIsNoFactory()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::NON_FACTORY_RETURNED_FROM_GATEWAY
		);
		$mModel = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$stdObject = new \stdClass();
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->at(2))
			->method('getFactory')
			->with(get_class($mModel))
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue([
				'key'		=> 'value',
				'object'	=> $stdObject
			]));
		$this->mGateway
			->expects($this->at(3))
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue(null));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExtraMethodCanBeCalledOnObjectSpecifiedBySelectMapAndItsValueReturned()
	{
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['fakeMethod'])
			->disableOriginalConstructor()
			->getMock();
		$mModel
			->expects($this->once())
			->method('fakeMethod')
			->will($this->returnValue('method value'));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'property' => 'value',
				'method_property' => 'method value'
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with(get_class($mModel))
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue(['property' => 'value']));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+fakeMethod as method_property'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testSimpleArrayReturnedFromObjectMethodWillBeReturned()
	{
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['fakeMethod'])
			->disableOriginalConstructor()
			->getMock();
		$mModel
			->expects($this->once())
			->method('fakeMethod')
			->will($this->returnValue([
				'sub_property_1' => 'sub value 1',
				'sub_property_2' => 'sub value 2'
			]));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'property' => 'value',
				'method_property' => [
					'sub_property_1' => 'sub value 1',
					'sub_property_2' => 'sub value 2'
				]
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with(get_class($mModel))
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue(['property' => 'value']));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+fakeMethod as method_property'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testSimpleArrayReturnedFromObjectMethodCanBeFilteredUsingSelectMap()
	{
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['fakeMethod'])
			->disableOriginalConstructor()
			->getMock();
		$mModel
			->expects($this->once())
			->method('fakeMethod')
			->will($this->returnValue([
				'sub_property_1' => 'sub value 1',
				'sub_property_2' => 'sub value 2'
			]));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'property' => 'value',
				'method_property' => [
					'sub_property_2' => 'sub value 2'
				]
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with(get_class($mModel))
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue(['property' => 'value']));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+fakeMethod as method_property' => [
					'-sub_property_1'
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfSelectMapMethodIsCalledOnANonObject()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::METHOD_CALL_ON_NON_OBJECT_INITIATED_BY_SELECT_MAP
		);
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['fakeMethod'])
			->disableOriginalConstructor()
			->getMock();
		$mModel
			->expects($this->once())
			->method('fakeMethod')
			->will($this->returnValue(['key' => 'value']));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with(get_class($mModel))
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue(['property' => 'value']));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+fakeMethod as method_property' => [
					'+getData as other_data'
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfSelectMapMethodDoesNotExist()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::UNKNOWN_METHOD_CALLED_BY_SELECT_MAP
		);
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['fakeMethod'])
			->disableOriginalConstructor()
			->getMock();
		$mModel
			->expects($this->once())
			->method('fakeMethod')
			->will($this->returnValue(new \stdClass()));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->any())
			->method('dismantle')
			->will($this->returnValue(['property' => 'value']));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+fakeMethod as method_property' => [
					'+getData as other_data'
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testObjectReturnedInDataIsDismantledIfAFactoryCanBeFoundFromGateway()
	{
		$model1 = new \stdClass();
		$model2 = new \stdClass();
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'model_1_property' => 'value',
				'model_2' => [
					'model_2_property_1' => 'value 1'
				]
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($model1));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->at(1))
			->method('dismantle')
			->with($model1)
			->will($this->returnValue([
				'model_1_property'	=> 'value',
				'model_2'			=> $model2
			]));
		$this->mFactory
			->expects($this->at(2))
			->method('dismantle')
			->with($model2)
			->will($this->returnValue([
				'model_2_property_1' => 'value 1',
				'model_2_property_2' => 'value 2'
			]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass',
			[
				'model_2' => [
					'-model_2_property_2'
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testFactoryMustMatchProvidedTargetClass()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::INCORRECT_FACTORY_PROVIDED_FROM_GATEWAY
		);
		$mFactory = $this->getMock('\PO\Gateway\IFactory');
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue(new \stdClass()));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($mFactory));
		$mFactory
			->expects($this->once())
			->method('approveClass')
			->with('stdClass')
			->will($this->returnValue(false));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testFactoryIsSoughtIfNewObjectTypeNeedsDismantling()
	{
		$model1 = new \stdClass();
		$mModel2 = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'model_1_property' => 'value',
				'model_2' => [
					'model_2_property_1' => 'value 1'
				]
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($model1));
		$this->mGateway
			->expects($this->at(2))
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mGateway
			->expects($this->at(3))
			->method('getFactory')
			->with(get_class($mModel2))
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->at(1))
			->method('dismantle')
			->with($model1)
			->will($this->returnValue([
				'model_1_property'	=> 'value',
				'model_2'			=> $mModel2
			]));
		$this->mFactory
			->expects($this->at(3))
			->method('dismantle')
			->with($mModel2)
			->will($this->returnValue([
				'model_2_property_1' => 'value 1',
				'model_2_property_2' => 'value 2'
			]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass',
			[
				'model_2' => [
					'-model_2_property_2'
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testNestedObjectMethodsCanBeCalled()
	{
		$mModel1 = $this->getMockBuilder('PO\Model')
			->setMethods(['getModelTwo'])
			->disableOriginalConstructor()
			->getMock();
		$mModel2 = $this->getMockBuilder('PO\Model')
			->setMethods(['getMoreData'])
			->disableOriginalConstructor()
			->getMock();
		$mModel1
			->expects($this->once())
			->method('getModelTwo')
			->will($this->returnValue($mModel2));
		$mModel2
			->expects($this->once())
			->method('getMoreData')
			->will($this->returnValue([
				'more_data_property_1' => 'value 1',
				'more_data_property_2' => 'value 2'
			]));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'model_1_property_1' => 'value 1',
				'model_1_property_2' => 'value 2',
				'model_two' => [
					'model_2_property_1' => 'value 1',
					'model_2_property_2' => 'value 2',
					'more_data' => [
						'more_data_property_1' => 'value 1',
						'more_data_property_2' => 'value 2'
					]
				]
			]);
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($mModel1));
		$this->mGateway
			->expects($this->at(2))
			->method('getFactory')
			->with(get_class($mModel1))
			->will($this->returnValue($this->mFactory));
		$this->mGateway
			->expects($this->at(3))
			->method('getFactory')
			->with(get_class($mModel2))
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->at(1))
			->method('dismantle')
			->with($mModel1)
			->will($this->returnValue([
				'model_1_property_1' => 'value 1',
				'model_1_property_2' => 'value 2'
			]));
		$this->mFactory
			->expects($this->at(3))
			->method('dismantle')
			->with($mModel2)
			->will($this->returnValue([
				'model_2_property_1' => 'value 1',
				'model_2_property_2' => 'value 2'
			]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+getModelTwo as model_two' => [
					'+getMoreData as more_data'
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfSelectMapKeyDoesNotContainPlusMinusOrAs()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::INVALID_SELECT_MAP_KEY
		);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'invalid_key'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfNestedSelectMapKeyDoesNotContainPlusMinusOrAs()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::INVALID_SELECT_MAP_KEY
		);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'-valid_key' => [
					'invalid_key'
				]
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfGatewayDoesNotReturnAFactory()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::NON_FACTORY_RETURNED_FROM_GATEWAY
		);
		$stdObject = new \stdClass();
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->will($this->returnValue(new \stdClass()));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfFactoryDoesNotDismantleObjectToArray()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::NON_ARRAY_RETURNED_FROM_FACTORY_DISMANTLE
		);
		$stdObject = new \stdClass();
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->will($this->returnValue($stdObject));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($stdObject)
			->will($this->returnValue($stdObject));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'stdClass'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testCollectionReturnedFromGatewayResultsInArrayOfDismantledObjects()
	{
		$stdObject1 = new \stdClass();
		$stdObject2 = new \stdClass();
		$stdData1 = ['property1' => 'value 1'];
		$stdData2 = ['property2' => 'value 2'];
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([$stdData1, $stdData2]);
		$this->mCollection
			->expects($this->at(0))
			->method('count')
			->will($this->returnValue(2));
		$this->mCollection
			->expects($this->at(1))
			->method('offsetGet')
			->with(0)
			->will($this->returnValue($stdObject1));
		$this->mCollection
			->expects($this->at(2))
			->method('count')
			->will($this->returnValue(2));
		$this->mCollection
			->expects($this->at(3))
			->method('offsetGet')
			->with(1)
			->will($this->returnValue($stdObject2));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with('fetch')
			->will($this->returnValue($this->mCollection));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('stdClass')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->at(1))
			->method('dismantle')
			->with($stdObject1)
			->will($this->returnValue($stdData1));
		$this->mFactory
			->expects($this->at(2))
			->method('dismantle')
			->with($stdObject2)
			->will($this->returnValue($stdData2));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testDataCanBePostedAndNewModelIsSavedIntoGatewaySettingResponseTo201()
	{
		$requestData = ['key' => 'value'];
		$dismantledData = [
			'id'	=> 10,
			'key'	=> 'value'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($requestData)
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('save')
			->with($mModel);
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue($dismantledData));
		$this->mResponse
			->expects($this->once())
			->method('set201')
			->with($dismantledData);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testResponseIsSetTo400IfPostedRequestBodyIsNotAnArray()
	{
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue(null));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->with('PO\Model')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->never())
			->method('build');
		$this->mResponse
			->expects($this->once())
			->method('set400'); // @todo Error message?
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testResponseISetTo400IfPostedDataIsInvalid()
	{
		$requestData = ['key' => 'value'];
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->with('PO\Model')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($requestData)
			->will($this->throwException(new \Exception('Couldn\'t build model')));
		$this->mGateway
			->expects($this->never())
			->method('save');
		$this->mResponse
			->expects($this->once())
			->method('set400'); // @todo Error message?
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testMultipleObjectsCanBeCreatedAndSavedViaPOST()
	{
		$requestData = [
			['key' => 'value 1'],
			['key' => 'value 2'],
			['key' => 'value 3']
		];
		$dismantledData = [
			[
				'id'	=> 10,
				'key'	=> 'value 1'
			],
			[
				'id'	=> 11,
				'key'	=> 'value 2'
			],
			[
				'id'	=> 12,
				'key'	=> 'value 3'
			]
		];
		$mModel1 = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$mModel2 = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$mModel3 = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$mGateway = $this->getMockBuilder('\PO\Gateway')
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$mGateway
			->expects($this->at(0))
			->method('typeIsRegistered')
			->with('PO\Model')
			->will($this->returnValue(true));
		$this->mFactory
			->expects($this->at(1))
			->method('build')
			->with($requestData[0])
			->will($this->returnValue($mModel1));
		$this->mFactory
			->expects($this->at(2))
			->method('build')
			->with($requestData[1])
			->will($this->returnValue($mModel2));
		$this->mFactory
			->expects($this->at(3))
			->method('build')
			->with($requestData[2])
			->will($this->returnValue($mModel3));
		$mGateway
			->expects($this->at(2))
			->method('save')
			->with($this->identicalTo($mModel1));
		$mGateway
			->expects($this->at(4))
			->method('save')
			->with($this->identicalTo($mModel2));
		$mGateway
			->expects($this->at(5))
			->method('save')
			->with($this->identicalTo($mModel3));
		$this->mFactory
			->expects($this->at(5))
			->method('dismantle')
			->with($mModel1)
			->will($this->returnValue($dismantledData[0]));
		$this->mFactory
			->expects($this->at(6))
			->method('dismantle')
			->with($mModel2)
			->will($this->returnValue($dismantledData[1]));
		$this->mFactory
			->expects($this->at(7))
			->method('dismantle')
			->with($mModel3)
			->will($this->returnValue($dismantledData[2]));
		$this->mResponse
			->expects($this->once())
			->method('set201')
			->with($dismantledData);
		$endpoint = new GatewayModel(
			$mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testResponseIsSetTo400AndNothingIsSavedIfAnyPostedDataIsInvalid()
	{
		$requestData = [
			['key' => 'value 1'],
			['invalid_key' => 'value 2'],
			['key' => 'value 3']
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$this->mGateway
			->expects($this->at(1))
			->method('getFactory')
			->with('PO\Model')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->at(1))
			->method('build')
			->with($requestData[0])
			->will($this->returnValue($mModel));
		$this->mFactory
			->expects($this->at(2))
			->method('build')
			->with($requestData[1])
			->will($this->throwException(new \Exception('Couldn\'t build model')));
		$this->mGateway
			->expects($this->never())
			->method('save');
		$this->mFactory
			->expects($this->never())
			->method('dismantle');
		$this->mResponse
			->expects($this->once())
			->method('set400');
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testPostedDataIsFilteredUsingSelectMapBeforeBeingPassedToResponse()
	{
		$requestData = ['key' => 'value'];
		$dismantledData = [
			'id'	=> 10,
			'key'	=> 'value',
			'other'	=> 'other value'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($requestData)
			->will($this->returnValue($mModel));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue($dismantledData));
		$this->mResponse
			->expects($this->once())
			->method('set201')
			->with([
				'id'	=> 10,
				'key'	=> 'value'
			]);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'-other'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testPostedDataCanCallObjectMethodsUsingSelectMapBeforeBeingPassedToResponse()
	{
		$requestData = ['key' => 'value'];
		$dismantledData = [
			'id'	=> 10,
			'key'	=> 'value'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['getData'])
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($requestData)
			->will($this->returnValue($mModel));
		$mModel
			->expects($this->once())
			->method('getData')
			->will($this->returnValue('other data value'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue($dismantledData));
		$this->mResponse
			->expects($this->once())
			->method('set201')
			->with([
				'id'			=> 10,
				'key'			=> 'value',
				'other_data'	=> 'other data value'
			]);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+getData as other_data'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testSaveMapCanBeProvidedAndItIsPassedToGatewayOnSavingPostData()
	{
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['getSubObject'])
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('POST'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue([]));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('save')
			->with($mModel, ['getSubObject']);
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue([]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			null,
			[
				'getSubObject'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testModelIsRetrievedAndSetMethodIsCalledForEachPropertyBeforeSavingWhenPutting()
	{
		$requestData = [
			'key_one' => 'value 1',
			'key_two' => 'value 2'
		];
		$dismantledData = [
			'id'		=> 10,
			'key_one'	=> 'value 1',
			'key_two'	=> 'value 2'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['setKeyOne', 'setKeyTwo'])
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('one'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(false));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model', 'one']
			)
			->will($this->returnValue($mModel));
		$mModel
			->expects($this->once())
			->method('setKeyOne')
			->with('value 1');
		$mModel
			->expects($this->once())
			->method('setKeyTwo')
			->with('value 2');
		$this->mGateway
			->expects($this->once())
			->method('save')
			->with($mModel);
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue($dismantledData));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($dismantledData);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testResponseIsSetTo400IfNonArrayRequestBodyIsProvidedWhilstPutting()
	{
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue(null));
		$this->mGateway
			->expects($this->never())
			->method('__call');
		$this->mResponse
			->expects($this->once())
			->method('set400');
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testResponseIsSetTo404IfNoDataIsFoundWhilstPutting()
	{
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue(['key' => 'value']));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model']
			)
			->will($this->throwException(new Gateway\Exception(Gateway\Exception::NO_DATA_FOUND)));
		$this->mResponse
			->expects($this->once())
			->method('set404')
			->with(null);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testExceptionIsThrownIfPutOperationIdentifiesCollection()
	{
		$this->setExpectedException(
			Exception::Class,
			'',
			Exception::UNEXPECTED_COLLECTION_IDENTIFIED
		);
		$requestData = [
			'key_one' => 'value 1',
			'key_two' => 'value 2'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue(['key' => 'value']));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model']
			)
			->will($this->returnValue($this->mCollection));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testPutObjectCanBeRetrievedViaAlternativeGatewayFetchStatement()
	{
		$requestData = ['key' => 'value'];
		$dismantledData = [
			'id'		=> 10,
			'key_one'	=> 'value'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['setKey'])
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(false));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetExists')
			->with('fetch_key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetGet')
			->with('fetch_key')
			->will($this->returnValue('by_some_method'));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetchBySomeMethod',
				['PO\Model']
			)
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('save')
			->with($mModel);
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue($dismantledData));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($dismantledData);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testPutDataIsFilteredUsingSelectMapBeforeBeingPassedToResponse()
	{
		$requestData = ['key' => 'value'];
		$dismantledData = [
			'id'	=> 10,
			'key'	=> 'value',
			'other'	=> 'other value'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['setKey'])
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('one'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(false));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model', 'one']
			)
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue($dismantledData));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'id'	=> 10,
				'key'	=> 'value'
			]);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'-other'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testPutDataCanCallObjectMethodsUsingSelectMapBeforeBeingPassedToResponse()
	{
		$requestData = ['key' => 'value'];
		$dismantledData = [
			'id'	=> 10,
			'key'	=> 'value'
		];
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['setKey', 'getData'])
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('one'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(false));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model', 'one']
			)
			->will($this->returnValue($mModel));
		$mModel
			->expects($this->once())
			->method('getData')
			->will($this->returnValue('other data value'));
		$this->mGateway
			->expects($this->any())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($mModel)
			->will($this->returnValue($dismantledData));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with([
				'id'			=> 10,
				'key'			=> 'value',
				'other_data'	=> 'other data value'
			]);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			[
				'+getData as other_data'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testSaveMapCanBeProvidedAndItIsPassedToGatewayOnSavingPutData()
	{
		$requestData = ['key' => 'value'];
		$mModel = $this->getMockBuilder('PO\Model')
			->setMethods(['setKey'])
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('PUT'));
		$this->mRequest
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue($requestData));
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->will($this->returnValue('one'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->will($this->returnValue(false));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model', 'one']
			)
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('save')
			->with($mModel, ['getSubObject']);
		$this->mGateway
			->expects($this->once())
			->method('getFactory')
			->will($this->returnValue($this->mFactory));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue([]));
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model',
			null,
			[
				'getSubObject'
			]
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testObjectCanBeDeletedFromGatewayAndResponseIsSetTo204()
	{
		$mModel = $this->getMockBuilder('PO\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('DELETE'));
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('10'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(false));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model', '10']
			)
			->will($this->returnValue($mModel));
		$this->mGateway
			->expects($this->once())
			->method('delete')
			->with($mModel);
		$this->mResponse
			->expects($this->once())
			->method('set204');
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
	public function testResponseISetTo404IfNoDataIsFoundWhilstDeleting()
	{
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('DELETE'));
		$this->mGateway
			->expects($this->once())
			->method('__call')
			->with(
				'fetch',
				['PO\Model']
			)
			->will($this->throwException(new Gateway\Exception(Gateway\Exception::NO_DATA_FOUND)));
		$this->mResponse
			->expects($this->once())
			->method('set404')
			->with(null);
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $this->mRouteVariables);
	}
	
	public function testMultipleObjectsCanBeDeletedFromGatewayAndResponseIsSetTo204()
	{
		$stdObject1 = new \stdClass();
		$stdObject2 = new \stdClass();
		$this->mRequest
			->expects($this->once())
			->method('getRequestMethod')
			->will($this->returnValue('DELETE'));
		$mRouteVariables = $this->getMockBuilder('\PO\Application\Dispatchable\Rest\RouteVariables')
			->disableOriginalConstructor()
			->getMock();
		$mRouteVariables
			->expects($this->at(0))
			->method('offsetExists')
			->with('key')
			->will($this->returnValue(true));
		$mRouteVariables
			->expects($this->at(1))
			->method('offsetGet')
			->with('key')
			->will($this->returnValue('10'));
		$mRouteVariables
			->expects($this->at(2))
			->method('offsetExists')
			->with('key_2')
			->will($this->returnValue(false));
		$this->mGateway
			->expects($this->at(1))
			->method('__call')
			->with(
				'fetch',
				['PO\Model', '10']
			)
			->will($this->returnValue($this->mCollection));
		$this->mCollection
			->expects($this->at(0))
			->method('count')
			->will($this->returnValue(2));
		$this->mCollection
			->expects($this->at(1))
			->method('offsetGet')
			->with(0)
			->will($this->returnValue($stdObject1));
		$this->mCollection
			->expects($this->at(2))
			->method('count')
			->will($this->returnValue(2));
		$this->mCollection
			->expects($this->at(3))
			->method('offsetGet')
			->with(1)
			->will($this->returnValue($stdObject2));
		$this->mGateway
			->expects($this->at(2))
			->method('delete')
			->with($stdObject1);
		$this->mGateway
			->expects($this->at(3))
			->method('delete')
			->with($stdObject2);
		$this->mResponse
			->expects($this->once())
			->method('set204');
		$endpoint = new GatewayModel(
			$this->mGateway,
			'PO\Model'
		);
		$endpoint->dispatch($this->mRequest, $this->mResponse, $mRouteVariables);
	}
	
}
