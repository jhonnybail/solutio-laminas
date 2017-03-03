<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Net
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Net;

use Solutio\InvalidArgumentException;

/**
 * Classe para requisições.
 */
class CURL
{

	/**
	 * Objeto URLRequest informada.
   * @var \Solutio\Utils\Net\URLRequest
   */
	private     $urlRequest;

	/**
	 * User-Agent que está gerando a requesição.
   * @var string
   */
	private     $userAgent;

	/**
	 * O servidor HTTP proxy pelo qual passar as requisições.
   * @var string
   */
	private     $proxy;
	
	/**
	 * Conteúdo retornado pela requisição.
   * @var string
   */
	protected   $content;

	/**
   * Construtor
   *
   * @param   \Solutio\Utils\Net\URLRequest	$urlRequest
   * @throws  \Solutio\InvalidArgumentException
   */
	public function __construct(URLRequest $urlRequest)
  {
		
		if(!function_exists("curl_init"))
			throw InvalidArgumentException::FromCode(4);
		
		parent::__construct();
		
		$this->urlRequest = $urlRequest;
		$this->proxy = '';
	
	}
		
	/**
   * Envia a requisiçõo e carrega resultado
   *
   * @return boolean
   */
	public function load()
  {
	
		if($this->urlRequest->method == URLRequest::METHODGET){
				
			if($this->urlRequest->data != '')
				$process = \curl_init($this->urlRequest->url."?".$this->urlRequest->data);
			else
				$process = \curl_init($this->urlRequest->url);
					
			//\curl_setopt($process, CURLOPT_REFERER, $refer);
			\curl_setopt($process, CURLOPT_HTTPHEADER, $this->urlRequest->requestHeaders);
			\curl_setopt($process, CURLOPT_USERAGENT, $this->userAgent);
				
			//\curl_setopt($process,CURLOPT_ENCODING , $this->compression);
			\curl_setopt($process, CURLOPT_TIMEOUT, 30);
				
			if(!empty($this->proxy))
				\curl_setopt($process, CURLOPT_PROXY, 'proxy_ip:proxy_port');
				
			\curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
			$return = \curl_exec($process);
			\curl_close($process);
			$this->content = $return;
				
		}elseif($this->urlRequest->method == URLRequest::METHODPOST){
				
			$process = \curl_init();
			\curl_setopt($process, CURLOPT_URL, $this->urlRequest->url);
			\curl_setopt($process, CURLOPT_POST, true);
			\curl_setopt($process, CURLOPT_POSTFIELDS, $this->urlRequest->data->toArrayObject());
			\curl_setopt($process, CURLOPT_FOLLOWLOCATION  ,true);
			\curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
			//\curl_setopt($process, CURLOPT_HTTPHEADER, $this->urlRequest->requestHeaders);
			\curl_setopt($process, CURLOPT_TIMEOUT, 30);
				
			//\curl_setopt($process, CURLOPT_REFERER, $refer);
			\curl_setopt($process, CURLOPT_USERAGENT, $this->userAgent);
			//\curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
			//\curl_setopt($process, CURLOPT_ENCODING , $this->compression);
				
			if(!empty($this->proxy))
				\curl_setopt($process, CURLOPT_PROXY, 'proxy_ip:proxy_port');
	
			$return = \curl_exec($process);
			\curl_close($process);
			$this->content = $return;
			
		}
		
		if(!empty($this->content))
			return true;
		else
			return false;
		
	}
	
	/**
   * Retorna o conteúdo retornado pela requisição.
   *
   * @return string
   */
	public function getContent()
	{
	  return $this->content;
	}
	
	/**
   * Usada para serialização do objeto.
   *
   * @return \Solutio\Utils\Data\ArrayObject
   */
	public function __sleep(){
		return parent::__sleep()->concat(array('urlRequest', 'userAgent', 'proxy', 'content'));
	}

}