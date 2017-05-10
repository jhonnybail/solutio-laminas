<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Data
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Data;

use Solutio\System,
    Solutio\SystemException,
    Solutio\Utils\Net\NetException,
    Solutio\Utils\Net\URLRequestHeader,
    Solutio\Utils\Net\URLRequest,
    Solutio\Utils\Net\CURL;

/**
 * Classe que mantém arquivos de qualquer tipo.
 */
class File implements IFileObject, \JsonSerializable
{

  /**
   * URLRequest informada.
   * @var \Solutio\Utils\Net\URLRequest
   */
  protected $urlRequest;

  /**
   * Dados do arquivo em formato de string.
   * @var string
   */
  protected $data;
  
  /**
   * URL informada pela URLRequest.
   * @var string
   */
  protected $url;
  
  /**
   * Extensão do arquivo.
   * @var string
   */
  protected $extension;
  
  /**
   * Nome do arquivo.
   * @var string
   */
  protected $fileName;
    
  /**
   * Verificador se o arquivo está aberto ou não.
   * @var string
   */
  protected $isOpen = false;
  
  /**
   * Constructor
   *
   * @param  \Solutio\Utils\Net\URLRequest	$urlRequest
   */
  public function __construct(URLRequest $urlRequest = null)
  {
    
    $this->extension 	= new StringManipulator();
    $this->fileName 	= new StringManipulator();
    $this->data			= null;
    
    if(!empty($urlRequest)){
      $this->urlRequest = $urlRequest;
      $this->whenLoaded();
    }else
      $this->urlRequest = null;
    
  }
  
  /**
   * Script executado toda a vez que o arquivo for carregado.
   *
   * @return	void
   */
  protected function whenLoaded()
  {
    if(!is_null($this->urlRequest)){
      $this->url = $this->urlRequest->url;
      $name = explode(".", basename($this->urlRequest->url));
      $this->fileName = str_replace(".".$name[count($name)-1], "", basename($this->urlRequest->url));
      $this->extension = $name[count($name)-1];
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
    if($property == 'name')
      return $this->fileName.'.'.$this->extension;
    if($property == 'size')
      return $this->urlRequest->requestHeaders[URLRequestHeader::CONTENTLENGTH];
    return $this->{$property};
  }

  /**
   * Insere valor nas propriedades protegidas.
   *
   * @param   string	$property
   * @param   mixed		$value
   */
  public function __set($property, $value)
  {
    if($property != 'size')
        $this->{$property} = $value;
  }

  /**
   * Abre o arquivo.
   *
   * @param   \Solutio\Utils\Net\URLRequest|null	$urlRequest
   * @throws  \Solutio\InvalidArgumentException
   * @throws  \Solutio\Utils\Net\NetException
   * @throws  \Solutio\SystemException
   * @return  void
   */
  public function open(URLRequest $urlRequest = null)
  {
    
    if(empty($urlRequest)) $urlRequest = $this->urlRequest;
    if(!empty($urlRequest)){
      
      if(!$this->isOpen){
        
        if($urlRequest->getType() == URLRequest::URLFILETYPE){
          
          $this->urlRequest = $urlRequest;
          
          if($this->urlRequest->requestHeaders[URLRequestHeader::CONTENTLENGTH] <= System::GetIni("memory_limit")){
              
            $data = $data = @file_get_contents($this->urlRequest->url);
            
            if(!empty($data)){
              $this->data = $data;
              $this->isOpen = true;
              $this->whenLoaded();
            }
                      
            if(empty($data)){
              $curl = new CURL($this->urlRequest);
              if($curl->load()){
                $this->isOpen = true;
                $this->data = $curl->content;
                $this->whenLoaded();
              }
            }
            
          }else
            throw SystemException::FromCode(13);
        
        }else
          throw NetException::FromCode(9);
        
      }
    
    }
    
  }
  
  /**
   * Cria um Objeto apartir de um caminho temporário.
   *
   * @param  mixed	$temp
   * @return \Solutio\Utils\Data\File
   */
  public static function OpenFileByTEMP($temp)
  {
    $urlR = new URLRequest($temp['tmp_name']);
    $file = new File($urlR);
    
    $ext = explode('.', $temp['name']);
      $file->extension = $ext[count($ext)-1];
      
    $file->fileName = '';
    for($i = 0; $i < count($ext)-1; $i++) $file->fileName .= $ext[$i];
      
    $file->fileName = new StringManipulator(preg_replace('! !', '_', preg_replace('!,!', '', $file->fileName)));
    $file->url = preg_replace('! !', '_', preg_replace('!,!', '', $file->url));
     
    return $file;
  }
   
  /**
   * Cria um arquivo apartir de dados em formato de string.
   *
   * @param  string	$str
   * @return \Solutio\Utils\Data\File
   */
  public static function CreateFileByString($str)
  {
    $file = new File;
    $file->data = new StringManipulator((string) $str);
    return $file;
  }

  /**
   * Implementa as modificações e obtém os dados do arquivo.
   *
   * @return \Solutio\Utils\Data\StringManipulator
   */
  public function getData()
  {
    return new StringManipulator((string) $this->data);
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
   * Usada para serialização do objeto.
   *
   * @return \Solutio\Utils\Data\ArrayObject
   */
  public function __sleep()
  {
    return array('urlRequest', 'url');
  }
  
  public function jsonSerialize()
  {
    return $this->urlRequest->url;
  }

  /**
   * Retorna o conteúdo do arquivo.
   *
   * @return string
   */
  public function __toString()
  {
    return !is_null($this->urlRequest) ? (string) $this->urlRequest->url : (string) "";
  }
  
}