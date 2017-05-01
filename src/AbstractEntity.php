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

abstract class AbstractEntity implements \JsonSerializable
{

  protected static $primaryKeys = ['id'];

  public function __construct($options = [])
  {
    if(is_array($options))
      $this->fromArray((array) $options);
    elseif(!empty($options) && method_exists($this, "setId"))
      $this->setId($options);
  }

  public function fromArray(array $data)
  {
    $reflection = \Zend\Server\Reflection::reflectClass($this);
    foreach($reflection->getMethods() as $v){
      $name = lcfirst((new StringManipulator($v->getName()))->replace('^set', ''));
      if(!empty($data[$name])){
        if(count($v->getPrototypes()) > 1){
          $type = $v->getPrototypes()[1]->getParameters()[0]->getType();
          if(class_exists($type)){
            if(isset($data[$name]) && !($data[$name] instanceof \Solutio\AbstractEntity)){
              $data[$name] = new $type($data[$name] instanceof \Solutio\Utils\Data\ArrayObject ? (array) $data[$name] : $data[$name]);
            }
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
      elseif($v instanceof \Doctrine\Common\Collections\ArrayCollection)
        $obj[$k] = $v->toArray();
      elseif($v === null)
        unset($obj[$k]);
    return $obj;
  }

  public static function NameOfPrimaryKeys()
  {
    return static::$primaryKeys;
  }

  public function jsonSerialize()
  {
    return $this->toArray();
  }

}