<?php
  /**
   * RTF parser/formatter
   *
   * This code reads RTF files and formats the RTF data to HTML.
   *
   * PHP version 5
   *
   * @author     Alexander van Oostenrijk
   * @copyright  2014 Alexander van Oostenrijk
   * @license    GNU
   * @version    1
   * @link       http://www.independent-software.com
   * 
   * Sample of use:
   * 
   * $reader = new RtfReader();
   * $rtf = file_get_contents("test.rtf"); // or use a string
   * if ($reader->Parse($rtf)) {
   *   //$reader->root->dump(); // to see what the reader read
   *   $formatter = new RtfHtml();
   *   echo $formatter->Format($reader->root);
   * } else { // Parse error occured.. bad RTF file
   *   echo "Parse error occured";
   * }
   *
   */
 
  class RtfElement
  {
    protected function Indent($level)
    {
      for($i = 0; $i < $level * 2; $i++) echo "&nbsp;";
    }
  }
 
  class RtfGroup extends RtfElement
  {
    public $parent;
    public $children;
 
    public function __construct()
    {
      $this->parent = null;
      $this->children = array();
    }
 
    public function GetType()
    {
      // No children?
      if(sizeof($this->children) == 0) return null;
      // First child not a control word?
      $child = $this->children[0];
      if($child instanceof RtfControlWord)
      return $child->word;
      elseif ($child instanceof RtfControlSymbol)
        return ($child->symbol == '*') ? '*' : null;
      
      return null;
    }    
 
    public function IsDestination()
    {
      // No children?
      if(sizeof($this->children) == 0) return null;
      // First child not a control symbol?
      $child = $this->children[0];
      if(!$child instanceof RtfControlSymbol) return null;
      return $child->symbol == '*';
    }
 
    public function dump($level = 0)
    {
      echo "<div>";
      $this->Indent($level);
      echo "{";
      echo "</div>";
 
      foreach($this->children as $child)
      {
        if($child instanceof RtfGroup) {
          if ($child->GetType() == "fonttbl") continue;
          if ($child->GetType() == "colortbl") continue;
          if ($child->GetType() == "stylesheet") continue;
          if ($child->GetType() == "info") continue;
          // Skip any pictures:
          if (substr($child->GetType(), 0, 4) == "pict") continue;
          if ($child->IsDestination()) continue;
        }
        $child->dump($level + 2);
      }
 
      echo "<div>";
      $this->Indent($level);
      echo "}";
      echo "</div>";
    }
  }
 
  class RtfControlWord extends RtfElement
  {
    public $word;
    public $parameter;
 
    public function dump($level)
    {
      echo "<div style='color:green'>";
      $this->Indent($level);
      echo "WORD {$this->word} ({$this->parameter})";
      echo "</div>";
    }
  }
 
  class RtfControlSymbol extends RtfElement
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
 
  class RtfText extends RtfElement
  {
    public $text;
 
    public function dump($level)
    {
      echo "<div style='color:red'>";
      $this->Indent($level);
      echo "TEXT {$this->text}";
      echo "</div>";
    }    
  }
 
  class RtfReader
  {
    public $root = null;
 
    protected function GetChar()
    {
      $this->char = null;
      if ($this->pos < strlen($this->rtf)) {
        $this->char = $this->rtf[$this->pos++];
      } else {
        $this->err = "Tried to read past EOF, RTF is probably truncated";
      }
    }
 
    protected function ParseStartGroup()
    {
      // Store state of document on stack.
      $group = new RtfGroup();
      if($this->group != null) $group->parent = $this->group;
      if($this->root == null) {
        // First group of the RTF document
        $this->group = $group;
        $this->root = $group;
        // Create uc stack and insert the first default value
        $this->uc = array(1);
      } else {
        array_push($this->uc, end($this->uc));
        array_push($this->group->children, $group);
        $this->group = $group;
      }
    }
 
    protected function is_letter()
    {
      if(ord($this->char) >= 65 && ord($this->char) <= 90) return True;
      if(ord($this->char) >= 97 && ord($this->char) <= 122) return True;
      return False;
    }
 
    protected function is_digit()
    {
      if(ord($this->char) >= 48 && ord($this->char) <= 57) return True;
      return False;
    }
 
    /*
     *  Checks for end of line (EOL)
     */
    protected function is_endofline()
    {
      if ($this->char == "\r" || $this->char == "\n") {
        // Checks for a Windows/Acron type EOL
        if( $this->rtf[$this->pos] == "\n" || $this->rtf[$this->pos] == "\r" ) {
          $this->GetChar();
        }
        return TRUE;
      }
      return FALSE;
    }
    
    /*
     *  Checks for a space delimiter
     */
    protected function is_space_delimiter()
    {
      if ($this->char == " " || $this->is_endofline()) return TRUE;
      return FALSE;
    }

    protected function ParseEndGroup()
    {
      // Retrieve state of document from stack.
      $this->group = $this->group->parent;
      // Retrieve last uc value from stack
      array_pop($this->uc);
    }
 
    protected function ParseControlWord()
    {
      $this->GetChar();
      $word = "";

      while($this->is_letter())
      {
        $word .= $this->char;
        $this->GetChar();
      }
 
      // Read parameter (if any) consisting of digits.
      // Paramater may be negative.
      $parameter = null;
      $negative = False;
      if($this->char == '-') {
        $this->GetChar();
        $negative = True;
      }
      while($this->is_digit())
      {
        if($parameter == null) $parameter = 0;
        $parameter = $parameter * 10 + $this->char;
        $this->GetChar();
      }
      // if no parameter assume control word's default (usually 1)
      // if no default then assign 0 to the parameter
      if($parameter === null) $parameter = 1;
      
      // convert to a negative number when applicable
      if($negative) $parameter = -$parameter;
      
      // Update uc value
      if ($word == "uc") {
        array_pop($this->uc);
        $this->uc[] = $parameter;
      }
      
      // Skip space delimiter
      if(!$this->is_space_delimiter()) $this->pos--;
      
      // If this is \u, then the parameter will be followed 
      // by {$this->uc} characters.
      if($word == "u") {
        // Convert parameter to unsigned decimal unicode
        if($negative) $parameter = 65536 + $parameter;
        
        // Will ignore replacement characters $uc times
        $uc = end($this->uc);
        while ($uc > 0) {
          $this->GetChar();          
          // If the replacement character is encoded as
          // hexadecimal value \'hh then jump over it
          if($this->char == '\\' && $this->rtf[$this->pos]=='\'')
              $this->pos = $this->pos + 3;
          
          // Break if it's an RTF scope delimiter
          elseif ($this->char == '{' || $this->char == '{')
            break;
          
          // - To include an RTF delimiter in skippable data, it must be
          //  represented using the appropriate control symbol (that is,
          //  escaped with a backslash,) as in plain text.
          // - Any RTF control word or symbol is considered a single character
          //  for the purposes of counting skippable characters. For this reason
          //  it's more appropriate to create à $skip flag and let the Parse()
          //  function take care of the skippable characters.
          $uc--;
        }
      }
             
      $rtfword = new RtfControlWord();
      $rtfword->word = $word;
      $rtfword->parameter = $parameter;
      array_push($this->group->children, $rtfword);
    }
 
    protected function ParseControlSymbol()
    {
      // Read symbol (one character only).
      $this->GetChar();
      $symbol = $this->char;
 
      // Symbols ordinarily have no parameter. However, 
      // if this is \', then it is followed by a 2-digit hex-code:
      $parameter = 0;
      // Treat EOL symbols as \par control word
      if ($this->is_endofline()) {
        $rtfword = new RtfControlWord();
        $rtfword->word = 'par';
        $rtfword->parameter = $parameter;
        array_push($this->group->children, $rtfword);
        return;
      
      } elseif($symbol == '\'') {
        $this->GetChar(); 
        $parameter = $this->char;
        $this->GetChar(); 
        $parameter = hexdec($parameter . $this->char);
      }
 
      $rtfsymbol = new RtfControlSymbol();
      $rtfsymbol->symbol = $symbol;
      $rtfsymbol->parameter = $parameter;
      array_push($this->group->children, $rtfsymbol);
    }
 
    protected function ParseControl()
    {
      // Beginning of an RTF control word or control symbol.
      // Look ahead by one character to see if it starts with
      // a letter (control world) or another symbol (control symbol):
      $this->GetChar();
      $this->pos--;
      if($this->is_letter()) 
        $this->ParseControlWord();
      else
        $this->ParseControlSymbol();
    }
 
    protected function ParseText()
    {
      // Parse plain text up to backslash or brace,
      // unless escaped.
      $text = "";
      $terminate = False;
      do
      {
        // Ignore EOL characters
        if($this->char == "\r" || $this->char == "\n") {
          $this->GetChar();
          continue;
        }
        // Is this an escape?
        if($this->char == '\\') {
          // Perform lookahead to see if this
          // is really an escape sequence.
          $this->GetChar();
          switch($this->char)
          {
            case '\\': break;
            case '{': break;
            case '}': break;
            default:
              // Not an escape. Roll back.
              $this->pos = $this->pos - 2;
              $terminate = True;
              break;
          }
        } elseif($this->char == '{' || $this->char == '}') {
          $this->pos--;
          $terminate = True;
        }
 
        if(!$terminate) {
          // Save plain text
          $text .= $this->char;
          $this->GetChar();
        } 
      } 
      while(!$terminate && $this->pos < $this->len);
 
      $rtftext = new RtfText();
      $rtftext->text = $text;

      // If group does not exist, then this is not a valid RTF file.
      // Throw an exception.
      if($this->group == null) {
        $err = "Parse error occured";
        trigger_error($err);
        throw new Exception($err);
      }

      array_push($this->group->children, $rtftext);
    }
 
    /*
     * Attempt to parse an RTF string. Parsing returns TRUE on success
     * or FALSE on failure
     */
    public function Parse($rtf)
    {
      try {
        $this->rtf = $rtf;
        $this->pos = 0;
        $this->len = strlen($this->rtf);
        $this->group = null;
        $this->root = null;

        while($this->pos < $this->len)
        {
          // Read next character:
          $this->GetChar();

          // Ignore \r and \n
          if($this->char == "\n" || $this->char == "\r") continue;

          // What type of character is this?
          switch($this->char)
          {
            case '{':
              $this->ParseStartGroup();
              break;
            case '}':
              $this->ParseEndGroup();
              break;
            case '\\':
              $this->ParseControl();
              break;
            default:
              $this->ParseText();
              break;
          }
        }

        return True;
      }
      catch(Exception $ex) {
        return False;
      }
    }
  }
 
  class RtfImage
  {
    public function __construct()
    {
      $this->Reset();
    }
    
    public function Reset()
    {
      $this->format = 'bmp';
      $this->width = 0; // in xExt if wmetafile otherwise in px
      $this->height = 0; // in yExt if wmetafile otherwise in px
      $this->goalWidth = 0; // in twips
      $this->goalHeight = 0; // in twips
      $this->pcScaleX = 100; // 100%
      $this->pcScaleY = 100; // 100%
      $this->binarySize = null; // Number of bytes of the binary data
      $this->ImageData = null; // Binary or Hexadecimal Data
    }
    
    public function PrintImage()
    {
      // <img src="data:image/{FORMAT};base64,{#BDATA}" />
      $output = "<img src=\"data:image/{$this->format};base64,";
      
      if (isset($this->binarySize)) { // process binary data
        return;
      } else { // process hexadecimal data
        $output .= base64_encode(pack('H*',$this->ImageData));
      }
      
      $output .= "\" />";
      return $output;
    }
  }
  
  class RtfFont
  {
      public $fontfamily;
      public $fontname;
      public $charset;
      public $codepage;         
  }
 
  class RtfState
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
        if (self::$colortbl[$this->fontcolor])
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
      if (!($state instanceof RtfState))
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
        elseif (  self::$fonttbl[$this->font]->fontfamily != 
                  self::$fonttbl[$state->font]->fontfamily)
          return False;        
      } elseif (isset($state->font))
        return False;
      
      return True;
    }
  }
 
  class RtfHtml
  {
    private $output = '';
    private $encoding;
    private $defaultFont;

    // Initialise Encoding
    public function __construct($encoding = 'HTML-ENTITIES')
    {
      if ($encoding != 'HTML-ENTITIES') {
        // Check if mbstring extension is loaded
        if (!extension_loaded('mbstring')) {
          trigger_error("PHP mbstring extension not enabled, reverting back to HTML-ENTITIES");
          $encoding = 'HTML-ENTITIES';
        // Check if the encoding is reconized by mbstring extension
        } elseif (!in_array($encoding, mb_list_encodings())){
          trigger_error("Unrecognized Encoding, reverting back to HTML-ENTITIES");
          $encoding = 'HTML-ENTITIES';
        }
      }
      $this->encoding = $encoding;
    }
    
    public function Format($root)
    {
      // Keep track of style modifications
      $this->previousState = null;
      // and create a stack of states
      $this->states = array();
      // Put an initial standard state onto the stack
      $this->state = new RtfState();
      array_push($this->states, $this->state);
      // Keep track of opened html tags
      $this->openedTags = array('span' => False, 'p' => False);
      // Create the first paragraph
      $this->OpenTag('p');
      // Begin format
      $this->ProcessGroup($root);
      // Remove the last opened <p> tag and return
      return substr($this->output ,0, -3);
    }
    
     protected function ExtractFontTable($fontTblGrp)
    {
       // {' \fonttbl (<fontinfo> | ('{' <fontinfo> '}'))+ '}'
       // <fontnum><fontfamily><fcharset>?<fprq>?<panose>?
       // <nontaggedname>?<fontemb>?<codepage>? <fontname><fontaltname>? ';'

      $fonttbl = array();
      $c = count($fontTblGrp);
      
      for ($i=1;$i<$c;$i++){
        $fname = '';
        $fN = null;
        foreach ($fontTblGrp[$i]->children as $child){
          
          if ($child instanceof RtfControlWord){
            switch ($child->word) {
              case 'f':
                $fN = $child->parameter;
                $fonttbl[$fN] = new RtfFont();
                break;

              // Font family names
              case 'froman': $fonttbl[$fN]->fontfamily = "serif"; break;
              case 'fswiss': $fonttbl[$fN]->fontfamily = "sans-serif"; break;
              case 'fmodern': $fonttbl[$fN]->fontfamily = "monospace"; break;
              case 'fscript': $fonttbl[$fN]->fontfamily = "cursive"; break;
              case 'fdecor': $fonttbl[$fN]->fontfamily = "fantasy"; break;
              // case 'fnil': break; // default font
              // case 'ftech': break; // symbol
              // case 'fbidi': break; // bidirectional font                      
              case 'fcharset': // charset
                $fonttbl[$fN]->charset = 
                  $this->GetEncodingFromCharset($child->parameter);
                break;              
              case 'cpg': // code page
                $fonttbl[$fN]->codepage = 
                  $this->GetEncodingFromCodepage($child->parameter);
                break;
              case 'fprq': // Font pitch
                $fonttbl[$fN]->fprq = $child->parameter;
                break;
              default: break;
            }
          } elseif ($child instanceof RtfText) {
            // Save font name
            $fname .= $child->text;
          } 
	  /*
	  elseif ($child instanceof RtfGroup) {
            // possible subgroups:
            // '{\*' \falt #PCDATA '}' = alternate font name
            // '{\*' \fontemb <fonttype> <fontfname>? <data>? '}'
            // '{\*' \fontfile <codepage>? #PCDATA '}'
            // '{\*' \panose <data> '}'
            continue;
          } elseif ($child instanceof RtfControlSymbol) {
            // the only authorized symbol here is '*':
            // \*\fname = non tagged file name (only WordPad uses it)
            continue;
          } 
	  */       
        }
        // Remove end ; delimiter from font name
        $fonttbl[$fN]->fontname = substr($fname,0,-1);
        
        // Save extracted Font
        RtfState::$fonttbl = $fonttbl;
      }      
    }
    
    protected function ExtractColorTable($colorTblGrp) {
      // {\colortbl;\red0\green0\blue0;}
      // Index 0 of the RTF color table  is the 'auto' color
      $colortbl = array(); 
      $c = count($colorTblGrp);
      $color = '';
      for ($i=1; $i<$c; $i++) { // Iterate through colors
        if($colorTblGrp[$i] instanceof RtfControlWord) {
          // Extract RGB color and convert it to hex string
          $color = sprintf('#%02x%02x%02x', // hex string format
                              $colorTblGrp[$i]->parameter, // red
                              $colorTblGrp[$i+1]->parameter, // green
                              $colorTblGrp[$i+2]->parameter); // blue
          $i+=2;
        } elseif($colorTblGrp[$i] instanceof RtfText) {
          // This is a delimiter ';' so
          if ($i != 1) { // Store the already extracted color
            $colortbl[] = $color;
          } else { // This is the 'auto' color
            $colortbl[] = 0;
          }
        }
      }
      RtfState::$colortbl = $colortbl;
    }
 
    protected function ExtractImage($pictGrp)
    {      
      $Image = new RtfImage();
      foreach ($pictGrp as $child) {
        if ($child instanceof RtfControlWord) {
          switch ($child->word) {
            // Picture Format
            case "emfblip": $Image->format = 'emf'; break;
            case "pngblip": $Image->format = 'png'; break;
            case "jpegblip": $Image->format = 'jpeg'; break;
            case "macpict": $Image->format = 'pict'; break;
            // case "wmetafile": $Image->format = 'bmp'; break;
            
            // Picture size and scaling
            case "picw": $Image->width = $child->parameter; break;
            case "pich": $Image->height = $child->parameter; break;
            case "picwgoal": $Image->goalWidth = $child->parameter; break;
            case "pichgoal": $Image->goalHeight = $child->parameter; break;
            case "picscalex": $Image->pcScaleX = $child->parameter; break;
            case "picscaley": $Image->pcScaleY = $child->parameter; break;
            
           // Binary or Hexadecimal Data ?
            case "bin": $Image->binarySize = $child->parameter; break;
            default: break;
          }
          
        } elseif ($child instanceof RtfText) { // store Data
          $Image->ImageData = $child->text;
        }
      }
      // output Image
      $this->output .= $Image->PrintImage();
      unset($Image);
    }
 
    protected function ProcessGroup($group)
    {
      // Can we ignore this group?      
      switch ($group->GetType())
      {
        case "fonttbl": // Extract Font table
          $this->ExtractFontTable($group->children);
          return;
        case "colortbl": // Extract color table
          $this->ExtractColorTable($group->children);
          return;      
        case "stylesheet":
          // Stylesheet extraction not yet supported
          return;
        case "info":
          // Ignore Document information
          return;
        case "pict":
          $this->ExtractImage($group->children);
          return;
        case "nonshppict":
          // Ignore alternative images
          return;        
        case "*": // Process destionation
          $this->ProcessDestination($group->children);
          return;
      }     
      
      // Pictures extraction not yet supported
      //if(substr($group->GetType(), 0, 4) == "pict") return;
      
      // Push a new state onto the stack:
      $this->state = clone $this->state;
      array_push($this->states, $this->state);
 
      foreach($group->children as $child)
        $this->FormatEntry($child);
 
      // Pop state from stack
      array_pop($this->states);
      $this->state = $this->states[sizeof($this->states)-1];
    }
    
    protected function ProcessDestination($dest)
    {
      if (!$dest[1] instanceof RtfControlWord) return;
      // Check if this is a Word 97 picture
      if ($dest[1]->word == "shppict") {
        $c = count($dest);
        for ($i=2;$i<$c;$i++)
          $this->FormatEntry($dest[$i]);
        }
    }
    
    protected function FormatEntry($entry)
    {
      if($entry instanceof RtfGroup) $this->ProcessGroup($entry);
      elseif($entry instanceof RtfControlWord) $this->FormatControlWord($entry);
      elseif($entry instanceof RtfControlSymbol) $this->FormatControlSymbol($entry);
      elseif($entry instanceof RtfText) $this->FormatText($entry);
    }
    
    protected function FormatControlWord($word)
    {
      // plain: Reset font formatting properties to default.
      // pard: Reset to default paragraph properties.
      if($word->word == "plain" || $word->word == "pard"){
        $this->state->Reset($this->defaultFont);
        
      // Font formatting properties:
      }elseif($word->word == "b"){ $this->state->bold = $word->parameter; // bold
      }elseif($word->word == "i"){ $this->state->italic = $word->parameter; // italic
      }elseif($word->word == "ul"){ $this->state->underline = $word->parameter; // underline
      }elseif($word->word == "ulnone"){ $this->state->underline = False; // no underline
      }elseif($word->word == "strike"){ $this->state->strike = $word->parameter; // strike through
      }elseif($word->word == "v"){ $this->state->hidden = $word->parameter; // hidden
      }elseif($word->word == "fs"){ $this->state->fontsize = ceil(($word->parameter / 24) * 16); // font size
      }elseif($word->word == "f"){ $this->state->font = $word->parameter;
      
      // Colors:
      }elseif ($word->word == "cf" || $word->word == "chcfpat") {
        $this->state->fontcolor = $word->parameter;
      }elseif ($word->word == "cb" || $word->word == "chcbpat") {
        $this->state->background = $word->parameter;
      }elseif ($word->word == "highlight") {
        $this->state->hcolor = $word->parameter;
        
      // RTF special characters:
      }elseif($word->word == "lquote"){ $this->Write("&lsquo;"); // &#145; &#8216;
      }elseif($word->word == "rquote"){ $this->Write("&rsquo;");  // &#146; &#8217;
      }elseif($word->word == "ldblquote"){ $this->Write("&ldquo;"); // &#147; &#8220;
      }elseif($word->word == "rdblquote"){ $this->Write("&rdquo;"); // &#148; &#8221;
      }elseif($word->word == "bullet"){ $this->Write("&bull;"); // &#149; &#8226;
      }elseif($word->word == "endash"){ $this->Write("&ndash;"); // &#150; &#8211;
      }elseif($word->word == "emdash"){ $this->Write("&mdash;"); // &#151; &#8212;
            
      // more special characters:
      }elseif($word->word == "enspace"){ $this->Write("&ensp;"); // &#8194;
      }elseif($word->word == "emspace"){ $this->Write("&emsp;"); // &#8195;
      //}elseif($word->word == "emspace" || $word->word == "enspace"){ $this->Write("&nbsp;"); // &#160; &#32;
      }elseif($word->word == "tab"){ $this->Write("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"); // character value 9
      }elseif($word->word == "line"){ $this->output .= "<br>"; // character value (line feed = &#10;) (carriage return = &#13;)
      
      // Unicode characters:
      }elseif($word->word == "u") {
        $uchar = $this->DecodeUnicode($word->parameter);
        $this->Write($uchar);
      
      // End of paragraph:
      }elseif($word->word == "par" || $word->word == "row") {
        // Close previously opened tags
        $this->CloseTags();
        // Begin a new paragraph
        $this->OpenTag('p');
      
      // Store defaults
      }elseif($word->word == "deff") {
        $this->defaultFont = $word->parameter;        
      }elseif(in_array($word->word, array('ansi','mac','pc','pca'))){
        $this->RTFencoding = $this->GetEncodingFromCodepage($word->word);
      }elseif ($word->word == "ansicpg" && $word->parameter) {
        $this->RTFencoding = $this->GetEncodingFromCodepage($word->parameter);
      }
    }
    
    protected function DecodeUnicode($code, $srcEnc = 'UTF-8')
    {
      $utf8 = '';
      
      if ($srcEnc != 'UTF-8') { // convert character to Unicode
        $utf8 = iconv($srcEnc, 'UTF-8', chr($code));
      }
      
      if ($this->encoding == 'HTML-ENTITIES') {
        return $utf8 ? "&#{$this->ord_utf8($utf8)};" : "&#{$code};";      
        
      } elseif ($this->encoding == 'UTF-8') {
        return $utf8 ? $utf8 : mb_convert_encoding("&#{$code};", $this->encoding, 'HTML-ENTITIES');
        
      } else {
        return $utf8 ? mb_convert_encoding($utf8, $this->encoding, 'UTF-8') :
          mb_convert_encoding("&#{$code};", $this->encoding, 'HTML-ENTITIES');
      }
    }
    
    protected function Write($txt)
    {
      // Create a new 'span' element only when a style change occur
      // 1st case: style change occured
      // 2nd case: there is no change in style but the already created 'span'
      // element is somehow closed (ex. because of an end of paragraph)
      if  (!$this->state->isLike($this->previousState) ||
          ($this->state->isLike($this->previousState) && !$this->openedTags['span']))
      {
        // If applicable close previously opened 'span' tag
        $this->CloseTag('span');
        
        $style = $this->state->PrintStyle();
        
        // Keep track of preceding style
        $this->previousState = clone $this->state;        
       
        // Create style attribute and open span
        $attr = $style ? "style=\"{$style}\"" : "";
        $this->OpenTag('span', $attr);
      }
      $this->output .= $txt;
    }
    
    protected function OpenTag($tag, $attr = '')
    {
      $this->output .= $attr ? "<{$tag} {$attr}>" : "<{$tag}>";
      $this->openedTags[$tag] = True;
    }
    
    protected function CloseTag($tag)
    {
      if ($this->openedTags[$tag]) {
        // Check for empty html elements
        if (substr($this->output ,-strlen("<{$tag}>")) == "<{$tag}>"){
          switch ($tag)
          {
            case 'p': // Replace empty 'p' element with a line break
              $this->output = substr($this->output ,0, -3)."<br>";
              break;            
            default: // Delete empty elements
              $this->output = substr($this->output ,0, -strlen("<{$tag}>"));
              break;
          }
        } else {
          $this->output .= "</{$tag}>";
          $this->openedTags[$tag] = False;
        }
      }
    }
    
    protected function CloseTags()
    {
      // Close all opened tags
      foreach ($this->openedTags as $tag => $b)
        $this->CloseTag($tag);
    }
    
    protected function FormatControlSymbol($symbol)
    {
      if($symbol->symbol == '\'') {
        $enc = $this->GetSourceEncoding();
        $uchar = $this->DecodeUnicode($symbol->parameter, $enc);
        $this->Write($uchar);
      }elseif ($symbol->symbol == '~') { 
        $this->Write("&nbsp;"); // Non breaking space
      }elseif ($symbol->symbol == '-') { 
        $this->Write("&#173;"); // Optional hyphen
      }elseif ($symbol->symbol == '_') {
        $this->Write("&#8209;"); // Non breaking hyphen
      }
    }
 
    protected function FormatText($text)
    {
      // Convert special characters to HTML entities
      $txt = htmlspecialchars($text->text, ENT_NOQUOTES, 'UTF-8');
      if($this->encoding == 'HTML-ENTITIES')
        $this->Write($txt);
      else
        $this->Write(mb_convert_encoding($txt, $this->encoding, 'UTF-8'));
    }
    
    protected function GetSourceEncoding()
    {
      if (isset($this->state->font)) {
        if (isset(RtfState::$fonttbl[$this->state->font]->codepage)) {
          return RtfState::$fonttbl[$this->state->font]->codepage;
        
        } elseif (isset(RtfState::$fonttbl[$this->state->font]->charset)) {
          return RtfState::$fonttbl[$this->state->font]->charset;
        }
      }
      return $this->RTFencoding;
    }
    
    protected function GetEncodingFromCharset($fcharset)
    {
      /* maps windows character sets to iconv encoding names */
      $charset = array ( 
          0   => 'CP1252', // ANSI: Western Europe
          1   => 'CP1252', //*Default
          2   => 'CP1252', //*Symbol
          3   => null,     // Invalid
          77  => 'MAC',    //*also [MacRoman]: Macintosh 
          128 => 'CP932',  //*or [Shift_JIS]?: Japanese
          129 => 'CP949',  //*also [UHC]: Korean (Hangul)
          130 => 'CP1361', //*also [JOHAB]: Korean (Johab)
          134 => 'CP936',  //*or [GB2312]?: Simplified Chinese
          136 => 'CP950',  //*or [BIG5]?: Traditional Chinese             
          161 => 'CP1253', // Greek
          162 => 'CP1254', // Turkish (latin 5)
          163 => 'CP1258', // Vietnamese
          177 => 'CP1255', // Hebrew
          178 => 'CP1256', // Simplified Arabic
          179 => 'CP1256', //*Traditional Arabic
          180 => 'CP1256', //*Arabic User
          181 => 'CP1255', //*Hebrew User
          186 => 'CP1257', // Baltic
          204 => 'CP1251', // Russian (Cyrillic)
          222 => 'CP874',  // Thai
          238 => 'CP1250', // Eastern European (latin 2)
          254 => 'CP437',  //*also [IBM437][437]: PC437
          255 => 'CP437'); //*OEM still PC437

      if (isset($charset[$fcharset]))
        return $charset[$fcharset];
      else {
        trigger_error("Unknown charset: {$fcharset}");
      }
    }

    protected function GetEncodingFromCodepage($cpg)
    {
      $codePage = array (
          'ansi' => 'CP1252',
          'mac'  => 'MAC',
          'pc'   => 'CP437',
          'pca'  => 'CP850',
          437 => 'CP437', // United States IBM          
          708 => 'ASMO-708', // also [ISO-8859-6][ARABIC] Arabic
          /*  Not supported by iconv
          709, => '' // Arabic (ASMO 449+, BCON V4) 
          710, => '' // Arabic (transparent Arabic) 
          711, => '' // Arabic (Nafitha Enhanced) 
          720, => '' // Arabic (transparent ASMO) 
          */
          819 => 'CP819',   // Windows 3.1 (US and Western Europe) 
          850 => 'CP850',   // IBM multilingual 
          852 => 'CP852',   // Eastern European 
          860 => 'CP860',   // Portuguese 
          862 => 'CP862',   // Hebrew 
          863 => 'CP863',   // French Canadian 
          864 => 'CP864',   // Arabic 
          865 => 'CP865',   // Norwegian 
          866 => 'CP866',   // Soviet Union 
          874 => 'CP874',   // Thai 
          932 => 'CP932',   // Japanese 
          936 => 'CP936',   // Simplified Chinese 
          949 => 'CP949',   // Korean 
          950 => 'CP950',   // Traditional Chinese 
          1250 => 'CP1250',  // Windows 3.1 (Eastern European) 
          1251 => 'CP1251',  // Windows 3.1 (Cyrillic) 
          1252 => 'CP1252',  // Western European 
          1253 => 'CP1253',  // Greek 
          1254 => 'CP1254',  // Turkish 
          1255 => 'CP1255',  // Hebrew 
          1256 => 'CP1256',  // Arabic 
          1257 => 'CP1257',  // Baltic 
          1258 => 'CP1258',  // Vietnamese 
          1361 => 'CP1361'); // Johab
      
      if (isset($codePage[$cpg]))
        return $codePage[$cpg];
      else {
        // Debug Error
        trigger_error("Unknown codepage: {$cpg}");
      }
    }
    
    protected function ord_utf8($chr)
    {
      $ord0 = ord($chr);
      if ($ord0 >= 0 && $ord0 <= 127)
        return $ord0;

      $ord1 = ord($chr[1]);
      if ($ord0 >= 192 && $ord0 <= 223)
        return ($ord0 - 192) * 64 + ($ord1 - 128);

      $ord2 = ord($chr[2]);
      if ($ord0 >= 224 && $ord0 <= 239)
        return ($ord0 - 224) * 4096 + ($ord1 - 128) * 64 + ($ord2 - 128);

      $ord3 = ord($chr[3]);
      if ($ord0 >= 240 && $ord0 <= 247)
        return ($ord0 - 240) * 262144 + ($ord1 - 128) * 4096 + ($ord2 - 128) * 64 + ($ord3 - 128);

      $ord4 = ord($chr[4]);
      if ($ord0 >= 248 && $ord0 <= 251)
        return ($ord0 - 248) * 16777216 + ($ord1 - 128) * 262144 + ($ord2 - 128) * 4096 + ($ord3 - 128) * 64 + ($ord4 - 128);

      if ($ord0 >= 252 && $ord0 <= 253)
        return ($ord0 - 252) * 1073741824 + ($ord1 - 128) * 16777216 + ($ord2 - 128) * 262144 + ($ord3 - 128) * 4096 + ($ord4 - 128) * 64 + (ord($chr[5]) - 128);
	
      trigger_error("Invalid Unicode character: {$chr}");
    }
  }

  if (__FILE__ === realpath($_SERVER['SCRIPT_NAME']) && php_sapi_name() === 'cli') {
    if (isset($_SERVER['argv'][1]) && ($_SERVER['argv'][1] !== '-')) {
      $file = $_SERVER['argv'][1];
    } else {
      $file = 'php://stdin';
    }

    $reader = new RtfReader();
    $rtf = file_get_contents($file);
    if ($reader->Parse($rtf)) {
      $formatter = new RtfHtml();
      echo $formatter->Format($reader->root);      
    } else {
      echo "Parse error occured";
    }
  }
