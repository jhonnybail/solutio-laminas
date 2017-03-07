<?php

namespace Solutio\Controller;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\View\Model\JsonModel;

class RestController extends AbstractRestfulController
{

	protected	$service;
	
	public function __construct(EntityService $service)
	{
		$this->service = $service;
	}

  // Listar - GET
  public function getList()
  {
		$data		= $this->service($this->getQuery()->toArray(), $this->getParams(), $this->getFields());
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
			$data = $data->toArray();
			$success = true;
		}else{
			$success = false;
			$message = 'Objeto não encontrado.';
		}
		return new JsonModel([
			'data'		=> $data, 
			'success'	=> $success, 
			'message'	=> $message
		]);
  }

  // Insere registro - POST
  public function create($data)
  {
		if($data){
			$obj = $this->service->insert($data);
			if($url)				{
				return new JsonModel([
					'data'		=> $obj->toArray(),
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
		if($data && $id){
			$obj = $this->service->update($data);
			if($url){
				return new JsonModel([
					'data'		=> $obj->toArray(),
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
		$res = $this->service->delete($id);
		if($res){
			return new JsonModel(['success' => true]);
		}else
			return new JsonModel(['success' => false]);
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