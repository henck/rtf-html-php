# rtf-html-php
RTF to HTML converter in PHP

In a recent project, I desperately needed an RTF to HTML converter written in PHP. Googling around turned up some matches, but I could not get them to work properly. Also, one of them called passthru() to use a RTF2HTML executable, which is something I didn’t want. I was looking for a RTF2HTML converter written purely in PHP.

Since I couldn’t find anything ready-made, I sat down and coded one up myself. It’s short, and it works, implementing the subset of RTF tags that you’ll need in HTML and ignoring the rest. As it turns out, the RTF format isn’t that complicated when you really look at it, but it isn’t something you code a parser for in 15 minutes either.

## How to use it

Include the file rtf-html-php.php somewhere in your project. Then do this:

    $reader = new RtfReader();
    $rtf = file_get_contents("test.rtf"); // or use a string
    $reader->Parse($rtf);

If you’d like to see what the parser read (for debug purposes), then call this:

    $reader->root->dump();

To convert the parser’s parse tree to HTML, call this:

    $formatter = new RtfHtml();
    echo $formatter->Format($reader->root);


#### Update 28 Oct '15:
 * A bug causing control words to be misparsed occasionally is now fixed.

#### Update 3 Sep ’14:

* Fixed bug: underlining would start but never end. Now it does.
* Feature request: images are now filtered out of the output.
