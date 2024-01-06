<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Data
 * @link        http://github.com/jhonnybail/solutio-laminas
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Data;

use Solutio\Utils\Net\URLRequest;

/**
 * Classe usada para trabalhar com XML.
 */
final class XMLFile extends DOMFile
{
  public function __construct(URLRequest $urlRequest = null)
  {
    $this->headString = new StringManipulator('<?xml version="1.0"?>');
    parent::__construct($urlRequest);
  }
  
  /**
   * Script executado toda a vez que o arquivo for carregado.
   *
   * @return	void
   */
  protected function whenLoaded()
  {
    parent::whenLoaded();
    $this->loadXML($this->data);
  }

  public static function CreateDOMFileByString($data)
  {
    $data = (string) $data;
    
    $fileXML = new XMLFile;
    $fileXML->data = $data;
    $fileXML->open();
    
    return $fileXML;
  }
  
  protected static function CreateDOMFileBySimpleXMLElement(\SimpleXMLElement $data)
  {
    $fileXML = new XMLFile;
    $fileXML->xml = $data;
    $fileXML->open();
    
    return $fileXML;
  }

  /**
   * Retorna em formato de string.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->toString();
  }
  

  /**
   * Retorna em formato de string.
   *
   * @return \Solutio\Utils\Data\StringManipulator
   */
  public function toString()
  {
    return $this->getData();
  }
}