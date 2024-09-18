<?php 

namespace RtfHtmlPhp\Html;
class State
{
  private $bold;
  private $italic;
  private $underline;
  private $strike;
  private $hidden;
  private $fontsize;
  private $fontcolor;
  private $background;
  private $hcolor;
  private $font;
  
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
  public static function setFontInFontTable(int $index, Font $font) {
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
    if(isset($this->font)) {
      $font = self::$fonttbl[$this->font];
      $style .= $font->toStyle();
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

  /**
   * Get the value of bold
   */
  public function getBold()
  {
    return $this->bold;
  }

  /**
   * Set the value of bold
   */
  public function setBold($bold): self
  {
    $this->bold = $bold;

    return $this;
  }

  /**
   * Get the value of italic
   */
  public function getItalic()
  {
    return $this->italic;
  }

  /**
   * Set the value of italic
   */
  public function setItalic($italic): self
  {
    $this->italic = $italic;

    return $this;
  }

  /**
   * Get the value of underline
   */
  public function getUnderline()
  {
    return $this->underline;
  }

  /**
   * Set the value of underline
   */
  public function setUnderline($underline): self
  {
    $this->underline = $underline;

    return $this;
  }

  /**
   * Get the value of strike
   */
  public function getStrike()
  {
    return $this->strike;
  }

  /**
   * Set the value of strike
   */
  public function setStrike($strike): self
  {
    $this->strike = $strike;

    return $this;
  }

  /**
   * Get the value of hidden
   */
  public function getHidden()
  {
    return $this->hidden;
  }

  /**
   * Set the value of hidden
   */
  public function setHidden($hidden): self
  {
    $this->hidden = $hidden;

    return $this;
  }

  /**
   * Get the value of fontsize
   */
  public function getFontsize()
  {
    return $this->fontsize;
  }

  /**
   * Set the value of fontsize
   */
  public function setFontsize($fontsize): self
  {
    $this->fontsize = $fontsize;

    return $this;
  }

  /**
   * Get the value of fontcolor
   */
  public function getFontcolor()
  {
    return $this->fontcolor;
  }

  /**
   * Set the value of fontcolor
   */
  public function setFontcolor($fontcolor): self
  {
    $this->fontcolor = $fontcolor;

    return $this;
  }

  /**
   * Get the value of background
   */
  public function getBackground()
  {
    return $this->background;
  }

  /**
   * Set the value of background
   */
  public function setBackground($background): self
  {
    $this->background = $background;

    return $this;
  }

  /**
   * Get the value of hcolor
   */
  public function getHcolor()
  {
    return $this->hcolor;
  }

  /**
   * Set the value of hcolor
   */
  public function setHcolor($hcolor): self
  {
    $this->hcolor = $hcolor;

    return $this;
  }

  /**
   * Get the value of font
   */
  public function getFont()
  {
    return $this->font;
  }

  /**
   * Set the value of font
   */
  public function setFont($font): self
  {
    $this->font = $font;

    return $this;
  }
}