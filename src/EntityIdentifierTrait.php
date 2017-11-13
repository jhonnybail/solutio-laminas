<?php

namespace Solutio;

trait EntityIdentifierTrait
{
  /**
   * @var string
   *
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="NONE")
   * @ORM\Column(name="id", type="integer", nullable=false)
   */
  private $id;
  
  public function getId()
  {
    return $this->id;
  }
  
  public function setId($id)
  {
    $this->id = (int) $id;
    return $this;
  }
}