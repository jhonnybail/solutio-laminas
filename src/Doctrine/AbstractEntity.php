<?php

/**
 * Solutio.Me
 *
 * @package     Solutio
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Doctrine;

use Zend\Hydrator,
    Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Annotations\AnnotationReader,
    Solutio\Utils\Data\StringManipulator;

/**
 * @ORM\MappedSuperclass
 * @ORM\EntityListeners({
 *  "Solutio\Doctrine\Listeners\GenerateIdentifierListener",
 *  "Solutio\Doctrine\Listeners\MappingsReferenceListener",
 *  "Solutio\Doctrine\Listeners\ValidateFieldsListener"
 * })
 */
abstract class AbstractEntity implements \JsonSerializable, \Solutio\EntityInterface
{
  private $annotationReader;
  private $childrenPendingRemovation  = [];
  private $changedValues              = [];
  private static $primaryKeys         = [];
  private static $toArrayObjs         = [];
  
  public function __construct($options = [])
  {
    if(is_array($options))
      $this->fromArray((array) $options);
    else{
      try{
        if(in_array('id', self::NameOfPrimaryKeys()))
          $this->setId($options);
      }catch(\Exception $e){}
    }
  }
  
  public function __call($name, $arguments)
  {
    $methodName = StringManipulator::GetInstance($name);
    if($methodName->search('get')){
      $propertyName = $methodName->replace('get', '')->toLowerCaseFirstChars();
      if(property_exists($this, $propertyName)){
        $reflection = new \ReflectionProperty($this, $propertyName);
        return ($reflection->isProtected() || $reflection->isPublic()) ? $this->{$propertyName} : null;
      }
    }elseif($methodName->search('set')){
      $propertyName         = $methodName->replace('set', '')->toLowerCaseFirstChars()->toString();
      try{
        if(is_string($arguments[0])){
          $propertyAnnotations  = $this->getAnnotationReader()->getPropertyAnnotations(new \ReflectionProperty(StringManipulator::GetInstance(get_class($this))->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString(), $propertyName));
          foreach($propertyAnnotations as $propertyAnnotation)
            if($propertyAnnotation instanceof ORM\Column && $propertyAnnotation->type === 'date')
              $arguments[0] = new \Solutio\Utils\Data\DateTime($arguments[0]);
            elseif($propertyAnnotation instanceof ORM\Column && $propertyAnnotation->type === 'datetime')
              $arguments[0] = new \Solutio\Utils\Data\DateTime($arguments[0], 'Y-m-d H:i:s');
        }
        if(property_exists($this, $propertyName)){
          $this->{$propertyName}              = $arguments[0];
          $this->changedValues[$propertyName] = $arguments[0] instanceof AbstractEntity ? $arguments[0]->getChangedValues() : $arguments[0];
        }
      }catch(\Exception $e){}
      return $this;
    }elseif($methodName->search('remove')){
      $propertyName = $methodName->replace('remove', '')->toLowerCaseFirstChars();
      if($propertyName->charAt($propertyName->length()-1) === 'y')
        $propertyName = $propertyName->substr(0, -1) . 'ies';
      elseif($propertyName->charAt($propertyName->length()-1) === 's')
        $propertyName = $propertyName . 'es';
      else
        $propertyName .= 's';
     
      if(!isset($this->changedValues[$propertyName]))
        $this->changedValues[$propertyName] = [];
        
      $entity = $this->findEntityInList($this->{$propertyName}, $arguments[0]);
      $this->{$propertyName}->removeElement($entity);
      
      if(isset($this->changedValues[$propertyName]) && $this->{$propertyName}->count() > 0)
        $this->changedValues[$propertyName] = $this->{$propertyName}->toArray();
      else
        $this->changedValues[$propertyName] = [];
        
      return $this;
    }elseif($methodName->search('add')){
      $propertyName = $methodName->replace('add', '')->toLowerCaseFirstChars();
      if($propertyName->charAt($propertyName->length()-1) === 'y')
        $propertyName = $propertyName->substr(0, -1) . 'ies';
      elseif($propertyName->charAt($propertyName->length()-1) === 's')
        $propertyName = $propertyName . 'es';
      else
        $propertyName .= 's';
      
      if(property_exists($this, $propertyName) && empty($this->{$propertyName}))
        $this->{$propertyName} = new \Doctrine\Common\Collections\ArrayCollection;
      
      if(!isset($this->changedValues[$propertyName]))
        $this->changedValues[$propertyName] = [];
      
      $setDependencyName  = 'set' . StringManipulator::GetInstance(get_class($this))
                                                              ->split('\\')
                                                              ->end();
      $getDependencyName  = 'get' . StringManipulator::GetInstance(get_class($this))
                                                              ->split('\\')
                                                              ->end();
      
      if($entity = $this->findEntityInList($this->{$propertyName}, $arguments[0])){
        $className  = get_class($arguments[0]);
        if(is_subclass_of($className, \Doctrine\ORM\Proxy\Proxy::class))
          $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
        $reflection = \Zend\Server\Reflection::reflectClass($className);
        $obj        = [];
        foreach($reflection->getProperties() as $property){
          $method = 'get' . ucfirst($property->getName());
          if ($arguments[0]->{$method}() !== null) {
            $existsValue = $entity->{$method}();
            if($existsValue instanceof AbstractEntity){
              $propertyAnnotations  = $this->getAnnotationReader()->getPropertyAnnotations($property);
              foreach($propertyAnnotations as $propertyAnnotation)
                if($propertyAnnotation instanceof ORM\ManyToOne || $propertyAnnotation instanceof ORM\OneToOne){
                  if(is_array($propertyAnnotation->cascade) && in_array('persist', $propertyAnnotation->cascade)){
                    $valuesChanged        = $arguments[0]->{$method}()->getChangedValues();
                    $existsValue->fromArray($valuesChanged);
                  }
                }
            }else
              $obj[$property->getName()] = $arguments[0]->{$method}();
          }
        }
        $entity->fromArray($obj);
      }else{
        $this->{$propertyName}[]  = $arguments[0];
        $arguments[0]->{$setDependencyName}($this);
      }
      
      $this->changedValues[$propertyName] = $this->{$propertyName}->toArray();
      
      return $this;
    }
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
        if($propertyAnnotation instanceof ORM\OneToMany || $propertyAnnotation instanceof ORM\ManyToMany){
          if(array_key_exists($name, $data) && !empty($data[$name])){
            $pending[$method] = [
              'name'                => $name,
              'propertyAnnotation'  => $propertyAnnotation,
              'data'                => $data[$name]
            ];
            unset($data[$name]);
          }elseif(array_key_exists($name, $data) && empty($data[$name])){
            unset($data[$name]);
          }
        }elseif($propertyAnnotation instanceof ORM\ManyToOne || $propertyAnnotation instanceof ORM\OneToOne){
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
              if($this->{$name} instanceof $className
                  && $newEntity->getKeys() === $this->{$name}->getKeys()
                  && ! $data[$name] instanceof \Doctrine\ORM\Proxy\Proxy){
                foreach($data[$name] as $k => $v)
                  if($v === $this)
                    unset($data[$name][$k]);
                $this->{$name}->fromArray($data[$name]);
                unset($data[$name]);
              }else
                $data[$name] = $newEntity;
            }
          }
        }
    }
    (new Hydrator\ClassMethods)->hydrate($data,$this);
    foreach($pending as $method => $array){
      $name               = $array['name'];
      $propertyAnnotation = $array['propertyAnnotation'];
      $data               = $array['data'];
      $method = StringManipulator::GetInstance('add' . ucfirst($name));
      if($method->substr($method->length()-3, 3)->toString() === 'ies')
        $method = $method->substr(0, -3)->concat('y')->toString();
      elseif($method->substr($method->length()-3, 3)->toString() === 'ses')
        $method = $method->substr(0, -2)->toString();
      else
        $method = $method->substr(0, -1)->toString();
      $methodRemove = StringManipulator::GetInstance($method)->replace('add', 'remove')->toString();
      $className    = preg_match('/\\\/', $propertyAnnotation->targetEntity) ? $propertyAnnotation->targetEntity : $reflection->getNamespaceName() . '\\' . $propertyAnnotation->targetEntity;
      $className    = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
      if(is_array($data) || $data instanceof \Traversable){
        foreach($data as $index => $occ){
          $occEntity  = ($occ instanceof $className) ? $occ : ($occ instanceof \Traversable ? new $className((array) $occ) : new $className($occ));
          $this->{$method}($occEntity);
          if((is_array($occ) || $occ instanceof \Traversable) && isset($occ['remove']) && $occ['remove']){
            $this->{$methodRemove}($occEntity);
            if(!isset($this->childrenPendingRemovation[$className]))
              $this->childrenPendingRemovation[$className] = [];
            if(!isset($this->childrenPendingRemovation[$className][$name]))
              $this->childrenPendingRemovation[$className][$name] = [
                'options' => [
                  'propertyAnnotation' => $propertyAnnotation
                ],
                'entities' => []
              ];
            $this->childrenPendingRemovation[$className][$name]['entities'][] = $occEntity;
          }
        }
      }
    }
  }

  public function toArray() : array
  {
    $className        = get_class($this);
    if(is_subclass_of($className, \Doctrine\ORM\Proxy\Proxy::class))
      $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
    $reflection       = \Zend\Server\Reflection::reflectClass($className);
    $parentReflection = \Zend\Server\Reflection::reflectClass(get_parent_class($className));
    $obj      = [];
    foreach($reflection->getProperties() as $property){
      $method = 'get' . ucfirst($property->getName());
      if ($this->{$method}() !== null) {
        $obj[$property->getName()]      = $this->{$method}();
      }
    }
    foreach($parentReflection->getProperties() as $property){
      if(!isset($obj[$property->getName()])){
        $method = 'get' . ucfirst($property->getName());
        if ($this->{$method}() !== null) {
          $obj[$property->getName()]      = $this->{$method}();
        }
      }
    }
    
    foreach($obj as $k => $v)
      if(preg_match('/^__(.*)__$/', $k))
        unset($obj[$k]);
      elseif($v instanceof AbstractEntity){
        if(isset(self::$toArrayObjs[spl_object_hash($v)]) && !empty(self::$toArrayObjs[spl_object_hash($v)])){
          $obj[$k] = self::$toArrayObjs[spl_object_hash($v)];
        }else{
          self::$toArrayObjs[spl_object_hash($v)] = $v;
          self::$toArrayObjs[spl_object_hash($v)] = $obj[$k] = $v->toArray();
        }
      }elseif($v instanceof \Doctrine\Common\Collections\ArrayCollection
              || $v instanceof \Doctrine\ORM\PersistentCollection){
        $array = [];
        foreach($v->toArray() as $content) $array[] = $content;
        $obj[$k] = $array;
        if(count($obj[$k]) <= 0) unset($obj[$k]);
      }elseif($v === null)
        unset($obj[$k]);
        
    return $obj;
  }
  
  public function getChangedValues() : array
  {
    return $this->changedValues;
  }
  
  protected function setChangedValue($property, $value) : self
  {
    $this->changedValues[$property] = $value;
    return $this;
  }
  
  public function getChildrenPendingRemovation() : array
  {
    return $this->childrenPendingRemovation;
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

  public static function NameOfPrimaryKeys() : array
  {
    $className        = get_called_class();
    if(is_subclass_of($className, \Doctrine\ORM\Proxy\Proxy::class))
      $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
    if(empty(self::$primaryKeys[$className])){
      $reflection       = \Zend\Server\Reflection::reflectClass($className);
      $annotationReader = new AnnotationReader;
      self::$primaryKeys[$className]  = [];
      foreach($reflection->getProperties() as $property){
        $propertyAnnotations = $annotationReader->getPropertyAnnotations($property);
        foreach($propertyAnnotations as $propertyAnnotation){
          if($propertyAnnotation instanceof ORM\Id)
            self::$primaryKeys[$className][] = $property->getName();
        }
      }
      $parentClassName  = get_parent_class($className);
      if($parentClassName && is_subclass_of($parentClassName, \Solutio\Doctrine\AbstractEntity::class)){
        $parentKeys = $parentClassName::NameOfPrimaryKeys();
        foreach($parentKeys as $key)
          if(!in_array($key, self::$primaryKeys[$className]))
            self::$primaryKeys[$className][] = $key;
      }
    }
    return self::$primaryKeys[$className];
  }

  public function jsonSerialize() : array
  {
    $className        = get_class($this);
    if(is_subclass_of($className, \Doctrine\ORM\Proxy\Proxy::class))
      $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
    $reflection       = \Zend\Server\Reflection::reflectClass($className);
    $parentReflection = \Zend\Server\Reflection::reflectClass(get_parent_class($className));
    $obj      = [];
    foreach($reflection->getProperties() as $property){
      $method = 'get' . ucfirst($property->getName());
      if ($this->{$method}() !== null) {
        $obj[$property->getName()]      = $this->{$method}();
      }
    }
    foreach($parentReflection->getProperties() as $property){
      if(!isset($obj[$property->getName()])){
        $method = 'get' . ucfirst($property->getName());
        if ($this->{$method}() !== null) {
          $obj[$property->getName()]      = $this->{$method}();
        }
      }
    }
    
    foreach($obj as $k => $v)
      if(preg_match('/^__(.*)__$/', $k))
        unset($obj[$k]);
      elseif($v instanceof \Doctrine\Common\Collections\ArrayCollection
              || $v instanceof \Doctrine\ORM\PersistentCollection){
        $array = [];
        foreach($v->toArray() as $content) $array[] = $content;
        $obj[$k] = $array;
        if(count($obj[$k]) <= 0) unset($obj[$k]);
      }elseif($v === null)
        unset($obj[$k]);
        
    return $obj;
  }
  
  protected function getAnnotationReader() : AnnotationReader
  {
    if(empty($this->annotationReader))
      $this->annotationReader = new AnnotationReader;
    return $this->annotationReader;
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