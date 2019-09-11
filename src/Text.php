<?php 

namespace RtfHtmlPhp;

class Text extends Element
{
  public $text;

  /*
   * Create a new Text instance with string content.
   */
  public function __construct($text)
  {
    $this->text = $text;
  }

  public function dump($level)
  {
    echo "<div style='color:red'>";
    $this->Indent($level);
    echo "TEXT {$this->text}";
    echo "</div>";
  }    
}