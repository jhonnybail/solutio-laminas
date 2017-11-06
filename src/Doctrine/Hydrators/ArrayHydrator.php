<?php

namespace Solutio\Doctrine\Hydrators;

class ArrayHydrator extends \Doctrine\ORM\Internal\Hydration\ArrayHydrator
{
  protected function hydrateRowData(array $row, array &$result)
  {
    $rows   = count($result);
    $data   = parent::hydrateRowData($row, $result);
    if($rows < count($result)){
      end($result);
      foreach($row as $key => $value){
        if($this->_cache[$key]['isIdentifier'] && isset($this->_cache[$key]['isMetaColumn']) && $this->_cache[$key]['isMetaColumn']){
          $class = $this->_rsm->aliasMap[$this->_cache[$key]['dqlAlias']];
          $metaData = $this->_metadataCache[$class];
          foreach($metaData->associationMappings as $fieldName => $assoc){
            if(isset($assoc['targetToSourceKeyColumns'][$this->_cache[$key]['fieldName']])
              && $assoc['targetToSourceKeyColumns'][$this->_cache[$key]['fieldName']] == $this->_cache[$key]['fieldName']){
                if(isset($this->_rsm->relationMap[$this->_cache[$key]['dqlAlias']]))
                  $nav  =&  $result[key($result)][$this->_rsm->relationMap[$this->_cache[$key]['dqlAlias']]];
                else
                  $nav  =& $result[key($result)];
                unset($nav[$this->_cache[$key]['fieldName']]);
              }
          }
        }
      }
    }
  }
}