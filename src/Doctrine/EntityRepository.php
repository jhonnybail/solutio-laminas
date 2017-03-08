<?php

namespace Solutio\Doctrine;

use Doctrine\ORM,
		Doctrine\ORM\Tools\Pagination\Paginator;

class EntityRepository extends ORM\EntityRepository 
{
	
	const		RESULT_OBJECT						= 1;
	const 	RESULT_ARRAY						= 2;
	
	private	$conditions							= [];
	private $disabledefaultFilters	= false;

	public function save(\Solutio\AbstractEntity $entity)
	{
		$this->getEntityManager()->persist($entity);
		$this->getEntityManager()->flush();
		return $entity;
	}

	public function getCollection(\Solutio\AbstractEntity $entity, $params = [], $fields = [], $type = self::RESULT_ARRAY)
	{
		
		$metaData 	= $this->getClassMetadata();
		$alias			= $metaData->getTableName();
		$query			= $this->createQueryBuilder($alias);
		$maps 			= $metaData->getAssociationMappings();
		$attrs 			= $metaData->getFieldNames();
		$obj			  = $entity->toArray();
		
		if(count($fields) > 0){
			$f = '';
			foreach($fields as $v){
				$f .= $alias.".{$v}, ";
			}
			$f = substr($f, 0, -2);
			$query->select($f);
		}
		
		if(!$this->disabledefaultFilters){
		
			foreach($attrs as $field){
				if($obj[$field] != null && $obj[$field] != ''){
					if($metaData->getTypeOfField($field) == 'integer'){
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
				$am = $metaData->getAssociationMapping($fieldName);
				if($am['type'] == 1 || $am['type'] == 2){
					$query->leftJoin("{$alias}.{$fieldName}", $fieldName);
					if(count($fields) <= 0)
						$query->addSelect($fieldName);
					if($am['type'] == 4){
						foreach($metaData->getIdentifier() as $order => $identifier){
							$query->addGroupBy("{$alias}.id");
						}
					}
				}
				if(($am['type'] == 2 || $am['type'] == 1) && $obj[$fieldName] != null){
					$id 	= null;
					$column = key($am['targetToSourceKeyColumns']);
					if($obj[$fieldName] instanceof \Solutio\AbstractEntity)
						$obj2	= $obj[$fieldName]->toArray();
					else
						$obj2	= $obj[$fieldName];
					if(!empty($obj2[$column])){
						$id = $obj2[$column];
						$query->andWhere($fieldName.".".$column." = ".$id);
					}
				}elseif(is_string($obj[$fieldName]) || is_numeric($obj[$fieldName])){
					if((int)((string)$obj[$fieldName]) < 0){
						$query->andWhere($alias.'.'.$fieldName.' IS NULL');
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
				if(current($order))
					$query = $query->orderBy($alias.".".key($order), current($order));
		}
		
		if(isset($params['limit'])){
			$limit	= $params['limit'];
			$query = $query->setMaxResults($limit);
		}
		
		if(isset($params['offset'])){
			$offset	= $params['offset'];
			$query = $query->setFirstResult($offset);
		}
					
		$rs = [
			'total' => count(new Paginator($query))
		];
		
		if($type === self::RESULT_OBJECT){
			$rs = $query->getQuery()->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
		}elseif($type === self::RESULT_ARRAY){
			$rs['result'] = $query->getQuery()->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
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
	
}