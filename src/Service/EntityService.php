<?php

namespace Solutio\Service;

use Solutio\EntityInterface;
use Solutio\EntityRepositoryInterface;
use Laminas\EventManager\EventManagerInterface;

class EntityService extends AbstractService
{
  protected $repository;
  
  public function __construct(EntityRepositoryInterface $repository)
  {
    $this->repository = $repository;
  }
  
  public function getRepository() : EntityRepositoryInterface
  {
    return $this->repository;
  }
  
  public function getClassName()
  {
    return $this->getRepository()->getClassName();
  }
  
  public function insert(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->insert($entity);
  }
  
  public function update(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->update($entity);
  }
  
  public function delete(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->delete($entity);
  }
  
  public function getById($id)
  {
    return $this->getRepository()->findById($id);
  }

  public function find(EntityInterface $entity, $filters = [], $params = [], $fields = [], $type = EntityRepositoryInterface::RESULT_ARRAY) : array
  {
    return $this->getRepository()->getCollection($entity, $filters, $params, $fields, $type);
  }
}