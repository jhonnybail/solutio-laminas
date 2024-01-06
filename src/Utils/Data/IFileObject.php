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
 * InterFace para implementação de classes que abstraem arquivos.
 */
interface IFileObject
{
	/*
	 * Constructor
   */
	public function __construct();
	
	/**
   * Abre o arquivo.
   *
   * @param  \Solutio\Utils\Net\URLRequest|null	$urlRequest
   * @return void
   */
	public function open(URLRequest $urlRequest = null);
	
	/**
   * Retorna os dados do objeto.
   *
   * @return string
   */
	public function getData();
}