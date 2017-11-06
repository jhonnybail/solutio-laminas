<?php

namespace Solutio;

trait EntityCandidateIdentifierTrait
{
  /**
   * @var string
   *
   * @ORM\Column(name="id_occ", type="string", length=36, nullable=false)
   */
  private $id;
  
  public function getId()
  {
    return $this->id;
  }
  
  public function setId($id)
  {
    $this->id = $id;
    return $this;
  }
}