<?php

namespace Solutio;

use Doctrine\ORM\QueryBuilder;

interface EntityRepositoryInterface
{
  const RESULT_OBJECT = 1;
  const RESULT_ARRAY	= 2;
  
  public function getClassName();

  public function insert(EntityInterface $entity) : EntityInterface;
  
  public function update(EntityInterface $entity) : EntityInterface;
  
  public function delete(EntityInterface $entity) : EntityInterface;
  
  public function findById($id) : EntityInterface;

  public function getCollection(EntityInterface $entity, array $filters = [], array $params = [], array $fields = [], $type = self::RESULT_ARRAY) : array;

  public function createQueryBuilder($alias, $indexBy = null);
}