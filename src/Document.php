<?php 

declare(strict_types=1);

namespace RtfHtmlPhp;

class Document
{
  private $rtf;        // RTF string being parsed
  private $pos;        // Current position in RTF string
  private $len;        // Length of RTF string
  public $root = null; // Root group
  private $group;      // Current RTF group

  public function __construct($rtf)
  {
    $this->Parse($rtf);
  }  

  // Get the next character from the RTF stream.
  // Parsing is aborted when reading beyond end of input string.
  protected function GetChar()
  {
    $this->char = null;
    if ($this->pos < strlen($this->rtf)) {
      $this->char = $this->rtf[$this->pos++];
    } else {
      $err = "Parse error: Tried to read past end of input; RTF is probably truncated.";
      trigger_error($err);
      throw new \Exception($err);
    }
  }

  /*
   * (Helper method)
   * Is the current character a letter?
   */
  protected function is_letter(): bool
  {
    if(ord($this->char) >= 65 && ord($this->char) <= 90) return true;
    if(ord($this->char) >= 97 && ord($this->char) <= 122) return true;
    return false;
  }

  /*
   * (Helper method)
   * Is the current character a digit?
   */
  protected function is_digit(): bool
  {
    return (ord($this->char) >= 48 && ord($this->char) <= 57);
  }

  /*
   * (Helper method)
   * Is the current character end-of-line (EOL)?
   */
  protected function is_endofline()
  {
    if ($this->char == "\r" || $this->char == "\n") {
      // Checks for a Windows/Acron type EOL
      if( $this->rtf[$this->pos] == "\n" || $this->rtf[$this->pos] == "\r" ) {
        $this->GetChar();
      }
      return true;
    }
    return false;
  }
  
  /*
   * (Helper method)
   * Is the current character for a space delimiter?
   */
  protected function is_space_delimiter()
  {
    return ($this->char == " " || $this->is_endofline());
  }  

  // Store state of document on stack.
  protected function ParseStartGroup()
  {
    $group = new Group();

    // Is there a current group? Then make the new group its child:
    if($this->group != null) {
      $group->parent = $this->group;
      array_push($this->group->children, $group);
      array_push($this->uc, end($this->uc));
    } 
    // If there is no parent group, then set this group
    // as the root group.
    else {
      $this->root = $group;
      // Create uc stack and insert the first default value
      $this->uc = array(1);
    }

    // Set the new group as the current group:
    $this->group = $group;
  }

  // Retrieve state of document from stack.
  protected function ParseEndGroup()
  {
    $this->group = $this->group->parent;
    // Retrieve last uc value from stack
    array_pop($this->uc);
  }

  protected function ParseControlWord()
  {
    // Read letters until a non-letter is reached.
    $word = "";
    $this->GetChar();
    while($this->is_letter())
    {
      $word .= $this->char;
      $this->GetChar();
    }

    // Read parameter (if any) consisting of digits.
    // Parameter may be negative, i.e., starting with a '-'
    $parameter = null;
    $negative = false;
    if($this->char == '-') {
      $this->GetChar();
      $negative = true;
    }
    while($this->is_digit())
    {
      if($parameter == null) $parameter = 0;
      $parameter = $parameter * 10 + $this->char;
      $this->GetChar();
    }
    // If no parameter present, assume control word's default (usually 1)
    // If no default then assign 0 to the parameter
    if($parameter === null) $parameter = 1;
    
    // Convert parameter to a negative number when applicable
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
        //   represented using the appropriate control symbol (that is,
        //   escaped with a backslash,) as in plain text.
        // 
        // - Any RTF control word or symbol is considered a single character
        //   for the purposes of counting skippable characters. For this reason
        //   it's more appropriate to create a $skip flag and let the Parse()
        //   function take care of the skippable characters.
        $uc--;
      }
    }
           
    // Add new RTF word as a child to the current group.
    $rtfword = new ControlWord();
    $rtfword->word = $word;
    $rtfword->parameter = $parameter;
    array_push($this->group->children, $rtfword);
  }

  protected function ParseControlSymbol()
  {
    // Read symbol (one character only).
    $this->GetChar();
    $symbol = $this->char;

    // Exceptional case: 
    // Treat EOL symbols as \par control word
    if ($this->is_endofline()) {
      $rtfword = new ControlWord();
      $rtfword->word = 'par';
      $rtfword->parameter = 0;
      array_push($this->group->children, $rtfword);
      return;
    }    

    // Symbols ordinarily have no parameter. However, 
    // if this is \' (a single quote), then it is 
    // followed by a 2-digit hex-code:
    $parameter = 0;
    if ($symbol == '\'') {
      $this->GetChar(); 
      $parameter = $this->char;
      $this->GetChar(); 
      $parameter = hexdec($parameter . $this->char);
    }

    // Add new control symbol as a child to the current group:
    $rtfsymbol = new ControlSymbol();
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
    $this->pos--; // (go back after look-ahead)
    if($this->is_letter()) {
      $this->ParseControlWord();
    } else {
      $this->ParseControlSymbol();
    }
  }

  protected function ParseText()
  {
    // Parse plain text up to backslash or brace,
    // unless escaped.
    $text = "";
    $terminate = false;
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
            $terminate = true;
            break;
        }
      } elseif ($this->char == '{' || $this->char == '}') {
        $this->pos--;
        $terminate = true;
      }

      if(!$terminate) {
        // Save plain text
        $text .= $this->char;
        $this->GetChar();
      } 
    } 
    while(!$terminate && $this->pos < $this->len);

    // Create new Text element:
    $text = new Text($text);

    // If there is no current group, then this is not a valid RTF file.
    // Throw an exception.
    if($this->group == null) {
      $err = "Parse error: RTF text outside of group.";
      trigger_error($err);
      throw new \Exception($err);
    }

    // Add text as a child to the current group:
    array_push($this->group->children, $text);
  }

  /*
   * Attempt to parse an RTF string.
   */
  protected function Parse(string $rtf)
  {
    $this->rtf = $rtf;
    $this->pos = 0;
    $this->len = strlen($this->rtf);
    $this->group = null;
    $this->root = null;

    while($this->pos < $this->len-1)
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
  }

  public function __toString() {
    if(!$this->root) return "No root group";
    return $this->root->toString();
  }
}
