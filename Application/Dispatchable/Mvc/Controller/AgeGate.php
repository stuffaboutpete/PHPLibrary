<?php

namespace Suburb\Application\Dispatchable\Mvc\Controller;

use Suburb\Application;
use Suburb\Application\Dispatchable\Mvc\Controller;

class AgeGate
extends Controller
{
	
	private $requiredAccessType;
	private $redirectUrl;
	private $requiredAge;
	
	public function __construct($requiredAccessType, $redirectUrl, $requiredAge = 18)
	{
		$this->requiredAccessType = $requiredAccessType;
		$this->redirectUrl = $redirectUrl;
		$this->requiredAge = $requiredAge;
	}
	
	public function dispatch(Application $application, $pathVariables = null)
	{
		
		if (!$application->hasExtension('authenticator')) {
			throw new \Exception('Authenticator does not exist as application extension');
		}
		
		if ($application->getAuthenticator()->userCanAccess($this->requiredAccessType)) {
			header('Location: ' . $this->redirectUrl);
			exit;
		}
		
		if ($_SERVER['REQUEST_METHOD'] != 'POST') return;
		
		if (!isset($_POST['year']) || !isset($_POST['month']) || !isset($_POST['day'])) {
			$this->addTemplateVariable('missingFields', true);
			return;
		}
		
		try {
			$birthDate = new \DateTime(
				intval($_POST['year']) . '-' . intval($_POST['month']) . '-' . intval($_POST['day'])
			);
		} catch (\Exception $exception) {
			$this->addTemplateVariable('missingFields', true);
			return;
		}
		
		if ($birthDate->diff(new \DateTime())->y < $this->requiredAge) {
			$this->addTemplateVariable('invalidAge', true);
		} else {
			$application->getAuthenticator()->assignAccessToUser($this->requiredAccessType);
			setcookie('dob', $birthDate->format('Y-m-d'));
			header('Location: ' . $this->redirectUrl);
			exit;
		}
		
	}
	
}
