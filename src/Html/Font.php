<?php

namespace RtfHtmlPhp\Html;

class Font
{
  public $family;
  public $name;
  public $charset;
  public $codepage;
  public $fprq;
  
  public function toStyle(): string {
    $list = array();
    // If the name is in double quotes replace them with single quotes
    if($this->name) $this->name = preg_replace("/^\"(.*)\"$/", '\'$1\'', $this->name);
    if($this->name) array_push($list, $this->name);
    if($this->family) array_push($list, $this->family);
    if(sizeof($list) == 0) return "";
    return "font-family:" . join(', ', $list) . ";";
  }
}

