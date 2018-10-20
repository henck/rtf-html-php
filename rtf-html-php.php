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
        $this->group = $group;
        $this->root = $group;
      } else {
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
      if($parameter === null) $parameter = 1;
      
      // convert to a negative number when applicable
      if($negative) $parameter = -$parameter;
      
      // If this is \u, then the parameter will be followed by 
      // a character.
      if($word == "u") {
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
      if($symbol == '\'') {
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
        $terminate = False;
        
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
 
        if(!$terminate) { // store normal text
          $text .= $this->char;
          $this->GetChar();
        }
      } 
      while(!$terminate && $this->pos < $this->len);
 
      $rtftext = new RtfText();
      $rtftext->text = $text;

      // If group does not exist, then this is not a valid RTF file. Throw an exception.
      if($this->group == null) {
        $err = "Parse error occured";
        trigger_error($err);
        throw new Exception("Parse error occured");
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

        return True;
      }
      catch(Exception $ex) {
        return False;
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
      $this->bold = False;
      $this->italic = False;
      $this->underline = False;
      $this->strike = False;
      $this->hidden = False;
      $this->fontsize = 0;
      $this->fontcolor = null;
      $this->background = null;
    }
  }
 
  class RtfHtml
  {
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
      $this->FormatGroup($root);
      // Remove the last opened <p> tag and return
      return substr($this->output ,0, -3);
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
      $this->colortbl = $colortbl;
    }
 
    protected function FormatGroup($group)
    {
      // Can we ignore this group?      
      // Font table extraction not yet supported
      if($group->GetType() == "fonttbl") return;      
      // Extract color table
      elseif($group->GetType() == "colortbl") {
        $this->ExtractColorTable($group->children);
        return;      
      } 
      // Stylesheet extraction not yet supported
      elseif($group->GetType() == "stylesheet") return;
      elseif($group->GetType() == "info") return;
      // Pictures extraction not yet supported
      if(substr($group->GetType(), 0, 4) == "pict") return;
      // Ignore Destionations
      if($group->IsDestination()) return;
 
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
 
      // Pop state from stack
      array_pop($this->states);
      $this->state = $this->states[sizeof($this->states)-1];
    }
 
    protected function FormatControlWord($word)
    {
      // plain: Reset font formatting properties to default.
      // pard: Reset to default paragraph properties.
      if($word->word == "plain" || $word->word == "pard"){ $this->state->Reset();
      
      // Font formatting properties:
      }elseif($word->word == "b"){ $this->state->bold = $word->parameter; // bold
      }elseif($word->word == "i"){ $this->state->italic = $word->parameter; // italic
      }elseif($word->word == "ul"){ $this->state->underline = $word->parameter; // underline
      }elseif($word->word == "ulnone"){ $this->state->underline = False; // no underline
      }elseif($word->word == "strike"){ $this->state->strike = $word->parameter; // strike through
      }elseif($word->word == "v"){ $this->state->hidden = $word->parameter; // hidden
      }elseif($word->word == "fs"){ $this->state->fontsize = ceil(($word->parameter / 24) * 16); // font size
      
      // Colors:
      }elseif ($word->word == "cf") { //|| $word->word == "chcfpat")
          $this->state->fontcolor = $word->parameter;
      }elseif ($word->word == "cb" || $word->word == "chcbpat" || $word->word == "highlight") {
           $this->state->background = $word->parameter;
      
      // RTF special characters:
      }elseif($word->word == "lquote"){ $this->output .= "&lsquo;"; // &#145; &#8216;
      }elseif($word->word == "rquote"){ $this->output .= "&rsquo;";  // &#146; &#8217;
      }elseif($word->word == "ldblquote"){ $this->output .= "&ldquo;"; // &#147; &#8220;
      }elseif($word->word == "rdblquote"){ $this->output .= "&rdquo;"; // &#148; &#8221;
      }elseif($word->word == "bullet"){ $this->output .= "&bull;"; // &#149; &#8226;
      }elseif($word->word == "endash"){ $this->output .= "&ndash;"; // &#150; &#8211;
      }elseif($word->word == "emdash"){ $this->output .= "&mdash;"; // &#151; &#8212;
            
      // more special characters:
      }elseif($word->word == "enspace"){ $this->output .= "&ensp;"; // &#8194;
      }elseif($word->word == "emspace"){ $this->output .= "&emsp;"; // &#8195;
      //}elseif($word->word == "emspace" || $word->word == "enspace"){ $this->output .= "&nbsp;"; // &#160; &#32;
      }elseif($word->word == "tab"){ $this->output .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; // character value 9
      }elseif($word->word == "line"){ $this->output .= "<br>"; // character value (line feed = &#10;) (carriage return = &#13;)
      
      // Unicode characters:
      }elseif($word->word == "u") {
        $uchar = $this->DecodeUnicode($word->parameter);
        $this->ApplyStyle($uchar);
      
      // End of paragraph:
      }elseif($word->word == "par" || $word->word == "row") {
        // Close previously opened tags
        $this->CloseTags();
        // Begin a new paragraph
        $this->OpenTag('p');
      }
    }
    
    protected function DecodeUnicode($code)
    {
      $htmlentity = "&#{$code};";
      if($this->encoding == 'HTML-ENTITIES') return $htmlentity;
      else {
        // Character codes 128 to 159 (U+0080 to U+009F) are not allowed in HTML
        if($code > 127 && $code < 160) {
          $utf = mb_convert_encoding(chr($code), 'UTF-8', 'windows-1252');
          $htmlentity = htmlentities($utf, ENT_QUOTES, 'UTF-8');
        }
        $mbChar = mb_convert_encoding($htmlentity, $this->encoding, 'HTML-ENTITIES');
        return $mbChar;
      }
    }
    
    protected function ApplyStyle($txt)
    {
      // Create a new 'span' element only when a style change occur
      // 1st case: style change occured
      // 2nd case: there is no change in style but the already created 'span'
      // element is somehow closed (ex. because of an end of paragraph)
      if  ($this->state != $this->previousState ||
          ($this->state == $this->previousState && !$this->openedTags['span']))
      {
        $style = "";
        if($this->state->bold) $style .= "font-weight:bold;";
        if($this->state->italic) $style .= "font-style:italic;";
        if($this->state->underline) $style .= "text-decoration:underline;";
        // state->underline is a toggle switch variable so no need for
        // a dedicated state->end_underline variable
        // if($this->state->end_underline) {$span .= "text-decoration:none;";}
        if($this->state->strike) $style .= "text-decoration:line-through;";
        if($this->state->hidden) $style .= "display:none;";
        if($this->state->fontsize != 0) $style .= "font-size:{$this->state->fontsize}px;";
        // Font color:
        if(isset($this->state->fontcolor)) {
          // Check if color is set. in particular when it's the 'auto' color
          if ($this->colortbl[$this->state->fontcolor])
            $style .= "color:".$this->PrintColor($this->state->fontcolor).";";
        }
        // Background color:
        if (isset($this->state->background)) {
          // Check if color is set. in particular when it's the 'auto' color
          if ($this->colortbl[$this->state->fontcolor])
            $style .= "background-color:".$this->PrintColor($this->state->background).";";
        }
        // Keep track of preceding style
        $this->previousState = clone $this->state;
        
        if ($style != '') {
          // If applicable close previously opened 'span' tag
          $this->CloseTag('span');
          // Create a new 'span' tag
          $this->OpenTag('span',"style=\"{$style}\"");
        }
      }
      $this->output .= $txt;
    }
    
    protected function PrintColor($index) {      
        return $this->colortbl[$index];
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
        $uchar = $this->DecodeUnicode($symbol->parameter);
        $this->ApplyStyle($uchar);
      }
    }
 
    protected function FormatText($text)
    {
      // Convert special characters to HTML entities
      $txt = htmlspecialchars($text->text, ENT_NOQUOTES, 'UTF-8');
      if($this->encoding == 'HTML-ENTITIES')
        $this->ApplyStyle($txt);
      else
        $this->ApplyStyle(mb_convert_encoding($txt, $this->encoding, 'UTF-8'));
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
