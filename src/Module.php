<?php

namespace Solutio;

use Zend\Mvc\MvcEvent,
    Zend\View\Model\JsonModel,
    Doctrine\DBAL\Logging\LoggerChain,
    Solutio\Doctrine\SqlLogger,
    Solutio\Utils\Data\ArrayObject;

class Module
{
  const VERSION = '2.2.3';
  
  public function onBootstrap(MvcEvent $e)
  {
    $sys                = new ArrayObject;
    $sys['directory']		= $_SERVER['DOCUMENT_ROOT'];
    if(strlen($sys['directory']) > 0 && $sys['directory'][strlen($sys['directory'])-1] != "/")
      $sys['directory'] .= '/';
    
    if(isset($_SERVER['SERVER_PROTOCOL'])){
      $protocol           = explode('/', $_SERVER['SERVER_PROTOCOL']);
      $sys['protocol']    = strtolower($protocol[0]);
    }
    if(isset($_SERVER['HTTP_HOST']))
     $sys['url']         = $sys['protocol']."://".$_SERVER['HTTP_HOST']."/";

    System::SetSystem((array) $sys);
    
    $e->getTarget()->getEventManager()->getSharedManager()
                                        ->attach(\Zend\Mvc\Controller\AbstractController::class,
                                                'dispatch', [$this, 'loadRequestCache'], 201);
    $e->getTarget()->getEventManager()->getSharedManager()
                                        ->attach(\Zend\Mvc\Controller\AbstractController::class,
                                                'dispatch', [$this, 'saveRequestCache'], 0);
    $e->getTarget()->getEventManager()->attach('dispatch.error',  [$this, 'onDispatchError'], 0);
    $e->getTarget()->getEventManager()->attach('finish',          [$this, 'applyCors'], 0);
    
    //Register Listeners Aggregate
    $serviceListeners = $e->getTarget()->getConfig()['service_listener'];
    foreach($serviceListeners as $serviceListener => $invokable){
      (new $invokable)($e->getTarget()->getServiceManager(), $serviceListener);
    }
    //
    
    //Register Doctrine Log
    $config = $e->getApplication()->getConfig()['solutio']['logs']['doctrine'];
    if($config['active']){
      $sm     = $e->getApplication()->getServiceManager();
      $em     = $sm->get('Doctrine\ORM\EntityManager');
      $log    = new SqlLogger((new \DateTime)->format("Y-m-d") . ".log", $config['path']);
      if (null !== $em->getConfiguration()->getSQLLogger()) {
          $logger = new LoggerChain();
          $logger->addLogger($log);
          $logger->addLogger($em->getConfiguration()->getSQLLogger());
          $em->getConfiguration()->setSQLLogger($logger);
      } else {
          $em->getConfiguration()->setSQLLogger($log);
      }
    }
    //
  }

  public function getConfig()
  {
    return include __DIR__ . '/../config/module.config.php';
  }
  
  public function loadRequestCache($e)
  {
    $key        = $e->getRequest()->getUri()->getPath() . ($e->getRequest()->getQuery()->count() > 0 ? urlencode(json_encode($e->getRequest()->getQuery()->toArray())) : '');
    $controller = $e->getTarget();
    if($controller instanceof Controller\CacheControllerInterface){
      $adapter    = $controller->getCacheAdapter();
      if($controller->isCacheable() && $adapter && $e->getRequest()->getMethod() === 'GET'){
        $content  = $adapter->getItem($key);
        if($content !== null){
          $model = new View\Model\JsonStringModel([$content]);
          $e->setViewModel($model);
          $e->stopPropagation();
          return $model;
        }
      }
    }
  }
  
  public function saveRequestCache($e)
  {
    $request    = $e->getRequest();
    $controller = $e->getTarget();
    if($controller instanceof Controller\CacheControllerInterface){
      if($controller->isCacheable()){
        $adapter  = $controller->getCacheAdapter();
        $reposito = $controller->getService()->getClassname();
        $key      = $e->getRequest()->getUri()->getPath() . ($e->getRequest()->getQuery()->count() > 0 ? urlencode(json_encode($e->getRequest()->getQuery()->toArray())) : '');
        $listaCa  = [];
        if($adapter->hasItem($reposito))
          $listaCa = $adapter->getItem($reposito);
        if($adapter && $request->getMethod() === 'GET'){
          $sharedEvents = $e->getApplication()->getEventManager()->getSharedManager();
          $sharedEvents->attach(\Zend\View\View::class, 'response', function($e) use ($adapter, $listaCa, $key, $reposito){
            $content  = $e->getResult();
            $adapter->addItem($key, $content);
            $listaCa[$key] = true;
            if(!$adapter->hasItem($reposito))
              $adapter->addItem($reposito, $listaCa);
            else
              $adapter->replaceItem($reposito, $listaCa);
          });
        }elseif($adapter && $e->getRequest()->getMethod() !== 'GET'
                  && $controller->getService() instanceof Service\EntityService){
          if(count($listaCa) > 0){
            foreach($listaCa as $k => $v)
              $adapter->removeItem($k);
          }
        }
      }
    }
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
      $data['trace'] = $exception->getTrace();
      $e->getResponse()->setStatusCode(\Zend\Http\PhpEnvironment\Response::STATUS_CODE_400);
      if($exception instanceof \Zend\Json\Exception\RuntimeException){
        $data['message'] = 'Invalid Request JSON';
      }elseif($exception instanceof \InvalidArgumentException)
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
    
    $e->setViewModel($model); 
    $e->stopPropagation(); 
    return $model; 
  }
  
  public function applyCors(MvcEvent $e)
  {
    $cors = $e->getApplication()->getServiceManager()->get('config')['solutio']['cors'];
    
    //Allow-Origin
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Origin")){
      $origin = System::GetVariable('HTTP_HOST');
      if(in_array('*', $cors['origin']) || in_array($origin, $cors['origin']))
        $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Origin", $origin);
    }
    
    //Allow-Methods
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Methods")){
      $methods = '';
      foreach($cors['methods'] as $method) $methods .= $method . ', ';
      $methods = substr($methods, 0, -2);
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Methods", $methods);
    }
    
    //Allow-Headers
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Headers")){
      $headers = '';
      foreach($cors['headers.allow'] as $header) $headers .= $header . ', ';
      $headers = substr($headers, 0, -2);
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Headers", $headers);
    }
    
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Allow-Credentials") && $cors['credentials'])
      $e->getResponse()->getHeaders()->addHeaderLine("Access-Control-Allow-Credentials", true);
      
    if(!$e->getResponse()->getHeaders()->has("Access-Control-Max-Age") && is_numeric($cors['cache']))
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