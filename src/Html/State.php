<?php 

namespace RtfHtmlPhp\Html;

class State
{
  public $bold;
  public $italic;
  public $underline;
  public $strike;
  public $hidden;
  public $fontsize;
  public $fontcolor;
  public $background;
  public $hcolor;
  public $font;

  public static $fonttbl = array();
  public static $colortbl = array();
  private static $highlight = array(
      1  => 'Black',
      2  => 'Blue',
      3  => 'Cyan',
      4  => 'Green',
      5  => 'Magenta',
      6  => 'Red',
      7  => 'Yellow',
      8  => 'Unused',
      9  => 'DarkBlue',
      10 => 'DarkCyan',
      11 => 'DarkGreen',
      12 => 'DarkMagenta',
      13 => 'DarkRed',
      14 => 'DarkYellow',
      15 => 'DarkGray',
      16 => 'LightGray'
  );
  
  public function __construct()
  {
    $this->Reset();
  }

  /* 
   * Store a font in the font table at the specified index.
   */
  public static function SetFont(int $index, Font $font) {
    State::$fonttbl[$index] = $font;
  }

  public function Reset($defaultFont = null)
  {
    $this->bold = false;
    $this->italic = false;
    $this->underline = false;
    $this->strike = false;
    $this->hidden = false;
    $this->fontsize = 0;
    $this->fontcolor = null;
    $this->background = null;
    $this->hcolor = null;
    $this->font = isset($defaultFont) ? $defaultFont : null;
  }
  
  public function PrintStyle()
  {
    $style = "";
    
    if($this->bold) $style .= "font-weight:bold;";
    if($this->italic) $style .= "font-style:italic;";
    if($this->underline) $style .= "text-decoration:underline;";
    // state->underline is a toggle switch variable so no need for
    // a dedicated state->end_underline variable
    // if($this->state->end_underline) {$span .= "text-decoration:none;";}
    if($this->strike) $style .= "text-decoration:line-through;";
    if($this->hidden) $style .= "display:none;";
    if(!is_null($this->font) && array_key_exists($this->font, self::$fonttbl)) {
      $font = self::$fonttbl[$this->font];
      $style .= $font->toStyle();
    }
    if($this->fontsize != 0) $style .= "font-size:{$this->fontsize}px;";
    // Font color:
    if(!is_null($this->fontcolor)) {
      // Check if color is set. in particular when it's the 'auto' color
      if (array_key_exists($this->fontcolor, self::$colortbl) && self::$colortbl[$this->fontcolor])
        $style .= "color:" . self::$colortbl[$this->fontcolor] . ";";
    }
    // Background color:
    if (!is_null($this->background) && array_key_exists($this->background, self::$colortbl)) {
      // Check if color is set. in particular when it's the 'auto' color
      if (self::$colortbl[$this->background])
        $style .= "background-color:" . self::$colortbl[$this->background] . ";";
    
    // Highlight color:
    } elseif (!is_null($this->hcolor)) {       
      if (isset(self::$highlight[$this->hcolor]))
        $style .= "background-color:" . self::$highlight[$this->hcolor] . ";";
    }
    
    return $style;
  }

  /* 
   * Check whether this State is equal to another State.
   */
  public function equals($state)
  {
    if (!($state instanceof State)) return false;

    if ($this->bold != $state->bold) return false;
    if ($this->italic != $state->italic) return false;
    if ($this->underline != $state->underline) return false;
    if ($this->strike != $state->strike) return false;
    if ($this->hidden != $state->hidden) return false;
    if ($this->fontsize != $state->fontsize) return false;
    
    // Compare colors
    if ($this->fontcolor != $state->fontcolor) return false;
    if ($this->background != $state->background) return false;
    if ($this->hcolor != $state->hcolor) return false;
    
    // Compare fonts
    if ($this->font != $state->font) return false;
    
    return true;
  }
}