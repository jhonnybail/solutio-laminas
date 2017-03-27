<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Net
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Net;

use Solutio\Utils\Data\ArrayObject;

/**
 * Classe para lista de cabeçalhos usados para requisições HTTP.
 */
class URLRequestHeader
{
  
  /**
   * @const string Constante definida para status da requisição.
   */
  const STATUS = '0';
  
  /**
   * @const string Constante definida para tipo do conteúdo requisitado.
   */
  const CONTENTTYPE = 'Content-Type';
  
  /**
   * @const string Constante definida para data e hora da última modificação.
   */
  const LASTMODIFIED = 'Last-Modified';
  
  /**
   * @const string Constante definida para data e hora da requisição.
   */
  const DATE = 'Date';
  
  /**
   * @const string Constante definida para o controle do cache da requisição.
   */
  const CACHECONTROL = 'Cache-Control';
  
  /**
   * @const string Constante definida para a opções do tipo do conteúdo requisitado.
   */
  const XCONTENTTYPEOPTIONS = 'X-Content-Type-Options';
  
  /**
   * @const string Constante definida para o servidor que foi requisitado.
   */
  const SERVER = 'Server';
  
  /**
   * @const string Constante definida para o tamanho em bytes do conteúdo requisitado.
   */
  const CONTENTLENGTH = 'Content-Length';
  
  /**
   * @const string Constante definida para o XXSS Protection.
   */
  const XXSSPROTECTION = 'X-XSS-Protection';
  
  /**
   * @const string Constante definida para o local de destino.
   */
  const LOCATION = 'Location';
  
  /**
   * Retorna um array com os cabeçalhos criados, porém, vazios.
   *
   * @return \Solutio\Utils\Data\ArrayObject
   */
  public static function GetHeader()
  {
    
    $ar = new ArrayObject();
    
    $ar[self::CACHECONTROL] 		  = '';
    $ar[self::CONTENTLENGTH] 		  = '';
    $ar[self::CONTENTTYPE] 			  = '';
    $ar[self::DATE] 				      = '';
    $ar[self::LASTMODIFIED] 		  = '';
    $ar[self::LOCATION] 			    = '';
    $ar[self::SERVER] 				    = '';
    $ar[self::STATUS]				      = '';
    $ar[self::XCONTENTTYPEOPTIONS]= '';
    $ar[self::XXSSPROTECTION] 		= '';
    
    return $ar;
    
  }
  
}