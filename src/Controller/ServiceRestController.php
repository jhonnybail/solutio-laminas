<?php

namespace Solutio\Controller;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\View\Model\JsonModel,
    Zend\Json,
    Zend\Stdlib\RequestInterface as Request,
    Zend\Stdlib\ResponseInterface as Response,
    Solutio\Utils\Data\StringManipulator,
    Solutio\Service\EntityService,
    Solutio\EntityInterface;

class ServiceRestController extends AbstractRestfulController
  implements CacheControllerInterface
{
  use CacheControllerTrait;
  
  private	  $service;
  protected $allowedCollectionMethods = ['*'];

  public function __construct(EntityService $service)
  {
    $this->service = $service;
  }

  public function getService() : EntityService
  {
    return $this->service;
  }

  public function getEntity(array $data = []) : EntityInterface
  {
    $className  = $this->getService()->getClassName();
    return new $className($this->getDataEntity($data));
  }
  
  public function setAllowedCollectionMethods($methods = [])
  {
    $alloweds = [];
    foreach($methods as $method)
      $alloweds[] = strtoupper($method);
    $this->allowedCollectionMethods = $alloweds;
  }
  
  public function getAllowedCollectionMethods()
  {
    return $this->allowedCollectionMethods;
  }

  public function dispatch(Request $request, Response $response = null)
  {
    if(!in_array(strtoupper($request->getMethod()), $this->allowedCollectionMethods) && !in_array('*', $this->allowedCollectionMethods))
      throw new NotFoundException('Service not found');

    return parent::dispatch($request, $response);
  }

  // Listar - GET
  public function getList()
  {
    $filters  = $this->getFilters();
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
    $data = (array) $values->concat($data);
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
    $values   = new \Solutio\Utils\Data\ArrayObject((array) $this->getDataEntity());
    $content  = $this->getRequest()->getContent();
    if(!empty($content)){
      $json = Json\Decoder::decode($content, Json\Json::TYPE_ARRAY);
      $data = new \Solutio\Utils\Data\ArrayObject($json ? $json : []);
      $data = (array) $values->concat($data);
    }else
      $data = (array) $values;
    $res  = $this->service->delete($this->getEntity($data));
    if($res){
      return new JsonModel(['success' => true]);
    }else
      return new JsonModel(['success' => false]);
    return new JsonModel(['success' => false]);
  }
  
  public function options()
  {
    if(in_array('*', $this->allowedCollectionMethods))
      $this->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Methods", "*");
    elseif(count($this->allowedCollectionMethods) > 0){
      $methods = '';
      foreach($this->allowedCollectionMethods as $v)
        $methods .= $v.', ';
      $methods = substr($methods, 0, -2);
      $this->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Methods", "{$methods}");
    }
    return $this->getResponse();
  }

  protected function getDataEntity(array $inheritData = []) : array
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
      if($keyFilled && !empty($data['id'])){
        if(!empty($keyEmpty))
          $data[$keyEmpty]  = $data['id'];
        unset($data['id']);
      }
    }
    //
    
    foreach($data as $k => $v){
      if(!empty($v)){
        if(is_string($v))
          $data[$k] = json_decode($v, true);
        if(empty($data[$k]))
          $data[$k] = $v;
      }
    }
    return $data;
  }

  protected function getFilters() : array
  {
    return $this->getRequest()->getQuery()->get('filters') ? Json\Decoder::decode($this->getRequest()->getQuery()->get('filters'), Json\Json::TYPE_ARRAY) : [];
  }

  protected function getParams() : array
  {
    $get		= $this->getRequest()->getQuery();
    return [
      'limit'		=> $get->get('limit'),
      'offset'	=> $get->get('offset'),
      'order'		=> json_decode($get->get('order'), true)
    ];
  }

  protected function getFields() : array
  {
    $get		= $this->getRequest()->getQuery();
    $fields		= [];
    if($get->get('fields'))
      $fields		= explode(',', $get->get('fields'));
    return $fields;
  }
}
