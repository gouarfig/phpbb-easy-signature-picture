<?php
/**
*
* Signature Picture
*
* @version 1.0.0
* @copyright (c) 2015 Fred Quointeau
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace fq\sigpic\migrations;

class release_1_0_0_config extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\fq\sigpic\migrations\release_1_0_0_add_bbcodes');
	}
	
	public function effectively_installed()
	{
		return isset($this->config['sigpic_path']);
	}
	
	public function update_data()
	{
		global $phpbb_root_path;
		$sigpic_path = 'images/signature_pics';
		
		if (substr($phpbb_root_path, -1, 1) != '/')
		{
			$phpbb_root_path .= '/';
		}
		if (!is_dir($phpbb_root_path . $sigpic_path))
		{
			@mkdir($phpbb_root_path . $sigpic_path, 0777, TRUE);
		}
		
		return array(
			array('config.add', array('sigpic_path', $sigpic_path)),
			array('config.add', array('sigpic_salt', $this->config['avatar_salt'])),
		);
	}

	public function revert_data()
	{
		return array(
		);
	}
}
