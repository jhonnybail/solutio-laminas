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
	
	public function getEntity()
	{
		return $this->getService()->getEntity();
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
		$data = $this->service->getById($id);
		if($data){
			$return = [
				'data'		=> $data,
				'success'	=> true
			];
		}else{
			$return = [
				'message'	=> 'Objeto não encontrado.',
				'success'	=> false
			];
		}
		return new JsonModel($return);
  }

  // Insere registro - POST
  public function create($data)
  {
  	if($content = $this->getRequest()->getContent()){
  		$data = Json\Decoder::decode($content, Json\Json::TYPE_ARRAY);
  	}
		if($data){
			$entity = $this->getEntity();
			$obj = $this->service->insert(new $entity($data));
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
  	if($content = $this->getRequest()->getContent()){
  		$data = Json\Decoder::decode($content, Json\Json::TYPE_ARRAY);
  	}
		if($data){
			if(!empty($id)){
				$data = new \Solutio\Utils\Data\ArrayObject($data);
				$data = (array) $data->concat(is_array($id) ? $id : ['id' => is_numeric($id) ? $id+0 : $id]);
			}
			$entity = $this->getEntity();
			$entity = new $entity($data);
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
		$data = new \Solutio\Utils\Data\ArrayObject($data);
		$data = (array) $data->concat(is_array($id) ? $id : ['id' => $id]);
		$entity = $this->getEntity();
		$entity = new $entity($data);
		$res = $this->service->delete($entity);
		if($res){
			return new JsonModel(['success' => true]);
		}else
			return new JsonModel(['success' => false]);
  }
  
  protected function getDataEntity()
  {
  	$data = $this->getRequest()->getQuery()->toArray();
  	foreach($data as $k => $v)
  		$data[$k] = json_decode($v, true);
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