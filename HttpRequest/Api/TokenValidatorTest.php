<?php

namespace Suburb\HttpRequest\Api;

require_once dirname(__FILE__) . '/../../HttpRequest.php';
require_once dirname(__FILE__) . '/TokenValidator.php';
require_once dirname(__FILE__) . '/TokenValidator/IMechanism.php';
require_once dirname(__FILE__) . '/../../Exception.php';
require_once dirname(__FILE__) . '/TokenValidator/Exception.php';
require_once dirname(__FILE__) . '/../ITransferMethod.php';
require_once dirname(__FILE__) . '/../Response.php';

class TokenValidatorTest
extends \PHPUnit_Framework_TestCase {
	
	private $mTransferMethod;
	private $mResponse;
	private $mTokenMechanism;
	
	public function setUp()
	{
		$this->mTransferMethod = $this->getMock('\Suburb\HttpRequest\ITransferMethod');
		$this->mResponse = $this->getMock('\Suburb\HttpRequest\Response');
		$this->mTokenMechanism = $this->getMock(
			'\Suburb\HttpRequest\Api\TokenValidator\IMechanism'
		);
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->mTransferMethod);
		unset($this->mResponse);
		unset($this->mTokenMechanism);
		parent::tearDown();
	}
	
	public function testTokenValidatorCanBeInstantiated()
	{
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$this->assertInstanceOf('\Suburb\HttpRequest\Api\TokenValidator', $validator);
	}
	
	public function testTokenMechanismIsRequired()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse
		);
	}
	
	public function testIsValidTokenRequiresString()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken(['some_token']);
	}
	
	public function testFieldsProvidedToIsValidTokenShouldMatchRequiredFieldsSuppliedByMechanism()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$this->mTokenMechanism
			->expects($this->once())
			->method('getEntryRequiredFields')
			->will($this->returnValue(['field1', 'field2', 'field3']));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken(
			'token',
			['field1' => 'value 1', 'field2' => 'value 2']
		);
	}
	
	public function testCallingIsValidTokenGetsFullPathAndMakesRequest()
	{
		$this->mTokenMechanism
			->expects($this->once())
			->method('getBasePath')
			->will($this->returnValue('http://somepath.com'));
		$this->mTokenMechanism
			->expects($this->once())
			->method('getEntryPath')
			->will($this->returnValue('entry_path'));
		$this->mTokenMechanism
			->expects($this->once())
			->method('getTokenFieldName')
			->will($this->returnValue('code'));
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				'http://somepath.com/entry_path',
				'POST',
				$this->mResponse,
				[],
				'{"field1":"value 1","field2":"value 2","code":"token"}'
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken(
			'token',
			['field1' => 'value 1', 'field2' => 'value 2']
		);
	}
	
	public function testEntryResponseIsPassedToMechanismWhichCanThrowError()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$this->mTokenMechanism
			->expects($this->once())
			->method('getEntryPath')
			->will($this->returnValue('entry_path'));
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('validateEntryResponse')
			->with($this->mResponse)
			->will($this->throwException(new \Suburb\HttpRequest\Api\TokenValidator\Exception(99)));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
	}
	
	public function testNoExceptionThrownFromValidateEntryResponseIndicatesValidToken()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$this->assertTrue($validator->isValidToken('token'));
	}
	
	public function testIsInstantWinRequiresString()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isInstantWin(['some_token']);
	}
	
	public function testAnExceptionIsThrownIfIsInstantWinIsCalledWithUntestedToken()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isInstantWin('unrecognised_token');
	}
	
	public function testResponseCanIndicateInstantWin()
	{
		$this->mTransferMethod
			->expects($this->once())
			->method('request');
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('isInstantWin')
			->with($this->mResponse)
			->will($this->returnValue(true));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
		$this->assertTrue($validator->isInstantWin('token'));
	}
	
	public function testResponseCanIndicateNonInstantWin()
	{
		$this->mTransferMethod
			->expects($this->once())
			->method('request');
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('isInstantWin')
			->with($this->mResponse)
			->will($this->returnValue(false));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
		$this->assertFalse($validator->isInstantWin('token'));
	}
	
	public function testAnExceptionIsThrownIfCompleteEntryIsCalledWithUntestedToken()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->completeEntry('unrecognised_token');
	}
	
	public function testExceptionIsThrownIfEntryIsCompletedWhenNotAnInstantWin()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$this->mTransferMethod
			->expects($this->once())
			->method('request');
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('isInstantWin')
			->with($this->mResponse)
			->will($this->returnValue(false));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
		$validator->completeEntry('token');
	}
	
	public function testCompleteEntryRequiresString()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->completeEntry(['some_token']);
	}
	
	public function testFieldsProvidedToCompleteEntryShouldMatchRequiredFieldsSuppliedByMechanism()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('isInstantWin')
			->with($this->mResponse)
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('getCompletionRequiredFields')
			->will($this->returnValue(['field1', 'field2', 'field3']));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
		$validator->completeEntry(
			'token',
			['field1' => 'value 1', 'field2' => 'value 2']
		);
	}
	
	public function testCompletingEntryGetsCompletionPathAndInstanceWinIdentityAndMakesRequest()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('isInstantWin')
			->with($this->mResponse)
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('getCompletionPath')
			->will($this->returnValue('completion_path'));
		$this->mTokenMechanism
			->expects($this->once())
			->method('getInstantWinIdentityField')
			->with($this->mResponse)
			->will($this->returnValue(['instant_win_id' => '12345']));
		$this->mTransferMethod
			->expects($this->at(1))
			->method('request')
			->with(
				'completion_path',
				'POST',
				$this->mResponse,
				[],
				'{"field1":"value 1","field2":"value 2","instant_win_id":"12345"}'
			);
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
		$validator->completeEntry('token', ['field1' => 'value 1', 'field2' => 'value 2']);
	}
	
	public function testCompletionResponseIsPassedToMechanismWhichCanThrowError()
	{
		$this->setExpectedException('\Suburb\HttpRequest\Api\TokenValidator\Exception');
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('isInstantWin')
			->with($this->mResponse)
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('validateCompletionResponse')
			->with($this->mResponse)
			->will($this->throwException(new \Suburb\HttpRequest\Api\TokenValidator\Exception(99)));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
		$validator->completeEntry('token');
	}
	
	public function testNoExceptionThrownFromValidateCompletionResponseIndicatesValidToken()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->once())
			->method('isInstantWin')
			->with($this->mResponse)
			->will($this->returnValue(true));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken('token');
		$this->assertTrue($validator->completeEntry('token'));
	}
	
	public function testRequestEncodingTypeCanBeProvidedByMechanism()
	{
		$this->mTokenMechanism
			->expects($this->once())
			->method('getRequestEncoding')
			->will($this->returnValue('form-urlencoded'));
		$this->mTokenMechanism
			->expects($this->once())
			->method('getEntryPath')
			->will($this->returnValue('entry_path'));
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				'entry_path',
				'POST',
				$this->mResponse,
				[],
				'field1=value%201&field2=value%202&token=token'
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->isValidToken(
			'token',
			['field1' => 'value 1', 'field2' => 'value 2']
		);
	}
	
	public function testInstantWinIdentityFieldCanBeProvidedToCompleteEntry()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mTokenMechanism
			->expects($this->never())
			->method('isInstantWin');
		$this->mTokenMechanism
			->expects($this->once())
			->method('getCompletionPath')
			->will($this->returnValue('completion_path'));
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				'completion_path',
				'POST',
				$this->mResponse,
				[],
				'{"field1":"value 1","field2":"value 2","instant_win_id":"12345"}'
			);
		$validator = new \Suburb\HttpRequest\Api\TokenValidator(
			$this->mTransferMethod,
			$this->mResponse,
			$this->mTokenMechanism
		);
		$validator->completeEntry(
			'token',
			['field1' => 'value 1', 'field2' => 'value 2'],
			['instant_win_id' => '12345']
		);
	}
	
	/**
	 * Should
	 * 
	 *	Accept transfermethod, response and IMechanism
	 *	Set base path to something provided by mechanism
	 *	Set encoding to something provided by mechanism
	 *	isValidToken requires string or number
	 *	isValidToken requires fields provided by getEntryRequiredFields
	 *	isValidToken gets entry path and makes post request
	 *	Response is provided to validateEntryResponse which may throw an exception
	 *	isInstantWin throws if no entry has been made
	 *	isInstantWin requires token
	 *	No request is made on isInstantWin but mechanism's isInstantWin is called with response from entry
	 *	Return value from mechanism's isInstantWin is returned to user
	 * Return value from mechanism's isInstantWin is converted to boolean
	 *	completeEntry can only be called if isInstantWin
	 *	Completing entry gets completion path and instantWinIdentityField and makes post request
	 *	Completing entry ensures required fields are provided
	 *	Response is provided to validateCompletionResponse which may throw an exception
	 *	Getcodefieldname
	 */
	
}