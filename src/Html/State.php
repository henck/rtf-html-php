<?php 

namespace RtfHtmlPhp\Html;

class State
{
  public static $fonttbl = array();
  public static $colortbl = array();
  private static $highlight = array(
      1 => 'Black',
      2 => 'Blue',
      3 => 'Cyan',
      4 => 'Green',
      5 => 'Magenta',
      6 => 'Red',
      7 => 'Yellow',
      8 => 'Unused',
      9 =>  'DarkBlue',
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

  public function Reset($defaultFont = null)
  {
    $this->bold = False;
    $this->italic = False;
    $this->underline = False;
    $this->strike = False;
    $this->hidden = False;
    $this->fontsize = 0;
    $this->fontcolor = null;
    $this->background = null;
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
    if(isset($this->font)) {
      if (isset(self::$fonttbl[$this->font]->fontfamily))
        $style .= "font-family:" . self::$fonttbl[$this->font]->fontfamily . ";";
    }
    if($this->fontsize != 0) $style .= "font-size:{$this->fontsize}px;";
    // Font color:
    if(isset($this->fontcolor)) {
      // Check if color is set. in particular when it's the 'auto' color
      if (array_key_exists($this->fontcolor, self::$colortbl) && self::$colortbl[$this->fontcolor])
        $style .= "color:" . self::$colortbl[$this->fontcolor] . ";";
    }
    // Background color:
    if (isset($this->background)) {
      // Check if color is set. in particular when it's the 'auto' color
      if (self::$colortbl[$this->background])
        $style .= "background-color:" . self::$colortbl[$this->background] . ";";
    
    // Highlight color:
    } elseif (isset($this->hcolor)) {       
      if (isset(self::$highlight[$this->hcolor]))
        $style .= "background-color:" . self::$highlight[$this->hcolor] . ";";
    }
    
    return $style;
  }

  public function isLike($state)
  {
    if (!($state instanceof State))
      return False;
    if ($this->bold != $state->bold)
      return False;
    if ($this->italic != $state->italic)
      return False;
    if ($this->underline != $state->underline)
      return False;
    if ($this->strike != $state->strike)
      return False;
    if ($this->hidden != $state->hidden)
      return False;
    if ($this->fontsize != $state->fontsize)
      return False;
    
    // Compare Font Color
    if (isset($this->fontcolor)) {
      if (!isset($state->fontcolor))
        return False;
      elseif ($this->fontcolor != $state->fontcolor)
        return False;
    } elseif (isset($state->fontcolor))
      return False;
    
    // Compare Background-color
    if (isset($this->background)) {
      if (!isset($state->background))
        return False;
      elseif ($this->background != $state->background)
        return False;
    } elseif (isset($state->background))
      return False;
    
    // Compare Background-color
    if (isset($this->hcolor)) {
      if (!isset($state->hcolor))
        return False;
      elseif ($this->hcolor != $state->hcolor)
        return False;
    } elseif (isset($state->hcolor))
      return False;
    
    if (isset($this->font)) {
      if (!isset($state->font))
        return False;
      elseif (  array_key_exists($this->font, self::$fonttbl) && self::$fonttbl[$this->font]->fontfamily != 
                self::$fonttbl[$state->font]->fontfamily)
        return False;        
    } elseif (isset($state->font))
      return False;
    
    return True;
  }
}