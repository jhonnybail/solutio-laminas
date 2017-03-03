<?php

/**
 * Solutio.Me
 *
 * @package     Solutio
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio;

use Solutio\Utils\Data\ArrayObject,
		Solutio\Utils\Data\String,
		Zend\Session\Container;
	
/**
 *  Trata e resgata variáveis do sistema.
 */
class System extends Object
{
	
	/**
   * Array com as variáveis do sistema
   * @var \Zend\Session\Container
   */
	private static	$variablesSystem;
	
	/**
   * Constructor
   */
	public function __construct()
  {
		parent::__construct();
	}
	
	/**
   * Registra variáveis do sistema.
   *
   * @param  array	$variables
   * @return void
   */
	public static function SetSystem(array $variables)
  {
		if(empty(self::$variablesSystem))
			self::$variablesSystem = new Container("system");
		foreach($variables as $k => $v)
			self::$variablesSystem->offsetSet($k, $v);
	}
	
	/**
   * Retorna a variável do sistema armazenada.
   *
   * @param  string $data
   * @return string
   */
	public static function GetVariable($data)
  {
		$value = new String;
    if(self::$variablesSystem->offsetGet((string) $data) != "")
			$value = self::$variablesSystem->offsetGet((string) $data);
		elseif(!empty($_SERVER[(string) $data]))
      $value = $_SERVER[(string) $data];
    return $value;
	}
	
	/**
   * Retorna valor da variável de requisição POST passada por parâmetro.
   *
   * @param  string $varName
   * @return string
   */
	public function requestPost($varName)
  {
		return $_POST[$varName];
	}
	
	/**
   * Retorna o arquivo enviado por requisição FILES passada por parâmetro.
   *
   * @param  string $varName
   * @return \Solutio\Utils\Data\ArrayObject
   */
	public function requestFile($varName)
  {
		return new ArrayObject($_FILES[$varName]);
	}
	
	/**
   * Retorna valor da variável de requesição GET passada por parâmetro.
   *
   * @param  string $varName
   * @return string
   */
	public function requestGet($varName)
  {
		return $_GET[$varName];
	}
	
	/**
   * Retorna informação da variável do sistema contido no php.ini requerida por parâmetro.
   *
   * @param  string $varName
   * @return float
   */	
	public static function GetIni($varName)
  {

		if($varName == 'memory_limit'){
			if(String::GetInstance(ini_get($varName))->search("M"))
				return (float)String::GetInstance(ini_get($varName))->replace("M", "")->toString()*1048576;
			elseif(String::GetInstance(ini_get($varName))->search("K"))
				return (float)String::GetInstance(ini_get($varName))->replace("K", "")->toString()*1024;
			elseif(String::GetInstance(ini_get($varName))->search("G"))
				return (float)String::GetInstance(ini_get($varName))->replace("G", "")->toString()*1073741824;
		}
			
		return (float) ini_get($varName);
	
	}
	
}