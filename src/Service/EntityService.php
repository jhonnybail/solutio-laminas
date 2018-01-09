<?php

namespace Solutio\Service;

use Solutio\EntityInterface;
use Solutio\EntityRepositoryInterface;
use Zend\EventManager\EventManagerInterface;

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
  
  protected function insert(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->insert($entity);
  }
  
  protected function update(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->update($entity);
  }
  
  protected function delete(EntityInterface $entity) : EntityInterface
  {
    return $this->getRepository()->delete($entity);
  }
  
  protected function getById($id)
  {
    return $this->getRepository()->findById($id);
  }

  protected function find(EntityInterface $entity, $filters = [], $params = [], $fields = [], $type = EntityRepositoryInterface::RESULT_ARRAY) : array
  {
    return $this->getRepository()->getCollection($entity, $filters, $params, $fields, $type);
  }
}