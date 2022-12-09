# rtf-html-php

_An RTF to HTML converter in PHP_

Este é um projeto proveniente do renck/rtfhtml onde consegui encontrar o que eu tanto precisava par aum projeto

## Como usar

Instale esse pacote usando isso:

```php
use RtfHtmlPhp\Document;

$rtf = file_get_contents("test.rtf"); 
$document = new Document($rtf); // or use a string directly
```

`Document` will raise an exception if the RTF document could not be parsed. Parse errors will generate PHP notices.

If you’d like to see what the parser read (for debug purposes), then call this:

```php
echo $document;
```

To convert the parser’s parse tree to HTML, call this (but only if the RTF was successfully parsed):

```php
use RtfHtmlPhp\Html\HtmlFormatter;
$formatter = new HtmlFormatter();
echo $formatter->Format($document);
```

For enhanced compatibility the default character encoding of the converted RTF unicode characters is set to `HTML-ENTITIES`. To change the default encoding, you can initialize the `Html` object with the desired encoding supported by `mb_list_encodings()`: ex. `UTF-8`

```php
$formatter = new HtmlFormatter('UTF-8');
```

## Install via Composer

```shell
composer require rafaelapuka/rtf-to-html
```

## Caveats

* Please note that rtf-html-php requires your PHP installation to support the `mb_convert_encoding` function. Therefore you must have the `php-mbstring` module installed. For fresh PHP installations, it will usually be there.

