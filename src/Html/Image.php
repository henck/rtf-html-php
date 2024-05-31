<?php

namespace RtfHtmlPhp\Html;

class Image
{
  public $format;
  public $width;
  public $height;
  public $goalWidth;
  public $goalHeight;
  public $pcScaleX;
  public $pcScaleY;
  public $binarySize;
  public $ImageData;

  public function __construct()
  {
    $this->Reset();
  }

  public function Reset(): void
  {
    $this->format = 'bmp';
    $this->width = 0;         // in xExt if wmetafile otherwise in px
    $this->height = 0;        // in yExt if wmetafile otherwise in px
    $this->goalWidth = 0;     // in twips
    $this->goalHeight = 0;    // in twips
    $this->pcScaleX = 100;    // 100%
    $this->pcScaleY = 100;    // 100%
    $this->binarySize = null; // Number of bytes of the binary data
    $this->ImageData = null;  // Binary or Hexadecimal Data
  }

  public function PrintImage()
  {
    // <img src="data:image/{FORMAT};base64,{#BDATA}" />
    $output = "<img src=\"data:image/{$this->format};base64,";

    if (!is_null($this->binarySize)) { // process binary data
      return;
    } else { // process hexadecimal data
      $output .= base64_encode(pack('H*',$this->ImageData));
    }

    $output .= "\" />";
    return $output;
  }
}
