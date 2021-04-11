<?php

namespace RtfHtmlPhp\Html;

class Font
{
  public $family;
  public $name;
  public $charset;
  public $codepage;
  
  public function toStyle(): string {
    $list = array();
    if($this->name) array_push($list, $this->name);
    if($this->family) array_push($list, $this->family);
    if(sizeof($list) == 0) return "";
    return "font-family:" . implode(',', $list) . ";";
  }
}