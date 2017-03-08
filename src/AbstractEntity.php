<?php

/**
 * Solutio.Me
 *
 * @package     Solutio
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio;

use Zend\Hydrator,
    Solutio\Utils\Data\StringManipulator;

abstract class AbstractEntity implements \JsonSerializable {
	
  public function __construct(array $options = array())
  {
    (new Hydrator\ClassMethods)->hydrate($options,$this);
  }
	
  public function toArray()
  {
    $obj = (new Hydrator\ClassMethods(false))->extract($this);
    foreach($obj as $k => $v)
      if((new StringManipulator($k))->search('^__(.*)__$'))
        unset($obj[$k]);
    return $obj;
  }
  
  public function jsonSerialize()
  {
    return $this->toArray();
  }
	
}