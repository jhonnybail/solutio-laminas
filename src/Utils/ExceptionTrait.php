<?php

/**
 * Solutio.Me
 *
 * @package     Solutio
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio;

use Solutio\Utils\Data\String,
	  Solutio\Utils\Data\ArrayObject;

/**
 * Classe padrão de Excessão.
 */
class ExceptionTrait
{
	
  /**
   * Função estática para formatar o código de excessão.
   *
   * @param  int $code
   * @return string
   */
  protected static function formatCode($code)
  {
  	
  	$code = new String($code);

  	if((int) $code->length() == 1)
  		$code = "0000".$code;
  	elseif((int) $code->length() == 2)
  		$code = "000".$code;
  	elseif((int) $code->length() == 3)
  		$code = "00".$code;
  	elseif((int) $code->length() == 4)
  		$code = "0".$code;
  	else
  		$code = $code;
  		
	  return $code;
  	
  }
    
  /**
   * Retorna a mensagem de erro completa, juntos com código do erro, linha e arquivo.
   *
   * @return string
   */
  public function showError()
  {
  	return $this->getMessage()." (Error: #".self::formatCode($this->getCode())." on line ".$this->getLine()." in file ".$this->getFile().")";
  }
    
  /**
   * Cria uma lista de mensagens apropriadas de acordo com o código do erro.
   *
   * @param  int $code
   * @return string
   */
  public static function getMessageFromCode($code)
  {
    $messages    = new ArrayObject;
  	$messages[1] = 'Não é um número válido';
  	$messages[2] = 'Argumento está em branco';
  	$messages[3] = 'Array inválida';
  	$messages[4] = 'A função curl não está habilitada';
  	$messages[5] = 'O valor passado para o atributo data não é um objeto URLVariables válido';
  	$messages[6] = 'URL não existe';
  	$messages[7] = 'O caminho não existe';
  	$messages[8] = 'O caminho deve ser local, não deve ser HTTP';
  	$messages[9] = 'Requisição não é um arquivo';
  	$messages[10] = 'A classe finfo não está habilitada';
  	$messages[11] = 'Não foi possível alterar a permissão do arquivo ou diretório';
  	$messages[12] = 'Requesição não é um diretório';
  	$messages[13] = 'A Requesição ultrapassa o limite de memória do sistema';
  	$messages[14] = 'Tipo de imagem não suportada';
  	$messages[15] = 'Arquivo não está aberto ou não foi criado';
  	$messages[16] = 'Não foi possível mover';
  	$messages[17] = 'Não foi possível renomear';
  	$messages[18] = 'O Argumento não é um booleano';
  	$messages[19] = 'O Argumento não é um DOM válido';
  	$messages[20] = 'Classe não está habilitada';
  	$messages[21] = 'Valor informado inválido';
  	$messages[22] = 'Categoria com filhos cadastrados';
  	$messages[23] = 'Pontos requeridos maior que a quantidade de pontos no pré-pago';
  	$messages[24] = 'Não exitem créditos disponiveis para o cliente informado';
  	$messages[25] = 'Não há saldo para realizar esta operação';
    $messages[26] = 'Objeto não encontrado';
    $messages[27] = 'Registro já existe';
    
    return $messages[$code];
  }
    
  /**
   * Inicializa uma exceção através do código.
   *
   * @param  int $code
   * @param  int $message Optional
   * @return \Solutio\Exception
   */
  public static function FromCode($code, $message = null)
  {
    $class = __CLASS__;
    return new $class($message ? $message : self::getMessageFromCode($code), $code);
  }
    
}