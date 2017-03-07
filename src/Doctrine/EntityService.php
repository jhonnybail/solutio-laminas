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
      $this->entity = $entity;
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
      $repo = $this->em->getRepository($this->entity);
      return $repo->find($id);
    }
    
    public function find($values, $params, $fields, $type = EntityRepository::RESULT_ARRAY)
    {
      $repo 	= $this->em->getRepository($this->entity);
		  $entity	= new $this->entity;

		  return $repo->getCollection($this->makeEntityWithParams($values), $params, $fields, $type);
    }
    
    public function insert(array $data)
    {
      $entity = new $this->entity($data);

      $this->em->persist($entity);
      $this->em->flush();
      return $entity;
    }

    public function update(array $data)
    {
      $entity = $this->em->getReference($this->entity, $data['id']);
      (new Hydrator\ClassMethods())->hydrate($data, $entity);

      $this->em->persist($entity);
      $this->em->flush();
      return $entity;
    }

    public function delete($identifiers)
    {
      $entity = $this->em->getReference($this->entity, $identifiers);
      if($entity)
      {
        $this->em->remove($entity);
        $this->em->flush();
        return $entity;
      }
    }
    
    protected function makeEntityWithParams($values)
    {
      $meta	= $this->em->getMetadataFactory()->getMetadataFor($this->entity);
  		$maps 	= $meta->getAssociationMappings();
  		$fields	= (new $this->entity)->toArray();
  		foreach($fields as $k => $v){
  			if(isset($maps[$k]) && isset($values[$k])){
  				$am = $meta->getAssociationMapping($k);
  				if($am['type'] == 1 || $am['type'] == 2){
  					$column = key($am['targetToSourceKeyColumns']);
  					$fields[$k] = new $maps[$k]["targetEntity"]([
  						$column => (int)$values[$k]
  					]);
  				}
  			}elseif(isset($values[$k]))
  				$fields[$k] = $values[$k];
  		}
  		return new $this->entity($fields);
    }
}