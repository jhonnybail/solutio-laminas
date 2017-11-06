<?php

namespace Solutio\Doctrine\Listeners;

use Doctrine\ORM\Mapping as ORM;
use Lubro\Sistema\Entities\AbstractEntity;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Ramsey\Uuid\Uuid;

class GeneretateIdentifierListener
{
  private $listMaxCount = [];
  
  /** 
   * @ORM\PrePersist
   */
  public function prePersistHandler(AbstractEntity $entity, LifecycleEventArgs $event)
  {
    //Verifica algum id vazio e gera automaticamente o valor
    $keys   = $entity->getKeys();
    $field  = '';
    foreach($keys as $key => $value)
      if(empty($value)) $field = $key;
    //
    
    if(!empty($field)){
      unset($keys[$field]);
      if(!isset($this->listMaxCount[get_class($entity)])){
        $query  = $event->getEntityManager()
                      ->createQueryBuilder()
                      ->from(get_class($entity), 'e')
                      ->select("MAX(e.{$field})");
        foreach($keys as $k => $v)
          $query->andWhere("e.{$k} = :{$k}")
                  ->setParameter($k, $v);
        $id     = $query->getQuery()
                      ->getSingleScalarResult();
      }else {
        $id = $this->listMaxCount[get_class($entity)];
      }
      $entity->{"set".ucfirst($field)}(++$id);
      $this->listMaxCount[get_class($entity)] = $id;
    }
    
    //Verifica se o attributo Id existir e estiver vazio, gera hash automaticamente
    if(method_exists($entity, 'getId') && empty($entity->getId())){
      $entity->setId((string) Uuid::uuid4());
    }
  }
}