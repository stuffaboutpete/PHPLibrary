<?php

namespace PO\Gateway;

interface IFactory
{
	
	public function approveClass($class);
	public function build(array $data);
	public function dismantle($object);
	
}
