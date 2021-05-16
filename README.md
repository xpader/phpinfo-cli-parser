# CLI phpinfo() Parser and Render.

Help you to show phpinfo() page in command line server.

Usage:
```php
<?php

use PhpInfoCliParser\Parser;

// parse phpinfo() and get data struct
$struct = Parser::parse();

// parse phpinfo() to HTML format
$phpinfoHtml = Parser::render();

```
