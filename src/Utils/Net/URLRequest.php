<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Net
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Net;

use Solutio\System,
    Solutio\Utils\Data\ArrayObject,
    Solutio\Utils\Data\StringManipulator,
    Solutio\InvalidArgumentException;

/**
 * Captura todas as informações de uma requisição HTTP.
 */
class URLRequest
{

  const URLFILETYPE       = 'file';
  const URLDIRECTORYTYPE  = 'dir';
  const URLFIFOTYPE       = 'fifo';
  const URLBLOCKTYPE      = 'block';
  const URLCHARTYPE       = 'char';
  const URLLINKTYPE       = 'link';
  const URLSOCKETTYPE     = 'socket';
  const URLUNKNOWNTYPE    = 'unknown';
  
  const METHODGET         = 'GET';
  const METHODPOST        = 'POST';
  const METHODPUT         = 'PUT';
  const METHODDELETE      = 'DELETE';

  /**
   * O tipo do dado requerido.
   * @var string
   */
  protected $method;
  
  /**
   * Um objeto que contém dados a serem transmitidos com a solicitação de URL.
   * @var \Solutio\Utils\Net\URLVariables
   */
  protected $data;
  
  /**
   * Lista de cabeçalhos para a requisição.
   * @var \Solutio\Utils\Data\ArrayObject
   */
  protected $requestHeaders;
  
  /**
   * URL da requisião.
   * @var string
   */
  protected $url;
  
  /**
   * Lista de possíveis arquivos.
   * @var \Solutio\Utils\Data\ArrayObject
   */
  protected $listFile;
  
  /**
   * Constructor
   *
   * @param   string	$url
   * @throws  \Solutio\InvalidArgumentException
   * @throws  \Solutio\Utils\Net\NetException
   */
  public function __construct($url)
  {

    $this->listFile = new ArrayObject(array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'txt', 'wav', 'mp3', 'wma', 'midi', 'pdf', 'php', 'html', 'js', 'json', 'xls', 'xlsx', 'doc', 'docx', 'otf', 'sql', 'ppt', 'pptx', 'psd', 'ai', 'cdr', 'ttf', 'wmv', 'avi', 'mpg', 'mpeg', 'mov', 'mkv', 'rmvb', 'swf', 'swc', 'fla', 'as', 'rar', 'zip', '7z'));
    
    if(empty($url))
      throw InvalidArgumentException::FromCode(2);
    
    $this->method 			  = self::METHODGET;
    $this->requestHeaders = URLRequestHeader::GetHeader();
    $this->url            = $url;
    $this->data           = null;
    $headers              = $this->getHeaders();
    if(!$headers){
      if(!($this->getLocalFileHeaders()))
        throw new NetException("URL não existe: ".$url, 6);
    }else{
      if(empty('Content-Length'))
        throw new NetException("URL não existe: ".$url, 6);
      $this->requestHeaders = $this->requestHeaders->concat((array) $headers);
    }
  }
  
  /**
   * Retorna propriedades protegidas do objeto.
   *
   * @param   string $property
   * @return	mixed
   */
  public function __get($property)
  {
    return $this->{$property};
  }

  /**
   * Insere valor nas propriedades protegidas.
   *
   * @param   string   $property
   * @param   mixed    $value
   * @throws  \Solutio\InvalidArgumentException
   */
  public function __set($property, $value)
  {
    if($property == 'data'){
      if(!($value instanceof URLVariables))
        throw InvalidArgumentException::FromCode(5);
      else
        $this->data = $value;
    }else
      $this->{$property} = $value;
  }
  
  /**
   * Retorna o cabeçalho da url requerida.
   *
   * @return \Solutio\Utils\Data\ArrayObject
   */
  private function getHeaders()
  {
    $headers = new ArrayObject((array)@get_headers($this->url, 1));
    return $headers;
  }
  
  /**
   * Carrega o cabeçalho da url local requerida.
   *
   * @return boolean
   */
  private function getLocalFileHeaders()
  {
      
    if(!file_exists($this->url))
      return false;

    if(System::GetVariable('SERVER_PROTOCOL')->search("HTTP"))
      $protocol = 'http';
    elseif(System::GetVariable('SERVER_PROTOCOL')->search("HTTPS"))
      $protocol = 'https';
    else
      $protocol = System::GetVariable('SERVER_PROTOCOL');

    $url 		    = $this->url;
    $newURL		  = $url->replace(System::GetVariable('DOCUMENT_ROOT'), $protocol."://".System::GetVariable('HTTP_HOST'));
    //$this->url	= $newURL;
    //$this->requestHeaders = $this->requestHeaders->concat((array) $this->getHeaders());
    $this->url	= $url;

    $this->requestHeaders->offsetSet(URLRequestHeader::CONTENTLENGTH, filesize($this->url));
    
    return true;

  }
  
  /**
   * Retorna o caminho url no protocólo http.
   *
   * @return string
   */
  public function getHTTPUrl()
  {
    $url = new StringManipulator($this->url);
    return $url->replace(System::GetVariable('directory_root'), System::GetVariable('protocol')."://".System::GetVariable('host'))->toString();
  }
  
  /**
   * Verifica e retorna o tipo do retorno da requisição.
   *
   * @return string
   */
  public function getType()
  {
    
    $type = @filetype($this->url);
    
    $verifyExtension = function($value, $key, $array, $oB){
      if(!empty($value) && count($array) > 0){
        if(\Solutio\Utils\Data\StringManipulator::GetInstance($oB->url)->search('\.'.$value))
            return false;
        if(StringManipulator::GetInstance($oB->requestHeaders['Content-Type'][1])->search($value))
            return false;
      }
      return true;
    };
    
    if(!empty($type))
      return $type;
    elseif(is_file($this->url) || !$this->listFile->every($verifyExtension, $this))
      return self::URLFILETYPE;
    elseif(is_dir($this->url))
      return self::URLDIRECTORYTYPE;
    elseif(is_link($this->url))
      return self::URLLINKTYPE;
    else
      return self::URLUNKNOWNTYPE;
    
  }
  
  /**
   * Retorna o caminho da pasta do arquivo ou pasta atual.
   *
   * @return \Solutio\Utils\Net\URLRequest
   */
  public function directoryPath()
  {
    return new URLRequest(dirname($this->url));
  }

  /**
   * Retorna o nome do arquivo ou pasta atual.
   *
   * @return string
   */
  public function baseName()
  {
    return basename($this->url);
  }

  /**
   * Função mágica para serialização do objeto.
   *
   * @return \Solutio\Utils\Data\ArrayObject
   */
  public function __sleep()
  {
    return array('method', 'data', 'requestHeaders', 'url');
  }

  /**
   * Retorna a url do arquivo.
   *
   * @return \Solutio\Utils\Data\StringManipulator
   */
  public function toString()
  {
    return new StringManipulator($this);
  }

  /**
   * Retorna a url do arquivo.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->url;
  }
  
}