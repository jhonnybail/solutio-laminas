<?php

/**
 * Solutio.Me
 *
 * @package     Solutio
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio;

use Zend\Stdlib\Hydrator;

abstract class AbstractEntity {
	
	public function __construct(array $options = array())
  {
    (new Hydrator\ClassMethods)->hydrate($options,$this);
  }
	
	public function toArray()
	{
		$obj = (new Hydrator\ClassMethods())->extract($this);
		return $obj;
  }
	
}