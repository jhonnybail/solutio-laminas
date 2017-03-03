<?php

namespace Solutio\Doctrine;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\View\Model\JsonModel;

class RestController extends AbstractRestfulController
{

	protected	$entity;

	protected	$service;

  // Listar - GET
  public function getList()
  {
	
		$em 		= $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
		$repo 		= $em->getRepository($this->entity);
		$entity		= new $this->entity;

		$data		= $repo->getCollection($this->getEntityWithValues(), $this->getParams(), $this->getFields());

		return new JsonModel(array('data'=>$data, 'success'=>true));

  }

  // Retornar o registro especifico - GET
  public function get($id)
  {
		$em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
		$repo = $em->getRepository($this->entity);
		$message = '';
		$data = $repo->find($id);
		if($data){
			$data = $data->toArray();
			$success = true;
		}else{
			$success = false;
			$message = 'Objeto não encontrado.';
		}
		return new JsonModel(array('data'=>$data, 'success'=>$success, 'message'=>$message));
  }

  // Insere registro - POST
  public function create($data)
  {
	
		if($this->service){

			$urlService = $this->getServiceLocator()->get($this->service);

			if($data)
			{
				$url = $urlService->insert($data);
				if($url)
				{
					return new JsonModel(array('data'=>array('id'=>$url->getId(),'success'=>true)));
				}
				else
				{
					return new JsonModel(array('data'=>array('success'=>false)));
				}
			}
			else
				return new JsonModel(array('data'=>array('success'=>false)));

		}

	}

  // alteracao - PUT
  public function update($id, $data)
  {
	
		if($this->service){

			$data['id'] = $id;
			$urlService = $this->getServiceLocator()->get($this->service);

			if($data)
			{
				$url = $urlService->update($data);
				if($url)
				{
					return new JsonModel(array('data'=>array('id'=>$url->getId(),'success'=>true)));
				}
				else
				{
					return new JsonModel(array('data'=>array('success'=>false)));
				}
			}
			else
				return new JsonModel(array('data'=>array('success'=>false)));

		}
	
  }

  // delete - DELETE
  public function delete($id)
  {
	
		if($this->service){

			$urlService = $this->getServiceLocator()->get($this->service);
			$res = $urlService->delete($id);

			if($res)
			{
				return new JsonModel(array('data'=>array('success'=>true)));
			}
			else
				return new JsonModel(array('data'=>array('success'=>false)));

		}
	
  }
	
	protected function getEntityWithValues()
	{
		
		$em		= $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
		$meta	= $em->getMetadataFactory()->getMetadataFor($this->entity);
		$maps 	= $meta->getAssociationMappings();
		$query 	= $this->getRequest()->getQuery();
		$fields	= (new $this->entity)->toArray();
		foreach($fields as $k => $v){
			if(isset($maps[$k])){
				$am = $meta->getAssociationMapping($k);
				if($am['type'] == 1 || $am['type'] == 2){
					$column = key($am['targetToSourceKeyColumns']);
					$fields[$k] = new $maps[$k]["targetEntity"]([
						$column => (int)$query->get($k)
					]);
				}
			}else
				$fields[$k] = $query->get($k);
		}
		return new $this->entity($fields);
		
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