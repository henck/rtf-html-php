<?php 

namespace RtfHtmlPhp;

class ControlSymbol extends Element
{
  public $symbol;
  public $parameter = 0;

  public function dump($level)
  {
    $this->Indent($level);
    echo "SYMBOL {$this->symbol} ({$this->parameter})\n";
  }    
}