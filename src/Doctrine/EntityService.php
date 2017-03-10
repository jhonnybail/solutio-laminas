<?php

namespace Solutio\Doctrine;

use Zend\Hydrator;

class EntityService
{
  private $entity;
  private $em;
  
  public function __construct(\Doctrine\ORM\EntityManager $em, \Solutio\AbstractEntity $entity = null)
  {
    $this->em     = $em;
    if($entity)
      $this->setEntity($entity);
  }
  
  public function getEntityManager()
  {
    return $this->em;
  }
  
  public function setEntity(\Solutio\AbstractEntity $entity)
  {
    $this->entity = get_class($entity);
    return $this;
  }
  
  public function getEntity()
  {
    return $this->entity;
  }
  
  public function getById($id)
  {
    $repo   = $this->em->getRepository($this->entity);
    return $repo->find($id);
  }
  
  public function find(\Solutio\AbstractEntity $entity, $params, $fields, $type = EntityRepository::RESULT_ARRAY)
  {
    $repo 	= $this->em->getRepository($this->entity);
	  return $repo->getCollection($entity, $params, $fields, $type);
  }
  
  public function insert(\Solutio\AbstractEntity $entity)
  {
    $entity = $this->getReferenceByEntity($entity);
    $this->em->persist($entity);
    $this->em->flush();
    return $entity;
  }

  public function update(\Solutio\AbstractEntity $entity)
  {
    $entity = $this->getReferenceByEntity($entity);
    $this->em->persist($entity);
    $this->em->flush();
    return $entity;
  }

  public function delete(\Solutio\AbstractEntity $entity)
  {
    $entity = $this->getReferenceByEntity($entity);
    $this->em->remove($entity);
    $this->em->flush();
    return $entity;
  }
  
  protected function getReferenceByEntity(\Solutio\AbstractEntity $entity)
  {
    $values = [];
    $data = $entity->toArray();
    $meta = $this->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($entity));
    $ids  = $meta->identifier;
    foreach($ids as $id){
      if(!empty($data[$id]))
        $values[$id] = $data[$id];
    }
    try{
      $newEntity = $this->em->getReference(get_class($entity), $values);
    }catch(\Doctrine\ORM\ORMException $e){
      $newEntity = $entity;
    }
    if(get_class($entity) === $this->getEntity()){
  		$maps 	= $meta->getAssociationMappings();
  		foreach($data as $k => $v){
  			if(isset($maps[$k]) && $v !== null){
  				$am = $meta->getAssociationMapping($k);
  				if($am['type'] == 1 || $am['type'] == 2){
  					$data[$k] = $this->getReferenceByEntity(new $maps[$k]["targetEntity"]($v->toArray()));
  				}
  			}
  		}
      $newEntity->fromArray($data);
    }
    return $newEntity;
  }
}