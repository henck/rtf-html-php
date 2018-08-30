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
      if(!$child instanceof RtfControlWord) return null;
      return $child->word;
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
        if($child instanceof RtfGroup)
        {
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
      if($this->root == null)
      {
        $this->group = $group;
        $this->root = $group;
      }
      else
      {
        array_push($this->group->children, $group);
        $this->group = $group;
      }
    }
 
    protected function is_letter()
    {
      if(ord($this->char) >= 65 && ord($this->char) <= 90) return TRUE;
      if(ord($this->char) >= 97 && ord($this->char) <= 122) return TRUE;
      return FALSE;
    }
 
    protected function is_digit()
    {
      if(ord($this->char) >= 48 && ord($this->char) <= 57) return TRUE;
      return FALSE;
    }
 
    protected function ParseEndGroup()
    {
      // Retrieve state of document from stack.
      $this->group = $this->group->parent;
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
      $negative = false;
      if($this->char == '-') 
      {
        $this->GetChar();
        $negative = true;
      }
      while($this->is_digit())
      {
        if($parameter == null) $parameter = 0;
        $parameter = $parameter * 10 + $this->char;
        $this->GetChar();
      }
      if($parameter === null) $parameter = 1;
      
      // convert to a negative number when applicable
      if($negative) $parameter = -$parameter;
      
      // If this is \u, then the parameter will be followed by 
      // a character.
      if($word == "u") 
      {
        // Ignore space delimiter
        if ($this->char==' ') $this->GetChar();
        
        // if the replacement character is encoded as
        // hexadecimal value \'hh then jump over it
        if($this->char == '\\' && $this->rtf[$this->pos]=='\'')
            $this->pos = $this->pos + 3;
        
        // Convert to UTF unsigned decimal code
        if($negative) $parameter = 65536 + $parameter;                 
      }
      // If the current character is a space, then
      // it is a delimiter. It is consumed.
      // If it's not a space, then it's part of the next
      // item in the text, so put the character back.
      else
      {
        if($this->char != ' ') $this->pos--;  
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
      if($symbol == '\'')
      {
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

      do
      {
        $terminate = false;
        
        // Is this an escape?
        if($this->char == '\\')
        {
          // Perform lookahead to see if this
          // is really an escape sequence.
          $this->GetChar();
          switch($this->char)
          {
            case '\\':
            case '{':
            case '}':
              break;
            default:
              // Not an escape. Roll back.
              $this->pos = $this->pos - 2;
              $terminate = true;
              break;
          }
        }
        else if($this->char == '{' || $this->char == '}')
        {
          $this->pos--;
          $terminate = true;
        }
 
        if(!$terminate)
        {
          $text .= $this->char;
          $this->GetChar();
        }
      }
      while(!$terminate && $this->pos < $this->len);
 
      $rtftext = new RtfText();
      $rtftext->text = $text;

      // If group does not exist, then this is not a valid RTF file. Throw an exception.
      if($this->group == NULL) {
        throw new Exception();
      }

      array_push($this->group->children, $rtftext);
    }
 
    /*
     * Attempt to parse an RTF string. Parsing returns TRUE on success or FALSE on failure
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

        return TRUE;
      }
      catch(Exception $ex) {
        return FALSE;
      }
    }
  }
 
  class RtfState
  {
    public function __construct()
    {
      $this->Reset();
    }
 
    public function Reset()
    {
      $this->bold = false;
      $this->italic = false;
      $this->underline = false;
      $this->end_underline = false;
      $this->strike = false;
      $this->hidden = false;
      $this->fontsize = 0;
    }
  }
 
  class RtfHtml
  {
    // initialise Encoding
    public function __construct($encoding = 'UTF-8') {
      if (in_array($encoding, mb_list_encodings()))
        $this->encoding = $encoding;
      else {
        trigger_error("Unrecognized Encoding, revert to UTF-8");
        $this->encoding = 'UTF-8';
      }
    }
    
    public function Format($root)
    {
      $this->output = "";
      // Keeping track of style modifications
      $this->previousState = null;
      $this->openedTags = array('span' => False, 'p' => False);
      // Create a stack of states:
      $this->states = array();
      // Put an initial standard state onto the stack:
      $this->state = new RtfState();
      array_push($this->states, $this->state);
      $this->FormatGroup($root);
      return $this->output;
    }
    
    protected function ExtractColorTable($colorTblGrp) {
      // {\colortbl;\red0\green0\blue0;}
      // index 0 is the 'auto' color
      // force array to begin at index 1
      $colortbl = array(0 => null); 
      $c = count($colorTblGrp);
      $color = '';
      for ($i=2; $i<$c; $i++) { // iterate through colors
        if ($colorTblGrp[$i] instanceof RtfControlWord) {
          // extract RGB color and convert it to hex string
          $color = sprintf('#%02x%02x%02x', // hex string format
                              $colorTblGrp[$i]->parameter, // red
                              $colorTblGrp[$i+1]->parameter, // green
                              $colorTblGrp[$i+2]->parameter); // blue
          $i+=2;
          } elseif ($colorTblGrp[$i] instanceof RtfText) {
            // this a delimiter ';' so store the already extracted color
            $colortbl[] = $color;
          }
      }
      $this->colortbl = $colortbl;
    }
 
    protected function FormatGroup($group)
    {
      // Can we ignore this group?
      // Font table extraction not yet supported
      if ($group->GetType() == "fonttbl") return;
      // Extract color table
      elseif ($group->GetType() == "colortbl") {
        $this->ExtractFontTable($group->children);
        return;
      // Stylesheet extraction not yet supported
      } elseif ($group->GetType() == "stylesheet") return;
      elseif ($group->GetType() == "info") return;
      // Pictures extraction not yet supported
      if (substr($group->GetType(), 0, 4) == "pict") return;
      if ($group->IsDestination()) return;
 
      // Push a new state onto the stack:
      $this->state = clone $this->state;
      array_push($this->states, $this->state);
 
      foreach($group->children as $child)
      {
        if($child instanceof RtfGroup) $this->FormatGroup($child);
        elseif($child instanceof RtfControlWord) $this->FormatControlWord($child);
        elseif($child instanceof RtfControlSymbol) $this->FormatControlSymbol($child);
        elseif($child instanceof RtfText) $this->FormatText($child);
      }
 
      // Pop state from stack.
      array_pop($this->states);
      $this->state = $this->states[sizeof($this->states)-1];
    }
 
    protected function FormatControlWord($word)
    {
      if($word->word == "plain") $this->state->Reset();
      elseif($word->word == "b") $this->state->bold = $word->parameter;
      elseif($word->word == "i") $this->state->italic = $word->parameter;
      elseif($word->word == "ul") $this->state->underline = $word->parameter;
      elseif($word->word == "ulnone") $this->state->underline = false;
      elseif($word->word == "strike") $this->state->strike = $word->parameter;
      elseif($word->word == "v") $this->state->hidden = $word->parameter;
      elseif($word->word == "fs") $this->state->fontsize = ceil(($word->parameter / 24) * 20);
 
      elseif($word->word == "par") {
        // close previously opened 'span' tag
        $this->CloseTag();
        // decide whether to open or to close a 'p' tag
        if ($this->openedTags["p"]) $this->CloseTag("p");
        else {
          $this->output .= "<p>";
          $this->openedTags['p'] = True;
        }
      }
 
      // RTF special characters:
      elseif($word->word == "lquote") $this->output .= "&lsquo;"; // character value 145
      elseif($word->word == "rquote") $this->output .= "&rsquo;";  // character value 146
      elseif($word->word == "ldblquote") $this->output .= "&ldquo;"; // character value 147
      elseif($word->word == "rdblquote") $this->output .= "&rdquo;"; // character value 148
      elseif($word->word == "bullet") $this->output .= "&bull;"; // character value 149
      elseif($word->word == "endash") $this->output .= "&ndash;"; // character value 150
      elseif($word->word == "emdash") $this->output .= "&mdash;"; // character value 151
      
      // more special characters
      elseif($word->word == "emspace" || $word->word == "enspace")
        $this->output .= " "; // character value 
      elseif($word->word == "tab") $this->output .= "\t"; // character value 9
      elseif($word->word == "line") $this->output .= "</br>"; // character value
      
      // style colors
      elseif ($word->word == "cf") //|| $word->word == "chcfpat")
        $this->state->textcolor = $word->parameter;
      elseif ($word->word == "cb" ||
              $word->word == "chcbpat" ||
              $word->word == "highlight")
        $this->state->background = $word->parameter;
          
      // Unicode characters
      elseif($word->word == "u") {
        $uchar = $this->DecodeUnicode($word->parameter);
        $this->ApplyStyle($uchar);
      }        

    }
    
    protected function DecodeUnicode($ucode){
      $baseEncoding = "UTF-8";
      $htmlentity = "&#{$ucode};";
      $decodedEntity = html_entity_decode($htmlentity, ENT_COMPAT, $baseEncoding);
      $mbChar = mb_convert_encoding($decodedEntity, $this->encoding, $baseEncoding);
      return $mbChar;
    }
 
    protected function ApplyStyle($txt)
    {
      // create span only when a style change occur
      if ($this->previousState != $this->state)
      {
        $span = "";
        if($this->state->bold) $span .= "font-weight:bold;";
        if($this->state->italic) $span .= "font-style:italic;";
        if($this->state->underline) $span .= "text-decoration:underline;";
        if($this->state->end_underline) $span .= "text-decoration:none;";
        if($this->state->strike) $span .= "text-decoration:strikethrough;";
        if($this->state->hidden) $span .= "display:none;";
        if($this->state->fontsize != 0) $span .= "font-size: {$this->state->fontsize}px;";
        
        // Keep track of preceding style
        $this->previousState = clone $this->state;
        
        // close previously opened 'span' tag
        $this->CloseTag();
        
        $this->output .= "<span style=\"{$span}\">" . $txt;
        $this->openedTags["span"] = True;
      }
      else $this->output .= $txt;
    }
 
    protected function CloseTag($tag = "span")
    {
      if ($this->openedTags[$tag])
      {
        $this->output .= "</{$tag}>";
        $this->openedTags[$tag] = False;
      }
    }
 
    protected function FormatControlSymbol($symbol)
    {
      if($symbol->symbol == '\'') {
        $uchar = $this->DecodeUnicode($symbol->parameter);
        $this->ApplyStyle($uchar);
      }
    }
 
    protected function FormatText($text)
    {
      $this->ApplyStyle($text->text);
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
