<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

class plgContentSyntaxhighlighterGhsvs extends CMSPlugin
{
	protected $basepath = 'plg_content_syntaxhighlighterghsvs';
	protected static $loaded = 0;
	protected $app;

	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (
			static::$loaded
			|| $context !== 'com_content.article'
			|| !$this->app->isClient('site')
			|| $this->app->input->get('view') !== 'article'
			|| (!$this->params->get('robots', 0) && $this->app->client->robot)
			|| !isset($article->text)
			|| !trim($article->text)
			|| $this->app->getDocument()->getType() !== 'html'
			|| $this->app->input->getBool('print')
		){
			return;
		}

		$tag = $this->params->get('tag', 'pre');

		$tag_ = '<' . $tag;

		if (
			strpos($article->text, $tag_ . ' ') === false
			|| (
				strpos($article->text, $tag_ . ' class="brush:') === false
				&& strpos($article->text, $tag_ . ' class=\'brush:') === false
				&& strpos($article->text, $tag_ . ' class=brush:') === false
			)
		){
			return;
		}

		$combine = $this->params->get('combine', 1);

		/*
		$muster   = array();
		$muster[] = '/';
		$muster[] = $tag_;
		$muster[] = '\s+class=("|\'|)brush:([a-zA-Z0-9#]+)';
		$muster[] = '/';
		$muster   = implode('', $muster);
		*/

		$muster = '/' . $tag_ . '\s+class=("|\'|)brush:([a-zA-Z0-9#]+)' . '/';

		if (!preg_match_all($muster, $article->text, $neededBrushes))
		{
			return;
		}

		$neededBrushes = array_flip($neededBrushes[2]);

		$config = array();
		$combineSuccess = 0;
		$min = JDEBUG ? '' : '.min';
		$version = JDEBUG ? time() : 'auto';

		JLoader::register('SyntaxhighlighterGhsvsHelper', __DIR__ . '/helper.php');

		$aliasesBrushfileMap = SyntaxhighlighterGhsvsHelper::getAliasesBrushfileMap();

		// Fatal error.
		if ($aliasesBrushfileMap === false)
		{
			return;
		}

		// Needed brushes in current article.
		/*
		Array
		(
    [php] => shBrushPhp
    [text] => shBrushPlain
    [xml] => shBrushXml
		)
		*/
		$neededBrushes = array_unique(array_intersect_key($aliasesBrushfileMap, $neededBrushes));

		if (empty($neededBrushes))
		{
			return;
		}
		
		// js files that shall be loaded or combined.
		$brushFiles = array();
		
		// parts of filename of combined files. 
		$combinedFilename = array();

		foreach ($neededBrushes as $alias => $brush)
		{
			$combinedFilename[] = trim(ucfirst($alias));
			$brushFiles[]      = $brush . $min . '.js';
		}

		// Nothing todo. Maybe error.
		if (!$brushFiles)
		{
			return;
		}
		elseif ($combine && $combinedFilename)
		{
			sort($combinedFilename, SORT_NATURAL | SORT_FLAG_CASE);

			// Filename for combined bruskes js. E.g. Core_PhpTextXmlBrushes.min.js.
			$combinedFilename = '_combiByPlugin/' . 'Core_' . implode('', $combinedFilename) . 'Brushes' . $min . '.js';

			// Combined brushes file or override exists?
			$fileExist = HTMLHelper::_('script',
				$this->basepath . '/' . $combinedFilename,
				array('relative' => true, 'pathOnly' => true)
			);
			
			// If it exists it already contains Core and xregexp.
			if ($fileExist)
			{
				// Now we need only this file and can forget other brushes.
				$brushFiles = array($combinedFilename);
				$combineSuccess = 1;
			}
			// We have to create a new combined file.
			else
			{
				$contents = array();

				// ToDo: Add Copyright if minified.
				$contents[] = file_get_contents(
					JPATH_SITE . '/media/' . $this->basepath . '/js/xregexp-2.0.0/xregexp' . $min . '.js'
				);

				// CORE-File or CORE-File-override exists?
				$fileExist = HTMLHelper::_('script',
					$this->basepath . '/' . 'shCore' . $min . '.js',
					array('relative' => true, 'pathOnly' => true)
				);
				
				// Fatal error.
				if (!$fileExist)
				{
					return;
				}
				
				// ToDo: Add Copyright.
				$contents[] = file_get_contents(JPATH_SITE . $fileExist);

				foreach ($brushFiles as $file)
				{
					// File or override exists?
					$fileExist = HTMLHelper::_('script',
						$this->basepath . '/' . $file,
						array('relative' => true, 'pathOnly' => true)
					);

					if ($fileExist)
					{
						$contents[] = file_get_contents(JPATH_SITE . $fileExist);
					}
					// Forget combining.
					else
					{
						$combineSuccess = 0;
						$contents = false;
						break;
					}
				}

				if ($contents)
				{
					$contents = implode(";\n;", $contents);

					if (
						File::write(JPATH_SITE . '/media/' . $this->basepath . '/js/' . $combinedFilename, $contents)
					){
						// We just need 1 file now. The combined one.
						$brushFiles = array($combinedFilename);
						$combineSuccess = 1;
					}
					else
					{
						$combineSuccess = 0;
					}
				}
			}
		} // END elseif ($combine && $combinedFilename)

		// Combination not wanted or failed. Load files separately from plugin core.
		$filesCore = array();

		if (!$combine || !$combineSuccess)
		{
			$filesCore = array(
				'xregexp-2.0.0/xregexp' . $min . '.js',
				'shCore' . $min . '.js',
			);
		}
		
		// $brushFiles contains 1 cmbined or several files.
		foreach (array_merge($filesCore, $brushFiles) as $file)
		{
			HTMLHelper::_('script',
				$this->basepath . '/' . $file,
				array('relative' => true, 'version' => $version)
			);
		}

		// Load configuration JS.
		if ($this->params->get('stripbrs', 0))
		{
			$config[] = 'SyntaxHighlighter.config.stripBrs = true;';
		}

		$config[] = 'SyntaxHighlighter.config.tagName="' . $tag . '";';

		if (! $this->params->get('auto-links', 0))
		{
		 $config[] = 'SyntaxHighlighter.defaults["auto-links"] = false;';
		}

		if (($cname = trim($this->params->get('class-name', ''))))
		{
			$config[] = 'SyntaxHighlighter.defaults["class-name"] = "' . $cname . '";';
		}

		// Nonsense.
		$config[] = 'SyntaxHighlighter.defaults["toolbar"] = false;';

		if (! $this->params->get('gutter', 1))
		{
			$config[] = 'SyntaxHighlighter.defaults["gutter"] = false;';
		}

		if (! $this->params->get('quick-code', 1))
		{
			$config[] = 'SyntaxHighlighter.defaults["quick-code"] = false;';
		}

 		$js = implode('', $config) . ';SyntaxHighlighter.all();';
 		Factory::getDocument()->addScriptDeclaration($js);

		if ($file = $this->params->get('stylesheets', 'shCoreDefault'))
		{
			HTMLHelper::_('stylesheet',
				$this->basepath . '/' . $file . $min . '.css',
				array('relative' => true, 'version' => $version)
			);
		}

		// Custom CSS.
		$customCssRules = $this->params->get('customCss', null);
		$css = '';

		if (is_object($customCssRules) && count(get_object_vars($customCssRules)))
		{
			foreach ($customCssRules as $customCssRule)
			{
				$customCssRule = new Registry($customCssRule);

				if (
					$customCssRule->get('active', 0)
					&& ($selector = trim($customCssRule->get('selector', '')))
					&& ($cssRules = trim($customCssRule->get('cssRules', '')))
				){
			  	$css .= $selector . '{' . $cssRules  . '}';
				}
			}
		}
		
		if ($css)
		{
			$css = str_replace(array("\n", "\r", "\t"), '', $css);
			Factory::getDocument()->addStyleDeclaration($css);
		}

		static::$loaded = 1;
		return true;
	}
}
