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
	
  public function __construct($options = [])
  {
    if(is_array($options))
      $this->fromArray($options);
  }
  
  public function fromArray(array $data)
  {
    $reflection = \Zend\Server\Reflection::reflectClass($this);
    foreach($reflection->getMethods() as $v){
      if(count($v->getPrototypes()) > 1){
        $type = $v->getPrototypes()[1]->getParameters()[0]->getType();
        if(class_exists($type)){
          $name = lcfirst((new StringManipulator($v->getName()))->replace('^set', ''));
          if(isset($data[$name]) && (is_array($data[$name]) || $data[$name] instanceof \Solutio\Utils\Data\ArrayObject)){
            $data[$name] = new $type((array) $data[$name]);
          }
        }
      }
    }
    (new Hydrator\ClassMethods)->hydrate($data,$this);
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