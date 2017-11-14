<?php

namespace Solutio;

use Zend\Mvc\MvcEvent,
    Zend\View\Model\JsonModel,
    Solutio\Utils\Data\ArrayObject;

class Module
{
  const VERSION = '2.0.2';
  
  public function onBootstrap(MvcEvent $e)
  {
    $sys                = new ArrayObject;
    $sys['directory']		= $_SERVER['DOCUMENT_ROOT'];
    if($sys['directory'][strlen($sys['directory'])-1] != "/")
      $sys['directory'] .= '/';
    $protocol           = explode('/', $_SERVER['SERVER_PROTOCOL']);
    $sys['protocol']    = strtolower($protocol[0]);
    $sys['url']         = $sys['protocol']."://".$_SERVER['HTTP_HOST']."/";

    System::SetSystem((array) $sys);
    
    $this->applyCors($e);
    $e->getTarget()->getEventManager()->attach('dispatch.error', [$this, 'onDispatchError'], 0);
    
    //Register Listeners Aggregate
    $serviceListeners = $e->getTarget()->getConfig()['service_listener'];
    foreach($serviceListeners as $serviceListener => $invokable){
      (new $invokable)($e->getTarget()->getServiceManager(), $serviceListener);
    }
    //
  }

  public function getConfig()
  {
    return include __DIR__ . '/../config/module.config.php';
  }
  
  public function onDispatchError($e) 
  { 
    $error = $e->getError(); 
    if (!$error || !($e->getTarget() instanceof \Solutio\Controller\ServiceRestController)) { 
      // No error? nothing to do. 
      return; 
    } 

    $request = $e->getRequest(); 
    $headers = $request->getHeaders(); 
    if (!$headers->has('Accept')) { 
      // nothing to do; can't determine what we can accept 
      return; 
    } 

    $accept = $headers->get('Accept'); 
    if (!$accept->match('application/json')) { 
      // nothing to do; does not match JSON 
      return; 
    } 

    $exception  = $e->getParam('exception');
    
    $data       = ['success' => false];
    if(!empty($exception)){
      $data['message'] = $exception->getMessage();
      $e->getResponse()->setStatusCode(\Zend\Http\PhpEnvironment\Response::STATUS_CODE_400);
      if($exception instanceof \Zend\Json\Exception\RuntimeException){
        $data['message'] = 'Invalid Request JSON';
      }elseif($exception instanceof System\User\AuthException)
        $e->getResponse()->setStatusCode(
          \Zend\Http\PhpEnvironment\Response::STATUS_CODE_401,
          'Unauthorized'
        );
      elseif($exception instanceof \InvalidArgumentException)
        $e->getResponse()->setStatusCode(
          \Zend\Http\PhpEnvironment\Response::STATUS_CODE_400,
          'Bad Request'
        );
      elseif($exception instanceof \Solutio\NotFoundException)
        $e->getResponse()->setStatusCode(
          \Zend\Http\PhpEnvironment\Response::STATUS_CODE_404,
          'Not Found'
        );
    }elseif(!empty($e->getParam('controller-class')))
      $data['message']  = $e->getParam('controller-class');
    elseif($error === 'error-router-no-match')
      $data['message']  = 'Route don\'t exists.';
      
    $model = new JsonModel($data); 
    // inject as needed with error/exception information. 
    // maybe set HTTP response codes based on type of error. 
    // etc. 
    
    $e->setViewModel($model); 
    $e->stopPropagation(); 
    return $model; 
  }
  
  protected function applyCors(MvcEvent $e)
  {
    $cors = $e->getApplication()->getServiceManager()->get('config')['solutio']['cors'];
    
    //Allow-Origin
    $origin = System::GetVariable('HTTP_HOST');
    if(in_array('*', $cors['origin']) || in_array($origin, $cors['origin']))
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Origin", $origin);
      
    //Allow-Methods
    $methods = '';
    foreach($cors['methods'] as $method) $methods .= $method . ', ';
    $methods = substr($methods, 0, -2);
    $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Methods", $methods);
    
    //Allow-Headers
    $headers = '';
    foreach($cors['headers.allow'] as $header) $headers .= $header . ', ';
    $headers = substr($headers, 0, -2);
    $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Headers", $headers);
    
    if($cors['credentials'])
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Credentials", true);
      
    if(is_numeric($cors['cache']))
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Max-Age", $cors['cache']);
  }
  
  public function getServiceConfig()
  {
    return [
      'factories' => [
        'Solutio\Utils\Net\Mail' => function($container) {
          return new Utils\Net\Mail\Mail($container->get('acmailer.mailservice.default'));
        }
      ]
    ];
  }
}