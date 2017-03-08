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
 * Classe substituta de Integer e Float.
 */
class Number
{
	
	/**
   * Guarda o numero passada.
   * @var float|integer
   */
	private $number;
	
	/**
   * Construtor
   *
   * @param   float|int    $numero
   * @throws  \Solutio\InvalidArgumentException
   */
	public function __construct($numero = 0)
  {
		if(!is_numeric($numero)){
			throw InvalidArgumentException::FromCode(1);
		}else
			$this->number = $numero;
	}

	/**
   * Retorna uma String, com a quantidade de decimais especificada.
   *
   * @param  int	 $decimals
   * @param  string	$decimal
   * @param  string	$milhar
   * @return string
   */
	public function toFixed($decimals, $decimal = '.', $milhar = ',')
  {
		return number_format($this->number, $decimals, $decimal, $milhar);
	}
	
	/**
   * Retorna o número em formato de String.
   * @return string
   */
	public function __toString()
  {
		return (string) $this->toString();
	}
	
	/**
   * Retorna o valor do número do Objeto.
   * @return float|integer
   */
	public function getValue()
  {
		return $this->number;
	}

	/**
   * Verifica se realmente é um objeto Number ou é um número válido e retorna o número.
   * 
   * @param   \TemTudoAqui\Utils\Data\Number|float|int    $num
   * @return  mixed
   */
	public static function VerifyNumber($num)
  {
		if(is_object($num)){
			if($num instanceof Number)
				return $num->getValue();
			else
				return false;
		}elseif(is_numeric($num))
			return $num;
		elseif($num == 0 || $num == "0")
			return 0;
		else
			return false;
	}

  /**
   * Usada para serialização do objeto.
   *
   * @return \TemTudoAqui\Utils\Data\ArrayObject
   */
  public function __sleep()
  {
		return new ArrayObject(array('number'));
	}
		
	/**
   * Retorna o numero em objeto StringManipulator.
   * @return \Solutio\Utils\Data\StringManipulator
   */
	public function toString()
  {
		return new StringManipulator($this->number);
	}
	
	/**
   * Chamado quando é destruido o objeto.
   * @return void
   */
	public function __destruct()
  {
  }

  /**
   * Cria uma instancia estáticamente.
   *
   * @param  float|int    $num
   * @return \TemTudoAqui\Utils\Data\Number
   */
	public static function GetInstance($num = 0)
	{
		return new Number($num);
	}
	
}