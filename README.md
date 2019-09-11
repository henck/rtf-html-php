# rtf-html-php

_An RTF to HTML converter in PHP_

In a recent project, I desperately needed an RTF to HTML converter written in PHP. Googling around turned up some matches, but I could not get them to work properly. Also, one of them called `passthru()` to use a RTF2HTML executable, which is something I didn’t want. I was looking for an RTF to HTML converter written purely in PHP.

Since I couldn’t find anything ready-made, I sat down and coded one up myself. It’s short, and it works, implementing the subset of RTF tags that you’ll need in HTML and ignoring the rest. As it turns out, the RTF format isn’t that complicated when you really look at it, but it isn’t something you code a parser for in 15 minutes either.

## How to use it

Include the file `rtf-html-php.php` somewhere in your project. Then do this:

    use RtfHtmlPhp\Reader;

    $reader = new Reader();
    $rtf = file_get_contents("test.rtf"); // or use a string
    $result = $reader->Parse($rtf);
    
The parser will return `true` if the RTF was parsed successfully, or `false` if the RTF was malformed. Parse errors will generate PHP notices.

If you’d like to see what the parser read (for debug purposes), then call this (but only if the RTF was successfully parsed):

    $reader->root->dump();

To convert the parser’s parse tree to HTML, call this (but only if the RTF was successfully parsed):

    use RtfHtmlPhp\Html\Html;
    $formatter = new Html();
    echo $formatter->Format($reader->root);

For enhanced compatibility the default character encoding of the converted RTF unicode characters is set to `HTML-ENTITIES`. To change the default encoding, you can initialize the `Html` object with the desired encoding supported by `mb_list_encodings()`: ex. `UTF-8`

    $formatter = new Html('UTF-8');

## Install via Composer

```
composer require henck/rtf-to-html
```

## Caveats

* Please note that rtf-html-php requires your PHP installation to support the `mb_convert_encoding` function. Therefore you must have the `php-mbstring` module installed. For fresh PHP installations, it will usually be there.


## History

#### Update 11 Sep '19:
* Split code up into several classes under `/src`
* Lots of code documentation
* Added some PHPUnit test cases
* Use namespace
* Set version to 1.1

#### Update 26 Oct '18:

* Adds support for Font table extraction.
* Adds support for Pictures.
* Adds support for additional control symbols.
* Updates the way the parser parses unicode and its replacement character(s).
* Updated Html formatter: now it reads the proper encoding from RTF document and/or from current font.
* Updated unicode conversion method: now it takes into account the right encoding of the Rtf document.

#### Update 2 Sep '18:

* Unicode characters are now fully supported
* Font color & background are now supported
* Better HTML tag handling

#### Update 11 Jun '18:

* Better display for text with altered font-size 

#### Update 10 Mar '16:

* The RTF parser would either issue warnings or go into an infinite loop when parsing a malformed RTF. Instead, it now returns TRUE when parsing was successful, and FALSE if it was not.

#### Update 23 Feb '16:

* The RTF to HTML converter can now be installed through Composer (thanks to felixkiss).

#### Update 28 Oct '15:

* A bug causing control words to be misparsed occasionally is now fixed.

#### Update 3 Sep ’14:

* Fixed bug: underlining would start but never end. Now it does.
* Feature request: images are now filtered out of the output.
