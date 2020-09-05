<?php

namespace RtfHtmlPhp\Html;

use RtfHtmlPhp\Document;

class HtmlFormatter
{
  private $output = '';
  private $encoding;
  private $defaultFont;

  // By default, HtmlFormatter uses HTML_ENTITIES for code conversion.
  // You can optionally support a different endoing when creating
  // the HtmlFormatter instance.
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

  public function Format(Document $document)
  {
    // Clear current output
    $this->output = '';
    // Keep track of style modifications
    $this->previousState = null;
    // and create a stack of states
    $this->states = array();
    // Put an initial standard state onto the stack
    $this->state = new State();
    array_push($this->states, $this->state);

    // Keep track of opened html tags
    $this->openedTags = array('span' => false, 'p' => false);
    // Create the first paragraph
    $this->OpenTag('p');
    // Begin format
    $this->ProcessGroup($document->root);
    // Instead of removing opened tags, we close them
      $append = $this->openedTags['span'] ? '</span>' : '';
      $append .= $this->openedTags['p'] ? '</p>' : '';

      return $this->output . $append;

  }

  protected function LoadFont(\RtfHtmlPhp\Group $fontGroup) {
    $fontNumber = 0;
    $font = new Font();

    // Loop through children of the font group. The font group
    // contains control words with the font number and charset,
    // and a control text with the font name.
    foreach($fontGroup->children as $child) {

      // Control word
      if ($child instanceof \RtfHtmlPhp\ControlWord){
        switch ($child->word) {
          case 'f':
            $fontNumber = $child->parameter;
            break;

          // Font family names
          case 'froman':  $font->family = "serif";      break;
          case 'fswiss':  $font->family = "sans-serif"; break;
          case 'fmodern': $font->family = "monospace";  break;
          case 'fscript': $font->family = "cursive";    break;
          case 'fdecor':  $font->family = "fantasy";    break;

          // case 'fnil': break; // default font
          // case 'ftech': break; // symbol
          // case 'fbidi': break; // bidirectional font

          case 'fcharset': // charset
            $font->charset = $this->GetEncodingFromCharset($child->parameter);
            break;
          case 'cpg': // code page
            $font->codepage = $this->GetEncodingFromCodepage($child->parameter);
            break;
          case 'fprq': // Font pitch
            $font->fprq = $child->parameter;
            break;
        }
      }

      // Control text contains the font name, if any:
      if ($child instanceof \RtfHtmlPhp\Text) {
        // Store font name (except ; delimiter at end)
        $font->name = substr($child->text, 0, -1);
      }

      /*
      elseif ($child instanceof \RtfHtmlPhp\Group) {
              // possible subgroups:
              // '{\*' \falt #PCDATA '}' = alternate font name
              // '{\*' \fontemb <fonttype> <fontfname>? <data>? '}'
              // '{\*' \fontfile <codepage>? #PCDATA '}'
              // '{\*' \panose <data> '}'
              continue;
            } elseif ($child instanceof \RtfHtmlPhp\ControlSymbol) {
              // the only authorized symbol here is '*':
              // \*\fname = non tagged file name (only WordPad uses it)
              continue;
            }
      */
    }

    State::SetFont($fontNumber, $font);
  }

  protected function ExtractFontTable($fontTblGrp)
  {
    // {' \fonttbl (<fontinfo> | ('{' <fontinfo> '}'))+ '}'
    // <fontnum><fontfamily><fcharset>?<fprq>?<panose>?
    // <nontaggedname>?<fontemb>?<codepage>? <fontname><fontaltname>? ';'

    // The Font Table group contains the control word "fonttbl" and some
    // subgroups. Go through the subgroups, ignoring the "fonttbl"
    // identifier.
    foreach($fontTblGrp->children as $child) {
      // Ignore non-group, which should be the fonttbl identified word.
      if(!($child instanceof \RtfHtmlPhp\Group)) continue;
      // Load the font specification in the subgroup:
      $this->LoadFont($child);
    }
  }

  protected function ExtractColorTable($colorTblGrp) {
    // {\colortbl;\red0\green0\blue0;}
    // Index 0 of the RTF color table  is the 'auto' color
    $colortbl = array();
    $c = count($colorTblGrp);
    $color = '';
    for ($i=1; $i<$c; $i++) { // Iterate through colors
      if($colorTblGrp[$i] instanceof \RtfHtmlPhp\ControlWord) {
        // Extract RGB color and convert it to hex string
        $color = sprintf('#%02x%02x%02x', // hex string format
                            $colorTblGrp[$i]->parameter, // red
                            $colorTblGrp[$i+1]->parameter, // green
                            $colorTblGrp[$i+2]->parameter); // blue
        $i+=2;
      } elseif($colorTblGrp[$i] instanceof \RtfHtmlPhp\Text) {
        // This is a delimiter ';' so
        if ($i != 1) { // Store the already extracted color
          $colortbl[] = $color;
        } else { // This is the 'auto' color
          $colortbl[] = 0;
        }
      }
    }
    State::$colortbl = $colortbl;
  }

  protected function ExtractImage($pictGrp)
  {
    $Image = new Image();
    foreach ($pictGrp as $child) {
      if ($child instanceof \RtfHtmlPhp\ControlWord) {
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

      } elseif ($child instanceof \RtfHtmlPhp\Text) { // store Data
        $Image->ImageData = $child->text;
      }
    }
    // output Image
    $this->output .= $Image->PrintImage();
    unset($Image);
  }

  protected function ProcessGroup($group)
  {
    // Special group processing:
    switch ($group->GetType())
    {
      case "fonttbl": // Extract font table
        $this->ExtractFontTable($group);
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
      case "*": // Process destination
        $this->ProcessDestination($group->children);
        return;
    }

    // Pictures extraction not yet supported
    //if(substr($group->GetType(), 0, 4) == "pict") return;

    // Push a new state onto the stack:
    $this->state = clone $this->state;
    array_push($this->states, $this->state);

    foreach($group->children as $child) {
      $this->FormatEntry($child);
    }

    // Pop state from stack
    array_pop($this->states);
    $this->state = $this->states[sizeof($this->states)-1];
  }

  protected function ProcessDestination($dest)
  {
    if (!$dest[1] instanceof \RtfHtmlPhp\ControlWord) return;
    // Check if this is a Word 97 picture
    if ($dest[1]->word == "shppict") {
      $c = count($dest);
      for ($i=2;$i<$c;$i++)
        $this->FormatEntry($dest[$i]);
      }
  }

  protected function FormatEntry($entry)
  {
    if($entry instanceof \RtfHtmlPhp\Group) $this->ProcessGroup($entry);
    elseif($entry instanceof \RtfHtmlPhp\ControlWord) $this->FormatControlWord($entry);
    elseif($entry instanceof \RtfHtmlPhp\ControlSymbol) $this->FormatControlSymbol($entry);
    elseif($entry instanceof \RtfHtmlPhp\Text) $this->FormatText($entry);
  }

  protected function FormatControlWord($word)
  {
    switch($word->word) {
      case 'plain': // Reset font formatting properties to default.
      case 'pard':  // Reset to default paragraph properties.
        $this->state->Reset($this->defaultFont);
        break;

      /*
       * Font formatting properties:
       */

      case 'b': // bold
        $this->state->bold = $word->parameter;
        break;
      case 'i': // italic
        $this->state->italic = $word->parameter;
        break;
      case 'ul': // underline
        $this->state->underline = $word->parameter;
        break;
      case 'ulnone': // no underline
        $this->state->underline = false;
        break;
      case 'strike': // strike-through
        $this->state->strike = $word->parameter;
        break;
      case 'v': // hidden
        $this->state->hidden = $word->parameter;
        break;
      case 'fs': // Font size
        $this->state->fontsize = ceil(($word->parameter / 24) * 16);
        break;
      case 'f': // Font
        $this->state->font = $word->parameter;
        break;
      case 'deff': // Store default font
        $this->defaultFont = $word->parameter;
        break;

      /*
       * Colors
       */

      case 'cf':
      case 'chcfpat':
        $this->state->fontcolor = $word->parameter;
        break;
      case 'cb':
      case 'chcbpat':
        $this->state->background = $word->parameter;
        break;
      case 'highlight':
        $this->state->hcolor = $word->parameter;
        break;

      /*
       * Special characters
       */

      case 'lquote':    $this->Write("&lsquo;"); break;  // &#145; &#8216;
      case 'rquote':    $this->Write("&rsquo;"); break;  // &#146; &#8217;
      case 'ldblquote': $this->Write("&ldquo;"); break;  // &#147; &#8220;
      case 'rdblquote': $this->Write("&rdquo;"); break;  // &#148; &#8221;
      case 'bullet':    $this->Write("&bull;");  break;  // &#149; &#8226;
      case 'endash':    $this->Write("&ndash;"); break;  // &#150; &#8211;
      case 'emdash':    $this->Write("&mdash;"); break;  // &#151; &#8212;
      case 'enspace':   $this->Write("&ensp;");  break;  // &#8194;
      case 'emspace':   $this->Write("&emsp;");  break;  // &#8195;
      case 'tab':       $this->Write("&nbsp;");  break;  // Character value 9
      case 'line':      $this->output .= "<br/>"; break; // character value (line feed = &#10;) (carriage return = &#13;)

      /*
       * Unicode characters
       */

      case 'u':
        $uchar = $this->DecodeUnicode($word->parameter);
        $this->Write($uchar);
        break;

      /*
       * Paragraphs
       */
      case 'par':
      case 'row':
        // Close previously opened tags
        $this->CloseTags();
        // Begin a new paragraph
        $this->OpenTag('p');
        break;

      /* Code pages */
      case 'ansi':
      case 'mac':
      case 'pc':
      case 'pca':
        $this->RTFencoding = $this->GetEncodingFromCodepage($word->word);
        break;
      case 'ansicpg':
        if($word->parameter) {
          $this->RTFencoding = $this->GetEncodingFromCodepage($word->parameter);
        }
        break;
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
    // Create a new 'span' element only when a style change occurs.
    // 1st case: style change occured
    // 2nd case: there is no change in style but the already created 'span'
    // element is somehow closed (ex. because of an end of paragraph)
    if  (!$this->state->equals($this->previousState) ||
        ($this->state->equals($this->previousState) && !$this->openedTags['span']))
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
    $this->openedTags[$tag] = true;
  }

  protected function CloseTag($tag)
  {
    if ($this->openedTags[$tag]) {
      // Check for empty html elements
      if (substr($this->output ,-strlen("<{$tag}>")) == "<{$tag}>"){
        switch ($tag)
        {
          case 'p': // Replace empty 'p' element with a line break
            $this->output = substr($this->output ,0, -3) . "<br>";
            break;
          default: // Delete empty elements
            $this->output = substr($this->output ,0, -strlen("<{$tag}>"));
            break;
        }
      } else {
        $this->output .= "</{$tag}>";
      }
      $this->openedTags[$tag] = false;
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
      if (isset(State::$fonttbl[$this->state->font]->codepage)) {
        return State::$fonttbl[$this->state->font]->codepage;

      } elseif (isset(State::$fonttbl[$this->state->font]->charset)) {
        return State::$fonttbl[$this->state->font]->charset;
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

