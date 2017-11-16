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
 *  "Solutio\Doctrine\Listeners\ValidateFieldsListener",
 *  "Solutio\Doctrine\Listeners\MappingsReferenceListener"
 * })
 */
abstract class AbstractEntity implements \JsonSerializable, \Solutio\EntityInterface
{
  private $annotationReader;
  private $childrenPendingRemovation  = [];
  private static $primaryKeys         = [];
  
  public function __construct($options = [])
  {
    if(is_array($options))
      $this->fromArray((array) $options);
    elseif(!empty($options)){
      try{
        $propertyReflection = new \ReflectionProperty(StringManipulator::GetInstance(get_class($this))->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString(), 'id');
        if($propertyReflection)
          $this->setId($options);
      }catch(\Exception $e){}
    }
  }
  
  public function __call($name, $arguments)
  {
    $methodName = StringManipulator::GetInstance($name);
    if($methodName->search('get')){
      $propertyName = $methodName->replace('get', '')->toLowerCaseFirstChars();
      return $this->{$propertyName};
    }elseif($methodName->search('set')){
      $propertyName         = $methodName->replace('set', '')->toLowerCaseFirstChars();
      if(is_string($arguments[0])){
        $propertyAnnotations  = $this->getAnnotationReader()->getPropertyAnnotations(new \ReflectionProperty(StringManipulator::GetInstance(get_class($this))->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString(), $propertyName));
        foreach($propertyAnnotations as $propertyAnnotation)
          if($propertyAnnotation instanceof ORM\Column && ($propertyAnnotation->type === 'date' || $propertyAnnotation->type === 'datetime'))
            $arguments[0] = new Utils\Data\DateTime($arguments[0]);
      }
      $this->{$propertyName} = $arguments[0];
      return $this;
    }elseif($methodName->search('add')){
      $propertyName = $methodName->replace('add', '')->toLowerCaseFirstChars();
      if($propertyName->charAt($propertyName->length()-1) === 'y')
        $propertyName = $propertyName->substr(0, -1) . 'ies';
      else
        $propertyName .= 's';
      
      if(property_exists($this, $propertyName) && empty($this->{$propertyName}))
        $this->{$propertyName} = new \Doctrine\Common\Collections\ArrayCollection;
        
      if($entity = $this->findEntityInList($this->{$propertyName}, $arguments[0])){
        $entity->fromArray($arguments[0]->toArray());
      }else{
        $this->{$propertyName}[]  = $arguments[0];
        $setDependencyName           = 'set' . StringManipulator::GetInstance(get_class($this))
                                                                ->split('\\')
                                                                ->end();
        $getDependencyName           = 'get' . StringManipulator::GetInstance(get_class($this))
                                                                ->split('\\')
                                                                ->end();
        if(empty($arguments[0]->{$getDependencyName}()))
          $arguments[0]->{$setDependencyName}($this);
      }
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
      foreach($propertyAnnotations as $propertyAnnotation)
        if($propertyAnnotation instanceof ORM\OneToMany || $propertyAnnotation instanceof ORM\ManyToMany){
          if(isset($data[$name]) && $data[$name] !== null){
            $pending[$method] = [
              'name'                => $name,
              'propertyAnnotation'  => $propertyAnnotation,
              'data'                => $data[$name]
            ];
            unset($data[$name]);
          }
        }elseif($propertyAnnotation instanceof ORM\ManyToOne || $propertyAnnotation instanceof ORM\OneToOne){
          if(isset($data[$name]) && $data[$name] !== null){
            $className  = preg_match('/\\\/', $propertyAnnotation->targetEntity) ? $propertyAnnotation->targetEntity : $reflection->getNamespaceName() . '\\' . $propertyAnnotation->targetEntity;
            $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
            if(isset($data[$name]) && !($data[$name] instanceof \Solutio\AbstractEntity)){
              $data[$name] = ($data[$name] instanceof \Traversable || is_array($data)) ? new $className((array) $data[$name]) : $data[$name];
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
      $method = (string) ($method->substr($method->length()-3, 3)->toString() === 'ies' ? $method->substr(0, -3)->concat('y') : $method->substr(0, -1));
      $className  = preg_match('/\\\/', $propertyAnnotation->targetEntity) ? $propertyAnnotation->targetEntity : $reflection->getNamespaceName() . '\\' . $propertyAnnotation->targetEntity;
      $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
      foreach($data as $index => $occ){
        $occEntity  = ($occ instanceof $className) ? $occ : new $className((array) $occ);
        $this->{$method}($occEntity);
        if((is_array($occ) || $occ instanceof \Traversable) && isset($occ['remove']) && $occ['remove'])
          $this->childrenPendingRemovation[$className][] = $occEntity;
      }
    }
  }

  public function toArray() : array
  {
    $className  = get_class($this);
    if(is_subclass_of($className, \Doctrine\ORM\Proxy\Proxy::class))
      $className = StringManipulator::GetInstance($className)->replace('DoctrineORMModule\\\Proxy\\\__CG__\\\\', '')->toString();
    $reflection = \Zend\Server\Reflection::reflectClass($className);
    $obj      = [];
    foreach($reflection->getProperties() as $property){
      $method = 'get' . ucfirst($property->getName());
      if ($this->{$method}() !== null) {
        $obj[$property->getName()] = $this->{$method}();
      }
    }
    
    foreach($obj as $k => $v)
      if(preg_match('/^__(.*)__$/', $k))
        unset($obj[$k]);
      elseif($v instanceof AbstractEntity){
        $obj[$k] = $v->toArray();
      }elseif($v instanceof \Doctrine\Common\Collections\ArrayCollection
              || $v instanceof \Doctrine\ORM\PersistentCollection){
        $obj[$k] = $v->toArray();
        if(count($obj[$k]) <= 0) unset($obj[$k]);
      }elseif($v === null)
        unset($obj[$k]);
        
    return $obj;
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
            self::$primaryKeys[$className] [] = $property->getName();
        }
      }
    }
    return self::$primaryKeys[$className];
  }

  public function jsonSerialize() : array
  {
    return $this->toArray();
  }
  
  private function getAnnotationReader() : AnnotationReader
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