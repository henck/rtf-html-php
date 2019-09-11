<?php 

namespace RtfHtmlPhp;

class ControlSymbol extends Element
{
  public $symbol;
  public $parameter = 0;

  public function dump($level)
  {
    echo "<div style='color:blue'>";
    $this->Indent($level);
    echo "SYMBOL {$this->symbol} ({$this->parameter})";
    echo "</div>";
  }    
}