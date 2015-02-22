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


namespace fq\sigpic\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $user = null;
	protected $avatar_salt = '';

	static public function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_cache_user_data' => 'viewtopic_signature_add_sigpic_bbcode_to_user_cache_data',
			'core.ucp_pm_view_messsage' => 'view_message_signature_add_sigpic_bbcode',
			//'core.message_parser_check_message' => 'message_parser_extend_sigpic_bbcode',
			'core.modify_format_display_text_after' => 'modify_format_display_sigpic_bbcode',
		);
	}

	/**
	 * Constructor
	 * 
	 * @param \phpbb\user $user
	 */
	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
	}

	/**
	 * When building the user cache data, replace the signature pictures bbcode to an img tag
	 * 
	 * @param \phpbb\event\data $event
	 */
	public function viewtopic_signature_add_sigpic_bbcode_to_user_cache_data(\phpbb\event\data $event)
	{
		$user_cache_data = $event['user_cache_data'];
		$poster_id = $event['poster_id'];
		$signature = $user_cache_data['sig'];
		$bbcode_uid = $user_cache_data['sig_bbcode_uid'];
		$bbcode_bitfield = $user_cache_data['sig_bbcode_bitfield'];
		$row = $event['row'];
		$sigpic = $row['user_sigpic'];
		$width = $row['user_sigpic_width'];
		$height = $row['user_sigpic_height'];
		
		if (($poster_id > 0) && !empty($sigpic) && !empty($signature) && !empty($bbcode_uid) && !empty($bbcode_bitfield))
		{
			$this->replace_sigpic_bbcode($signature, $bbcode_uid, $sigpic, $width, $height);
			$user_cache_data['sig'] = $signature;
		}
		$event['user_cache_data'] = $user_cache_data;
	}
	
	/**
	 * When building the Private Message, replace the signature picture blank tag with the proper img tag
	 * We have a blank tag here because the signature have been BBcoded already
	 * 
	 * @param \phpbb\event\data $event
	 */
	public function view_message_signature_add_sigpic_bbcode(\phpbb\event\data $event)
	{
		$message_row = $event['message_row'];
		$sigpic = $message_row['user_sigpic'];
		$width = $message_row['user_sigpic_width'];
		$height = $message_row['user_sigpic_height'];

		$msg_data = $event['msg_data'];
		$signature = $msg_data['SIGNATURE'];
		if (($msg_data['S_BBCODE_ALLOWED']) && !empty($sigpic) && !empty($signature))
		{
			$this->replace_sigpic_tag($signature, $sigpic, $width, $height);
			$msg_data['SIGNATURE'] = $signature;
		}
		$event['msg_data'] = $msg_data;
	}
	
	/**
	 * TODO: Remove this unused function before packaging
	 * 
	 * @param \phpbb\event\data $event
	 */
	public function message_parser_extend_sigpic_bbcode($event)
	{
		if (($event['mode'] == 'sig') && !empty($event['message']))
		{
			$user_data = $this->user->data;
			$sigpic = $user_data['user_sigpic'];
			if (!empty($sigpic))
			{
				$message = $event['message'];
				$this->extend_sigpic_bbcode($message, $sigpic);
				$event['message'] = $message;
			}
		}
	}
	
	/**
	 * This function is called when preparing a preview of a new post/pm/signature.
	 * The annoying thing is that a [sigpic] code in a PM and a post will be displayed on the "preview" but never after.
	 * 
	 * @param \phpbb\event\data $event
	 */
	public function modify_format_display_sigpic_bbcode(\phpbb\event\data $event)
	{
		$text = $event['text'];
		$allow_bbcode = $event['allow_bbcode'];
		if ($allow_bbcode && !empty($text)) {
			$user_data = $this->user->data;
			$sigpic = $user_data['user_sigpic'];
			$width = $user_data['user_sigpic_width'];
			$height = $user_data['user_sigpic_height'];
			if (!empty($sigpic))
			{
				$this->replace_sigpic_tag($text, $sigpic, $width, $height);
				$event['text'] = $text;
			}
		}
		// It's only for the preview, we don't record the changes
		$event['update_this_message'] = false;
	}
	
	/**
	 * Replace the BBcode with the IMG tag
	 * 
	 * @param string $signature
	 * @param string $bbcode_uid
	 * @param string $sigpic
	 * @param int $width
	 * @param int $height
	 */
	protected function replace_sigpic_bbcode(&$signature, $bbcode_uid, $sigpic, $width, $height)
	{
		// TODO: We could check for the bbcode bitfield first, but I don't know if the bbcode information is already loaded and available
		$signature = preg_replace(
				'#\[sigpic:' . $bbcode_uid . '\].*?\[/sigpic:' . $bbcode_uid . '\]#i',
				'<img src="./ext/fq/sigpic/sig/file.php?sigpic=' . $sigpic . '" width=' . $width . ' height=' . $height . ' alt="User signature picture">',
				$signature);
	}

	/**
	 * Replace the blank IMG tag (that have been BBcoded already) with the proper one
	 * 
	 * @param string $signature
	 * @param string $sigpic
	 * @param int $width
	 * @param int $height
	 */
	protected function replace_sigpic_tag(&$signature, $sigpic, $width, $height)
	{
		$signature = str_replace(
				'<img class="signature_picture"',
				'<img src="./ext/fq/sigpic/sig/file.php?sigpic=' . $sigpic . '" width=' . $width . ' height=' . $height . ' alt="User signature picture"',
				$signature);
	}
	
	/**
	 * Not used any more
	 * 
	 * @param string $signature
	 * @param string $sigpic
	 */
	protected function extend_sigpic_bbcode(&$signature, $sigpic)
	{
		$signature = str_replace(
				'[sigpic]',
				'[sigpic=' . $sigpic . ']',
				$signature);
	}
}
