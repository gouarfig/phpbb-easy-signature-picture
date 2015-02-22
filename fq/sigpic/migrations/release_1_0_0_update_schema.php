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

class release_1_0_0_update_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_sigpic');
	}

	public function update_schema()
	{
		return array(
			'add_columns'		=> array(
				$this->table_prefix . 'users'	=> array(
					'user_sigpic'			=> array('VCHAR', ''),
					'user_sigpic_width'		=> array('USINT', 0),
					'user_sigpic_height'	=> array('USINT', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
//			'drop_columns'		=> array(
//				$this->table_prefix . 'users'	=> array(
//					'user_sigpic',
//					'user_sigpic_width',
//					'user_sigpic_height',
//				),
//			),
		);
	}
}
