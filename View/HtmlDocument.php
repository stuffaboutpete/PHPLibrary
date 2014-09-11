<?php

namespace PO\View;

use PO\View;

class HtmlDocument
extends View
{
	
	private $title;
	private $styles = [];
	private $headScripts = [];
	private $footScripts = [];
	private $content;
	
	public function __construct($content = null)
	{
		if (isset($content)) $this->setContent($content);
		parent::__construct();
	}
	
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	public function addStylesheet($stylesheet)
	{
		array_push($this->styles, $stylesheet);
	}
	
	public function addScript($script, $head = false)
	{
		array_push(((bool) $head) ? $this->headScripts : $this->footScripts, $script);
	}
	
	public function setContent($content)
	{
		$this->content = $content;
	}
	
	public function __toString()
	{
		foreach (['title', 'styles', 'headScripts', 'footScripts', 'content'] as $property) {
			$this->addTemplateVariable($property, $this->$property);
		}
		return parent::__toString();
	}
	
}
