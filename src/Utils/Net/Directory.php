<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Net
 * @link        http://github.com/jhonnybail/solutio-laminas
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Net;

use Solutio\Utils\Data\File,
    Solutio\Utils\Data\HTMLFile,
    Solutio\Utils\Data\XMLFile,
    Solutio\Utils\Data\ImageFile,
    Solutio\Utils\Data\StringManipulator;

/**
 *Classe que mantém diretórios.
 */
class Directory
{
  /**
   * URLRequest informada.
   * @var \Solutio\Utils\Net\URLRequest
   */
  protected $urlRequest;
  
  /**
   * URL informada pela URLRequest.
   * @var string
   */
  protected $url;
  
  /**
   * Ponteiro para o diretório informado.
   * @var Dir
   */
  protected $dir;
  
  /**
   * Construtor
   *
   * @param   \Solutio\Utils\Net\URLRequest	$rootPath
   * @throws  \Solutio\Utils\Net\NetException
   */
  public function __construct(URLRequest $rootPath)
  {
    parent::__construct();
    
    if(URLRequest::URLDIRECTORYTYPE == $rootPath->getType()){
      $this->urlRequest = $rootPath;
      $this->url 			  = $rootPath->url;
      $this->dir			  = dir($this->url);
    }else
      throw NetException::FromCode(12);
  }
  
  /**
   * Atualisa o diretório.
   *
   * @return void
   */
  public function refresh()
  {
    $this->dir = dir($this->url);
  }
  
  /**
   * Destrói o objeto.
   *
   * @return void
   */
  public function __destruct()
  {
  }

  /**
   * Retorna o caminho do diretório atual.
   *
   * @return \Solutio\Utils\Net\URLRequest|null
   */
  public function getPath()
  {
    if(isset($this->urlRequest))
      return $this->urlRequest;
    return null;
  }
  
  /**
   * Abre e percorre o diretório.
   *
   * @return \Solutio\Utils\Data\File|\Solutio\Utils\Net\Directory
   */
  public function read()
  {
    while($d = $this->dir->read()){
        
      if($d != '.' && $d != '..'){

        $pathD = $this->url.$d;
          
        $urlR 	= new URLRequest($pathD);
        
        if($urlR->getType() == URLRequest::URLFILETYPE){
          
          $file = new File($urlR);
          
          if($urlR->extension == 'html' || $urlR->extension == 'htm' || $urlR->extension == 'xhtml')
            return new HTMLFile($urlR);
          elseif($urlR->extension == 'xml')
            return new XMLFile($urlR);
          if($file->extension->toLowerCase() == ImageFile::IMAGETYPEJPEG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEJPG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEPNG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEGIF)
            return new ImageFile($urlR);
          else
            return $file;
            
        }elseif($urlR->getType() == URLRequest::URLDIRECTORYTYPE)
          return new Directory($urlR);
        
      }

    }

    return null;
  }
  
  /**
   * Executa uma função de teste em cada item do diretório, se extendendo aos diretórios filhos.
   *
   * @param  callable	  $function
   * @param  mixed|null	$thisObject
   * @return void
   */
  public function forEachRecursive(callable $function, $thisObject = null)
  {
    while($o = $this->read()){
      $function($o, $this, $thisObject);
      if($o->urlRequest->getType() == URLRequest::URLDIRECTORYTYPE)
        $o->forEachRecursive($function, $thisObject);
    }
  }
  
  /**
   * Usada para serialização do objeto.
   *
   * @return \Solutio\Utils\Data\ArrayObject
   */
  public function __sleep()
  {
    return parent::__sleep()->concat(array('urlRequest', 'url', 'dir'));
  }
  
  /**
   * Retorna o nome do diretório.
   *
   * @return \Solutio\Utils\Data\StringManipulator
   */
  public function toString()
  {
    return new StringManipulator($this);
  }
  
  /**
   * Retorna o nome do diretório.
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this->urlRequest->url;
  }
}