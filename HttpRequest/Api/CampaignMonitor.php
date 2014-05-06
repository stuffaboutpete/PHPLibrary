<?php

namespace PO\HttpRequest\Api;

class CampaignMonitor
extends \PO\HttpRequest
{
	
	public function __construct($transferMethod, $response)
	{
		parent::__construct(
			$transferMethod,
			$response,
			'https://api.createsend.com/api/v3'
		);
	}
	
	public function addSubscriberToList(
		CampaignMonitor\ICampaignList	$list,
		array							$data
	)
	{
		$listId = $list->getId();
		$campaignMonitorKey = $list->getCampaignMonitorKey();
		return $this->post(
			"subscribers/$listId.json",
			$data,
			[
				'Authorization' => 'Basic ' . base64_encode(
					$campaignMonitorKey . ':'
				)
			]
		);
	}
	
}