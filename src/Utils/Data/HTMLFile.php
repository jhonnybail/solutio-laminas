<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Data
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Data;

use Solutio\Utils\Net\URLRequest;

/**
 * Classe usada para trabalhar com HTML.
 */
final class HTMLFile extends DOMFile
{
	
	public function __construct(URLRequest $urlRequest = null)
  {
		$this->headString = new StringManipulator'<!DOCTYPE html>');
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
		$this->data = $this->replaceCommercialE($this->data);
	}
	
	public static function CreateDOMFileByString($data)
  {
		
		$data = (string) $data;
		
		$fileXML = new HTMLFile;
		$fileXML->data = $data;
		$fileXML->open();
		
		return $fileXML;
			
	}
	
	protected static function CreateDOMFileBySimpleXMLElement(\SimpleXMLElement $data)
  {
		
		$fileXML = new HTMLFile;
		$fileXML->xml = $data;
		$fileXML->open();
		
		return $fileXML;
			
	}
	
	/**
   * Padroniza os & da estrutura HTML.
   *
   * @param  string	$data
   * @return string
   */
	public function replaceCommercialE($data){
		
		$string = (string) $data;
		
		$reg = array();
		$tags = array(
			'img'       => 'src',
			'input'     => 'src',
			'td'        => 'background',
			'th'        => 'background',
			'table'     => 'background',
			'link'      => 'href',
			'script'    => 'src',
			'object'    => 'data',
			'embed'     => 'src',
			'a'         => 'href'
		);
		
		foreach($tags as $tag => $att) {
			
			preg_match_all('@<'.$tag.'(.*?)'.$att.'="(.+?)"(.*?)>@i', $string, $reg);
			
			for($i = 0; $i < count($reg[0]); $i++) {
				
				$url = new StringManipulator($reg[2][$i]);
				if($url->search("&")){
				
					$urlA 	= $url->match("|&|");
					$urlA2 	= $url->match("|&amp;|");
					if($urlA2->count() == 0)
						$nova = '<'.$tag.$reg[1][$i].$att.'="'.str_replace("&", "&amp;", $reg[2][$i]).'"'.$reg[3][$i].'>';
					elseif($urlA2->count() < $urlA->count()){
						
						$ex = new ArrayObject(explode("&amp;", (string)$url));
						$newURL = new StringManipulator('');
						foreach($ex as $str) $newURL->concat(str_replace("&", "&amp;", $str)."&amp;");
						$newURL = $newURL->substr(0, $newURL->length()-5);
						$nova = '<'.$tag.$reg[1][$i].$att.'="'.$newURL.'"'.$reg[3][$i].'>';
					}else{
						$nova = '<'.$tag.$reg[1][$i].$att.'="'.$reg[2][$i].'"'.$reg[3][$i].'>';
					}
					
					$string = str_replace($reg[0][$i], $nova, $string);
				}

				
			}
			
		}
		
		// css
		
		preg_match_all('@url\((.*?)\)@i', $string, $reg);
		
		for($i=0; $i<count($reg[0]); $i++) {
				
			$nova = sprintf('url(%s)', str_replace("&", "&amp;", $reg[1][$i]));
			$string = str_replace($reg[0][$i], $nova, $string);
				
			
		}
		
		return $string;
		
	}
	
	public function getData()
  {
		$data = parent::getData();
		return new StringManipulator(trim((string) $data->replace("<\?xml(.*)\"\?>", "")));
	}
		
}