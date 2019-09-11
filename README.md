# rtf-html-php

_An RTF to HTML converter in PHP_

In a recent project, I desperately needed an RTF to HTML converter written in PHP. Googling around turned up some matches, but I could not get them to work properly. Also, one of them called `passthru()` to use a RTF2HTML executable, which is something I didn’t want. I was looking for an RTF to HTML converter written purely in PHP.

Since I couldn’t find anything ready-made, I sat down and coded one up myself. It’s short, and it works, implementing the subset of RTF tags that you’ll need in HTML and ignoring the rest. As it turns out, the RTF format isn’t that complicated when you really look at it, but it isn’t something you code a parser for in 15 minutes either.

## How to use it

Install this package using composer. Then do this:

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

