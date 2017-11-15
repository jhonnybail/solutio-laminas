<?php

namespace Solutio\Doctrine;

trait EntityCandidateIdentifierTrait
{
  /**
   * @ORM\Column(name="id", type="string", length=36, nullable=false)
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