<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Data
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Data;

use Solutio\InvalidArgumentException;

/**
 * Classe usada para tratar arrays.
 */
class ArrayObject extends \ArrayObject implements \JsonSerializable
{
  
  /**
   * Construtor
   *
   * @param   array $array
   * @throws  \Solutio\InvalidArgumentException
   */
  public function __construct(array $array = [])
  {
    
    if(!empty($array)){
    
      if(!is_array($array))
        throw InvalidArgumentException::FromCode(3);
      else{
        
        parent::__construct($array);
        
        foreach($array as $key => $value){
          
          if(is_array($value))
            $this->offsetSet($key, new ArrayObject($value));
          else
            $this->offsetSet($key, $value);
          
        }
      
      }
    
    }
    
  }
  
  /**
   * Retorna o numero de elementos na Array.
   *
   * @return int
   */
  public function length()
  {
    return (int)($this->count());
  }

  /**
   * Adiciona os elementos do ArrayObject passado por parametro no final desta ArrayObject.
   *
   * @param   array $array
   * @throws  \Solutio\InvalidArgumentException
   * @return  \Solutio\Utils\Data\ArrayObject
   */
  public function concat(array $array)
  {
    if(!is_array((array) $array))
      throw InvalidArgumentException::FromCode(3);
    else
      return new ArrayObject(array_merge((array) $this, (array) $array));
  }

  /**
   * Mescla uma array na atual.
   *
   * @param  array $array
   * @throws  \Solutio\InvalidArgumentException
   * @return void
   */
  public function merge(array $array)
  {
    if(!is_array((array) $array))
        throw InvalidArgumentException::FromCode(3);
    else{
      $array = new ArrayObject((array)$array);
      foreach($array->getIterator() as $k => $v)
        $this->offsetSet($k, $v);
    }
  }

  /**
   * Executa uma função de teste em cada item da matriz e verifica se todos os elementros são verdadeiros.
   *
   * @param  callable 	$function
   * @param  object|null	$thisObject
   * @return boolean
   */
  public function every(callable $function, $thisObject = null)
  {
    
    $iterator = $this->getIterator();
    while($iterator->valid()){
      if(!call_user_func($function, $iterator->current(), $iterator->key(), $this, $thisObject))
        return false;
      $iterator->next();
    }
    
    return true;
    
  }

  /**
   * Executa uma função de teste em cada item na ArrayObject e constrói uma nova ArrayObject para todos os itens que retornam verdadeiro para a função especificada.
   *
   * @param  callable 	    $function
   * @param  object|null		$thisObject Optional.
   * @return \Solutio\Utils\Data\ArrayObject
   */
  public function filter(callable $function, $thisObject = null)
  {
    
    $array = new ArrayObject();
    
    $iterator = $this->getIterator();
    while($iterator->valid()){
      if(!call_user_func($function, $iterator->current(), $iterator->key(), $this, $thisObject))
        $array->offsetSet($iterator->key(), $iterator->current());
      $iterator->next();
    }
    
    return $array;
    
  }
  
  /**
   * Procura por um item em uma matriz usando (===) estrita igualdade e retorna a chave.
   *
   * @param  mixed 		    $searchElement
   * @param  string|int|null	$fromIndex      caso queira que o ponteiro inicie de uma determinada posição.
   * @return boolean
   */
  public function indexOf($searchElement, $fromIndex = 0)
  {
    
    $iterator = $this->getIterator();
    $pos      = 0;
    while($iterator->valid()){
      if($pos >= $fromIndex){
        if($iterator->current() === $searchElement)
          return $iterator->key();
        $iterator->next();
      }
      $pos++;
    }
    
    return false;
    
  }
  
  /**
   * Ordena Arrays multidimencionais.
   *
   * @param  array
   * @param  string|null
   * @param  string|null
   * @return \Solutio\Utils\Data\ArrayObject
   */
  public static function ArrayOrderBy()
  {
    $args = func_get_args();
    $data = (array) array_shift($args);
    foreach ($args as $n => $field) {
      if (is_string($field)) {
        $tmp = array();
        foreach ($data as $key => $row)
          @$tmp[$key] = @$row[$field];
        $args[$n] = $tmp;
      }
    }
    $args[] = &$data;
    @call_user_func_array('array_multisort', $args);
    return new ArrayObject(array_pop($args));
  }


  /**
   * Retorna em formato de string os indices e seus valores.
   *
   * @return string
   */
  public function __toString()
  {
    
    $string = '';
    
    $iterator = $this->getIterator();
    while($iterator->valid()){
      
      if(!empty($string))
        $string .= "; ";
      
      if($iterator->current() instanceof ArrayObject)
        $string .= $iterator->key().": [".$iterator->current()."]";
      else
        $string .= $iterator->key().": ".$iterator->current()."";
      
      $iterator->next();
        
    }
    
    return $string;
    
  }
  
  /**
   * Elimina o ultimo elemento da array e retorna o elemento eliminado.
   *
   * @return mixed
   */
  public function pop()
  {
    $el = $this->offsetGet($this->length()-1);
    $this->offsetUnset($this->length()-1);
    return $el;
  }
  
  /**
   * Aponta para o primeiro elemento do array e retorna-o.
   *
   * @return mixed
   */
  public function reset()
  {
    $aI = $this->getIterator();
    $aI->rewind();
    $k = $aI->key();
    if(!empty($k))
      return $this[$k];
    else
      return null;
  }
  
  /**
   * Aponta para o último elemento do array e retorna-o.
   *
   * @return mixed
   */
  public function &end()
  {
    $value = null;
    $aI = $this->getIterator();
    foreach($aI as $value);
    return $value;
  }

  /**
   * Retorna propriedades protegidas do objeto.
   *
   * @param  string  $property
   * @return mixed
   */
  public function __get($property)
  {
    $reflectionClass = new \ReflectionClass(__CLASS__);
    
    if(!$reflectionClass->hasProperty($property)){
      if(!empty($this[$property])){
        return $this[$property];
      }				
    }else
      return self::__get($property);

    return null;
  }

  /**
   * Insere valor nas propriedades protegidas.
   *
   * @param  string   $property
   * @param  mixed    $value
   */
  public function __set($property, $value)
  {
    $reflectionClass = new \ReflectionClass(__CLASS__);
    
    if(!$reflectionClass->hasProperty($property))
      $this[$property] = $value;
    else
      self::__set($property, $value);
  }
  
  /**
   * Retorna em objeto string os indices e seus valores.
   *
   * @return \Solutio\Utils\Data\StringManipulator
   */
  public function toString()
  {
    return new StringManipulator((string) $this);
  }
  
  /**
   * Chamado quando é destruido o objeto.
   *
   * @return void
   */
  public function __destruct()
  {
  }
  
  public function jsonSerialize()
  {
    return (array) $this;
  }

  /**
   * Cria uma instancia estáticamente.
   *
   * @param  array    $array
   * @return \Solutio\Utils\Data\ArrayObject
   */
  public static function GetInstance(array $array)
  {
    return new ArrayObject($array);
  }
    
}