<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Solutio\Doctrine\AbstractEntity;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Ramsey\Uuid\Uuid;

class GenerateIdentifierListener
{
  private $listMaxCount         = [];
  private $allowGeneratorFlush  = [];
  private $generatorFieldName   = [];
  
  private function getTotalEmptyKeys(AbstractEntity $entity) : int
  {
    $keys = $entity->getKeys();
    $cont = 0;
    foreach($keys as $key => $value)
      if(empty($value)){
        $cont++;
      }
    return $cont;
  }
  
  private function getLastEmptyKey(AbstractEntity $entity) : string
  {
    $keys   = $entity->getKeys();
    $field  = '';
    foreach($keys as $key => $value)
      if(empty($value)){
        $field = $key;
      }
    return $field;
  }
  
  private function getField(AbstractEntity $entity) : string
  {
    $field  = '';
    $keys   = $entity->getKeys();
    if(!isset($this->generatorFieldName[get_class($entity)])){
      
      //Verifica algum id vazio e gera automaticamente o valor
      $field  = $this->getLastEmptyKey($entity);
      $cont   = $this->getTotalEmptyKeys($entity);
      //
      
      if(!empty($field)){
        if($cont > 1){
          $field      = '';
          $reflection = \Laminas\Server\Reflection::reflectClass($entity);
          foreach($reflection->getProperties() as $property){
              $propertyAnnotations = (new \Doctrine\Common\Annotations\AnnotationReader)->getPropertyAnnotations($property);
              foreach($propertyAnnotations as $propertyAnnotation){
                  if($propertyAnnotation instanceof ORM\GeneratedValue && $propertyAnnotation->strategy === "NONE"){
                      $field  = $property->getName();
                      break;
                  }
              }
          }
        }
      }
      
      if(!empty($field))
        $this->generatorFieldName[get_class($entity)] = $field;
    }else{
      $field  = $this->generatorFieldName[get_class($entity)];
    }
    
    return $field;
  }
  
  private function generateIdToKey(AbstractEntity $entity, string $field, EntityManager $em)
  {
    if(!empty($field)){
      $originalValue  = $entity->{"get".ucfirst($field)}();
      if(!empty($originalValue) && $originalValue instanceof AbstractEntity){
        $className  = get_class($originalValue);
        
        //Geração de Id para entidade filha
        $keys       = $originalValue->getKeys();
        $cont       = $this->getTotalEmptyKeys($originalValue);
        $childField = $this->getField($originalValue);
        if($cont > 0){
          unset($keys[$childField]);
          $listIndex  = $className . (count($keys) === 0 ? '': new \Solutio\Utils\Data\ArrayObject($keys));
          if(!isset($this->listMaxCount[$listIndex])){
            $query  = $em->createQueryBuilder()
                          ->from($className, 'e')
                          ->select("MAX(e.{$childField})");
            foreach($keys as $k => $v)
              $query->andWhere("e.{$k} = :{$k}")
                      ->setParameter($k, $v);
            $id     = $query->getQuery()
                          ->getSingleScalarResult();
          }else
            $id = $this->listMaxCount[$listIndex];
          //
          
          $value      = new $className(++$id);
          $value->fromArray($originalValue->toArray());
          $entity->{"set".ucfirst($field)}($value);
        }
      }else{
        
        //Geração de Id para a própria entidade
        $keys = $entity->getKeys();
        $cont = $this->getTotalEmptyKeys($entity);
        if($cont > 0){
          unset($keys[$field]);
          $listIndex = get_class($entity) . (count($keys) === 0 ? '': new \Solutio\Utils\Data\ArrayObject($keys));
          if(!isset($this->listMaxCount[$listIndex])){
            $query  = $em->createQueryBuilder()
                          ->from(get_class($entity), 'e')
                          ->select("MAX(e.{$field})");
            foreach($keys as $k => $v)
              $query->andWhere("e.{$k} = :{$k}")
                      ->setParameter($k, $v);
            $id     = $query->getQuery()
                          ->getSingleScalarResult();
          }else
            $id = $this->listMaxCount[$listIndex];
          //
          
          $entity->{"set".ucfirst($field)}(++$id);
        }
      }
      
      if($cont === 1)
        $this->listMaxCount[$listIndex] = $id;
      
    }
  }
  
  /** 
   * @ORM\PrePersist
   */
  public function prePersistHandler(AbstractEntity $entity, LifecycleEventArgs $event)
  {
    $field = $this->getField($entity);
    
    if(!empty($field)){
    
      $this->generateIdToKey($entity, $field, $event->getObjectManager());
      
      //Verifica se o attributo Id existir e estiver vazio, gera hash automaticamente
      if(method_exists($entity, 'getId') && empty($entity->getId())){
        $entity->setId((string) Uuid::uuid4());
      }
    
    }
  }
  
  /** 
   * @ORM\PreFlush
   */
  public function preFlushHandler(AbstractEntity $entity, PreFlushEventArgs $arguments)
  {
    $field = $this->getField($entity);
    
    if(!empty($field) && ! $entity instanceof \Doctrine\ORM\Proxy\Proxy)
      $this->generateIdToKey($entity, $field, $arguments->getObjectManager());
  }
}