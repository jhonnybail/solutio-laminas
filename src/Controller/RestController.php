<?php

namespace Solutio\Controller;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\View\Model\JsonModel,
    Zend\Json;

class RestController extends AbstractRestfulController
{
  private	$service;

  public function __construct(\Solutio\Doctrine\EntityService $service)
  {
    $this->service = $service;
  }

  public function getService()
  {
    return $this->service;
  }

  public function getEntity($data = null)
  {
    return $this->getService()->getEntity($data);
  }

  // Listar - GET
  public function getList()
  {
    $entity = $this->getEntity();
    $data		= $this->service->find(new $entity($this->getDataEntity()), $this->getParams(), $this->getFields());
    return new JsonModel([
      'data'		=> $data,
      'success'	=> true
    ]);
  }

  // Retornar o registro especifico - GET
  public function get($id)
  {
    $values = $this->getDataEntity();
    if(count($values) > 0){
      $ids  = $this->getEntity()::NameOfPrimaryKeys();
      foreach($ids as $key)
        if(empty($values[$key])) $values[$key] = $id;
      $id = $values;
    }
    $data = $this->service->getById($id);
    if($data){
      $return = [
        'data'		=> $data,
        'success'	=> true
      ];
    }else
      throw new \Solutio\NotFoundException('Content not found.');
    
    return new JsonModel($return);
  }

  // Insere registro - POST
  public function create($data)
  {
    $values = new \Solutio\Utils\Data\ArrayObject((array) $this->getDataEntity());
    if(is_array($data) && count($data) > 0){
      $data = new \Solutio\Utils\Data\ArrayObject((array) $data);
    }else{
      $data = new \Solutio\Utils\Data\ArrayObject(Json\Decoder::decode($this->getRequest()->getContent(), Json\Json::TYPE_ARRAY));
    }
    $data   = $data->concat($values);
    if($data->length() > 0){
      $entity = $this->getEntity((array) $data);
      $obj = $this->service->insert(new $entity((array) $data));
      if($obj)				{
        return new JsonModel([
          'data'		=> $obj,
          'success'	=> true
        ]);
      }else{
        return new JsonModel(['success' => false]);
      }
    }else
      return new JsonModel(['success' => false]);
  }

  // alteracao - PUT
  public function update($id, $data)
  {
    $values = new \Solutio\Utils\Data\ArrayObject((array) $this->getDataEntity());
    $data = new \Solutio\Utils\Data\ArrayObject(Json\Decoder::decode($this->getRequest()->getContent(), Json\Json::TYPE_ARRAY));
    $ids    = $this->getEntity()::NameOfPrimaryKeys();
    if(count($ids) > 1){
      $idsNotNull = 0;
      foreach($ids as $key)
        if(empty($values[$key])) $values[$key] = $id;
        else $idsNotNull++;
      if($idsNotNull < count($ids)-1)
        $values = ['id' => $id];
    }else
      $values = [$ids[0] => $id];
    $data   = $data->concat($values);
    if($data->length() > 0){
      $entity = $this->getEntity();
      $entity = new $entity((array) $data);
      $obj = $this->service->update($entity);
      if($obj){
        return new JsonModel([
          'data'		=> $obj,
          'success'	=> true
        ]);
      }else{
        return new JsonModel(['success' => false]);
      }
    }else
      return new JsonModel(['success' => false]);
  }

  // delete - DELETE
  public function delete($id)
  {
    $values = new \Solutio\Utils\Data\ArrayObject((array) $this->getDataEntity());
    $data   = new \Solutio\Utils\Data\ArrayObject(
      $this->getRequest()->getContent() ? Json\Decoder::decode($this->getRequest()->getContent(), Json\Json::TYPE_ARRAY) : []);
    $ids    = $this->getEntity()::NameOfPrimaryKeys();
    if(count($ids) > 1){
      $idsNotNull = 0;
      foreach($ids as $key)
        if(empty($values[$key])) $values[$key] = $id;
        else $idsNotNull++;
      if($idsNotNull < count($ids)-1)
        $values = ['id' => $id];
    }else
      $values = [$ids[0] => $id];
    $data   = $data->concat($values);
    if($data->length() > 0){
      $entity = $this->getEntity();
      $entity = new $entity((array) $data);
      $res = $this->service->delete($entity);
      if($res){
        return new JsonModel(['success' => true]);
      }else
        return new JsonModel(['success' => false]);
    }
    return new JsonModel(['success' => false]);
  }

  protected function getDataEntity(array $data = [])
  {
    $data = array_merge($this->getRequest()->getQuery()->toArray(), $data);
    foreach($data as $k => $v){
      if(!empty($v)){
        $data[$k] = json_decode($v, true);
        if(empty($data[$k]))
          $data[$k] = $v;
      }
    }
    return $data;
  }

  protected function getParams()
  {
    $get		= $this->getRequest()->getQuery();
    return [
      'limit'		=> $get->get('limit'),
      'offset'	=> $get->get('offset'),
      'order'		=> json_decode($get->get('order'), true)
    ];
  }

  protected function getFields()
  {
    $get		= $this->getRequest()->getQuery();
    $fields		= [];
    if($get->get('fields'))
      $fields		= explode(',', $get->get('fields'));
    return $fields;
  }
}