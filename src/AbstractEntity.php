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
    Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Annotations\AnnotationReader,
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
    $pending    = [];
    foreach($reflection->getProperties() as $property){
      $method = 'get' . ucfirst($property->getName());
      $name = $property->getName();
      $propertyAnnotations = $this->getAnnotationReader()->getPropertyAnnotations($property);
      if(array_key_exists($name, $data) && is_null($data[$name])){
        $method = str_replace('get', 'set', $method);
        $this->{$method}(null); 
      }
      foreach($propertyAnnotations as $propertyAnnotation)
        if($propertyAnnotation instanceof ORM\ManyToOne || $propertyAnnotation instanceof ORM\OneToOne){
          if(array_key_exists($name, $data)){
            $className  = preg_match('/\\\/', $propertyAnnotation->targetEntity) ? $propertyAnnotation->targetEntity : $reflection->getNamespaceName() . '\\' . $propertyAnnotation->targetEntity;
            $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
            if(array_key_exists($name, $data) && !($data[$name] instanceof $className)){
              $newEntity = new $className(($data[$name] instanceof \Traversable || is_array($data[$name])) ? (array) $data[$name] : $data[$name]);
              if(!(is_array($propertyAnnotation->cascade) && in_array('persist', $propertyAnnotation->cascade))){
                $data[$name]  = $newEntity->getKeys();
                foreach($data[$name] as $v){
                  if(is_null($v) || $v === ""){
                    $data[$name] = null;
                    break;
                  }
                }
                if(is_null($data[$name]))
                  continue;
                $newEntity    = new $className($data[$name]);
              }
              if($this->{$method}() instanceof $className
                  && $newEntity->getKeys() === $this->{$method}()->getKeys()
                  && ! $data[$name] instanceof \Doctrine\ORM\Proxy\Proxy){
                foreach($data[$name] as $k => $v)
                  if($v === $this)
                    unset($data[$name][$k]);
                $this->{$method}()->fromArray($data[$name]);
                unset($data[$name]);
              }else
                $data[$name] = $newEntity;
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
  
  public function getKeys() : array
  {
    $keys   = self::NameOfPrimaryKeys();
    $return = [];
    foreach($keys as $key){
      $methodName = 'get' . ucfirst($key);
      $return[$key] = $this->{$methodName}();
      if($this->{$methodName}() instanceof AbstractEntity){
        $return[$key] = $this->{$methodName}()->getKeys();
        if(count($return[$key]) === 1)
          $return[$key] = current($return[$key]);
      }
    }
    return $return;
  }

  public static function NameOfPrimaryKeys()
  {
    return static::$primaryKeys;
  }

  protected function getAnnotationReader() : AnnotationReader
  {
    if(empty($this->annotationReader))
      $this->annotationReader = new AnnotationReader;
    return $this->annotationReader;
  }

  public function jsonSerialize()
  {
    return $this->toArray();
  }
}