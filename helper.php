<?php
defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\Folder;

class SyntaxhighlighterGhsvsHelper
{
	protected static $basepath = '/media/plg_content_syntaxhighlighterghsvs';
	
	/**
   * Detect brushes and their aliases from shBrushXyz.js files.
	 * Write collected arrays to json files.
	 */
	public static function brushesToFiles($forceRefresh = false)
	{
		$brushfileAliasesMapJson = JPATH_SITE . self::$basepath . '/js/_combiByPlugin/brushfileAliasesMap.json';
		$aliasesBrushfileMapJson = JPATH_SITE . self::$basepath . '/js/_combiByPlugin/aliasesBrushfileMap.json';

		if ($forceRefresh || !is_file($brushfileAliasesMapJson) || !is_file($aliasesBrushfileMapJson))
		{
			$files = Folder::files(JPATH_SITE . self::$basepath . '/js', 'shBrush[A-Za-z]+\.js$');
			
			if (!$files)
			{
				return false;
			}

			$brushfileAliasesMap = array();
			$aliasesBrushfileMap = array();
			$muster  = '/Brush.aliases\s*\=\s*\[([^]]+)\]\s*;/';
			
			foreach ($files as $brushFile)
			{
				$content = @file_get_contents(JPATH_SITE . self::$basepath . '/js/' . $brushFile);

				if (empty($content))
				{
					continue;
				}
				
				preg_match($muster, $content, $matches);
				
				// Comma separated aliases string.
				if (empty($matches[1]))
				{
					continue;
				}
				
				$matches[1] = str_replace(array('"', "'", ' '), '', $matches[1]);
				$matches[1] = explode(',', $matches[1]);
				$brushFile  = str_replace('.js', '', $brushFile);

				$brushfileAliasesMap[$brushFile] = $matches[1];

				foreach ($matches[1] as $alias)
				{
					$aliasesBrushfileMap[$alias] = $brushFile;
				}
			}

			if ($brushfileAliasesMap && $aliasesBrushfileMap)
			{
				file_put_contents($brushfileAliasesMapJson, json_encode($brushfileAliasesMap));
				file_put_contents($aliasesBrushfileMapJson, json_encode($aliasesBrushfileMap));
			}
		}

		if (is_file($brushfileAliasesMapJson) && is_file($aliasesBrushfileMapJson))
		{
			return true;
		}
		return false;
	}
	
	/**
   * Detect CSS Themes.
	 * Write collected array to json file.
	 * shCoreXyz CSS files contain shThemeXyz contents because of SASS compilation (@import).
	 */
	public static function stylesheetsToFile($forceRefresh = false)
	{
		$coreStylesheets = JPATH_SITE . self::$basepath . '/js/_combiByPlugin/coreStylesheets.json';

		if ($forceRefresh || !is_file($coreStylesheets))
		{
			$files = Folder::files(JPATH_SITE . self::$basepath . '/css', 'shCore[A-Za-z]+\.css$');

			if (!$files)
			{
				return false;
			}

			$collect = array();

			foreach ($files as $file)
			{
				$collect[str_replace('.css', '', $file)] = $file;
			}

			file_put_contents($coreStylesheets, json_encode($collect));
		}

		if (is_file($coreStylesheets))
		{
			return true;
		}
		return false;
	}

	public static function getAliasesBrushfileMap($forceRefresh = false)
	{
		if (self::brushesToFiles($forceRefresh))
		{
			$content = file_get_contents(JPATH_SITE . self::$basepath . '/js/_combiByPlugin/aliasesBrushfileMap.json');
			return json_decode($content, true);
		}
		return false;
	}

	public static function getBrushfileAliasesMap($forceRefresh = false)
	{
		if (self::brushesToFiles($forceRefresh))
		{
			$content = file_get_contents(JPATH_SITE . self::$basepath . '/js/_combiByPlugin/brushfileAliasesMap.json');
			return json_decode($content, true);
		}
		return false;
	}

	public static function getCoreStylesheets($forceRefresh = false)
	{
		if (self::stylesheetsToFile($forceRefresh))
		{
			$content = file_get_contents(JPATH_SITE . self::$basepath . '/js/_combiByPlugin/coreStylesheets.json');
			return json_decode($content, true);
		}
		return false;
	}
}