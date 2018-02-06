<?php

namespace Solutio\Doctrine;

use Doctrine\ORM,
    Doctrine\DBAL\Query\Expression\ExpressionBuilder,
    Doctrine\DBAL\Query\Expression\CompositeExpression,
    Doctrine\ORM\Tools\Pagination\Paginator,
    Solutio\EntityInterface;

class EntityRepository extends ORM\EntityRepository implements \Solutio\EntityRepositoryInterface
{
  private	$conditions							= [];
  private $disabledefaultFilters	= false;
  private $queryCache             = [];

  protected function save(AbstractEntity $entity)
  {
    $this->getEntityManager()->persist($entity);
    $this->getEntityManager()->flush();
    $this->clearCache();
    return $entity;
  }
  
  public function insert(EntityInterface $entity) : EntityInterface
  {
    return $this->save($entity);
  }
  
  public function update(EntityInterface $entity) : EntityInterface
  {
    $metaData = $this->getClassMetadata();
    $keys     = $entity->getKeys();
    $data     = $entity->getChangedValues();
    foreach($keys as $key => $value)
      unset($data[$key]);
    try{
      if(!$this->getEntityManager()->contains($entity)){
        $findedEntity   = $this->getEntityManager()->getReference(get_class($entity), $keys);
        if(!$findedEntity) throw new \InvalidArgumentException('Content not found.');
        $findedEntity->fromArray($data);
      }else
        $findedEntity = $entity;
    }catch(\Exception $e){
      if(isset($data['id']))
        $findedEntity   = $this->findById($data['id']);
    }
    return $this->save($findedEntity);
  }
  
  public function delete(EntityInterface $entity) : EntityInterface
  {
    $isCacheable  = $this->getEntityManager()->getCache() !== null;
    $metaData     = $this->getClassMetadata();
    $keys         = $entity->getKeys();
    if($isCacheable)
      $this->getEntityManager()->getCache()->evictEntity($this->getClassname(), $keys);
    $data         = $entity->toArray();
    foreach($keys as $key => $value)
      unset($data[$key]);
    try{
      if(!$this->getEntityManager()->contains($entity)){
        $findedEntity   = $this->getEntityManager()->getReference(get_class($entity), $keys);
        if(!$findedEntity) throw new \InvalidArgumentException('Content not found.');
        $findedEntity->fromArray($data);
      }else
        $findedEntity = $entity;
    }catch(\Exception $e){
      if(isset($data['id']))
        $findedEntity   = $this->findById($data['id']);
    }
    $this->getEntityManager()->remove($findedEntity);
    $this->getEntityManager()->flush($findedEntity);
    $this->clearCache();
    return $findedEntity;
  }
  
  public function findById($id) : EntityInterface
  {
    $metaData 	= $this->getClassMetadata();
    if(count($metaData->getIdentifier()) > 1 && !is_array($id)){
      $entities = parent::findById($id);
      if(count($entities) === 1)
        $entity = current($entities);
    }else
      $entity = $this->find($id);
    if(!$entity)
      throw new \InvalidArgumentException('Content not found.');
    return $entity;
  }

  public function getCollection(EntityInterface $entity, array $filters = [], array $params = [], array $fields = [], $type = self::RESULT_ARRAY) : array
  {
    $metaData 	  = $this->getClassMetadata();
    $alias			  = $metaData->getTableName();
    $query			  = $this->createQueryBuilder($alias);
    $maps 			  = $metaData->getAssociationMappings();
    $attrs 			  = $metaData->getFieldNames();
    $obj			    = $entity->toArray();
    $isCacheable  = $this->getEntityManager()->getCache() !== null;
    
    if(count($fields) > 0){
      $f = '';
      foreach($fields as $v){
        $f .= $alias.".{$v}, ";
      }
      $f = substr($f, 0, -2);
      $query->select($f);
    }
    
    if(!$this->disabledefaultFilters){
      
      if(count($filters) <= 0){
        
        foreach($attrs as $field){
          if(array_key_exists($field, $obj) && $obj[$field] !== null && $obj[$field] !== ''){
            if(preg_match("/int/", $metaData->getTypeOfField($field))){
              $query->andWhere($alias.'.'.$field.' = :'.$field)
                  ->setParameter($field, $obj[$field]);		
            }elseif($metaData->getTypeOfField($field) == 'float'){
              if((float)((string)$obj[$field]) > 0)
                  $query->andWhere($alias.'.'.$field.' = :'.$field)
                      ->setParameter($field, ((float)((string)$obj[$field])));
            }elseif($metaData->getTypeOfField($field) == 'string'){
              if(strpos((string) $obj[$field], "|(equals)") !== false)
                $query->orWhere($alias.'.'.$field.' = :'.$field)
                  ->setParameter($field, addslashes((string) str_replace("|(equals)", "", $obj[$field])));
              elseif(strpos((string) $obj[$field], "(equals)") !== false)
                $query->andWhere($alias.'.'.$field.' = :'.$field)
                  ->setParameter($field, addslashes((string) str_replace("(equals)", "", $obj[$field])));
              elseif(strpos((string) $obj[$field], "|") === 0)
                $query->orWhere($alias.'.'.$field.' LIKE :'.$field)
                  ->setParameter($field, '%'.addslashes(str_replace('|', '', (string) $obj[$field])).'%');
              else
                $query->andWhere($alias.'.'.$field.' LIKE :'.$field)
                  ->setParameter($field, '%'.addslashes((string) $obj[$field]).'%');
            }elseif($obj[$field] instanceof \DateTime){
              $query->andWhere($alias.'.'.$field.' LIKE :'.$field)
                    ->setParameter($field, $obj[$field]->format("Y-m-d H:i:s"));
            }elseif($metaData->getTypeOfField($field) == 'boolean'){
              $bool = $obj[$field];
              if((string) $obj[$field] == (string)'0')
                $obj[$field] = 0;
              elseif((string) $obj[$field] == (string)'1')
                $obj[$field] = 1;
              $query->andWhere($alias.'.'.$field.' = :'.$field)
                ->setParameter($field, $obj[$field]);
              $obj[$field] = $bool;
            }
          }
        }
        
        foreach($maps as $fieldName => $field){
          $am         = $metaData->getAssociationMapping($fieldName);
          $className  = $am['targetEntity'];
          $nick       = "{$fieldName}";
          if($am['type'] == 1 || $am['type'] == 2){
            $query->leftJoin("{$alias}.{$fieldName}", $nick);
            if(count($fields) <= 0){
              $query->addSelect($nick);
              $subMetaData  = $this->getEntityManager()->getClassMetadata($className);
              foreach($subMetaData->getIdentifier() as $key){
                try{
                  if($subMetaData->getAssociationMapping($key)){
                    $query->leftJoin("{$nick}.{$key}", "sub".$nick.$key);
                    if(!$isCacheable || $type !== self::RESULT_OBJECT)
                      $query->addSelect("sub".$nick.$key);
                  }
                }catch(\Exception $e){}
              }
            }
          }
          if(isset($obj[$fieldName])){
            if(($am['type'] == 2 || $am['type'] == 1) && $obj[$fieldName] != null){
              $id 	= null;
              if(is_subclass_of($className, AbstractEntity::class)){
                $obj2	= $obj[$fieldName];
                foreach($obj2 as $k => $v){
                  if(is_string($v) || is_numeric($v))
                    $query->andWhere($nick.".".$k." = '".$v."'");
                  elseif($v instanceof \Solutio\Utils\Data\ArrayObject || is_array($v)){
                    $id = $v;
                    while(is_array($id)){
                      $id = current($id);
                    }
                    $query->andWhere($nick.".".$k." = ".$id);
                  }elseif($v instanceof AbstractEntity){
                    $class  = get_class($v);
                    $vIds   = $class::NameOfPrimaryKeys();
                    $va     = $v->toArray();
                    $query->innerJoin("{$nick}.{$k}", $nick."_".$k);
                    foreach($vIds as $vId)
                      if(!empty($va[$vId]) && (is_string($va[$vId]) || is_numeric($va[$vId])))
                        $query->andWhere($nick."_".$k.".".$vId." = '".$va[$vId]."'");
                  }
                }
              }else{
                $obj2	= $obj[$fieldName];
                $column = key($am['targetToSourceKeyColumns']);
                $id = $obj2[$column];
                while(is_array($id)){
                  $id = current($id);
                }
                $query->andWhere($nick.".".$column." = ".$id);
              }
            }elseif(is_string($obj[$fieldName]) || is_numeric($obj[$fieldName])){
              if((int)((string)$obj[$fieldName]) < 0){
                $query->andWhere($alias.'.'.$fieldName.' IS NULL');
              }
            }
          }
        }
      
      }else{
        
        //Get filters by object
        $objectFilter = [];
        foreach($attrs as $field){
          if(array_key_exists($field, $obj) && $obj[$field] !== null && $obj[$field] !== ''){
            if(preg_match("/int/", $metaData->getTypeOfField($field))){
              $objectFilter[] = [
                'field' => $field,
                'value' => $obj[$field]
              ];	
            }elseif($metaData->getTypeOfField($field) == 'float'){
              $objectFilter[] = [
                'field' => $field,
                'value' => ((float)((string)$obj[$field]))
              ];
            }elseif($metaData->getTypeOfField($field) == 'string'){
              if(strpos((string) $obj[$field], "(equals)") !== false)
                $objectFilter[] = [
                  'field'     => $field,
                  'condition' => '!=',
                  'value'     => (string) str_replace("|(equals)", "", $obj[$field])
                ];
              else
                $objectFilter[] = [
                  'field'     => $field,
                  'condition' => '%',
                  'value'     => '%'.addslashes((string) $obj[$field]).'%'
                ];
            }elseif($obj[$field] instanceof \DateTime){
              $objectFilter[] = [
                'field' => $field,
                'value' => $obj[$field]->format("Y-m-d H:i:s")
              ];
            }elseif($metaData->getTypeOfField($field) == 'boolean'){
              $bool = $obj[$field];
              if((string) $obj[$field] == (string)'0')
                $obj[$field] = 0;
              elseif((string) $obj[$field] == (string)'1')
                $obj[$field] = 1;
              $obj[$field] = $bool;
              $objectFilter[] = [
                'field' => $field,
                'value' => $obj[$field]
              ];
            }
          }
        }
        foreach($maps as $fieldName => $field){ 
          if(isset($obj[$fieldName])){
          $am = $metaData->getAssociationMapping($fieldName);
            if(($am['type'] == 2 || $am['type'] == 1) && $obj[$fieldName] != null){
              $id 	= null;
              if($obj[$fieldName] instanceof \Solutio\AbstractEntity){
                $obj2	= $obj[$fieldName]->toArray();
                $vIds = get_class($obj[$fieldName])::NameOfPrimaryKeys();
                foreach($vIds as $k => $v){
                  if(is_string($obj2[$v]) || is_numeric($obj2[$v]))
                    $objectFilter[] = [
                      'field' => $fieldName,
                      'value' => $obj2[$v]
                    ];
                }
              }else{
                $obj2	= $obj[$fieldName];
                $column = key($am['targetToSourceKeyColumns']);
                $id = $obj2[$column];
                while(is_array($id)){
                  $id = current($id);
                }
                $objectFilter[] = [
                  'field' => $fieldName,
                  'value' => $id
                ];
              }
            }elseif(is_string($obj[$fieldName]) || is_numeric($obj[$fieldName])){
              if((int)((string)$obj[$fieldName]) < 0){
                $objectFilter[] = [
                  'field'     => $fieldName,
                  'condition' => 'isn'
                ];
              }
            }
          }
        }
        if(count($objectFilter) > 0)
          $filters = [$objectFilter, $filters];
        //
        
        $listValues = [];
        
        if(!function_exists(__NAMESPACE__ . '\getCondition')){
          function getCondition($query, $field, $value, $condition){
            
            if($condition === '='){
              $exp = $query->expr()->eq($field, $value);
            }elseif($condition === '!='){
              $exp = $query->expr()->neq($field, $value);
            }elseif($condition === '<'){
              $exp = $query->expr()->lt($field, $value);
            }elseif($condition === '<='){
              $exp = $query->expr()->lte($field, $value);
            }elseif($condition === '>'){
              $exp = $query->expr()->gt($field, $value);
            }elseif($condition === '>='){
              $exp = $query->expr()->gte($field, $value);
            }elseif($condition === 'isn'){
              $exp = $query->expr()->isNull($field);
              $fieldName = null;
            }elseif($condition === 'isnn'){
              $exp = $query->expr()->isNotNull($field);
              $fieldName = null;
            }elseif($condition === '%'){
              $exp = $query->expr()->like($field, $value);
            }elseif($condition === '!%'){
              $exp = $query->expr()->notLike($field, $value);
            }elseif($condition === 'in'){
              $exp = $query->expr()->in($field, $value);
            }elseif($condition === 'nin'){
              $exp = $query->expr()->notIn($field, $value);
            }else
              throw new \Solutio\InvalidArgumentException("Expression '{$condition}' don`t exists.");
            
            return $exp;
            
          }
        }
        
        if(!function_exists(__NAMESPACE__ . '\makeExpression')){
          function fieldExists($em, $metaData, $field){
            $divs = explode(".", $field);
            if(count($divs) > 1){
              $metaDataField  = $metaData->getAssociationMappings()[$divs[0]];
              $field          = str_replace($divs[0] . '.', '', $field);
              return fieldExists($em, $em->getClassMetadata($metaDataField['targetEntity']), $field);
            }
            return isset($metaData->getReflectionProperties()[$field]);
          }
          function makeExpression($em, $metaData, $query, &$listValues, $filters, $or = false){
            
            if($or === true || $or === 'true' || $or == 1)
              $type = CompositeExpression::TYPE_OR;
            else
              $type = CompositeExpression::TYPE_AND;
            
            $expr = new CompositeExpression($type);
            
            foreach($filters as $index => $filter){
              
              $expression = null;
              $value      = null;
              
              if(is_array($filter) || $filter instanceof \Traversable){
                if(isset($filter['field']))
                  $field      = $filter['field'];
                if(isset($filter['condition']))
                  $condition  = $filter['condition'];
                if(isset($filter['value']))
                  $value      = $filter['value'];
                  
              }else{
                $field  = $index;
                $value  = $filter;
              }
              if(empty($condition))
                $condition = '=';
                
              if(empty($field) && empty($value) && count($filter) > 0){
                $childOr = false;
                if(isset($filter['or'])){
                  $childOr = $filter['or'];
                  unset($filter['or']);
                }
                $expression = makeExpression($em, $metaData, $query, $listValues, $filter, $childOr);
              }elseif(!empty($field) && fieldExists($em, $metaData, $field)){
                if((is_array($value) || $value instanceof \Traversable) && !isset($value[0])){
                  foreach($value as $subField => $subValue){
                    $fieldName = str_replace(".", "", $field.$subField . rand());
                    $expression = getCondition($query, $field.".".$subField, ':'.$fieldName, $condition);
                    if($subValue || $subValue === false)
                      $listValues[$fieldName] = $subValue;
                    if(!empty($expression))
                      $expr->add($expression);
                  }
                  $expression = null;
                }elseif($value || $value === false || $value === 0){
                  $fieldName = str_replace(".", "", $field . rand());
                  $expression = getCondition($query, (!preg_match('/\./', $field) ? $query->getRootAliases()[0]."." : "").$field, ':'.$fieldName, $condition);
                  $listValues[$fieldName] = $value;
                }
              }
              
              if(!empty($expression))
                $expr->add($expression);
              
            }
            
            if($expr->count() > 0)
              return $expr;
              
            return null;
            
          }
          
        }
          
        $orFilter = false;
        if(isset($filters['or'])){
          $orFilter = $filters['or'];
          unset($filters['or']);
        }
        $expression = makeExpression($this->getEntityManager(), $metaData, $query, $listValues, $filters, $orFilter);
        if($expression){
          $query->where((string) $expression)
                  ->setParameters($listValues);
        }
        
        foreach($maps as $fieldName => $field){
          $am         = $metaData->getAssociationMapping($fieldName);
          $className  = $am['targetEntity'];
          $nick       = $fieldName;
          if($am['type'] == 1 || $am['type'] == 2){
            $query->leftJoin("{$alias}.{$fieldName}", $nick);
            if(count($fields) <= 0){
              $query->addSelect($nick);
              $subMetaData  = $this->getEntityManager()->getClassMetadata($className);
              foreach($subMetaData->getIdentifier() as $key){
                try{
                  if($subMetaData->getAssociationMapping($key)){
                    $query->leftJoin("{$nick}.{$key}", "sub".$nick.$key);
                    if(!$isCacheable || $type !== self::RESULT_OBJECT)
                      $query->addSelect("sub".$nick.$key);
                  }
                }catch(\Exception $e){}
              }
            }
          }
        }
      
      }
      
    }
    
    if(!empty($this->conditions)){
      $query = $this->conditions[0]($alias, $query);
    }
    
    if(isset($params['order'])){
      $order	= $params['order'];
      if(is_array($order))
        foreach($order as $field => $dir)
          if(!empty($dir)){
            $fieldName  = preg_match("/\./", $field) ? $field : $alias . "." . $field;
            $query      = $query->addOrderBy($fieldName, $dir);
          }
    }
    
    if(isset($params['limit']) && $params['limit'] > 0){
      $limit	= $params['limit'];
      $query = $query->setMaxResults($limit);
    }
    
    if(isset($params['offset'])){
      $offset	= $params['offset'];
      $query = $query->setFirstResult($offset);
    }
    
    $query        = $query->getQuery();
    
    //Config Query Cache
    $query->setCacheable(false)
            ->useResultCache(false);
    if($isCacheable){
      $cacheRegion  = $this->getEntityManager()->getCache()->getEntityCacheRegion($this->getClassname());
      if($cacheRegion){
        $ttl          = $this->getEntityManager()->getConfiguration()->getSecondLevelCacheConfiguration()->getRegionsConfiguration()->getLifetime($cacheRegion->getName());
        $adapter      = $cacheRegion->getCache();
        $arrayCacheId = [
          'sql'     => $query->getSQL(),
          'obj'     => $obj,
          'filters' => $filters
        ];
        $cacheId      = md5(json_encode($arrayCacheId));
        $this->queryCache[$this->getClassname()] = [];
        if($adapter->contains($this->getClassname()))
            $this->queryCache[$this->getClassname()] = $adapter->fetch($this->getClassname());
        if(!isset($this->queryCache[$this->getClassname()][$cacheId])){
          $this->queryCache[$this->getClassname()][$cacheId] = [
            'sequence'  => 1
          ];
          $adapter->save($this->getClassname(), $this->queryCache[$this->getClassname()]);
        }
        $sequenceCacheId = $this->queryCache[$this->getClassname()][$cacheId]['sequence'];
      }else
        $isCacheable = false;
    }
    //
    
    if(count($fields) <= 0){
      if($isCacheable && $cacheId 
          && isset($this->queryCache[$this->getClassname()]) 
          && isset($this->queryCache[$this->getClassname()][$cacheId]['total' . $sequenceCacheId])){
        $rs = [
          'total' => $this->queryCache[$this->getClassname()][$cacheId]['total' . $sequenceCacheId]
        ];
      }else{
        $rs = [
          'total' => count(new Paginator($query))
        ];
        if($isCacheable){
          $this->queryCache[$this->getClassname()][$cacheId]['total' . $sequenceCacheId] = $rs['total'];
          $adapter->save($this->getClassname(), $this->queryCache[$this->getClassname()]);
        }
      }
    }
    
    if($type === self::RESULT_OBJECT){
      if($isCacheable)
        $query->setCacheable(true)
                ->setLifetime($ttl);
      $rs['result'] = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
    }elseif($type === self::RESULT_ARRAY){
      if($isCacheable)
        $query->useResultCache(true, $ttl, $cacheId . $sequenceCacheId);
      $rs['result'] = $query->getResult('SolutioArrayHydrator');
    }
    
    return $rs;
  }
  
  protected function setFilter($conditions, $disableDefaultFilters = false)
  {
    $this->conditions[0]					= $conditions;
    $this->disableDefaultFilters	= $disableDefaultFilters;
  }
  
  protected function executeFilter($alias, $query)
  {
    return $this->conditions[0]($alias, $query);
  }
  
  public function clearCache()
  {
    $isCacheable  = $this->getEntityManager()->getCache() !== null;
    if($isCacheable){
      $cacheRegion  = $this->getEntityManager()->getCache()->getEntityCacheRegion($this->getClassname());
      if($cacheRegion){
        $ttl          = $this->getEntityManager()->getConfiguration()->getSecondLevelCacheConfiguration()->getRegionsConfiguration()->getLifetime($cacheRegion->getName());
        if(method_exists($cacheRegion, 'getCache'))
          $adapter = $cacheRegion->getCache();
        else
          $adapter = $this->getEntityManager()->getConfiguration()->getSecondLevelCacheConfiguration()->getCacheFactory()->getRegion([])->getCache();
        if($adapter->contains($this->getClassname())){
          $this->queryCache[$this->getClassname()] = $adapter->fetch($this->getClassname());
          foreach($this->queryCache[$this->getClassname()] as $k => $v)
            $this->queryCache[$this->getClassname()][$k]['sequence']++;
          $adapter->save($this->getClassname(), $this->queryCache[$this->getClassname()]);
          $metaData 	  = $this->getClassMetadata();
          $maps 			  = $metaData->getAssociationMappings();
          foreach($maps as $assoc){
            $className  = $assoc['targetEntity'];
            $cacheRegion  = $this->getEntityManager()->getCache()->getEntityCacheRegion($className);
            if(method_exists($cacheRegion, 'getCache'))
              $adapter = $cacheRegion->getCache();
            else
              $adapter = $this->getEntityManager()->getConfiguration()->getSecondLevelCacheConfiguration()->getCacheFactory()->getRegion([])->getCache();
            if($adapter->contains($className)){
              $this->queryCache[$className] = $adapter->fetch($className);
              foreach($this->queryCache[$className] as $k => $v)
                $this->queryCache[$className][$k]['sequence']++;
              $adapter->save($className, $this->queryCache[$className]);
            }
          }
        }
      }else
        $isCacheable = false;
    }
  }
}