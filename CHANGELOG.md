# Changelog

## 1.2

### Update 05 June '24:
- Declared properties in classes to prevent dynamic property warnings.
- Allowed default formatting to be applied even when a font is set without valid table definitions.
- Updated tests to fix previously failing tests .
- Set version to 1.2

## 1.1

### Update 11 Sep '19:
- Split code up into several classes under `/src`
- Lots of code documentation
- Added some PHPUnit test cases
- Use namespace
- Set version to 1.1

## 1.0

### Update 26 Oct '18:

- Adds support for Font table extraction.
- Adds support for Pictures.
- Adds support for additional control symbols.
- Updates the way the parser parses unicode and its replacement character(s).
- Updated Html formatter: now it reads the proper encoding from RTF 
  document and/or from current font.
- Updated unicode conversion method: now it takes into account the 
  right encoding of the RTF document.

### Update 2 Sep '18:

- Unicode characters are now fully supported
- Font color & background are now supported
- Better HTML tag handling

### Update 11 Jun '18:

- Better display for text with altered font-size 

### Update 10 Mar '16:

- The RTF parser would either issue warnings or go into an infinite 
  loop when parsing a malformed RTF. Instead, it now returns `true` when 
  parsing was successful, and `false` if it was not.

### Update 23 Feb '16:

- The RTF to HTML converter can now be installed through Composer 
  (thanks to @felixkiss).

### Update 28 Oct '15:

- A bug causing control words to be misparsed occasionally is now fixed.

### Update 3 Sep ’14:

- Fixed bug: underlining would start but never end. Now it does.
- Feature request: images are now filtered out of the output. 
