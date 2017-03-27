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
    Solutio\InvalidArgumentException,
    Solutio\Utils\Net\NetException,
    Solutio\Utils\Net\URLRequestHeader,
    Solutio\Utils\Net\URLRequest,
    Solutio\Utils\Net\CURL;

/**
 * Classe que trabalham com arquivos estruturados em DOM.
 */
class DOMFile extends ArrayObject implements IFileObject, \JsonSerializable
{

  /**
   * Responsável para acessar as Annotations da classe.
   * @var \ReflectionClass
   */
  protected $reflectionClass;
  
  /**
   * URL informada.
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
   * Instância do XML.
   * @var \SimpleXMLElement
   */
  private $xml;
  
  /**
   * Texto dentro da tag, se houver.
   * @var string
   */
  private $content;
  
  /**
   * Array com os atributos da tag.
   * @var \Solutio\Utils\Data\ArrayObject
   */
  private $attributes;
  
  /**
   * Array com os filhos da tag.
   * @var \Solutio\Utils\Data\ArrayObject
   */
  private $childrens;
  
  /**
   * Cabeçalho do tipo de Arquivo.
   * @var \Solutio\Utils\Data\StringManipulator
   */
  protected $headString;
  
  /**
   * Constructor
   *
   * @param  \Solutio\Utils\Net\URLRequest|null	$urlRequest
   */
  public function __construct(URLRequest $urlRequest = null)
  {
    
    parent::__construct();
    
    $this->extension 	= new StringManipulator();
    $this->fileName 	= new StringManipulator();
    
    $this->reflectionClass = new \ReflectionClass(get_class($this));
    
    if(!empty($urlRequest)){
      $this->urlRequest = $urlRequest;
    }else
      $this->urlRequest = null;
      
    $this->attributes = new ArrayObject();
    $this->childrens = new ArrayObject();
    $this->childrens->nameSpace	= '-||-';
    
  }
  
  /**
   * Script executado toda a vez que o arquivo for carregado.
   *
   * @return	void
   */
  protected function whenLoaded()
  {
    $this->url = $this->urlRequest->url;
    $name = explode(".", basename($this->urlRequest->url));
    $this->fileName = str_replace(".".$name[count($name)-1], "", basename($this->urlRequest->url));
    $this->extension = $name[count($name)-1];
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
    
    if(!class_exists("SimpleXMLElement"))
      throw new InvalidArgumentException('A classe SimpleXMLElement não está habilitada', 20);
      
    if(empty($urlRequest)) $urlRequest = $this->urlRequest;
    
    if(!empty($urlRequest)){
      
      if($urlRequest->getType() == URLRequest::URLFILETYPE){
      
        $this->urlRequest = $urlRequest;
        
        if($this->urlRequest->requestHeaders[URLRequestHeader::CONTENTLENGTH] <= System::GetIni("memory_limit")){

          $data = @file_get_contents($this->urlRequest->url);

          if(!empty($data)){
            $this->data = $data;
            $this->whenLoaded();
          }
                  
          if(empty($data)){
            $curl = new CURL($this->urlRequest);
            if($curl->load()){
              $this->data = $curl->content;
            $this->whenLoaded();
            }
          }
        
        }else
          throw SystemException::FromCode(13);
      
      }else
        throw NetException::FromCode(9);
    
    }
    
    if(!empty($this->data)){
      
      try{
        $this->xml = @new \SimpleXMLElement($this->data);
      }catch(\Exception $e){
        if(!empty($this->headString->toString())){
          try{
            $this->xml = @new \SimpleXMLElement($this->headString.$this->data);
          }catch(\Exception $e){
            throw new InvalidArgumentException('Estrutura DOM não está correta, sendo assim, não é possível instanciar objeto SimpleXMLElement', 20);
          }
        }else
          throw new InvalidArgumentException('Estrutura DOM não está correta, sendo assim, não é possível instanciar objeto SimpleXMLElement', 20);
      }
      
    }
    
    $this->commitData();
    
    //Atributos
    $this->createAttributesArray();
    //
    //Filhos
    //$this->createChildrensArray();
    //
    
  }

  /**
   * Retorna um node de acordo com a posição passada.
   *
   * @param  int  $index
   * @return \Solutio\Utils\Data\DOMFile
   */
  public function offsetGet($index)
  {
    return $this->xml[$index];
  }

  /**
   * Insere um novo valor para a node de acordo com a posição passada.
   *
   * @param  int    $index
   * @param  mixed  $newval
   * @return \Solutio\Utils\Data\DOMFile
   */
  public function offsetSet($index, $newval)
  {
    return $this->xml[$index] = $newval;
  }
  
  /**
   * Cria um objeto apartir de uma string em DOM válido.
   *
   * @param  string	$data
   * @return \Solutio\Utils\Data\DOMFile
   */
  public static function CreateDOMFileByString($data)
  {
    
    $data = (string) $data;
    
    $fileXML = new DOMFile;
    $fileXML->data = $data;
    $fileXML->open();
    
    return $fileXML;
      
  }
  
  /**
   * Cria um objeto apartir de um objeto SimpleXMLElement válido.
   *
   * @param  \SimpleXMLElement	            $data
   * @return \Solutio\Utils\Data\DOMFile
   */
  protected static function CreateDOMFileBySimpleXMLElement(\SimpleXMLElement $data)
  {
    
    $fileXML = new DOMFile;
    $fileXML->xml = $data;
    $fileXML->open();
    
    return $fileXML;
      
  }
  
  /**
   * Grava as informações passadas para o arquivo DOM.
   *
   * @return void
   */
  protected function commitData()
  {
    $this->data = $this->xml->asXML();
  }
  
  /**
   * Retorna o nome da tag.
   *
   * @throws  \Solutio\Utils\Net\NetException
   * @return  string
   */
  public function getName()
  {
    if(($this->xml instanceof \SimpleXMLElement))
      return (string) $this->xml->getName();
    else
      throw NetException::FromCode(15);
  }
  
  /**
   * Retorna o nome da namespace.
   *
   * @throws  \Solutio\Utils\Net\NetException
   * @return  string
   */
  public function getNamespace()
  {
    if(($this->xml instanceof \SimpleXMLElement)){
      $array = ArrayObject::GetInstance($this->xml->getNamespaces());
      if($array->count() > 0)
        return (string) @$array->getIterator()->current();
    }else
      throw NetException::FromCode(15);
    return "";
  }
  
  /**
   * Retorna uma lista com os namespaces usado no documento.
   *
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\ArrayObject
   */
  public function getAllNamespaces()
  {
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    return new ArrayObject($this->xml->getNamespaces(true));
  }
  
  /**
   * Verifica se há nodes filhos com determinada Namespace.
   *
   * @param   string	$prefix
   * @throws  \Solutio\Utils\Net\NetException
   * @return  boolean
   */
  public function hasChildrensNameSpace($prefix)
  {
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    
    if($prefix){
      $str = $this->getData();
      return $str->search("<".$prefix.":");
    }else
      return false;
  }
  
  /**
   * Retorna o numero de elementos filhos da tag.
   *
   * @throws  \Solutio\Utils\Net\NetException
   * @return  int
   */
  public function length()
  {
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    return $this->xml->count() ? $this->xml->count() : 0;
  }
  
  /**
   * Adiciona conteúdo em texto na tag.
   *
   * @param   string	$value
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\DOMFile
   */
  private function addContent($value)
  {
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
     
    $this->content = $value;
    $this->xml->{0} = $this->content;
    $this->commitData();
    return $this;
  }
   
  /**
   * Adiciona um atributo na tag.
   *
   * @param   string	                                $name
   * @param   string	                                $value
   * @param   string|null                             $nameSpace
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\DOMFile
   */
  public function addAttribute($name, $value, $nameSpace = null)
  {
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    
    $this->xml->addAttribute($name, $value, $nameSpace);
    $this->createAttributesArray();
    $this->commitData();
    return $this;
  }
  
  /**
   * Adiciona um filho a tag e retorna-o.
   *
   * @param   string      $name
   * @param   string      $value
   * @param   string|null $nameSpace
   * @throws  \Solutio\Utils\Net\NetException
   * @throws  \Solutio\InvalidArgumentException
   * @return  \Solutio\Utils\Data\DOMFile
   */
  public function addChild($name, $value = '', $nameSpace = null)
  {
    
    if(empty($name))
      throw NetException::FromCode(2);
      
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);

    $this->xml->addChild($name, $value, $nameSpace);
    $this->createChildrensArray();
    $this->commitData();
    
    $arrayChilds 	= $this->getChildrens();
    $child 			= $arrayChilds[$name]->end();
    
    return $child;
    
  }
  
  /**
   * Adiciona um filho a tag de acordo com o índice e retorna-o.
   *
   * @param   string      $name
   * @param   string      $value
   * @param   string|null $index
   * @param   string|null $nameSpace
   * @throws  \Solutio\Utils\Net\NetException
   * @throws  \Solutio\InvalidArgumentException
   * @return  \Solutio\Utils\Data\DOMFile
   */
  public function addChildAt($name, $value = '', $index = null, $nameSpace = null)
  {
    
    if(empty($name))
      throw NetException::FromCode(2);
      
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
      
    $parent = dom_import_simplexml($this->xml);
    if($nameSpace)
      $child  = $parent->ownerDocument->createElementNS($nameSpace, $name, $value);
    else
      $child  = $parent->ownerDocument->createElement($name, $value);
        
    $target = $parent->getElementsByTagname('*')->item($index);
    if ($target === null) {
        $parent->appendChild($child);
    } else {
        $parent->insertBefore($child, $target);
    }
        
    $this->createChildrensArray();
    $this->commitData();
    
    $arrayChilds 	= $this->getChildrens();
    $child			= $arrayChilds[$name][$index];
    
    return $child;
    
  }
  
  /**
   * Remove um filho da tag e retorna-o.
   *
   * @param   \Solutio\Utils\Data\DOMFile	    $node
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\DOMFile
   */
  public function removeChild(DOMFile $node)
  {
    
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    
    $dom = dom_import_simplexml($node->xml);
    $child = $dom->parentNode->removeChild($dom);
    
    $this->commitData();
    
    //Atributos
    $this->createAttributesArray();
    //
    
    //Filhos
    $this->createChildrensArray();
    //
    
    return $child;
    
  }
  
  /**
   * Remove um filho da tag de acordo com seu índice e retorna-o.
   *
   * @param   string      $name
   * @param   int         $index
   * @param   string|null $nameSpace
   * @throws  \Solutio\Utils\Net\NetException
   * @throws  \Solutio\InvalidArgumentException
   * @return  \Solutio\Utils\Data\DOMFile
   */
  public function removeChildAt($name, $index = 0, $nameSpace = null)
  {
    
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    
    $arrayChilds 	= $this->getChildrens($nameSpace);
    $index			= (int)(string) $index;
    
    if(!empty($arrayChilds[$name])){
      
      if($arrayChilds[$name]->count() > 0){
        if(!empty($arrayChilds[$name][$index])){
          $dom = dom_import_simplexml($arrayChilds[$name][$index]->xml);
          $child = $dom->parentNode->removeChild($dom);
        }else
          throw new InvalidArgumentException('Indíce '.$index.' de nome '.$name.' não existe no arquivo', 3);
      }else{
        $dom = dom_import_simplexml($arrayChilds[$name]->xml);
        $child = $dom->parentNode->removeChild($dom);
      }
      
    }else
      throw new InvalidArgumentException($name.' não existe no arquivo', 3);
    
    $this->commitData();
    
    //Atributos
    $this->createAttributesArray();
    //
    //Filhos
    $this->createChildrensArray();
    //
    return $child;
    
  }
  
  /**
   * Troca um node filho por outro.
   *
   * @param   \Solutio\Utils\Data\DOMFile	    $node
   * @param   \Solutio\Utils\Data\DOMFile	    $newNode
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\DOMFile
   */
  public function replaceChild(DOMFile $node, DOMFile $newNode)
  {
    
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    
    $dom 	= dom_import_simplexml($node->xml);
    $newDom = dom_import_simplexml($newNode->xml);
    $newDom = $dom->ownerDocument->importNode($newDom, true);
    $dom->parentNode->replaceChild($newDom, $dom);
    
    $this->commitData();
    
    //Atributos
    $this->createAttributesArray();
    //
    //Filhos
    $this->createChildrensArray();
    //
    
    return $node;
    
  }
  
  /**
   * Retorna um Array dos atributos da tag.
   *
   * @param   string|null $nameSpace
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\ArrayObject
   */
  public function getAttributes($nameSpace = null)
  {
    
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    
    if($this->attributes->nameSpace != $nameSpace)
      $this->createAttributesArray($nameSpace);
    
    return $this->attributes->attributes;
    
  }
  
  /**
   * Cria um Array dos atributos da tag.
   *
   * @param  string|null	$nameSpace	Optional.
   * @return void
   */
  protected function createAttributesArray($nameSpace = null)
  {
    
    //if(empty($this->xml))
      //throw new NetException(15, $this->reflectionClass->getName(), 428);
    
    $this->attributes->attributes = new ArrayObject();
    $this->attributes->nameSpace = $nameSpace;
    foreach($this->xml->attributes($nameSpace) as $key => $value){
      $this->attributes->attributes->$key = $value;
      //$this[$key] = $value;
    }

  }
  
  /**
   * Retorna um Array dos filhos da tag.
   *
   * @param   string|null	                        $nameSpace
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\ArrayObject
   */
  public function getChildrens($nameSpace = null)
  {
    
    if(!($this->xml instanceof \SimpleXMLElement))
      throw NetException::FromCode(15);
    
    if($this->childrens->nameSpace != $nameSpace)
      $this->createChildrensArray($nameSpace);
    
    return $this->childrens->childrens;
    
  }
  
  /**
   * Cria um Array dos filhos da tag.
   *
   * @param  string|null	$nameSpace
   * @return void
   */
  private function createChildrensArray($nameSpace = null)
  {
    
    //if(empty($this->xml))
      //throw new NetException(15, $this->reflectionClass->getName(), 466);
    
    $this->childrens->childrens = new ArrayObject();
    $this->childrens->nameSpace = $nameSpace;
    
    foreach($this->xml->children($nameSpace) as $key => $value){
      
      $value = static::CreateDOMFileBySimpleXMLElement($value);
      
      if(!empty($this->childrens->childrens[$key])){

        //if(!($this->childrens->childrens->$key->count())){
          //$old = $this->childrens->childrens->$key;
          //$this->childrens->childrens->$key = new ArrayObject();
          //$this->childrens->childrens->$key->append($old);
        //}
        
        $this->childrens->childrens->$key->append($value);

      }else{
        $this->childrens->childrens->$key = new ArrayObject();	
        $this->childrens->childrens->$key->append($value);
        //$this->childrens->childrens->$key = $value;
      }	
    }
    
    
  }

  /**
   * Retorna propriedades protegidas do objeto.
   *
   * @param   string  $property
   * @throws  \Solutio\Utils\Net\NetException
   * @return  mixed
   */
  public function __get($property)
  {

    if($property == 'content')
      return $this->content;
    elseif($property == 'xml'){
      
      if(!($this->xml instanceof \SimpleXMLElement))
        throw NetException::FromCode(15);
      
      return $this->xml;
    }elseif(isset($this->xml->$property)){
      
      if(empty($this->xml))
        throw NetException::FromCode(15);
      
      return static::CreateDOMFileBySimpleXMLElement($this->xml->$property);
    }else
      return $this->$property;
    
  }

  /**
   * Insere valor nas propriedades protegidas.
   *
   * @param   string  $property
   * @param   mixed   $value
   * @throws  \Solutio\InvalidArgumentException
   */
  public function __set($property, $value)
  {
    if($property == 'content')
      $this->addContent($value);
    elseif($property == 'xml'){			
      if(!($value instanceof \SimpleXMLElement))
        throw new InvalidArgumentException('O Argumento não é um SimpleXMLElement válido', 19);
      $this->xml = $value;
    }else
      $this->$property = $value;
  }

  /**
   * Implementa as modificações e obtém os dados do arquivo.
   *
   * @return string
   */
  public function getData()
  {
    $this->commitData();
    return (string) $this->data;
  }
  
  /**
   * Retorna a classe espelho da classe.
   *
   * @param   string  $class
   * @return  \ReflectionClass
   */
  public static function GetReflection($class)
  {
    $c = __CLASS__;
    return new \ReflectionClass(!empty($class) ? new $class : new $c);
  }
  
  /**
   * Compara o próprio objeto com o objeto passado por parâmetro.
   *
   * @param  \Solutio\Utils\Data\DOMFile $obj
   * @return bool
   */
  public function equals(DOMFile $obj)
  {
    if($this === $obj) return true;
    else return false;
  }

  /**
   * Dispara métodos protegidos do objeto.
   *
   * @param  string   $name
   * @param  array    $arguments
   * @return void
   */
  public function __call($name, $arguments)
  {
  }

  /**
   * Dispara métodos estáticos protegidos do objeto.
   *
   * @param  string   $name
   * @param  array    $arguments
   * @return void
   */
  public static function __callStatic($name, $arguments)
  {
  }
  
  /**
   * Usada para serialização do objeto.
   *
   * @return \Solutio\Utils\Data\ArrayObject
   */	
  public function __sleep()
  {
    return new ArrayObject(array('reflectionClass','urlRequest','events','attributes','childrens'));
  }
  
  /**
   * Retorna o conteúdo do arquivo.
   *
   * @return string
   */
  public function __toString()
    {
    return (string) $this->getData();
  }
  
  /**
   * Retorna um objeto String da função mágica __toString.
   *
   * @return \Solutio\Utils\Data\StringManipulator
   */
  public function toString()
  {
    return new StringManipulator((string) $this);
  }
  
  public function jsonSerialize()
  {
    return (string) $this;
  }
  
  /**
   * Chamado quando ser destruido o objeto.
   *
   * @return void
   */
  public function __destruct()
  {
    parent::__destruct();
  }

}