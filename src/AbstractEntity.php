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

/**
 * @ORM\MappedSuperclass
 * @ORM\EntityListeners({
 *  "Solutio\Doctrine\Listeners\ValidateFieldsListener",
 *  "Solutio\Doctrine\Listeners\MappingsReferenceListener",
 *  "Solutio\Doctrine\Listeners\GeneretateIdentifierListener"
 * })
 */
abstract class AbstractEntity implements \JsonSerializable
{
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
      if(strpos($v->getName(), 'set') === 0){
        $name = StringManipulator::GetInstance($v->getName())->replace('set', '')->toLowerCaseFirstChars();
        if(isset($data[$name]) && $data[$name] !== null){
          $class  = $v->getParameters()[0]->getClass() ? $v->getParameters()[0]->getClass()->getName() : null;
          if(isset($data[$name]) && !($data[$name] instanceof \Solutio\AbstractEntity) && !empty($class)){
            $data[$name] = new $class($data[$name]);
          }
        }
      }elseif(strpos($v->getName(), 'add') === 0){
        $name = StringManipulator::GetInstance($v->getName())->replace('add', '')->toLowerCaseFirstChars().'s';
        if(isset($data[$name]) && $data[$name] !== null){
          $class  = $v->getParameters()[0]->getClass() ? $v->getParameters()[0]->getClass()->getName() : null;
          foreach($data[$name] as $index => $occ){
            $data[$name][$index] = ($class && !($occ instanceof $class) ? new $class($occ) : $occ);
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
      if(preg_match('/^__(.*)__$/', $k))
        unset($obj[$k]);
      elseif($v instanceof \Lubro\Sistema\Entities\AbstractEntity){
        $obj[$k] = $v->toArray();
      }elseif($v instanceof \Doctrine\Common\Collections\ArrayCollection
              || $v instanceof \Doctrine\ORM\PersistentCollection){
        $obj[$k] = $v->toArray();
        if(count($obj[$k]) <= 0) unset($obj[$k]);
      }elseif($v === null)
        unset($obj[$k]);
        
    return $obj;
  }

  public function getKeys()
  {
    $keys   = self::NameOfPrimaryKeys();
    $data   = $this->toArray();
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

  public function jsonSerialize()
  {
    return $this->toArray();
  }
  
  protected function findEntityInList(\Traversable $list, AbstractEntity $entity)
  {
    $keys = $entity->getKeys();
    if($list->count() > 0){
      foreach($list as $occ){
        $data   = $occ->getKeys();
        $exists = true;
        foreach($keys as $k => $v)
          if(!($data[$k] === $v) || $v === null){
            $exists = false;
            break;
          }
        if($exists) return $occ;
      }
      return false;
    }
  }
}