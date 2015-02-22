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

class release_1_0_0_add_bbcodes extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\fq\sigpic\migrations\release_1_0_0_update_schema');
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'update_sigpic_bbcode'))),
		);
	}

	public function revert_data()
	{
		return array(
			array('custom', array(array($this, 'revert_sigpic_bbcode'))),
		);
	}

	public function update_sigpic_bbcode()
	{
		// We change the BBcode to an hidden tag. That's because:
		// - when the extension is activated, it's managed by an event
		// - when the extension is not activated, it's showing nothing
		$bbcode_data = array(
			'sigpic' => array(
				'bbcode_helpline'		=> 'Insert signature picture: [sigpic][/sigpic]',
				'bbcode_match'			=> '[sigpic]{TEXT}[/sigpic]',
				'bbcode_tpl'			=> '<img class="signature_picture">',
				'display_on_posting'	=> 0,
			),
		);
		$this->install_bbcodes($bbcode_data);
	}
	
	public function revert_sigpic_bbcode()
	{
		// We display an error message in place of the signature picture
		$bbcode_data = array(
			'sigpic' => array(
				'bbcode_helpline'		=> 'Display signature picture: [sigpic][/sigpic]',
				'bbcode_match'			=> '[sigpic]{TEXT}[/sigpic]',
				'bbcode_tpl'			=> '<table class="ModTable" style="background-color:#FFFFFF;border:1px solid #000000;border-collapse:separate;border-spacing:5px;padding:0;width:100%;color:#333333;overflow:hidden;"><tr><td class="exclamation" rowspan="2" style="background-color:#ff6060;font-weight:bold;font-family:\'Times New Roman\',Verdana,sans-serif;font-size:4em;color:#ffffff;vertical-align:middle;text-align:center;width:1%;">&nbsp;!&nbsp;</td><td class="rowuser" style="border-bottom:1px solid #000000;font-weight:bold;">Warning</td></tr><tr><td class="row text">The signature picture extension is not installed.</td></tr></table>',
				'display_on_posting'	=> 0,
			),
		);
		$this->install_bbcodes($bbcode_data);
	}
	
	/**
	* Installs BBCodes, used by migrations to perform add/updates
	*
	* @param array $bbcode_data Array of BBCode data to install
	* @return null
	* @access public
	*/
	public function install_bbcodes($bbcode_data)
	{
		// Load the acp_bbcode class
		if (!class_exists('acp_bbcodes'))
		{
			include($this->phpbb_root_path . 'includes/acp/acp_bbcodes.' . $this->php_ext);
		}
		$bbcode_tool = new \acp_bbcodes();

		foreach ($bbcode_data as $bbcode_name => $bbcode_array)
		{
			// Build the BBCodes
			$data = $bbcode_tool->build_regexp($bbcode_array['bbcode_match'], $bbcode_array['bbcode_tpl']);

			$bbcode_array += array(
				'bbcode_tag'			=> $data['bbcode_tag'],
				'first_pass_match'		=> $data['first_pass_match'],
				'first_pass_replace'	=> $data['first_pass_replace'],
				'second_pass_match'		=> $data['second_pass_match'],
				'second_pass_replace'	=> $data['second_pass_replace']
			);

			$sql = 'SELECT bbcode_id
				FROM ' . BBCODES_TABLE . "
				WHERE LOWER(bbcode_tag) = '" . strtolower($bbcode_name) . "'
				OR LOWER(bbcode_tag) = '" . strtolower($bbcode_array['bbcode_tag']) . "'";
			$result = $this->db->sql_query($sql);
			$row_exists = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($row_exists)
			{
				// Update existing BBCode
				$bbcode_id = $row_exists['bbcode_id'];

				$sql = 'UPDATE ' . BBCODES_TABLE . '
					SET ' . $this->db->sql_build_array('UPDATE', $bbcode_array) . '
					WHERE bbcode_id = ' . $bbcode_id;
				$this->db->sql_query($sql);
			}
			else
			{
				// Create new BBCode
				$sql = 'SELECT MAX(bbcode_id) AS max_bbcode_id
					FROM ' . BBCODES_TABLE;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if ($row)
				{
					$bbcode_id = $row['max_bbcode_id'] + 1;

					// Make sure it is greater than the core BBCode ids...
					if ($bbcode_id <= NUM_CORE_BBCODES)
					{
						$bbcode_id = NUM_CORE_BBCODES + 1;
					}
				}
				else
				{
					$bbcode_id = NUM_CORE_BBCODES + 1;
				}

				if ($bbcode_id <= BBCODE_LIMIT)
				{
					$bbcode_array['bbcode_id'] = (int) $bbcode_id;
					$bbcode_array['display_on_posting'] = 1;

					$this->db->sql_query('INSERT INTO ' . BBCODES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $bbcode_array));
				}
			}
		}
	}
}
