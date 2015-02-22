<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../../../../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

/**
* A simplified function to deliver signature pictures
* The argument needs to be checked before calling this function.
*/
function send_sigpic_to_browser($file, $browser)
{
	global $config, $phpbb_root_path;

	if (!isset($config['sigpic_salt']))
	{
		$config['sigpic_salt'] = $config['avatar_salt'];
	}
	$prefix = $config['sigpic_salt'] . '_';
	$image_dir = (isset($config['sigpic_path'])) ? $config['sigpic_path'] : 'images/signature_pics';

	// Adjust image_dir path (no trailing slash)
	if (substr($image_dir, -1, 1) == '/' || substr($image_dir, -1, 1) == '\\')
	{
		$image_dir = substr($image_dir, 0, -1) . '/';
	}
	$image_dir = str_replace(array('../', '..\\', './', '.\\'), '', $image_dir);

	if ($image_dir && ($image_dir[0] == '/' || $image_dir[0] == '\\'))
	{
		$image_dir = '';
	}
	$file_path = $phpbb_root_path . $image_dir . '/' . $prefix . $file;

	if ((@file_exists($file_path) && @is_readable($file_path)) && !headers_sent())
	{
		header('Cache-Control: public');

		$image_data = @getimagesize($file_path);
		header('Content-Type: ' . image_type_to_mime_type($image_data[2]));

		if ((strpos(strtolower($browser), 'msie') !== false) && !phpbb_is_greater_ie_version($browser, 7))
		{
			header('Content-Disposition: attachment; ' . header_filename($file));

			if (strpos(strtolower($browser), 'msie 6.0') !== false)
			{
				header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
			}
			else
			{
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
			}
		}
		else
		{
			header('Content-Disposition: inline; ' . header_filename($file));
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
		}

		$size = @filesize($file_path);
		if ($size)
		{
			header("Content-Length: $size");
		}

		if (@readfile($file_path) == false)
		{
			$fp = @fopen($file_path, 'rb');

			if ($fp !== false)
			{
				while (!feof($fp))
				{
					echo fread($fp, 8192);
				}
				fclose($fp);
			}
		}

		flush();
	}
	else
	{
		header('HTTP/1.0 404 Not Found');
	}
}

// Thank you sun.
if (isset($_SERVER['CONTENT_TYPE']))
{
	if ($_SERVER['CONTENT_TYPE'] === 'application/x-java-archive')
	{
		exit;
	}
}
else if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Java') !== false)
{
	exit;
}

if (isset($_GET['sigpic']))
{
	require($phpbb_root_path . 'includes/startup.' . $phpEx);

	require($phpbb_root_path . 'phpbb/class_loader.' . $phpEx);
	$phpbb_class_loader = new \phpbb\class_loader('phpbb\\', "{$phpbb_root_path}phpbb/", $phpEx);
	$phpbb_class_loader->register();

	$phpbb_config_php_file = new \phpbb\config_php_file($phpbb_root_path, $phpEx);
	extract($phpbb_config_php_file->get_all());

	if (!defined('PHPBB_INSTALLED') || empty($dbms) || empty($acm_type))
	{
		echo 'Error #1001';
		exit;
	}

	require($phpbb_root_path . 'includes/constants.' . $phpEx);
	require($phpbb_root_path . 'includes/functions.' . $phpEx);
	require($phpbb_root_path . 'includes/functions_download' . '.' . $phpEx);
	require($phpbb_root_path . 'includes/utf/utf_tools.' . $phpEx);

	// Setup class loader first
	$phpbb_class_loader_ext = new \phpbb\class_loader('\\', "{$phpbb_root_path}ext/", $phpEx);
	$phpbb_class_loader_ext->register();

	phpbb_load_extensions_autoloaders($phpbb_root_path);

	// Set up container
	$phpbb_container_builder = new \phpbb\di\container_builder($phpbb_config_php_file, $phpbb_root_path, $phpEx);
	$phpbb_container = $phpbb_container_builder->get_container();

	$phpbb_class_loader->set_cache($phpbb_container->get('cache.driver'));
	$phpbb_class_loader_ext->set_cache($phpbb_container->get('cache.driver'));

	// set up caching
	$cache = $phpbb_container->get('cache');

	$phpbb_dispatcher = $phpbb_container->get('dispatcher');
	$request	= $phpbb_container->get('request');
	$db			= $phpbb_container->get('dbal.conn');
	$phpbb_log	= $phpbb_container->get('log');

	unset($dbpasswd);

	request_var('', 0, false, false, $request);

	$config = $phpbb_container->get('config');
	set_config(null, null, null, $config);
	set_config_count(null, null, null, $config);

	// worst-case default
	$browser = strtolower($request->header('User-Agent', 'msie 6.0'));

	$filename = request_var('sigpic', '');
	$exit = false;

	// '==' is not a bug - . as the first char is as bad as no dot at all
	if (strpos($filename, '.') == false)
	{
		send_status_line(403, 'Forbidden');
		$exit = true;
	}

	if (!$exit)
	{
		$ext		= substr(strrchr($filename, '.'), 1);
		$stamp		= (int) substr(stristr($filename, '_'), 1);
		$filename	= (int) $filename;
		$exit = set_modified_headers($stamp, $browser);
	}
	if (!$exit && !in_array($ext, array('png', 'gif', 'jpg', 'jpeg')))
	{
		// no way such an avatar could exist.
		send_status_line(403, 'Forbidden');
		$exit = true;
	}

	if (!$exit)
	{
		if (!$filename)
		{
			// no way such an avatar could exist.
			send_status_line(403, 'Forbidden');
		}
		else
		{
			send_sigpic_to_browser($filename . '.' . $ext, $browser);
		}
	}
	file_gc();
}
