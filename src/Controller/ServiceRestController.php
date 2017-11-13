<?php

namespace Solutio\Controller;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\View\Model\JsonModel,
    Zend\Json,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Service\EntityService,
    Solutio\AbstractEntity;

class ServiceRestController extends AbstractRestfulController
{
  private	$service;

  public function __construct(EntityService $service)
  {
    $this->service = $service;
  }

  public function getService() : EntityService
  {
    return $this->service;
  }

  public function getEntity(array $data = []) : AbstractEntity
  {
    $className  = $this->getService()->getClassName();
    return new $className($this->getDataEntity($data));
  }

  // Listar - GET
  public function getList()
  {
    $filters  = $this->getRequest()->getQuery()->get('filters') ? Json\Decoder::decode($this->getRequest()->getQuery()->get('filters'), Json\Json::TYPE_ARRAY) : [];
    $data		  = $this->service->find($this->getEntity(), $filters, $this->getParams(), $this->getFields());
    return new JsonModel([
      'data'		=> $data,
      'success'	=> true
    ]);
  }

  // Retornar o registro especifico - GET
  public function get($id)
  {
    $data = $this->getDataEntity();
    $data = $this->service->getById(count($data) === 1 ? $id : $this->getEntity()->getKeys());
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
    $values   = new \Solutio\Utils\Data\ArrayObject((array) $this->getDataEntity());
    $content  = $this->getRequest()->getContent();
    if(!empty($content)){
      $json = Json\Decoder::decode($content, Json\Json::TYPE_ARRAY);
      $data = new \Solutio\Utils\Data\ArrayObject($json ? $json : []);
    }elseif(is_array($data) && count($data) > 0){
      $data = new \Solutio\Utils\Data\ArrayObject((array) $data);
    }
    $data   = (array) $data->concat($values);
    $obj = $this->service->insert($this->getEntity($data));
    if($obj)				{
      return new JsonModel([
        'data'		=> $obj,
        'success'	=> true
      ]);
    }else
      return new JsonModel(['success' => false]);
  }

  // alteracao - PUT
  public function update($id, $data)
  {
    $values   = new \Solutio\Utils\Data\ArrayObject((array) $this->getDataEntity());
    $content  = $this->getRequest()->getContent();
    if(!empty($content)){
      $json = Json\Decoder::decode($content, Json\Json::TYPE_ARRAY);
      $data = new \Solutio\Utils\Data\ArrayObject($json ? $json : []);
    }elseif(is_array($data) && count($data) > 0){
      $data = new \Solutio\Utils\Data\ArrayObject((array) $data);
    }
    $data = (array) $data->concat($values);
    $obj  = $this->service->update($this->getEntity($data));
    if($obj){
      return new JsonModel([
        'data'		=> $obj,
        'success'	=> true
      ]);
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

  protected function getDataEntity(array $inheritData = [])
  {
    $data = [];
    $paramsRoute  = $this->params()->fromRoute();
    foreach($paramsRoute as $k => $v)
      if(!StringManipulator::GetInstance($k)->search('param') && !StringManipulator::GetInstance($k)->search('value')
          && $k !== 'controller')
        $data[$k] = $v;
    for($i = 1; $i <= 3; $i++){
      if(isset($paramsRoute['param'.$i]) && isset($paramsRoute['value'.$i])){
        $data[$paramsRoute['param'.$i]] = $paramsRoute['value'.$i];
      }
    }
    $data = array_merge($data, $inheritData);
    $data = array_merge($this->getRequest()->getQuery()->toArray(), $data);
    
    //Verify key id
    $className  = $this->getService()->getClassName();
    $keys       = $className::NameOfPrimaryKeys();
    if(!in_array('id', $keys)){
      $keyFilled  = false;
      $keyEmpty   = '';
      foreach($keys as $key){
        if(!empty($data[$key])) $keyFilled = true;
        else{
          $keyEmpty = $key;
        }
      }
      if($keyFilled){
        if(!empty($keyEmpty))
          $data[$keyEmpty]  = $data['id'];
        unset($data['id']);
      }
    }
    //
    
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