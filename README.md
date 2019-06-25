# Joomla plugin plg_content_syntaxhighlighterghsvs ("Content - Syntax Highlighter by GHSVS.de")

Code syntax highlighter that uses SyntaxHighlighter library (V3.0.90) of Alex Gorbatchev (https://github.com/syntaxhighlighter/syntaxhighlighter). Yes, it's a dinosaur but supports line numbers and line accentuation.

- Code syntax highlighter plugin for Joomla.
- Supports line numbers.
- Supports accentuation of lines.

## Last tests
- Joomla 3.9.8
- Joomla 4.0.0-dev alpha
- PHP 7.2, 7.3

## Prerequisites
- The `/media/` folder of your Joomla installation must be writable. That's the standard for Joomla that is hosted at a "normal" host/provider. You can check that in Joomla back-end (System Information > Folder Permissions). **Normally you don't have to change something**!
- - The plugin creates a folder `/media/plg_content_syntaxhighlighterghsvs/js/_combiByPlugin/` during installation.
- - There it saves later on combined JavaScript files like `Core_PhpTextXmlBrushes.min.js` for faster loading of ressources.
- - There it saves `*.json files` that are elementary for correct functionality of this plugin and the related editor button plugin ().
- - You can delete the files in directory `/media/plg_content_syntaxhighlighterghsvs/js/_combiByPlugin/`. The plugin will refresh them afterwards.

## How it works
- The plugin searches for `pre` HTML tags that have a special `class=brush:alias` attribute/value combination. Example for a php code snippet that shall be formatted in the front-end.

```
<pre class="brush: php;">
YOUR PHP CODE HERE
</pre>
```

- Thus the plugin knows that it shall load a so called brush with formatting instructions for PHP code.
- You'll find a list of available brushes and usable aliases after installation of the plugin inside plugin configuration.

## Installation
- Download last release https://github.com/GHSVS-de/plg_content_syntaxhighlighterghsvs/releases (Assets tab).
- Install in Joomla.
- Activate it and **don't forget to save it once**.

## Recommended
- For easier insertion of code snippets in Joomla editors: Install also the editor button plugin https://github.com/GHSVS-de/plg_editors-xtd_syntaxhighlighterghsvs/releases
