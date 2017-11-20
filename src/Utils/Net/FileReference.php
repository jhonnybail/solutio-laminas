<?php

/**
 * Solutio.Me
 *
 * @package     Solutio\Utils\Net
 * @link        http://github.com/jhonnybail/solutio-zf2
 * @copyright   Copyright (c) 2017 Solutio.Me. (http://solutio.me)
 */
namespace Solutio\Utils\Net;

use Solutio\Utils\Data\StringManipulator,
    Solutio\Utils\Data\HTMLFile,
    Solutio\Utils\Data\XMLFile,
    Solutio\Utils\Data\ImageFile,
    Solutio\Utils\Data\File,
    Solutio\Utils\Data\IFileObject,
    Solutio\InvalidArgumentException;

/**
 * Classe para trabalhar com arquivos, como salvar e deletar.
 */
class FileReference
{
  /**
   * Salva o arquivo passado por referencia.
   *
   * @param   \Solutio\Utils\Data\IFileObject	    $file
   * @param   string								    $newPath
   * @param   string								    $newName
   * @param   string								    $newExtension
   * @throws  \Solutio\InvalidArgumentException
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\IFileObject
   */
  public static function Save(IFileObject $file, $newPath = '', $newName = '', $newExtension = '')
  {
    if(!empty($newName))
      $file->fileName = new StringManipulator($newName);
    else
      if($file->fileName == '')
        throw new InvalidArgumentException('O nome do arquivo está em branco', 2);

    if(!empty($newExtension))
      $file->extension = new StringManipulator($newExtension);
    else
      if($file->extension == '')
        throw new InvalidArgumentException('A extensão do tipo arquivo está em branco', 2);

    if(!empty($newPath))
      $newPath = new StringManipulator((string) $newPath);
    else
      $newPath = new StringManipulator(dirname($file->url));

    if($newPath->search("http:\/\/") || $newPath->search("https:\/\/"))
      throw NetException::FromCode(8);
    elseif($file->getData()->toString() == '')
      throw NetException::FromCode(15);
    elseif($file->urlRequest != null){

      if($file->urlRequest->getType() != URLRequest::URLFILETYPE)
        throw NetException::FromCode(9);
      else{

        if($newPath->toString() == ""){
           
          if(!file_exists(dirname($file->urlRequest->url)))
            throw NetException::FromCode(7);
          else{
            
            $f = fopen(dirname($file->urlRequest->url)."/".$file->fileName.".".$file->extension, 'w');
            fwrite($f, (string) $file->getData());
            fclose($f);
            
            $urlR = new URLRequest(dirname($file->urlRequest->url)."/".$file->fileName.".".$file->extension);
            
            if($file->extension == 'html' || $file->extension == 'htm' || $file->extension == 'xhtml')
              return new HTMLFile($urlR);
            elseif($file->extension == 'xml')
              return new XMLFile($urlR);
            elseif($file->extension->toLowerCase() == ImageFile::IMAGETYPEJPEG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEJPG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEPNG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEGIF)
              return new ImageFile($urlR);
            else
              return new File($urlR);
             
          }

        }elseif(!file_exists((string) $newPath))
          throw NetException::FromCode(7);
        else{

          $f = fopen(((string) $newPath)."/".$file->fileName.".".$file->extension, 'w');
          fwrite($f, (string) $file->getData());
          fclose($f);

          $urlR = new URLRequest(((string) $newPath)."/".$file->fileName.".".$file->extension);

          if($file->extension == 'html' || $file->extension == 'htm' || $file->extension == 'xhtml')
            return new HTMLFile($urlR);
          elseif($file->extension == 'xml')
            return new XMLFile($urlR);
          elseif($file->extension->toLowerCase() == ImageFile::IMAGETYPEJPEG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEJPG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEPNG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEGIF)
            return new ImageFile($urlR);
          else
            return new File($urlR);
            
        }

      }
        
    }else{

      if(empty($newPath)){

        if(!file_exists(dirname($file->urlRequest->url)))
          throw NetException::FromCode(7);
        else{

          $f = fopen(dirname($file->urlRequest->url)."/".$file->fileName.".".$file->extension, 'w+');
          fwrite($f, (string) $file->getData());
          fclose($f);

          $urlR = new URLRequest(dirname($file->urlRequest->url)."/".$file->fileName.".".$file->extension);

          if($file->extension == 'html' || $file->extension == 'htm' || $file->extension == 'xhtml')
            return new HTMLFile($urlR);
          elseif($file->extension == 'xml')
            return new XMLFile($urlR);
          elseif($file->extension->toLowerCase() == ImageFile::IMAGETYPEJPEG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEJPG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEPNG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEGIF)
            return new ImageFile($urlR);
          else
            return new File($urlR);

        }
         
      }elseif(!file_exists($newPath))
        throw NetException::FromCode(7);
      else{

        $f = fopen($newPath."/".$file->fileName.".".$file->extension, 'w+');
        fwrite($f, (string) $file->getData());
        fclose($f);

        $urlR = new URLRequest($newPath."/".$file->fileName.".".$file->extension);

        if($file->extension == 'html' || $file->extension == 'htm' || $file->extension == 'xhtml')
          return new HTMLFile($urlR);
        elseif($file->extension == 'xml')
          return new XMLFile($urlR);
        elseif($file->extension->toLowerCase() == ImageFile::IMAGETYPEJPEG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEJPG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEPNG || $file->extension->toLowerCase() == ImageFile::IMAGETYPEGIF)
          return new ImageFile($urlR);
        else
          return new File($urlR);

      }
       
    }
  }

  /**
   * Deleta o arquivo passado por refêrencia.
   *
   * @param   mixed $file
   * @throws  \Solutio\Utils\Net\NetException
   * @return  \Solutio\Utils\Data\IFileObject
   */
  public static function Delete($file)
  {
    $returnFile = '';
    $path       = '';
    if($file instanceof IFileObject){
      $path = $file->urlRequest->url;
      $returnFile = $file;
    }elseif($file instanceof StringManipulator)
      $path = $file->toString();
    elseif(!empty($file))
      $path = $file;
      
    $path = new StringManipulator((string) $path);	
    
    if(!empty($path)){
        
      $urlR = new URLRequest($path);
        
      if($path->search("http:\/\/") || $path->search("https:\/\/"))
        throw NetException::FromCode(8);
      elseif($urlR->getType() != URLRequest::URLFILETYPE)
        throw NetException::FromCode(9);
      elseif(file_exists($path)){
        
        if(!($file instanceof File)){
          
          $urlR 		= new URLRequest($path);
          $div1 		= explode(".", $path);
          $extension 	= new StringManipulator($div1[count($div1)-1]);
          
          if($extension->toString() == 'html' || $extension->toString() == 'htm' || $extension->toString() == 'xhtml')
            $returnFile = new HTMLFile($urlR);
          elseif($extension->toString() == 'xml')
            $returnFile = new XMLFile($urlR);
          elseif($extension->toLowerCase()->toString() == ImageFile::IMAGETYPEJPEG || $extension->toLowerCase()->toString() == ImageFile::IMAGETYPEJPG || $extension->toLowerCase()->toString() == ImageFile::IMAGETYPEPNG || $extension->toLowerCase()->toString() == ImageFile::IMAGETYPEGIF)
            $returnFile = new ImageFile($urlR);
          else
            $returnFile = new File($urlR);
          
          $returnFile->open();
            
        }else{
          $returnFile = $file;
          $returnFile->open();
        }
        
        @unlink($path);

        return $returnFile;

      }else
        NetException::FromCode(7);
        
    }

    return $returnFile;
  }

  /**
   * Define a permissão do arquivo.
   *
   * @param   mixed $file
   * @param   string $mode
   * @throws  \Solutio\Utils\Net\NetException
   * @throws  \Solutio\InvalidArgumentException
   * @return  void
   */
  public static function Permission($file, $mode = '755')
  {
    $path = '';
    if($file instanceof IFileObject)
      $path = $file->urlRequest->url;
    elseif($file instanceof StringManipulator)
      $path = $file->toString();
    elseif(!empty($file))
      $path = $file;

    $path = new StringManipulator((string) $path);	
  
    if(!empty($path)){
  
      $urlR = new URLRequest($path);
  
      if($path->search("http:\/\/") || $path->search("https:\/\/"))
        throw NetException::FromCode(8);
      elseif($urlR->getType() != URLRequest::URLFILETYPE)
        throw NetException::FromCode(9);
      elseif(file_exists($path)){
          
        if(!empty($mode)){
          if(!chmod($path, $mode)){
            throw NetException::FromCode(11);
          }
        }else
          throw InvalidArgumentException::FromCode(9);
          
      }else
        throw NetException::FromCode(7);
        
    }
  }

  /**
   * Move o arquivo.
   *
   * @param   mixed   $file
   * @param   string  $newPath
   * @param   boolean $overwrite
   * @throws  \Solutio\Utils\Net\NetException
   * @return  void
   */
  public static function Move($file, $newPath, $overwrite = false)
  {
    if($file instanceof File)
      if(!is_null($file->urlRequest)){
        $path   = new StringManipulator((string) $file->urlRequest->url);
      }
    else
      $path 		= new StringManipulator((string) $file);

    $newPath 	= new StringManipulator((string) $newPath);

    if(!empty($path) && !empty($newPath)){
        
      $urlR 	= new URLRequest((string) $path);
        
      try{
        $urlNR	= new URLRequest(dirname((string) $newPath));
      }catch(NetException $e){
        throw new NetException("Caminho de destino inválido", 6);
      }

      if(($path->search("http:\/\/") || $path->search("https:\/\/")) && ($newPath->search("http:\/\/") || $newPath->search("https:\/\/")))
        throw NetException::FromCode(8);
      elseif($urlR->getType() != URLRequest::URLFILETYPE)
        throw NetException::FromCode(8);
      elseif(file_exists((string) $path)){

        try{
            
          if($newPath instanceof File){
            if($overwrite)
              self::Delete($newPath);
            else
              throw new NetException("Não é possível mover o arquivo pois o caminho de destino já existe", 16);
          }
            
        }catch(NetException $e){
          if($e->getCode() != 6)
            throw $e;
        }

        if($urlNR->getType() == URLRequest::URLFILETYPE || $urlNR->url != ((string) $newPath)){
          if(!@rename((string) $path, (string) $newPath))
            throw new NetException("Não foi possível mover o arquivo", 16);
        }elseif($urlNR->getType() == URLRequest::URLDIRECTORYTYPE){
          if(!@rename((string) $path, ((string) $newPath).basename($path)))
            throw new NetException("Não foi possível mover o arquivo", 16);
        }

      }else
        throw NetException::FromCode(7);
        
    }
  }

  /**
   * Renomeia o arquivo.
   *
   * @param   mixed	  $file
   * @param   string	$newName
   * @throws  \Solutio\Utils\Net\NetException
   * @return  void
   */
  public static function Rename($file, $newName)
  {
    $path = '';
    if($file instanceof IFileObject)
      $path = $file->urlRequest->url;
    elseif($file instanceof StringManipulator)
      $path = $file->toString();
    elseif(!empty($file))
      $path = $file;
    
    $path = new StringManipulator((string) $path);
    
    if(!empty($path)){
        
      $urlR 	= new URLRequest($path);
        
      try{
        new URLRequest(dirname($path)."/".$newName);
        throw new NetException("O novo nome escolhido já existe", 17);
      }catch(NetException $e){

        if($e->getCode() != 6)
          throw $e;
          
        if($path->search("http:\/\/") || $path->search("https:\/\/"))
          throw NetException::FromCode(8);
        elseif($urlR->getType() != URLRequest::URLFILETYPE)
          throw NetException::FromCode(9);
        elseif(file_exists($path)){
            
          if(!@rename($path, dirname($path)."/".$newName))
            throw new NetException("Não foi possível renomear o arquivo", 17);
            
        }else
          throw NetException::FromCode(7);

      }
        
    }
  }
}