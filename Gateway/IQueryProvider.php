<?php

namespace PO\Gateway;

interface IQueryProvider
{
	
	public function approveClass($class);
	public function getSingleSelectPreparedStatements($keys);
	public function getMultipleSelectPreparedStatements($keys);
	public function getSavePreparedStatement($keys, $allFields);
	public function getDeletePreparedStatement($keys);
	
}
