<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
*
* @Extra credits for this file
* Vjacheslav Trushkin (http://www.stsoftware.biz)
*
*/

// CTracker_Ignore: File checked by human
if (!defined('IN_ICYPHOENIX'))
{
	die('Hacking attempt');
}

/*

=================
Includes
=================

include(IP_ROOT_PATH . 'includes/bbcode.' . PHP_EXT);

=================
Globals
=================

global $bbcode;

=================
BBCode Parsing
=================

$text = $bbcode->parse($text);

=================
BBCode Conditions
=================

$bbcode->allow_html = ($userdata['user_allowhtml'] && $board_config['allow_html']) ? true : false;
$bbcode->allow_bbcode = ($userdata['user_allowbbcode'] && $board_config['allow_bbcode']) ? true : false;
$bbcode->allow_smilies = ($userdata['user_allowsmile'] && $board_config['allow_smilies']) ? true : false;

=================

$html_on = ($userdata['user_allowhtml'] && $board_config['allow_html']) ? 1 : 0 ;
$bbcode_on = ($userdata['user_allowbbcode'] && $board_config['allow_bbcode']) ? 1 : 0 ;
$smilies_on = ($userdata['user_allowsmile'] && $board_config['allow_smilies']) ? 1 : 0 ;

$bbcode->allow_html = $html_on;
$bbcode->allow_bbcode = $bbcode_on;
$bbcode->allow_smilies = $smilies_on;

=================

$bbcode->allow_html = ($board_config['allow_html'] ? $board_config['allow_html'] : false);
$bbcode->allow_bbcode = ($board_config['allow_bbcode'] ? $board_config['allow_bbcode'] : false);
$bbcode->allow_smilies = ($board_config['allow_smilies'] ? $board_config['allow_smilies'] : false);

=================

$bbcode->allow_html = (($board_config['allow_html'] && $row['enable_bbcode']) ? true : false);
$bbcode->allow_bbcode = (($board_config['allow_bbcode'] && $row['enable_bbcode']) ? true : false);
$bbcode->allow_smilies = (($board_config['allow_smilies'] && $row['enable_smilies']) ? true : false);

=================

$bbcode->allow_html = ($board_config['allow_html'] && $postrow[$i]['enable_bbcode'] ? true : false);
$bbcode->allow_bbcode = ($board_config['allow_bbcode'] && $postrow[$i]['enable_bbcode'] ? true : false);
$bbcode->allow_smilies = ($board_config['allow_smilies'] && $postrow[$i]['enable_smilies'] ? true : false);

=================

=================================
Acronyms, Autolinks, Word Wrap
=================================

$orig_autolink = array();
$replacement_autolink = array();
obtain_autolink_list($orig_autolink, $replacement_autolink, 99999999);
$text = $bbcode->acronym_pass($text);
if(count($orig_autolink))
{
	$text = autolink_transform($text, $orig_autolink, $replacement_autolink);
}
$text = kb_word_wrap_pass($text);
====================


*/

define('BBCODE_UID_LEN', 10);
define('BBCODE_NOSMILIES_START', '<!-- no smilies start -->');
define('BBCODE_NOSMILIES_END', '<!-- no smilies end -->');
define('AUTOURL', time());
global $db, $board_config, $lang;

// To use this file outside Icy Phoenix you need to comment the define below and remove the check on top of the file.
define('IS_ICYPHOENIX', true);
if(defined('IS_ICYPHOENIX'))
{
	include_once(IP_ROOT_PATH . 'language/lang_' . $board_config['default_lang'] . '/lang_bbcb_mg.' . PHP_EXT);
}
else
{
	if (!defined('IP_ROOT_PATH')) define('IP_ROOT_PATH', './../');
	if (!defined('PHP_EXT')) define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
	$board_config['default_lang'] = 'english';
	$board_config['server_name'] = 'icyphoenix.com';
	$board_config['script_path'] = '/';
	$board_config['liw_enabled'] = 0;
	$board_config['liw_max_width'] = 0;
	$board_config['thumbnail_cache'] = 0;
	$board_config['thumbnail_posts'] = 0;
	$board_config['thumbnail_lightbox'] = 0;
	$board_config['disable_html_guests'] = 0;
	$board_config['quote_iterations'] = 3;
	$board_config['switch_bbcb_active_content'] = 1;
	$userdata['session_logged_in'] = 0;
	$userdata['user_lang'] = 'english';
	$lang['OpenNewWindow'] = 'Open in new window';
	$lang['Click_enlarge_pic'] = 'Click to enlarge the image';
	$lang['Links_For_Guests'] = 'You must be logged in to see this link';
	$lang['Quote'] = 'Quote';
	$lang['Code'] = 'Code';
	$lang['OffTopic'] = 'Off Topic';
	$lang['ReviewPost'] = 'Review Post';
	$lang['wrote'] = 'wrote';
	$lang['Description'] = 'Description';
	$lang['Download'] = 'Download';
	$lang['Hide'] = 'Hide';
	$lang['Show'] = 'Show';
	$lang['Select'] = 'Select';
	$lang['xs_bbc_hide_message'] = 'Hidden';
	$lang['xs_bbc_hide_message_explain'] = 'This message is hidden, you have to answer this topic to see it.';
	$lang['DOWNLOADED'] = 'Downloaded';
	$lang['FILESIZE'] = 'Filesize';
	$lang['FILENAME'] = 'Filename';
	$lang['Not_Authorised'] = 'Not Authorised';
	$lang['FILE_NOT_AUTH'] = 'You are not authorised to download this file';
}

$urls_local = array(
	'http://www.' . $board_config['server_name'] . $board_config['script_path'],
	'http://' . $board_config['server_name'] . $board_config['script_path']
);

if (function_exists('create_server_url'))
{
	array_merge(array(create_server_url()), $urls_local);
}

class BBCode
{
	var $text = '';
	var $html = '';
	var $tag = '';

	var $code_counter = 0;

	var $allow_html = false;
	var $allow_styling = true;
	var $allow_bbcode = true;
	var $allow_smilies = true;
	var $is_sig = false;

	var $params = array();
	var $data = array();
	var $replaced_smilies = array();

	var $self_closing_tags = array('[*]', '[hr]');

	var $allowed_bbcode = array(
		'b'					=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'strong'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'em'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'i'					=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'u'					=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'tt'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'strike'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'sup'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'sub'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),

		'color'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'highlight'	=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'rainbow'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'gradient'	=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'fade'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'opacity'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),

		'align'			=> array('nested' => true, 'inurl' => false, 'allow_empty' => false),
		'center'		=> array('nested' => true, 'inurl' => false, 'allow_empty' => false),
		'font'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'size'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'hr'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => true),

		'url'				=> array('nested' => false, 'inurl' => false),
		'a'					=> array('nested' => false, 'inurl' => false),
		'email'			=> array('nested' => false, 'inurl' => false),

		'list'			=> array('nested' => true, 'inurl' => false),
		'ul'				=> array('nested' => true, 'inurl' => false),
		'ol'				=> array('nested' => true, 'inurl' => false),
		'li'				=> array('nested' => true, 'inurl' => false),
		'*'					=> array('nested' => true, 'inurl' => false),

		'span'			=> array('nested' => true, 'inurl' => false, 'allow_empty' => false),
		'cell'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'spoiler'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'hide'			=> array('nested' => false, 'inurl' => true, 'allow_empty' => false),

		'quote'			=> array('nested' => true, 'inurl' => false),
		'ot'				=> array('nested' => true, 'inurl' => false),
		'code'			=> array('nested' => false, 'inurl' => false),
		'codeblock'	=> array('nested' => false, 'inurl' => false),

		'img'				=> array('nested' => false, 'inurl' => true),
		'albumimg'	=> array('nested' => false, 'inurl' => true),
		'attachment' => array('nested' => false, 'inurl' => false, 'allow_empty' => true),
		'download'	=> array('nested' => false, 'inurl' => false, 'allow_empty' => true),

		'search'		=> array('nested' => true, 'inurl' => false, 'allow_empty' => false),
		'langvar'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => true),
		'language'	=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),

		'random'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => true),
		'marquee'		=> array('nested' => true, 'inurl' => false, 'allow_empty' => false),
		'smiley'		=> array('nested' => true, 'inurl' => false, 'allow_empty' => false),

		'flash'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'swf'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'flv'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'video'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'ram'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'quick'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'stream'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'emff'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'mp3'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'youtube'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'googlevideo' => array('nested' => true, 'inurl' => true, 'allow_empty' => false),

		// All these tags require HTML 4 specification (NON XHTML) and only work with IE!
		// Decomment below to use these properly...
		'glow'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'shadow'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'blur'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'wave'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'fliph'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'flipv'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		/*
		*/

		// Requires external file for parsing TEX
		//'tex'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),

		// To use tables you just need to decomment this... no need to decomment even TR and TD
		//'table'			=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		/*
		'tr'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'td'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		*/

		// To use IFRAMES you just need to decomment this line... good luck!
		//'iframe'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
	);

	var $allowed_html = array(
		'b'					=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'strong'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'em'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'i'					=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'u'					=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'tt'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'strike'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'sup'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'sub'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),

		'span'			=> array('nested' => true, 'inurl' => true),
		'center'		=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),
		'hr'				=> array('nested' => true, 'inurl' => true, 'allow_empty' => false),

		'a'					=> array('nested' => false, 'inurl' => false),
		'ul'				=> array('nested' => true, 'inurl' => false),
		'ol'				=> array('nested' => true, 'inurl' => false),
		'li'				=> array('nested' => true, 'inurl' => false),
		'blockquote' => array('nested' => true, 'inurl' => false),

		'table'			=> array('nested' => true, 'inurl' => false),
		/*
		'tr' => array('nested' => true, 'inurl' => false),
		'td' => array('nested' => true, 'inurl' => false),
		*/

		// To use IFRAMES you just need to decomment this line... good luck!
		//'iframe' => array('nested' => true, 'inurl' => true, 'allow_empty' => false),
	);

	var $allowed_smilies = array(
		array('code' => ':wink:', 'replace' => '(wink)'),
		array('code' => ';)', 'replace' => '(smile1)'),
		array('code' => ':)', 'replace' => '(smile2)'),
	);

	/*
	Clean bbcode/html tag.
	*/
	function clean_tag(&$item)
	{
		$tag = $item['tag'];
		//echo 'clean_tag(', $tag, ')<br />';
		$start = substr($this->text, $item['start'], $item['start_len']);
		$end = substr($this->text, $item['end'], $item['end_len']);
		$content = substr($this->text, $item['start'] + $item['start_len'], $item['end'] - $item['start'] - $item['start_len']);
		$error = array(
			'valid' => false,
			'start' => $this->process_text($start),
			'end' => $this->process_text($end)
		);
		if(isset($item['valid']) && $item['valid'] == false)
		{
			return $error;
		}

		// check if empty item is allowed
		if(!strlen($content))
		{
			$allow_empty = true;
			if($item['is_html'] && isset($this->allowed_html[$tag]['allow_empty']) && !$this->allowed_html[$tag]['allow_empty'])
			{
				$allow_empty = false;
			}
			if(!$item['is_html'] && isset($this->allowed_bbcode[$tag]['allow_empty']) && !$this->allowed_bbcode[$tag]['allow_empty'])
			{
				$allow_empty = false;
			}
			if(!$allow_empty)
			{
				return array(
					'valid' => true,
					'html' => '',
					'end' => '',
					'allow_nested' => false,
				);
			}
		}

		return array(
			'valid' => true,
			'start' => '',
			'end' => ''
		);
	}

	/*
	Process bbcode/html tag.
	This is the only function you would want to modify to add your own bbcode/html tags.
	Note: this bbcode parser doesn't make any differece of bbcode and html, so <b> and [b] are treated exactly same way
	*/
	function process_tag(&$item)
	{
		global $lang, $board_config, $userdata;
		//LIW - BEGIN
		$max_image_width = intval($board_config['liw_max_width']);
		//LIW - END
		$tag = $item['tag'];
		//echo 'process_tag(', $tag, ')<br />';
		$start = substr($this->text, $item['start'], $item['start_len']);
		$end = substr($this->text, $item['end'], $item['end_len']);
		$content = substr($this->text, $item['start'] + $item['start_len'], $item['end'] - $item['start'] - $item['start_len']);
		$error = array(
			'valid' => false,
			'start' => $this->process_text($start),
			'end' => $this->process_text($end)
		);
		if(isset($item['valid']) && $item['valid'] == false)
		{
			return $error;
		}

		// check if empty item is allowed
		if(!strlen($content))
		{
			$allow_empty = true;
			if($item['is_html'] && isset($this->allowed_html[$tag]['allow_empty']) && !$this->allowed_html[$tag]['allow_empty'])
			{
				$allow_empty = false;
			}
			if(!$item['is_html'] && isset($this->allowed_bbcode[$tag]['allow_empty']) && !$this->allowed_bbcode[$tag]['allow_empty'])
			{
				$allow_empty = false;
			}
			if(!$allow_empty)
			{
				return array(
					'valid' => true,
					'html' => '',
					'end' => '',
					'allow_nested' => false,
				);
			}
		}

		// check if nested item is allowed
		if($item['iteration'])
		{
			if($item['is_html'] && !$this->allowed_html[$tag]['nested'])
			{
				return $error;
			}
			if(!$item['is_html'] && !$this->allowed_bbcode[$tag]['nested'])
			{
				return $error;
			}
		}

		// Simple tags: B, EM, STRONG, I, U, TT, STRIKE, SUP, SUB, SPAN, CENTER
		if(($tag === 'b') || ($tag === 'em') || ($tag === 'strong') || ($tag === 'i') || ($tag === 'u') || ($tag === 'tt') || ($tag === 'strike') || ($tag === 'sup') || ($tag === 'sub') || ($tag === 'span') || ($tag === 'center'))
		{
			$extras = $this->allow_styling ? array('style', 'class', 'name') : array('class', 'name');
			$html = '<' . $tag . $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</' . $tag . '>'
			);
		}

		// COLOR
		if($tag === 'color')
		{
			$extras = $this->allow_styling ? array('class') : array();
			$color = $this->valid_color((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['color']) ? $item['params']['color'] : false)));
			if($color === false)
			{
				return $error;
			}
			/*
			$html = '<div style="display:inline;' . ($this->allow_styling && isset($item['params']['style']) ? htmlspecialchars($this->valid_style($item['params']['style'], '')) : '') . 'color: ' . $color . ';"' . $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</div>',
			);
			*/
			$html = '<span style="' . ($this->allow_styling && isset($item['params']['style']) ? htmlspecialchars($this->valid_style($item['params']['style'], '')) : '') . 'color: ' . $color . ';"' . $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</span>',
			);
		}

		// RAINBOW
		if($tag === 'rainbow')
		{
			/*
			if($this->is_sig)
			{
				return $error;
			}
			*/
			$html = $this->rainbow($content);
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false,
			);
		}

		// GRADIENT
		if($tag === 'gradient')
		{
			/*
			if($this->is_sig)
			{
				return $error;
			}
			*/
			$default_color1 = '#000080';
			$color1 = $this->valid_color((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['cols']) ? $item['params']['cols'] : $default_color1)), true);
			$color1 = (($color1 === false) ? $default_color1 : $color1);

			$default_color2 = '#aaccee';
			$color2 = $this->valid_color((isset($item['params']['cole']) ? $item['params']['cole'] : $default_color2), true);
			$color2 = (($color2 === false) ? $default_color2 : $color2);

			$mode = $this->process_text((isset($item['params']['mode']) ? $item['params']['mode'] : ''));

			$default_iterations = 10;
			$iterations = intval(isset($item['params']['iterations']) ? $item['params']['iterations'] : $default_iterations);
			$iterations = ((($iterations < 10) || ($iterations > 100)) ? $default_iterations : $iterations);

			$html = $this->gradient($content, $color1, $color2, $mode, $iterations);
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false,
			);
		}

		// HIGHLIGHT
		if($tag === 'highlight')
		{
			$extras = $this->allow_styling ? array('class') : array();
			$default_param = '#FFFFAA';
			$color = (isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['color']) ? $item['params']['color'] : $default_param));
			$color = $this->valid_color($color);
			if($color === false)
			{
				return $error;
			}
			$html = '<span style="' . ($this->allow_styling && isset($item['params']['style']) ? htmlspecialchars($this->valid_style($item['params']['style'], '')) : '') . 'background-color:' . $color . ';"' . $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</span>',
			);
		}

		// SIZE
		if($tag === 'size')
		{
			$extras = $this->allow_styling ? array('class') : array();
			$default_param = 0;
			$size = intval((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['size']) ? $item['params']['size'] : $default_param)));
			if($size > 0 && $size < 7)
			{
				// vBulletin-style sizes
				switch($size)
				{
					case 1: $size = 7; break;
					case 2: $size = 8; break;
					case 3: $size = 10; break;
					case 4: $size = 12; break;
					case 5: $size = 15; break;
					case 6: $size = 24; break;
				}
			}
			if($size < 6 || $size > 48)
			{
				return $error;
			}
			$html = '<span style="' . ($this->allow_styling && isset($item['params']['style']) ? htmlspecialchars($this->valid_style($item['params']['style'], '')) : '') . 'font-size:' . $size . 'px;"' . $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</span>',
			);
		}

		// Single tags: HR
		if($tag === 'hr')
		{
			if($this->is_sig)
			{
				return $error;
			}
			$extras = $this->allow_styling ? array('style', 'class') : array();
			$color = $this->valid_color((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['color']) ? $item['params']['color'] : false)));
			if($color === false)
			{
				$html = '<' . $tag . ' />';
			}
			else
			{
				$html = '<' . $tag . ' color="' . $color . '" />';
			}
			return array(
				'valid' => true,
				'html' => $html
			);
		}

		// ALIGN
		if($tag === 'align')
		{
			$extras = $this->allow_styling ? array('style', 'class') : array();
			$default_param = 'left';
			$align = (isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['align']) ? $item['params']['align'] : $default_param));
			if (($align === 'left') || ($align === 'right') || ($align === 'center') || ($align === 'justify'))
			{
				if ($align === 'center')
				{
					$html = '<div style="text-align:' . $align . ';margin-left:auto;margin-right:auto;">';
				}
				else
				{
					$html = '<div style="text-align:' . $align . ';">';
				}
				return array(
					'valid' => true,
					'start' => $html,
					'end' => '</div>',
				);
			}
			else
			{
				return $error;
			}
		}

		// IMG
		if($tag === 'img')
		{
			if($this->is_sig)
			{
				return $error;
			}
			// main parameters
			$params = array(
				'src' => false,
				'alt' => false,
				'slide' => false,
			);

			// additional allowed parameters
			$extras = $this->allow_styling ? array('width', 'height', 'border', 'style', 'class', 'title', 'align') : array('width', 'height', 'border', 'title', 'align');
			$slideshow = !empty($item['params']['slide']) ? 'lightbox[' . $this->process_text($item['params']['slide']) . ']' : 'lightbox';
			$liw_bypass = false;

			// [img=blah]blah2[/img]
			if(isset($item['params']['param']))
			{
				$params['src'] = $item['params']['param'];
				$img_url = $params['src'];
				$img_url_enc = urlencode(utf8_decode($params['src']));
				$params['alt'] = $content;
			}
			// [img src=blah alt=blah width=123][/img]
			elseif(isset($item['params']['src']))
			{
				$params['src'] = $item['params']['src'];
				$img_url = $params['src'];
				$img_url_enc = urlencode(utf8_decode($params['src']));
				$params['alt'] = isset($item['params']['alt']) ? $item['params']['alt'] : $content;
				for($i = 0; $i < count($extras); $i++)
				{
					if(!empty($item['params'][$extras[$i]]))
					{
						if($extras[$i] === 'style')
						{
							$style = $this->valid_style($item['params']['style']);
							if($style !== false)
							{
								$params['style'] = $style;
							}
						}
						else
						{
							$params[$extras[$i]] = $item['params'][$extras[$i]];
						}
					}
				}
			}
			// [img]blah[/img], [img width=blah]blah[/img]
			elseif(!empty($content))
			{
				$params['src'] = $content;
				$img_url = $params['src'];
				$img_url_enc = urlencode(utf8_decode($params['src']));
				// LIW - BEGIN
				if (($board_config['liw_enabled'] == 1) && ($max_image_width > 0) && ($board_config['thumbnail_posts'] == 0))
				{
					$liw_bypass = true;
					if (isset($item['params']['width']))
					{
						$item['params']['width'] = ($item['params']['width'] > $max_image_width) ? $max_image_width : $item['params']['width'];
					}
					else
					{
						$image_size = @getimagesize($content);
						$item['params']['width'] = ($image_size[0] > $max_image_width) ? $max_image_width : $image_size[0];
					}
				}
				// LIW - END
				$params['alt'] = isset($item['params']['alt']) ? $item['params']['alt'] : (isset($params['title']) ? $params['title'] : 'Image');
				for($i = 0; $i < count($extras); $i++)
				{
					if(!empty($item['params'][$extras[$i]]))
					{
						if($extras[$i] === 'style')
						{
							$style = $this->valid_style($item['params']['style']);
							if($style !== false)
							{
								$params['style'] = $style;
							}
						}
						else
						{
							$params[$extras[$i]] = $item['params'][$extras[$i]];
						}
					}
				}
			}

			if (($board_config['thumbnail_posts'] == true) && ($liw_bypass == false))
			{
				$thumb_exists = false;
				if($board_config['thumbnail_cache'] == true)
				{
					$pic_id = $img_url;
					$pic_fullpath = str_replace(array(' '), array('%20'), $pic_id);
					$pic_id = str_replace('http://', '', str_replace('https://', '', $pic_id));
					$pic_path[] = array();
					$pic_path = explode('/', $pic_id);
					$pic_filename = $pic_path[count($pic_path) - 1];
					$file_part = explode('.', strtolower($pic_filename));
					$pic_filetype = $file_part[count($file_part) - 1];
					$thumb_ext_array = array('gif', 'jpg', 'png');
					if (in_array($pic_filetype, $thumb_ext_array))
					{
						$user_dir = '';
						$users_images_path = str_replace('http://', '', str_replace('https://', '', create_server_url() . POSTED_IMAGES_PATH));
						$pic_title = substr($pic_filename, 0, strlen($pic_filename) - strlen($pic_filetype) - 1);
						$pic_title_reg = ereg_replace("[^A-Za-z0-9]", '_', $pic_title);
						$pic_thumbnail = 'mid_' . md5($pic_id) . '_' . $pic_filename;
						if (strpos($pic_id, $users_images_path) !== false)
						{
							$user_dir = str_replace($pic_filename, '', str_replace($users_images_path, '', $pic_id));
							$pic_thumbnail = $pic_filename;
						}
						$pic_thumbnail_fullpath = POSTED_IMAGES_THUMBS_PATH . $user_dir . $pic_thumbnail;
						if(file_exists($pic_thumbnail_fullpath))
						{
							$thumb_exists = true;
							$params['src'] = $pic_thumbnail_fullpath;
						}
					}
				}
				$cache_image = true;
				$cache_append = '';
				if (isset($item['params']['cache']))
				{
					if ($item['params']['cache'] == 'false')
					{
						$cache_image = false;
						$cache_append = 'cache=false&';
					}
					else
					{
						$cache_image = true;
					}
				}
				if (($thumb_exists == false) || ($cache_image == false))
				{
					$params['src'] = 'posted_img_thumbnail.' . PHP_EXT . '?' . $cache_append . 'pic_id=' . $img_url_enc;
				}
			}

			// generate html
			$html = '<img';
			foreach($params as $var => $value)
			{
				if ($this->process_text($value) != '')
				{
					$html .= ' ' . $var . '="' . $this->process_text($value) . '"';
				}
			}
			if(!isset($params['title']))
			{
				$html .= ' title="' . $this->process_text($params['alt']) . '"';
			}
			$html .= ' />';
			// add url
			/*
			if (strpos($params['src'], trim($board_config['server_name'])) == false)
			{
				$html = $this->process_text($params['alt']);
			}
			*/
			if(empty($item['inurl']))
			{
				if (($board_config['thumbnail_posts'] == 1) && ($board_config['thumbnail_lightbox'] == 1))
				{
					$extra_html = ' rel="' . $slideshow . '" title="' . $this->process_text($params['alt']) . '"';
				}
				else
				{
					$extra_html = ' target="_blank" title="' . $lang['OpenNewWindow'] . '"';
				}
				$html = '<a href="' . $this->process_text($img_url) . '"' . $extra_html . '>' . $html . '</a>';
			}
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false,
			);
		}

		// ALBUMIMG
		if($tag === 'albumimg')
		{
			if($this->is_sig)
			{
				return $error;
			}
			// main parameters
			$params = array(
				'src' => false,
				'alt' => false,
			);
			// additional allowed parameters
			$extras = $this->allow_styling ? array('width', 'height', 'border', 'style', 'class', 'title', 'align') : array('width', 'height', 'border', 'title', 'align');
			// [albumimg=blah]blah2[/albumimg]
			if(isset($item['params']['param']))
			{
				$params['src'] = $item['params']['param'];
				$pic_url = $item['params']['param'];
				$params['alt'] = $content;
			}
			// [albumimg src=blah alt=blah width=123][/albumimg]
			elseif(isset($item['params']['src']))
			{
				$params['src'] = $item['params']['src'];
				$pic_url = $item['params']['src'];
				$params['alt'] = isset($item['params']['alt']) ? $item['params']['alt'] : $content;
				for($i = 0; $i < count($extras); $i++)
				{
					if(!empty($item['params'][$extras[$i]]))
					{
						if($extras[$i] === 'style')
						{
							$style = $this->valid_style($item['params']['style']);
							if($style !== false)
							{
								$params['style'] = $style;
							}
						}
						else
						{
							$params[$extras[$i]] = $item['params'][$extras[$i]];
						}
					}
				}
			}
			// [albumimg]blah[/albumimg], [albumimg width=blah]blah[/albumimg]
			elseif(!empty($content))
			{
				$params['src'] = $content;
				$pic_url = $content;
				$params['alt'] = isset($item['params']['alt']) ? $item['params']['alt'] : (isset($params['title']) ? $params['title'] : '');
				for($i = 0; $i < count($extras); $i++)
				{
					if(!empty($item['params'][$extras[$i]]))
					{
						if($extras[$i] === 'style')
						{
							$style = $this->valid_style($item['params']['style']);
							if($style !== false)
							{
								$params['style'] = $style;
							}
						}
						else
						{
							$params[$extras[$i]] = $item['params'][$extras[$i]];
						}
					}
				}
			}
			// generate html
			$pic_url = 'album_showpage.' . PHP_EXT . '?pic_id=' . $pic_url;
			if(isset($item['params']['mode']))
			{
				$pic_mode = $item['params']['mode'];
				if ($pic_mode === 'full')
				{
					$params['src'] = 'album_picm.' . PHP_EXT . '?pic_id=' . $params['src'];
				}
				else
				{
					$params['src'] = 'album_thumbnail.' . PHP_EXT . '?pic_id=' . $params['src'];
				}
			}
			else
			{
				$params['src'] = 'album_thumbnail.' . PHP_EXT . '?pic_id=' . $params['src'];
			}
			$html = '<img';
			foreach($params as $var => $value)
			{
				$html .= ' ' . $var . '="' . $this->process_text($value) . '"';
			}
			if(!isset($params['title']))
			{
				$html .= ' title="' . $this->process_text($params['alt']) . '"';
			}
			$html .= ' />';
			// add url
			if(empty($item['inurl']))
			{
				$html = '<a href="' . $this->process_text($pic_url) . '" title="' . $lang['Click_enlarge_pic'] . '">' . $html . '</a>';
			}
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false,
			);
		}

		// ATTACHMENT
		if(($tag === 'attachment') || ($tag === 'download'))
		{
			if($this->is_sig)
			{
				return $error;
			}
			$html = '';
			$params['id'] = isset($item['params']['param']) ? intval($item['params']['param']) : (isset($item['params']['id']) ? intval($item['params']['id']) : false);
			$params['title'] = isset($item['params']['title']) ? $this->process_text($item['params']['title']) : false;
			$params['description'] = isset($item['params']['description']) ? $this->process_text($item['params']['description']) : false;
			$params['icon'] = isset($item['params']['icon']) ? $this->process_text($item['params']['icon']) : false;
			$color = $this->valid_color(isset($item['params']['color']) ? $item['params']['color'] : false);
			$bgcolor = $this->valid_color(isset($item['params']['bgcolor']) ? $item['params']['bgcolor'] : false);

			$errored = false;
			if ($params['id'] <= 0)
			{
				$errored = true;
			}

			if (!$errored)
			{
				if ($tag === 'attachment')
				{
					$is_auth_ary = auth(AUTH_READ, AUTH_LIST_ALL, $userdata);
					$is_download_auth_ary = auth(AUTH_DOWNLOAD, AUTH_LIST_ALL, $userdata);
					$attachment_details = get_attachment_details($params['id']);
					if (($attachment_details == false) || !$is_auth_ary[$attachment_details['forum_id']]['auth_read'] || !$is_download_auth_ary[$attachment_details['forum_id']]['auth_download'])
					{
						$errored = true;
					}
				}
				else
				{
					$attachment_details = get_download_details($params['id']);
					$errored = ($attachment_details == false) ? true : false;
				}
			}

			if (!$errored)
			{
				if ($tag === 'attachment')
				{
					$params['title'] = $params['title'] ? $params['title'] : (!empty($attachment_details['real_filename']) ? $attachment_details['real_filename'] : '&nbsp;');
					$params['description'] = $params['description'] ? $params['description'] : (!empty($attachment_details['comment']) ? $attachment_details['comment'] : '&nbsp;');
					$params['icon'] = IP_ROOT_PATH . FILES_ICONS_DIR . ($params['icon'] ? $params['icon'] : 'default.png');
					$download_url = 'download.' . PHP_EXT;
				}
				else
				{
					$params['title'] = $params['title'] ? $params['title'] : (!empty($attachment_details['file_name']) ? $attachment_details['file_name'] : '&nbsp;');
					$params['description'] = $params['description'] ? $params['description'] : (!empty($attachment_details['file_desc']) ? $attachment_details['file_desc'] : '&nbsp;');
					$params['icon'] = IP_ROOT_PATH . FILES_ICONS_DIR . ($params['icon'] ? $params['icon'] : (!empty($attachment_details['file_posticon']) ? $attachment_details['file_posticon'] : 'default.png'));
					$attachment_details['filesize'] = $attachment_details['file_size'];
					$attachment_details['download_count'] = $attachment_details['file_dls'];
					$download_url = 'dload.' . PHP_EXT;
				}
				//$params['icon'] = strip_tags(ereg_replace("[^A-Za-z0-9_.]", "_", $params['icon']));
				$params['icon'] = file_exists($params['icon']) ? $params['icon'] : (IP_ROOT_PATH . FILES_ICONS_DIR . 'default.png');
				$style = ($color || $bgcolor) ? (' style="' . ($color ? 'color: ' . $color . ';' : '') . ($bgcolor ? 'background-color: ' . $bgcolor . ';' : '') . '"') : '';

				$html .= '<div class="mg_attachtitle"' . $style . '>' . $params['title'] . '</div>';
				$html .= '<div class="mg_attachdiv"><table width="100%" cellpadding="0" cellspacing="0" border="0">';
				$html .= '<tr><td width="15%"><b class="gensmall">' . $lang['Description'] . ':</b></td><td width="75%"><span class="gensmall">' . $params['description'] . '</span></td><td rowspan="3" width="10%" class="row-center"><img src="' . $params['icon'] . '" alt="' . $params['description'] . '" /><br /><a href="' . append_sid(IP_ROOT_PATH . $download_url . '?id=' . $params['id']) . '"><b>' . $lang['Download'] . '</b></a></td></tr>';
				$html .= '<tr><td><b class="gensmall">' . $lang['FILESIZE'] . ':</b></td><td><span class="gensmall">' . round(($attachment_details['filesize'] / 1024), 2) . ' KB</span></td></tr>';
				$html .= '<tr><td><b class="gensmall">' . $lang['DOWNLOADED'] . ':</b></td><td><span class="gensmall">' . $attachment_details['download_count'] . '</span></td></tr>';
				$html .= '</table></div>';
			}
			else
			{
				$html .= '<div class="mg_attachtitle"' . $style . '>' . $lang['Not_Authorised'] . '</div>';
				$html .= '<div class="mg_attachdiv"><div style="text-align:center;">' . $lang['FILE_NOT_AUTH'] . '</div></div>';
			}

			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false,
			);
		}

		// LIST
		if(($tag === 'list') || ($tag === 'ul') || ($tag === 'ol'))
		{
			if($this->is_sig)
			{
				return $error;
			}
			$extras = $this->allow_styling ? array('style', 'class') : array();
			// check if nested tags are all [*]
			$nested_count = 0;
			for($i = 0; $i < count($item['items']); $i++)
			{
				$tag2 = $item['items'][$i]['tag'];
				if(($tag2 === '*') || ($tag2 === 'li'))
				{
					$nested_count ++;
				}
			}
			if(!$nested_count)
			{
				// no <li> items. return error
				return $error;
			}
			// replace "list" with html tag
			if($tag === 'list')
			{
				if(isset($item['params']['param']) || isset($item['params']['type']))
				{
					$tag = 'ol';
				}
				else
				{
					$tag = 'ul';
				}
			}
			// valid tag. process subitems to make sure there are no extra items and remove all code between elements
			$last_item = false;
			for($i = 0; $i < count($item['items']); $i++)
			{
				$item2 = &$item['items'][$i];
				$tag2 = $item2['tag'];
				if(($tag2 === '*') || ($tag2 === 'li'))
				{
					// mark as valid
					$item2['list_valid'] = true;
					if($last_item === false)
					{
						// change start position to end of [list]
						$pos2 = $item2['start'] + $item2['start_len'];
						$item2['start'] = $pos;
						$item2['start_len'] = $pos2 - $pos;
						$item2['first_entry'] = true;
					}
					$last_item = &$item['items'][$i];
				}
			}
			// generate html
			$html = '<' . $tag;
			if(isset($item['params']['param']))
			{
				$html .= ' type="' . htmlspecialchars($item['params']['param']) . '"';
			}
			elseif(isset($item['params']['type']))
			{
				$html .= ' type="' . htmlspecialchars($item['params']['type']) . '"';
			}
			$html .= $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</li></' . $tag . '>'
			);
		}

		// [*], LI
		if(($tag === '*') || ($tag === 'li'))
		{
			if($this->is_sig)
			{
				return $error;
			}
			$extras = $this->allow_styling ? array('style', 'class') : array();
			// if not marked as valid return error
			if(empty($item['list_valid']))
			{
				return $error;
			}
			$html = '<li';
			if(empty($item['first_entry']))
			{
				// add closing tag for previous list entry
				$html = '</li>' . $html;
			}
			$html .= $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '',
			);
		}

		// FONT
		if($tag === 'font')
		{
			$extras = $this->allow_styling ? array('style', 'class') : array();
			$default_param = 'Verdana';
			$font = (isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['font']) ? $item['params']['font'] : $default_param));
			if ($font === 'Arial' ||
				$font === 'Arial Black' ||
				$font === 'Comic Sans MS' ||
				$font === 'Courier New' ||
				$font === 'Impact' ||
				$font === 'Lucida Console' ||
				$font === 'Lucida Sans Unicode' ||
				$font === 'Microsoft Sans Serif' ||
				$font === 'Symbol' ||
				$font === 'Tahoma' ||
				$font === 'Times New Roman' ||
				$font === 'Traditional Arabic' ||
				$font === 'Trebuchet MS' ||
				$font === 'Verdana' ||
				$font === 'Webdings' ||
				$font === 'Wingdings')
			{
				$font = $font;
			}
			else
			{
				$font = 'Verdana';
			}
			$html = '<span style="font-family:' . $font . ';">';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</span>',
			);
		}

		// CELL
		if($tag === 'cell')
		{
			$extras = $this->allow_styling ? array('class', 'align', 'border') : array('class', 'align');

			$width = (isset($item['params']['width']) ? (' width: ' . intval($item['params']['width']) . 'px;') : '');
			$height = (isset($item['params']['height']) ? (' height: ' . intval($item['params']['height']) . 'px;') : '');
			$padding = (isset($item['params']['padding']) ? (' padding: ' . intval($item['params']['padding']) . 'px;') : '');
			$margin = (isset($item['params']['margin']) ? (' margin: ' . intval($item['params']['margin']) . 'px;') : '');
			$borderwidth = (isset($item['params']['borderwidth']) ? (' border-width: ' . intval($item['params']['borderwidth']) . 'px;') : '');

			$bgcolor = $this->valid_color((isset($item['params']['bgcolor']) ? $item['params']['bgcolor'] : false));
			$bgcolor = (($bgcolor !== false) ? (' background-color: ' . $bgcolor . ';') : '');

			$bordercolor = $this->valid_color((isset($item['params']['bordercolor']) ? $item['params']['bordercolor'] : false));
			$bordercolor = (($bordercolor !== false) ? (' border-color: ' . $bordercolor . ';') : '');

			$color = $this->valid_color((isset($item['params']['color']) ? $item['params']['color'] : false));
			$color = (($color !== false) ? (' color: ' . $color . ';') : '');

			$html = '<div style="' . ($this->allow_styling && isset($item['params']['style']) ? htmlspecialchars($this->valid_style($item['params']['style'], '')) : '') . $height . $width . $bgcolor . $bordercolor . $borderwidth . $color . $padding . $margin . '"' . $this->add_extras($item['params'], $extras) . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</div>',
			);
		}

		// URL, A
		if(($tag === 'url') || ($tag === 'a'))
		{
			$extras = $this->allow_styling ? array('style', 'class', 'name', 'title') : array('name', 'title');
			$allow_nested = true;
			$strip_text = false;
			$show_content = true;
			$url = '';
			// get url
			if(!empty($item['params']['param']))
			{
				$url = $item['params']['param'];
			}
			elseif(!empty($item['params']['href']))
			{
				$url = $item['params']['href'];
			}
			elseif(!$item['is_html'])
			{
				$url = $content;
				$allow_nested = false;
				$strip_text = true;
			}
			else
			{
				return $error;
			}
			if(($url === $content) && (strlen($content) > 64))
			{
				$content = htmlspecialchars(substr($content, 0, 35) . '...' . substr($content, strlen($content) - 15));
				$show_content = false;
			}
			// check if its email
			if(substr(strtolower($url), 0, 7) === 'mailto:')
			{
				$item['tag'] = 'email';
				return $this->process_tag($item);
			}
			// check for invalid urls
			$url = $this->valid_url($url, '');
			if(empty($url))
			{
				return $error;
			}
			// check nested items
			if(!$allow_nested)
			{
				for($i = 0; $i < count($item['items']); $i++)
				{
					$item['items'][$i]['valid'] = false;
				}
			}
			else
			{
				for($i = 0; $i < count($item['next']); $i++)
				{
					$tag2 = $item['next'][$i]['tag'];
					$is_html = $item['next'][$i]['item']['is_html'];
					$item['next'][$i]['item']['inurl'] = true;
					if($is_html && !$this->allowed_html[$tag2]['inurl'])
					{
						$item['next'][$i]['item']['valid'] = false;
					}
					if(!$is_html && !$this->allowed_bbcode[$tag2]['inurl'])
					{
						$item['next'][$i]['item']['valid'] = false;
					}
				}
			}
			// check for incomplete url
			if(substr(strtolower($url), 0, 4) === 'www.')
			{
				$url = 'http://' . $url;
			}
			// remove extra characters at the end
			$last_char = substr($url, strlen($url) - 1);
			$last_char_i = ord($last_char);
			if(($last_char_i > 32 && $last_char_i < 47) || ($last_char_i > 57 && $last_char_i < 65))
			{
				$url = substr($url, 0, strlen($url) - 1);
			}
			// check if url is local
			$url_local = false;
			global $urls_local;
			for($i = 0; $i < count($urls_local); $i++)
			{
				if(strlen($url) > strlen($urls_local[$i]) && strpos($url, $urls_local[$i]) === 0)
				{
					$url_local = true;
					// Local Urls
					//$url = substr($url, strlen($urls_local[$i]));
					// Full Path
					$url = $url;
				}
			}
			if(!$url_local)
			{
				if(strpos($url, ':') === false)
				{
					$url_local = true;
				}
			}
			// generate html
			$html = '<a' . ($this->allow_styling && isset($item['params']['class']) ? '' : ' class="post-url"') . ' href="' . htmlspecialchars($url) . '"' . ($url_local ? '' : ' target="_blank"') . $this->add_extras($item['params'], $extras) . '>';

			if (($board_config['disable_html_guests'] == 1) && (!$userdata['session_logged_in']))
			{
				return array(
					'valid' => true,
					'html' => $lang['Links_For_Guests'],
					'allow_nested' => false,
				);
			}
			else
			{
				if($show_content)
				{
					return array(
						'valid' => true,
						'start' => $html,
						'end' => '</a>',
					);
				}
				else
				{
					return array(
						'valid' => true,
						'html' => $html . $content . '</a>',
						'allow_nested' => false,
					);
				}
			}
		}

		// EMAIL
		if($tag === 'email')
		{
			$extras = $this->allow_styling ? array('style', 'class', 'name', 'title') : array('name', 'title');
			$allow_nested = true;
			$strip_text = false;
			$url = '';
			// get url
			if(!empty($item['params']['param']))
			{
				$url = $item['params']['param'];
			}
			elseif(!empty($item['params']['href']))
			{
				$url = $item['params']['href'];
			}
			elseif(!empty($item['params']['addr']))
			{
				$url = $item['params']['addr'];
			}
			else
			{
				$url = $content;
				$pos = strpos($url, '?');
				if($pos)
				{
					$content = substr($url, 0, $pos);
				}
				if(substr(strtolower($url), 0, 7) === 'mailto:')
				{
					$content = substr($content, 7);
				}
				$allow_nested = false;
				$strip_text = true;
			}
			if(empty($url))
			{
				return $error;
			}
			// disable nested items
			for($i = 0; $i < count($item['items']); $i++)
			{
				$item['items'][$i]['valid'] = false;
			}
			// generate html
			if(substr(strtolower($url), 0, 7) === 'mailto:')
			{
				$url = substr($url, 7);
			}
			$email = '<a' . ($this->allow_styling && isset($item['params']['class']) ? '' : ' class="post-email"') . ' href="mailto:' . htmlspecialchars($url) . '"' . $this->add_extras($item['params'], $extras) . '>' . $content . '</a>';
			$pos = strpos($url, '?');
			if($pos)
			{
				$str = substr($url, 0, $pos);
			}
			else
			{
				$str = $url;
			}
			$noscript = '<noscript>' . htmlspecialchars(str_replace(array('@', '.'), array(' [at] ', ' [dot] '), $str)) . '</noscript>';
			// make javascript from it
			$html = BBCODE_NOSMILIES_START . '<script type="text/javascript">' . "\n" . '<!--' . "\n";
			for($i = 0; $i<strlen($email); $i+=5)
			{
				$str = substr($email, $i, 5);
				$html .= 'document.write(\'' . addslashes($str) . '\');' . "\n";
			}
			$html .= "\n" . '//-->' . "\n" . '</script>' . $noscript . BBCODE_NOSMILIES_END;
			return array(
				'valid' => true,
				'html' => $html,
				//'html' => $email,
				'allow_nested' => false,
			);
		}

		// QUOTE
		if(($tag === 'quote') || ($tag === 'blockquote') || ($tag === 'ot'))
		{
			if($this->is_sig)
			{
				return $error;
			}
			if($item['iteration'] > ($board_config['quote_iterations']))
			{
				return $error;
			}
			// check user
			$user = '';
			$post_rev = '';
			if(isset($item['params']['param']))
			{
				$user = htmlspecialchars($item['params']['param']);
			}
			elseif(isset($item['params']['user']))
			{
				$user = htmlspecialchars($item['params']['user']);
				if(isset($item['params']['userid']) && intval($item['params']['userid']))
				{
					$user = '<a href="' . PROFILE_MG . '?mode=viewprofile&amp;' . POST_USERS_URL . '=' . intval($item['params']['userid']) . '">' . $user . '</a>';
				}
			}
			// generate html
			$html = '<blockquote class="quote"';
			if(isset($item['params']['post']) && intval($item['params']['post']))
			{
				$post_rev = '[<a href="#_somewhat" onclick="javascript:open_postreview(\'show_post.php?p=' . intval($item['params']['post']) . '\');" class="genmed">' . $lang['ReviewPost'] . '</a>]';
				$html .= ' cite="'. VIEWTOPIC_MG . '?' . POST_POST_URL . '=' . intval($item['params']['post']) . '#p' . intval($item['params']['post']) . '"';
			}
			$html .= '>';
			if($user)
			{
				if ($tag === 'ot')
				{
					$html .= '<div class="quote-user"><div class="error-message" style="display:inline;">' . $lang['OffTopic'] . '</div>&nbsp;' . $user . ':&nbsp;' . $post_rev . '</div>';
				}
				else
				{
					$html .= '<div class="quote-user">' . $user . '&nbsp;' . $lang['wrote'] . ':&nbsp;' . $post_rev . '</div>';
				}
			}
			else
			{
				if ($tag === 'ot')
				{
					$html .= '<div class="quote-nouser">&nbsp;<div class="error-message" style="display:inline;">' . $lang['OffTopic'] . '</div>:</div>';
				}
				else
				{
					$html .= '<div class="quote-nouser">' . $lang['Quote'] . ':</div>';
				}
			}
			$html .= '<div class="post-text">';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</div></blockquote>'
			);
		}

		// CODE
		if($tag === 'code')
		{
			if($this->is_sig)
			{
				return $error;
			}
			// replace spaces and tabs with &nbsp;
			if(!defined('EXTRACT_CODE'))
			{
				$search = array(
					'  ',
					"\t"
				);
				$replace = array(
					' &nbsp;',
					' &nbsp; &nbsp;'
				);
				$text = str_replace($search, $replace, $this->process_text($content, false, true));
			}
			else
			{
				$text = $this->process_text($content, false, true);
				$search = array('[highlight]', '[/highlight]');
				$replace = array('', '');
				$text = str_replace($search, $replace, $text);
			}

			// check filename
			if(isset($item['params']['filename']))
			{
				$item['params']['file'] = $item['params']['filename'];
			}
			if(defined('EXTRACT_CODE') && ($this->code_counter == EXTRACT_CODE))
			{
				$GLOBALS['code_text'] = $text;
				if(!empty($item['params']['file']))
				{
					$GLOBALS['code_filename'] = $item['params']['file'];
				}
			}
			if(substr($text, 0, 1) === "\n")
			{
				$text = substr($text, 1);
			}
			elseif(substr($text, 0, 2) === "\r\n")
			{
				$text = substr($text, 2);
			}
			$linenumbers = true;
			if(isset($item['params']['linenumbers']))
			{
				$linenumbers = ($item['params']['linenumbers'] == 'true') ? true : false;
			}

			if ($linenumbers == true)
			{
				// convert to list
				if(isset($item['params']['syntax']))
				{
					if ($item['params']['syntax'] == 'php')
					{
						/*
						$html = strtr($text, array_flip(get_html_translation_table(HTML_ENTITIES)));
						$html = highlight_string($html, true);
						$html_search = array('<font color="', '</font', '&nbsp;');
						$xhtml_replace = array('<code style="color:', '</code', ' ');
						//$xhtml_replace = array('<div style="display:inline;color:', '</div', ' ');
						//$xhtml_replace = array('<span style="display:inline;color:', '</span', ' ');
						$html = str_replace ($html_search, $xhtml_replace, $html);
						$html = '<li class="code-row"><div class="code-row-text">' . $html . '</div></li>';
						*/
						/*
						$html_search = array('<br />');
						$xhtml_replace = array('</div></li><li class="code-row"><div class="code-row-text">');
						$html = str_replace ($html_search, $xhtml_replace, $html);
						*/

						//PHP Highlight - Start
						$code_ary = explode("\n", $text);

						$open_php_tag = 0;
						$close_php_tag = 0;
						for ($i = 0; $i < count($code_ary); $i++)
						{
							if (($code_ary[$i] == '') || ($code_ary[$i] == ' ') || ($code_ary[$i] == '&nbsp;') || ($code_ary[$i] == "\n") || ($code_ary[$i] == "\r") || ($code_ary[$i] == "\n\r"))
							{
								$html .= '<li class="code-row"><span class="code-row-text">&nbsp;&nbsp;</span></li>';
							}
							else
							{
								$prefix = (strpos(' ' . $code_ary[$i], '&lt;?')) ? '' : '<?php ';
								$suffix = (strpos(' ' . $code_ary[$i], '?&gt;')) ? '' : '?>';

								$code_ary[$i] = str_replace(array('&lt;', '&gt;'), array('<', '>'), $code_ary[$i]);
								$code_ary[$i] = highlight_string(strtr($prefix . $code_ary[$i] . $suffix, array_flip(get_html_translation_table(HTML_ENTITIES))), true);

								$html_search = array('<code>', '</code>');
								$xhtml_replace = array('', '');
								$code_ary[$i] = str_replace($html_search, $xhtml_replace, $code_ary[$i]);

								if ($open_php_tag || ($prefix != ''))
								{
									$html_search = array('&lt;?php');
									$xhtml_replace = array('');
									$code_ary[$i] = str_replace($html_search, $xhtml_replace, $code_ary[$i]);
								}

								if ($close_php_tag || ($suffix != ''))
								{
									$html_search = array('?&gt;&nbsp;', '?&gt;');
									$xhtml_replace = array('', '');
									$code_ary[$i] = str_replace($html_search, $xhtml_replace, $code_ary[$i]);
								}

								($prefix == '') ? $open_php_tag++ : (($open_php_tag) ? $open_php_tag-- : '');
								($suffix == '') ? $close_php_tag++ : (($close_php_tag) ? $close_php_tag-- : '');

								$html .= '<li class="code-row"><span class="code-row-text">' . $code_ary[$i] . '&nbsp;</span></li>';
							}
						}

						$html_search = array('<font color="', '</font', '&nbsp;', '<code style="color:#0000BB"></code>', '<code style="color:#0000BB"> </code>', '>  <');
						$xhtml_replace = array('<code style="color:', '</code', ' ', '', '', '>&nbsp;<');
						$html = str_replace($html_search, $xhtml_replace, $html);
						//PHP Highlight - End
					}
					else
					{
						$search = array("\n", '[highlight]', '[/highlight]');
						$replace = array('&nbsp;</span></li><li class="code-row"><span class="code-row-text">', '<span class="code-row-highlight">', '</span>');
						$html = '<li class="code-row code-row-first"><span class="code-row-text">' . str_replace($search, $replace, $text) . '&nbsp;</span></li>';
					}
				}
				else
				{
					$search = array("\n", '[highlight]', '[/highlight]');
					$replace = array('&nbsp;</span></li><li class="code-row"><span class="code-row-text">', '<span class="code-row-highlight">', '</span>');
					$html = '<li class="code-row code-row-first"><span class="code-row-text">' . str_replace($search, $replace, $text) . '&nbsp;</span></li>';
				}

				$str = '<li class="code-row"><div class="code-row-text">&nbsp;</div></li>';
				if(substr($html, strlen($html) - strlen($str)) === $str)
				{
					$html = substr($html, 0, strlen($html) - strlen($str));
				}
				$start = isset($item['params']['start']) ? intval($item['params']['start']) : 1;
				$can_download = !empty($GLOBALS['code_post_id']) ? $GLOBALS['code_post_id'] : 0;
				if($can_download)
				{
					//$download_text = ' [<a href="download.php?post=' . $can_download;
					$download_text = ' [<a href="download_post.' . PHP_EXT . '?post=' . $can_download;
					if($this->code_counter)
					{
						$download_text .= '&amp;item=' . $this->code_counter;
					}
					$download_text .= '">' . $lang['Download'] . '</a>]';
				}
				else
				{
					$download_text = '';
				}
				$code_id = substr(md5($content . mt_rand()), 0, 8);
				$str = BBCODE_NOSMILIES_START . '<div class="code">';
				$str .= '<div class="code-header" id="codehdr2_' . $code_id . '" style="position:relative;">' . $lang['Code'] . ':' . (empty($item['params']['file']) ? '' : ' (' . htmlspecialchars($item['params']['file']) . ')') . $download_text . ' [<a href="javascript:void(0)" onclick="ShowHide(\'code_' . $code_id . '\',\'code2_' . $code_id . '\',\'\'); ShowHide(\'codehdr_' . $code_id . '\', \'codehdr2_' . $code_id . '\', \'\')">' . $lang['Hide'] . '</a>]</div>';
				$str .= '<div class="code-header" id="codehdr_' . $code_id . '" style="position:relative;display:none;">' . $lang['Code'] . ':' . (empty($item['params']['file']) ? '' : ' (' . htmlspecialchars($item['params']['file']) . ')') . $download_text . ' [<a href="javascript:void(0)" onclick="ShowHide(\'code_' . $code_id . '\',\'code2_' . $code_id . '\',\'\');ShowHide(\'codehdr_' . $code_id . '\',\'codehdr2_' . $code_id . '\',\'\')">' . $lang['Show'] . '</a>]</div>';
				$html = $str . '<div class="code-content" id="code_' . $code_id . '" style="position:relative;"><ol class="code-list" start="' . $start . '">' . $html . '</ol></div></div>' . BBCODE_NOSMILIES_END;
				// check highlight
				// format: highlight="1,2,3-10"
				if(isset($item['params']['highlight']))
				{
					$search = '<li class="code-row';
					$replace = '<li class="code-row code-row-highlight';
					$search_len = strlen($search);
					$replace_len = strlen($replace);
					// get highlight string
					$items = array();
					$str = $item['params']['highlight'];
					$list = explode(',', $str);
					for($i = 0; $i < count($list); $i++)
					{
						$str = trim($list[$i]);
						if(strpos($str, '-'))
						{
							$row = explode('-', $str);
							if(count($row) == 2)
							{
								$num1 = intval($row[0]);
								if($num1 == 0)
								{
									$num1 = 1;
								}
								$num2 = intval($row[1]);
								if($num1 > 0 && $num2 > $num1 && ($num2 - $num1) < 256)
								{
									for($j=$num1; $j<=$num2; $j++)
									{
										$items['row' . $j] = true;
									}
								}
							}
						}
						else
						{
							$num = intval($str);
							if($num)
							{
								$items['row' . $num] = true;
							}
						}
					}
					if(count($items))
					{
						// process all lines
						$num = $start - 1;
						$pos = strpos($html, $search);
						$total = count($items);
						$found = 0;
						while($pos !== false)
						{
							$num ++;
							if(isset($items['row' . $num]))
							{
								$found ++;
								$html = substr($html, 0, $pos) . $replace . substr($html, $pos + $search_len);
								$pos += $replace_len;
							}
							else
							{
								$pos += $search_len;
							}
							$pos = $found < $total ? strpos($html, $search, $pos) : false;
						}
					}
				}
				// $html = BBCODE_NOSMILIES_START . '<div class="code"><div class="code-header">Code:</div><div class="code-content">' . $text . '</div></div>' . BBCODE_NOSMILIES_END;
				$this->code_counter++;
				return array(
					'valid' => true,
					'html' => $html,
					'allow_nested' => false
				);
			}
			else
			{
				$syntax_highlight = false;
				if(isset($item['params']['syntax']))
				{
					if ($item['params']['syntax'] == 'php')
					{
						$html = strtr($text, array_flip(get_html_translation_table(HTML_ENTITIES)));
						$html = highlight_string($html, true);
						$html_search = array('<code>', '</code>', '<font color="', '</font', '&nbsp;', '<code style="color:#0000BB"></code>', '<code style="color:#0000BB"> </code>');
						$xhtml_replace = array('', '', '<code style="color:', '</code', ' ', '', '');
						$html = str_replace ($html_search, $xhtml_replace, $html);
						$syntax_highlight = true;
					}
				}
				if ($syntax_highlight == false)
				{
					$html = $text;
					$search = array('[highlight]', '[/highlight]');
					$replace = array('</span><span class="code-row code-row-highlight">', '</span><span class="code-row-text">');
					$html = str_replace($search, $replace, $html);
					$html = str_replace(array("\n", "\r\n"), array("<br />\n", "<br />\r\n"), $html);
				}

				$can_download = !empty($GLOBALS['code_post_id']) ? $GLOBALS['code_post_id'] : 0;
				if($can_download)
				{
					$download_text = ' [<a href="download_post.' . PHP_EXT . '?post=' . $can_download;
					if($this->code_counter)
					{
						$download_text .= '&amp;item=' . $this->code_counter;
					}
					$download_text .= '">' . $lang['Download'] . '</a>]';
				}
				else
				{
					$download_text = '';
				}
				$code_id = substr(md5($content . mt_rand()), 0, 8);
				$str = BBCODE_NOSMILIES_START . '<div class="code">';
				$str .= '<div class="code-header" id="codehdr2_' . $code_id . '" style="position:relative;">' . $lang['Code'] . ':' . (empty($item['params']['file']) ? '' : ' (' . htmlspecialchars($item['params']['file']) . ')') . $download_text . ' [<a href="javascript:void(0)" onclick="ShowHide(\'code_' . $code_id . '\',\'code2_' . $code_id . '\',\'\');ShowHide(\'codehdr_' . $code_id . '\',\'codehdr2_' . $code_id . '\',\'\')">' . $lang['Hide'] . '</a>] [<a href="javascript:void(0)" onclick="select_text(\'code_' . $code_id . '\')">' . $lang['Select'] . '</a>]</div>';
				$str .= '<div class="code-header" id="codehdr_' . $code_id . '" style="position:relative;display:none;">' . $lang['Code'] . ':' . (empty($item['params']['file']) ? '' : ' (' . htmlspecialchars($item['params']['file']) . ')') . $download_text . ' [<a href="javascript:void(0)" onclick="ShowHide(\'code_' . $code_id . '\',\'code2_' . $code_id . '\',\'\'); ShowHide(\'codehdr_' . $code_id . '\',\'codehdr2_' . $code_id . '\',\'\')">' . $lang['Show'] . '</a>]</div>';
				$html = $str . '<div class="code-content" id="code_' . $code_id . '" style="position:relative;"><span class="code-row-text">' . $html . '</span></div></div>' . BBCODE_NOSMILIES_END;

				$this->code_counter ++;
				return array(
					'valid' => true,
					'html' => $html,
					'allow_nested' => false
				);
			}
		}

		// CODEBLOCK
		if($tag === 'codeblock')
		{
			if($this->is_sig)
			{
				return $error;
			}
			if(!defined('EXTRACT_CODE'))
			{
				$search = array(
					'  ',
					"\t"
				);
				$replace = array(
					' &nbsp;',
					' &nbsp; &nbsp;'
				);
				$text = str_replace($search, $replace, $this->process_text($content, false, true));
			}
			else
			{
				$text = $this->process_text($content, false, true);
				$search = array('[highlight]', '[/highlight]');
				$replace = array('', '');
				$text = str_replace($search, $replace, $text);
			}
			// check filename
			if(isset($item['params']['filename']))
			{
				$item['params']['file'] = $item['params']['filename'];
			}
			if(defined('EXTRACT_CODE') && $this->code_counter == EXTRACT_CODE)
			{
				$GLOBALS['code_text'] = $text;
				if(!empty($item['params']['file']))
				{
					$GLOBALS['code_filename'] = $item['params']['file'];
				}
			}
			if(substr($text, 0, 1) === "\n")
			{
				$text = substr($text, 1);
			}
			elseif(substr($text, 0, 2) === "\r\n")
			{
				$text = substr($text, 2);
			}

			$syntax_highlight = false;
			if(isset($item['params']['syntax']))
			{
				if ($item['params']['syntax'] == 'php')
				{
					$html = strtr($text, array_flip(get_html_translation_table(HTML_ENTITIES)));
					$html = highlight_string($html, true);
					$html_search = array('<code>', '</code>', '<font color="', '</font', '&nbsp;', '<code style="color:#0000BB"></code>', '<code style="color:#0000BB"> </code>');
					$xhtml_replace = array('', '', '<code style="color:', '</code', ' ', '', '');
					$html = str_replace ($html_search, $xhtml_replace, $html);
					$syntax_highlight = true;
				}
			}
			if ($syntax_highlight == false)
			{
				$html = $text;
				$search = array('[highlight]', '[/highlight]');
				$replace = array('</span><span class="code-row code-row-highlight">', '</span><span class="code-row-text">');
				$html = str_replace($search, $replace, $html);
				$html = str_replace(array("\n", "\r\n"), array("<br />\n", "<br />\r\n"), $html);
			}

			$can_download = !empty($GLOBALS['code_post_id']) ? $GLOBALS['code_post_id'] : 0;
			if($can_download)
			{
				$download_text = ' [<a href="download_post.' . PHP_EXT . '?post=' . $can_download;
				if($this->code_counter)
				{
					$download_text .= '&amp;item=' . $this->code_counter;
				}
				$download_text .= '">' . $lang['Download'] . '</a>]';
			}
			else
			{
				$download_text = '';
			}
			$code_id = substr(md5($content . mt_rand()), 0, 8);
			$str = BBCODE_NOSMILIES_START . '<div class="code">';
			$str .= '<div class="code-header" id="codehdr2_' . $code_id . '" style="position:relative;">' . $lang['Code'] . ':' . (empty($item['params']['file']) ? '' : ' (' . htmlspecialchars($item['params']['file']) . ')') . $download_text . ' [<a href="javascript:void(0)" onclick="ShowHide(\'code_' . $code_id . '\',\'code2_' . $code_id . '\',\'\');ShowHide(\'codehdr_' . $code_id . '\',\'codehdr2_' . $code_id . '\',\'\')">' . $lang['Hide'] . '</a>] [<a href="javascript:void(0)" onclick="select_text(\'code_' . $code_id . '\')">' . $lang['Select'] . '</a>]</div>';
			$str .= '<div class="code-header" id="codehdr_' . $code_id . '" style="position:relative;display:none;">' . $lang['Code'] . ':' . (empty($item['params']['file']) ? '' : ' (' . htmlspecialchars($item['params']['file']) . ')') . $download_text . ' [<a href="javascript:void(0)" onclick="ShowHide(\'code_' . $code_id . '\',\'code2_' . $code_id . '\',\'\'); ShowHide(\'codehdr_' . $code_id . '\',\'codehdr2_' . $code_id . '\',\'\')">' . $lang['Show'] . '</a>]</div>';
			$html = $str . '<div class="code-content" id="code_' . $code_id . '" style="position:relative;"><span class="code-row-text">' . $html . '</span></div></div>' . BBCODE_NOSMILIES_END;

			$this->code_counter ++;
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false
			);
		}

		// HIDE
		if($tag === 'hide')
		{
			if($this->is_sig)
			{
				return $error;
			}
			if($item['iteration'] > 1)
			{
				return $error;
			}
			global $db, $topic_id, $mode;
			$show = false;
			if(defined('IS_ICYPHOENIX') && $userdata['session_logged_in'])
			{
				$sql = "SELECT p.poster_id, p.topic_id
					FROM " . POSTS_TABLE . " p
					WHERE p.topic_id = $topic_id
					AND p.poster_id = " . $userdata['user_id'];
				$resultat = $db->sql_query($sql);
				$show = $db->sql_numrows($resultat) ? true : false;
				$db->sql_freeresult($result);

				$sql = "SELECT *
					FROM " . THANKS_TABLE . "
					WHERE topic_id = $topic_id
					AND user_id = " . $userdata['user_id'];
				$resultat = $db->sql_query($sql);
				$show = ($db->sql_numrows($resultat) || ($show == true))? true : false;
				$db->sql_freeresult($result);

				if (($userdata['user_level'] == ADMIN) || ($userdata['user_level'] == MOD))
				{
					$show = true;
				}
			}
			// generate html
			$html = '<blockquote class="quote"><div class="quote-nouser">' . $lang['xs_bbc_hide_message'] . ':</div><div class="post-text">';
			if(!$show)
			{
				return array(
					'valid' => true,
					'html' => $html . $lang['xs_bbc_hide_message_explain'] . '</div></blockquote>',
					'allow_nested' => false,
				);
			}
			else
			{
				return array(
					'valid' => true,
					'start' => $html,
					'end' => '</div></blockquote>'
				);
			}
		}

		// SPOILER
		if($tag === 'spoiler')
		{
			if($this->is_sig)
			{
				return $error;
			}
			if($item['iteration'] > 1)
			{
				return $error;
			}
			$spoiler_id = substr(md5($content . mt_rand()), 0, 8);
			$str = '<div class="spoiler">';
			$str .= '<div class="code-header" id="spoilerhdr_' . $spoiler_id . '" style="position: relative;">' . $lang['bbcb_mg_spoiler'] . ': [ <a href="javascript:void(0)" onclick="ShowHide(\'spoiler_' . $spoiler_id . '\', \'spoiler2_' . $spoiler_id . '\', \'\'); ShowHide(\'spoilerhdr_' . $spoiler_id . '\', \'spoilerhdr2_' . $spoiler_id . '\', \'\')">' . $lang['Show'] . '</a> ]</div>';
			$str .= '<div class="code-header" id="spoilerhdr2_' . $spoiler_id . '" style="position: relative; display: none;">' . $lang['bbcb_mg_spoiler'] . ': [ <a href="javascript:void(0)" onclick="ShowHide(\'spoiler_' . $spoiler_id . '\', \'spoiler2_' . $spoiler_id . '\', \'\'); ShowHide(\'spoilerhdr_' . $spoiler_id . '\', \'spoilerhdr2_' . $spoiler_id . '\', \'\')">' . $lang['Hide'] . '</a> ]</div>';
			$str .= '<div class="spoiler-content" id="spoiler2_' . $spoiler_id . '" style="position: relative; display: none;">' . $html;
			return array(
				'valid' => true,
				'start' => $str,
				'end' => '</div></div>'
			);
		}

		// LANGVAR
		// Insert the content of a lang var into post... maybe we need to filter something?
		if($tag === 'langvar')
		{
			if(isset($item['params']['param']))
			{
				$langvar = $item['params']['param'];
			}
			else
			{
				$langvar = $content;
			}
				$langvar = $content;
			$html = (isset($lang[$langvar]) ? $lang[$langvar] : '');
			return array(
				'valid' => true,
				'html' => $html
			);
		}

		// LANGUAGE
		// Parse the content only if in the same language of the user viewing it!!!
		if($tag === 'language')
		{
			if($content == '')
			{
				return $error;
			}

			$language = '';
			if(isset($item['params']['param']))
			{
				$language = $item['params']['param'];
			}

			if ($userdata['user_lang'] != $language)
			{
				$html = '';
			}
			else
			{
				$html = $content;
			}

			return array(
				'valid' => true,
				'html' => $html
			);
		}

		// SEARCH
		if($tag === 'search')
		{
			if($content == '')
			{
				return $error;
			}
			$str = '<a href="' . SEARCH_MG . '?search_keywords=' . $this->process_text($content) . '">';
			return array(
				'valid' => true,
				'start' => $str,
				'end' => '</a>'
			);
		}

		// Random number or quote (quote not implemented yet)
		if($tag === 'random')
		{
			$max_n = 6;
			$max_n = intval((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['max']) ? $item['params']['max'] : 6)));
			$max_n = ($max_n <= 0) ? 6 : $max_n;
			/*
			include_once(IP_ROOT_PATH . 'language/lang_' . $board_config['default_lang'] . '/lang_randomquote.' . PHP_EXT);
			$randomquote_phrase = $randomquote[rand(0, count($randomquote) - 1)];
			*/
			$html = rand(1, $max_n);
			return array(
				'valid' => true,
				'html' => $html
			);
		}

		// MARQUEE
		if($tag === 'marquee')
		{
			if($this->is_sig)
			{
				return $error;
			}
			$extras = $this->allow_styling ? array('style', 'class') : array();

			$directions_array = array('up', 'right', 'down', 'left');
			$default_param = 'right';
			$direction = (isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['direction']) ? $item['params']['direction'] : $default_param));
			$direction = (in_array($direction, $directions_array) ? $direction : $default_param);

			$default_scroll = '120';
			$scrolldelay = (isset($item['params']['scrolldelay']) ? intval($item['params']['scrolldelay']) : $default_scroll);
			$scrolldelay = ((($scrolldelay > 10) && ($scrolldelay < 601)) ? $scrolldelay : $default_scroll);

			$default_behavior = 'scroll';
			$behavior = (isset($item['params']['behavior']) ? intval($item['params']['behavior']) : $default_behavior);
			$behavior = ((($behavior === 'alternate') || ($behavior === 'slide')) ? $behavior : $default_behavior);

			$html = '<marquee behavior="' . $behavior . '" direction="' . $direction . '" scrolldelay="' . $scrolldelay . '" loop="true" onmouseover="this.stop()" onmouseout="this.start()">';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</marquee>',
			);
		}

		// Active Content - BEGIN
		// Added by Tom XS2 Build 054
		if ($board_config['switch_bbcb_active_content'] == 1)
		{
			// FLASH, SWF, FLV, VIDEO, REAL, QUICK, STREAM, EMFF, YOUTUBE, GOOGLEVIDEO
			if(($tag === 'flash') || ($tag === 'swf') || ($tag === 'flv') || ($tag === 'video') || ($tag === 'ram') || ($tag === 'quick') || ($tag === 'stream') || ($tag === 'emff') || ($tag === 'mp3') || ($tag === 'youtube') || ($tag === 'googlevideo'))
			{
				if($this->is_sig)
				{
					return $error;
				}
				$content = $this->process_text(isset($item['params']['param']) ? $item['params']['param'] : $content);

				$default_width = '320';
				$width = (isset($item['params']['width']) ? intval($item['params']['width']) : $default_width);
				$width = ((($width > 10) && ($width < 641)) ? $width : $default_width);

				$default_height = '240';
				$height = (isset($item['params']['height']) ? intval($item['params']['height']) : $default_height);
				$height = ((($height > 10) && ($height < 481)) ? $height : $default_height);

				if (($tag === 'flash') || ($tag === 'swf'))
				{
					$html = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" width="' . $width . '" height="' . $height . '"><param name="movie" value="' . $content . '"><param name="quality" value="high"><param name="scale" value="noborder"><param name="wmode" value="transparent"><param name="bgcolor" value="#000000"><embed src="' . $content . '" quality="high" scale="noborder" wmode="transparent" bgcolor="#000000" width="' . $width . '" height="' . $height . '" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"></embed></object>';
				}
				elseif ($tag === 'flv')
				{
					$html = '<object type="application/x-shockwave-flash" width="' . $width . '" height="' . $height . '" wmode="transparent" data="flv_player.swf?file=' . $content . '&amp;autoStart=false"><param name="movie" value="flv_player.swf?file=' . $content . '&amp;autoStart=false"/><param name="wmode" value="transparent"/></object>';
				}
				elseif ($tag === 'video')
				{
					$html = '<div align="center"><embed src="' . $content . '" width="' . $width . '" height="' . $height . '"></embed></div>';
				}
				elseif ($tag === 'ram')
				{
					$html = '<div align="center"><embed src="' . $content . '" align="center" width="275" height="40" type="audio/x-pn-realaudio-plugin" console="cons" controls="ControlPanel" autostart="false"></embed></div>';
				}
				elseif ($tag === 'quick')
				{
					$html = '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0" width="' . $width . '" height="' . $height . '" align="middle"><param name="controller" value="true"><param name="type" value="video/quicktime"><param name="autoplay" value="true"><param name="target" value="myself"><param name="src" value="' . $content . '"><param name="pluginspage" value="http://www.apple.com/quicktime/download/indext.html"><param name="kioskmode" value="true"><embed src="' . $content . '" width="' . $width . '" height="' . $height . '" align="middle" kioskmode="true" controller="true" target="myself" type="video/quicktime" border="0" pluginspage="http://www.apple.com/quicktime/download/indext.html"></embed></object>';
				}
				elseif ($tag === 'stream')
				{
					$html = '<object id="wmp" width="' . $width . '" height="' . $height . '" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,0,0" standby="Loading Microsoft Windows Media Player components..." type="application/x-oleobject"><param name="FileName" value="' . $content . '"><param name="ShowControls" value="1"><param name="ShowDisplay" value="0"><param name="ShowStatusBar" value="1"><param name="AutoSize" value="1"><embed type="application/x-mplayer2" pluginspage="http://www.microsoft.com/windows95/downloads/contents/wurecommended/s_wufeatured/mediaplayer/default.asp" src="' . $content . '" name="MediaPlayer2" showcontrols="1" showdisplay="0" showstatusbar="1" autosize="1" visible="1" animationatstart="0" transparentatstart="1" loop="0" height="70" width="300"></embed></object>';
				}
				elseif (($tag === 'emff') || ($tag === 'mp3'))
				{
					$html = '<object data="emff_player.swf" type="application/x-shockwave-flash" width="200" height="55" align="top" ><param name="FlashVars" value="src=' . $content . '" /><param name="movie" value="emff_player.swf" /><param name="quality" value="high" /><param name="bgcolor" value="#F8F8F8" /></object>';
				}
				elseif ($tag === 'youtube')
				{
					$html = '<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/' . $content . '" /><embed src="http://www.youtube.com/v/' . $content . '" type="application/x-shockwave-flash" width="425" height="350"></embed></object><br /><a href="http://youtube.com/watch?v=' . $content . '" target="_blank">Link</a><br />';
				}
				elseif ($tag === 'googlevideo')
				{
					$html = '<object width="425" height="350"><param name="movie" value="http://video.google.com/googleplayer.swf?docId=' . $content . '"></param><embed style="width:400px; height:326px;" id="VideoPlayback" align="middle" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=' . $content . '" allowScriptAccess="sameDomain" quality="best" bgcolor="#f8f8f8" scale="noScale" salign="TL" FlashVars="playerMode=embedded"></embed></object><br /><a href="http://video.google.com/videoplay?docid=' . $content . '" target="_blank">Link</a><br />';
				}
				return array(
					'valid' => true,
					'html' => $html
				);
			}
		}
		// Active Content - END

		// SMILEY
		if($tag === 'smiley')
		{
			if($this->is_sig)
			{
				return $error;
			}
			$extras = $this->allow_styling ? array('style', 'class') : array();

			$text = htmlspecialchars((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['text']) ? $item['params']['text'] : $content)));

			if(isset($item['params']['smilie']))
			{
				if (($item['params']['smilie'] == 'standard') || ($item['params']['smilie'] == 'random'))
				{
					//$smilie = $item['params']['smilie'];
					$smilie = '1';
				}
				else
				{
					$smilie = intval($item['params']['smilie']);
				}
			}
			else
			{
				$smilie = '1';
			}

			$default_fontcolor = '000000';
			$fontcolor = $this->valid_color((isset($item['params']['fontcolor']) ? $item['params']['fontcolor'] : $default_fontcolor));
			$fontcolor = (($fontcolor === false) ? $default_fontcolor : str_replace('#', '', $fontcolor));

			$default_shadowcolor = '888888';
			$shadowcolor = $this->valid_color((isset($item['params']['shadowcolor']) ? $item['params']['shadowcolor'] : $default_shadowcolor));
			$shadowcolor = (($shadowcolor === false) ? $default_shadowcolor : str_replace('#', '', $shadowcolor));

			$default_shieldshadow = 0;
			$shieldshadow = (isset($item['params']['shieldshadow']) ? (($item['params']['shieldshadow'] == 1) ? 1 : $default_param) : $default_param);

			//$html = '<img src="text2shield.' . PHP_EXT . '?smilie=' . $smilie . '&amp;fontcolor=' . $fontcolor . '&amp;shadowcolor=' . $shadowcolor . '&amp;shieldshadow=' . $shieldshadow . '&amp;text=' . $text . '" alt="Smiley" title="Smiley" />';
			$html = '<img src="text2shield.' . PHP_EXT . '?smilie=' . $smilie . '&amp;fontcolor=' . $fontcolor . '&amp;shadowcolor=' . $shadowcolor . '&amp;shieldshadow=' . $shieldshadow . '&amp;text=' . urlencode(utf8_decode($text)) . '" alt="'. $text . '" title="' . $text . '" />';
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false,
			);
		}

		// OPACITY
		if($tag === 'opacity')
		{
			if($this->is_sig)
			{
				return $error;
			}
			if(isset($item['params']['param']))
			{
				$opacity = intval($item['params']['param']);
				if (($opacity > 0) && ($opacity < 101))
				{
					$opacity = $opacity;
				}
			}
			else
			{
				$opacity = '100';
			}
			$opacity_dec = $opacity / 100;
			$html = '<div style="display:inline;width:100%;-moz-opacity:' . $opacity_dec . ';opacity:' . $opacity_dec . ';-khtml-opacity:' . $opacity_dec . ';filter:Alpha(Opacity=' . $opacity . ');" onMouseOut="fade2(this,' . $opacity . ');" onMouseOver="fade2(this,100);">';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</div>',
			);
		}

		// FADE
		if($tag === 'fade')
		{
			if($this->is_sig)
			{
				return $error;
			}
			if(isset($item['params']['param']))
			{
				$opacity = intval($item['params']['param']);
				if (($opacity > 0) && ($opacity < 101))
				{
					$opacity = $opacity;
				}
			}
			else
			{
				$opacity = '100';
			}
			$opacity_dec = $opacity / 100;
			$html = '<div style="display:inline;height:1;-moz-opacity:' . $opacity_dec . ';opacity:' . $opacity_dec . ';-khtml-opacity:' . $opacity_dec . ';filter:Alpha(Opacity=' . $opacity . ',FinishOpacity=0,Style=1,StartX=0,FinishX=100%);">';
			//$html = '<div style="display:inline;height:1;filter:Alpha(Opacity=' . $opacity . ',FinishOpacity=0,Style=1,StartX=0,FinishX=100%);">';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</div>',
			);
		}

		// IE AND HTML 4 ONLY TAGS - BEGIN
		// Let's add a global IF so we can skip them all in once to speed up things...
		// Enable these tags only if you know how to make them work...
		if(($tag === 'glow') || ($tag === 'shadow') || ($tag === 'blur') || ($tag === 'wave') || ($tag === 'fliph') || ($tag === 'flipv'))
		{
			return array(
				'valid' => true,
				'start' => '',
				'end' => '',
			);
		}
		/*
		if(($tag === 'glow') || ($tag === 'shadow') || ($tag === 'blur') || ($tag === 'wave') || ($tag === 'fliph') || ($tag === 'flipv'))
		{
			// GLOW
			if($tag === 'glow')
			{
				$default_color = '#fffffa';
				$color = $this->valid_color((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['color']) ? $item['params']['color'] : $default_color)));
				if($color === false)
				{
					return $error;
				}
				$html = '<div style="display:inline;filter:glow(color=' . $color . ');height:20;">';
				return array(
					'valid' => true,
					'start' => $html,
					'end' => '</div>',
				);
			}

			// SHADOW
			if($tag === 'shadow')
			{
				$default_color = '#666666';
				$color = $this->valid_color((isset($item['params']['param']) ? $item['params']['param'] : (isset($item['params']['color']) ? $item['params']['color'] : $default_color)));
				if($color === false)
				{
					return $error;
				}
				$html = '<div style="display:inline;filter:shadow(color=' . $color . ');height:20;">';
				return array(
					'valid' => true,
					'start' => $html,
					'end' => '</div>',
				);
			}

			// BLUR
			if($tag === 'blur')
			{
				if($this->is_sig)
				{
					return $error;
				}
				if(isset($item['params']['param']))
				{
					$strenght = intval($item['params']['param']);
					if (($strenght > 0) && ($strenght < 101))
					{
						$strenght = $strenght;
					}
				}
				else
				{
					$strenght = '100';
				}
				$strenght_dec = $strenght / 100;
				$html = '<div style="display:inline;width:100%;height:20;filter:Blur(add=1,direction=270,strength=' . $strenght . ');">';
				return array(
					'valid' => true,
					'start' => $html,
					'end' => '</div>',
				);
			}

			// WAVE
			if($tag === 'wave')
			{
				if($this->is_sig)
				{
					return $error;
				}
				if(isset($item['params']['param']))
				{
					$strenght = intval($item['params']['param']);
					if (($strenght > 0) && ($strenght < 101))
					{
						$strenght = $strenght;
					}
				}
				else
				{
					$strenght = '100';
				}
				$strenght_dec = $strenght / 100;
				$html = '<div style="display:inline;width:100%;height:20;filter:Wave(add=1,direction=270,strength=' . $strenght . ');">';
				return array(
					'valid' => true,
					'start' => $html,
					'end' => '</div>',
				);
			}

			// FLIPH, FLIPV
			if(($tag === 'fliph') || ($tag === 'flipv'))
			{
				if($this->is_sig)
				{
					return $error;
				}
				$html = '<div style="display:inline;filter:' . $tag . ';height:1;">';
				return array(
					'valid' => true,
					'start' => $html,
					'end' => '</div>',
				);
			}
		}
		*/
		// OLD IE AND HTML 4 ONLY TAGS - END

		// TEX
		if($tag === 'tex')
		{
			if($this->is_sig)
			{
				return $error;
			}
			$html = '<img src="cgi-bin/mimetex.cgi?' . $content . '" alt="" border="0" style="vertical-align:middle;" />';
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => false,
			);
		}

		// TABLE
		if($tag === 'table')
		{
			if($this->is_sig)
			{
				return $error;
			}
			// additional allowed parameters
			$extras = $this->allow_styling ? array('class', 'align', 'width', 'height', 'border', 'cellspacing', 'cellpadding') : array('class', 'align', 'width');
			if(isset($item['params']['param']))
			{
				$table_class = $item['params']['param'];
			}
			else
			{
				$table_class = 'empty-table';
			}

			for($i = 0; $i < count($extras); $i++)
			{
				if(!empty($item['params'][$extras[$i]]))
				{
					if($extras[$i] === 'style')
					{
						$style = $this->valid_style($item['params']['style']);
						if($style !== false)
						{
							$params['style'] = $style;
						}
					}
					else
					{
						$params[$extras[$i]] = $item['params'][$extras[$i]];
					}
				}
			}
			if (!isset($params['class']))
			{
				$params['class'] = $table_class;
			}
			// generate html
			$html = '<table';
			foreach($params as $var => $value)
			{
				$html .= ' ' . $var . '="' . $this->process_text($value) . '"';
			}
			$html .= ' >' . $content . '</table>';
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => true,
			);
		}

		/*
		// TR
		if($tag === 'tr')
		{
			if($this->is_sig)
			{
				return $error;
			}
			// generate html
			$html = '<tr>' . $content . '</tr>';
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => true,
			);
		}

		// TD
		if($tag === 'td')
		{
			if($this->is_sig)
			{
				return $error;
			}
			// additional allowed parameters
			$extras = $this->allow_styling ? array('class', 'align', 'width', 'height') : array('class', 'align', 'width', 'height');

			for($i = 0; $i < count($extras); $i++)
			{
				if(!empty($item['params'][$extras[$i]]))
				{
					if($extras[$i] === 'style')
					{
						$style = $this->valid_style($item['params']['style']);
						if($style !== false)
						{
							$params['style'] = $style;
						}
					}
					else
					{
						$params[$extras[$i]] = $item['params'][$extras[$i]];
					}
				}
			}
			// generate html
			$html = '<td';
			foreach($params as $var => $value)
			{
				$html .= ' ' . $var . '="' . $this->process_text($value) . '"';
			}
			$html .= ' >' . $content . '</td>';
			return array(
				'valid' => true,
				'html' => $html,
				'allow_nested' => true,
			);
		}
		*/

		// IFRAME
		/*
		//<iframe src="index.html" scrolling="no" width="100%" height="190" frameborder="0" marginheight="0" marginwidth="0"></iframe>
		//[iframe height=100]docs/index.html[/iframe]
		//[iframe src=docs/index.html height=100] [/iframe]
		if($tag === 'iframe')
		{
			if(isset($item['params']['param']))
			{
				$params['src'] = $item['params']['param'];
			}
			elseif(isset($item['params']['src']))
			{
				$params['src'] = $item['params']['src'];
			}
			elseif(!empty($content))
			{
				$params['src'] = $content;
			}
			if(isset($item['params']['scrolling']) && ($params['scrolling'] == 'no'))
			{
				$params['scrolling'] = 'no';
				//$params['scrolling'] = $item['params']['scrolling'];
			}
			else
			{
				$params['scrolling'] = 'yes';
			}
			if(isset($item['params']['width']))
			{
				$params['width'] = $item['params']['width'];
			}
			else
			{
				$params['width'] = '100%';
			}
			if(isset($item['params']['height']))
			{
				$params['height'] = $item['params']['height'];
			}
			else
			{
				$params['height'] = '600';
			}

			foreach($params as $var => $value)
			{
				if ($this->process_text($value) != '')
				{
					$html .= ' ' . $var . '="' . $this->process_text($value) . '"';
				}
			}
			$extras = $this->allow_styling ? array('style', 'class') : array('class');
			$html = '<iframe' . $html . '>';
			return array(
				'valid' => true,
				'start' => $html,
				'end' => '</iframe>'
			);
		}
		*/

		// Invalid tag
		return $error;
	}

	// Check if bbcode tag is valid
	function valid_tag($tag, $is_html)
	{
		if($is_html)
		{
			return (isset($this->allowed_html[$tag]) && preg_match('/^[a-z]+$/', $tag)) ? true : false;
		}
		else
		{
			$tag_ok = false;
			if(($tag === '*') || ($tag === '[*]') || ($tag === '[hr]'))
			{
				$tag_ok = true;
			}
			return (isset($this->allowed_bbcode[$tag]) && (preg_match('/^[a-z]+$/', $tag) || ($tag_ok === true))) ? true : false;
		}
	}

	// Check if parameter name is valid
	function valid_param($param)
	{
		return preg_match('/^[a-z]+$/', $param);
	}

	// Check if color is valid
	function valid_color($color, $hex_only = false)
	{
		if ($color === false)
		{
			return false;
		}
		$color = strtolower($color);
		if(substr($color, 0, 1) === '#')
		{
			// normal color
			if(preg_match('/^[0-9a-f]+$/', substr($color, 1)))
			{
				if ($hex_only == true)
				{
					if(strlen($color) == 7)
					{
						return $color;
					}
				}
				else
				{
					if((strlen($color) == 4) || (strlen($color) == 7))
					{
						return $color;
					}
				}
			}
			return false;
		}
		// color with missing #
		if(preg_match('/^[0-9a-f]+$/', $color))
		{
			if ($hex_only == true)
			{
				if(strlen($color) == 6)
				{
					return '#' . $color;
				}
			}
			else
			{
				if((strlen($color) == 3) || (strlen($color) == 6))
				{
					return '#' . $color;
				}
			}
		}
		if($hex_only == true)
		{
			// We didn't find any valid 6 digits hex color
			return false;
		}
		if(preg_match('/^[a-z]+$/', $color))
		{
			// text color
			return $color;
		}
		// rgb color
		if((substr($color, 0, 4) === 'rgb(') && preg_match('/^rgb\([0-9]+,[0-9]+,[0-9]+\)$/', $color))
		{
			$colors = explode(',', substr($color, 4, strlen($color) - 5));
			for($i = 0; $i < 3; $i++)
			{
				if($colors[$i] > 255)
				{
					return false;
				}
			}
			return sprintf('#%02X%02X%02X', $colors[0], $colors[1], $colors[2]);
		}
		return false;
	}

	// Parse style
	function valid_style($style, $error = false)
	{
		$style = str_replace(array('\\', '"', '@'), array('','',''), $style);
		$str = strtolower($style);
		if(strpos($str, 'expression') !== false || strpos($str, 'javascript:') !== false || strpos($str, 'vbscript:') !== false || strpos($str, 'about:') !== false)
		{
			// attempt to use javascript
			return $error;
		}
		if(strpos($str, '//') !== false)
		{
			// attempt to use external file
			return $error;
		}
		if(strpos($str, '!important') !== false)
		{
			// attempt to completely mess up forum layout?
			return $error;
		}
		return $style;
	}

	// Validate url
	function valid_url($url, $error = '')
	{
		$str = strtolower($url);
		if(substr($str, 0, 11) === 'javascript:')
		{
			// attempt to use javascript
			return $error;
		}
		if(substr($str, 0, 9) === 'vbscript:')
		{
			// attempt to use vbscript
			return $error;
		}
		if(substr($str, 0, 6) === 'about:')
		{
			// attempt to use about: url
			return $error;
		}
		return $url;
	}

	// Add extras
	function add_extras($params, $extras)
	{
		$html = '';
		for($i = 0; $i < count($extras); $i++)
		{
			if(isset($params[$extras[$i]]))
			{
				if($extras[$i] === 'style')
				{
					$style = $this->valid_style($params['style']);
					if($style !== false)
					{
						$html .= ' style="' . htmlspecialchars($style) . '"';
					}
				}
				else
				{
					$html .= ' ' . $extras[$i] . '="' . htmlspecialchars($params[$extras[$i]]) . '"';
				}
			}
		}
		return $html;
	}

	// Splits string to tag and parameters
	function extract_params($tag, $is_html)
	{
		$this->tag = $tag;
		$this->params = array();
		$tag = str_replace("\t", ' ', $tag);
		// get parameters
		$pos_eq = strpos($tag, '=');
		$pos_space = strpos($tag, ' ');
		if($pos_space !== false && $pos_eq !== false && $pos_space < $pos_eq)
		{
			// mutiple parameters
			$param_start = 0;
			$param_str = substr($tag, $pos_space + 1);
			$param_len = strlen($param_str);
			$this->tag = strtolower(substr($tag, 0, $pos_space));
			if(!$this->valid_tag($this->tag, $is_html))
			{
				return false;
			}
			while($param_start < $param_len)
			{
				// find entry for '='
				$pos = strpos($param_str, '=', $param_start);
				if($pos === false)
				{
					return false;
				}
				else
				{
					// get parameter name
					$str = substr($param_str, $param_start, $pos - $param_start);
					if(!$this->valid_param($str))
					{
						return false;
					}
					// get value
					$pos++;
					$quoted = false;
					if(substr($param_str, $pos, 1) === '"')
					{
						$pos2 = strpos($param_str, '"', $pos + 1);
						if($pos2 === false)
						{
							// invalid quote. search for space instead
							$pos2 = strpos($param_str, ' ', $pos + 1);
						}
						else
						{
							$pos++;
							$quoted = true;
						}
					}
					else
					{
						$pos2 = strpos($param_str, ' ', $pos);
					}
					// end not found. counting until end of expression
					if($pos2 === false)
					{
						$pos2 = $param_len;
					}
					$this->params[$str] = substr($param_str, $pos, $pos2 - $pos);
					$param_start = $pos2 + 1;
					if($quoted)
					{
						$param_start ++;
					}
				}
			}
		}
		elseif($pos_eq !== false)
		{
			// single parameter
			$str = substr($tag, $pos_eq + 1);
			$this->tag = strtolower(substr($tag, 0, $pos_eq));
			if(!$this->valid_tag($this->tag, $is_html))
			{
				return false;
			}
			if(strlen($str) > 1 && substr($str, 0, 1) === '"' && substr($str, strlen($str) - 1) === '"')
			{
				$str = substr($str, 1, strlen($str) - 2);
			}
			if(trim($str) !== $str)
			{
				return false;
			}
			$this->params['param'] = $str;
		}
		else
		{
			// no parameters
			$this->tag = strtolower($tag);
			if(!$this->valid_tag($this->tag, $is_html))
			{
				return false;
			}
		}
		return true;
	}

	// Recursive function that converts text to bbcode tree
	function push($start, $level, $prev_tags)
	{
		//echo '<b>push</b>(', $start, ', ', $level, ', (', implode(',', $prev_tags), '))<br />';
		$items = array();
		$pos_start_bbcode = $this->allow_bbcode ? strpos($this->text, '[', $start) : false;
		$pos_start_html = $this->allow_html ? strpos($this->text, '<', $start) : false;
		while($pos_start_bbcode !== false || $pos_start_html !== false)
		{
			$pos_start = $pos_start_bbcode === false ? $pos_start_html : ($pos_start_html === false ? $pos_start_bbcode : min($pos_start_bbcode, $pos_start_html));
			$is_html = $pos_start_html === $pos_start ? true : false;
			$prev_start = $start;
			// found tag. get data.
			$pos_end = strpos($this->text, $is_html ? '>' : ']', $pos_start);
			if($pos_end === false)
			{
				$tag_valid = false;
			}
			else
			{
				$code = substr($this->text, $pos_start, $pos_end - $pos_start + 1);
				// check if tag is valid and get type of tag
				$tag_valid = true;
				$tag_closing = false;
				$tag_self_closing = false;
				if(strlen($code) < 3)
				{
					$tag_valid = false;
				}
				elseif(!$is_html && strpos($code, '[', 1) !== false)
				{
					$tag_valid = false;
				}
				elseif($is_html && strpos($code, '<', 1) !== false)
				{
					$tag_valid = false;
				}
				elseif(!$is_html && strpos($code, "\n") !== false)
				{
					$tag_valid = false;
				}
				elseif(substr($code, 0, 2) === ($is_html ? '</' : '[/'))
				{
					$tag_closing = true;
					$tag = substr($code, 2, strlen($code) - 3);
				}
				elseif(substr($code, strlen($code) - 3) === ($is_html ? ' />' : ' /]'))
				{
					$tag_self_closing = true;
					$tag = substr($code, 1, strlen($code) - 4);
				}
				else
				{
					$tag = substr($code, 1, strlen($code) - 2);
				}
				// do not process tag if it requires too much recursion
				if($level > 12 && (!$tag_closing && !$tag_self_closing))
				{
					$tag_valid = false;
				}
				// special tags
				if(in_array($code, $this->self_closing_tags) != false)
				{
					$tag_self_closing = true;
				}
			}
			if($tag_valid)
			{
				$start = $pos_end;
				$params = array();
				if(!$tag_closing)
				{
					if(!$this->extract_params($tag, $is_html))
					{
						$tag_valid = false;
					}
					else
					{
						$tag = $this->tag;
						$params = $this->params;
					}
				}
				else
				{
					if(strpos($tag, ' autourl=' . AUTOURL))
					{
						$tag = str_replace(' autourl=' . AUTOURL, '', $tag);
					}
					$tag = strtolower($tag);
					if(!$this->valid_tag($tag, $is_html))
					{
						$tag_valid = false;
					}
				}
			}
			if($tag_valid)
			{
				if($tag_closing)
				{
					// check if this is correct closing tag
					if(in_array($tag, $prev_tags))
					{
						return array(
							'items' => $items,
							'tag' => $tag,
							'pos' => $pos_end,
							'start' => $pos_start,
							'len' => strlen($code)
							);
					}
				}
				elseif($tag_self_closing)
				{
					// found self-closing tag
					$items[] = array(
						'tag' => $tag,
						'code' => $code,
						'params' => $params,
						'start' => $pos_start,
						'start_len' => strlen($code),
						'end' => $pos_end + 1,
						'end_len' => 0,
						'level' => $level + 1,
						'iteration' => 0,
						'self_closing' => 1,
						'prev' => array(),
						'next' => array(),
						'is_html' => $is_html,
						'items' => array()
						);
				}
				else
				{
					// found correct tag. call recursive search
					$result = $this->push($pos_end, $level + 1, array_merge($prev_tags, array($tag)));
					if($result['tag'] === $tag)
					{
						// found correctly finished tag
						$items[] = array(
							'tag' => $tag,
							'code' => $code,
							'params' => $params,
							'start' => $pos_start,
							'start_len' => strlen($code),
							'end' => $result['start'],
							'end_len' => $result['len'],
							'level' => $level + 1,
							'iteration' => 0,
							'self_closing' => 2,
							'prev' => array(),
							'next' => array(),
							'is_html' => $is_html,
							'items' => $result['items']
							);
						$start = $result['pos'];
					}
					else
					{
						$items = array_merge($items, $result['items']);
						return array(
							'items' => $items,
							'tag' => $result['tag'],
							'pos' => $result['pos'],
							'start' => $result['start'],
							'len' => $result['len']
							);
					}
				}
			}
			else
			{
				$start = $pos_start + 1;
			}
			$pos_start_bbcode = $this->allow_bbcode ? strpos($this->text, '[', $start) : false;
			$pos_start_html = $this->allow_html ? strpos($this->text, '<', $start) : false;
		}
		return array(
			'items' => $items,
			'tag' => false,
			);
	}

	// Debug fuction. Prints tree of bbcode
	function debug($items)
	{
		for($i = 0; $i < count($items); $i++)
		{
			$item = $items[$i];
			if($item['tag'])
			{
				for($j=0; $j<$item['level']; $j++)
				{
					echo '-';
				}
				echo ' ', $item['tag'], ' (';
				$first = true;
				foreach($item['params'] as $var => $value)
				{
					if(!$first) echo ', ';
					$first = false;
					echo $var, '="', htmlspecialchars($value), '"';
				}
				echo ")<br />\n";
				$this->debug($item['items']);
			}
		}
	}

	// Post-processing. Adds previous/next items to every item.
	function add_pointers(&$items, $prev_tags)
	{
		$tags = array();
		for($i = 0; $i < count($items); $i++)
		{
			$item = &$items[$i];
			$tags[] = array(
				'tag' => $item['tag'],
				'item' => &$items[$i]
				);
			$iterations = 0;
			for($j = 0; $j < count($prev_tags); $j++)
			{
				if($prev_tags[$j]['tag'] === $item['tag'])
				{
					$iterations++;
				}
			}
			$item['iteration'] = $iterations;
			$item['prev'] = $prev_tags;
			// todo: check if subitems are allowed
			// parse sub-items
			if(count($item['items']))
			{
				$arr = array(
					'tag' => $item['tag'],
					'item' => &$items[$i]
					);
				$item['next'] = $this->add_pointers($item['items'], array_merge($prev_tags, array($arr)));
				$tags = array_merge($tags, $item['next']);
			}
		}
		return $tags;
	}

	// Process text
	function process_text($text, $br = true, $chars = true)
	{
		$search = array(
			'[url autourl=' . AUTOURL . ']',
			'[/url autourl=' . AUTOURL  .']',
			'[email autourl=' . AUTOURL . ']',
			'[/email autourl=' . AUTOURL .']'
		);
		$replace = array('', '', '', '');
		$text = str_replace($search, $replace, $text);
		if($chars)
		{
			$text = htmlspecialchars($text);
			$text = str_replace('&amp;#', '&#', $text);
		}
		else
		{
			$text = str_replace(
				array('&amp;', '>', '%3E', '<', '%3C', '"', '&amp;#'),
				array('&amp;amp;', '&gt;', '&gt;', '&lt;', '&lt;', '&quot;', '&#'),
				$text
			);
		}
		if($br)
		{
			$text = str_replace("\n", "<br />\n", $text);
		}
		return $text;
	}

	// Process tree
	function process($start, $end, &$items, $clean_tags = false)
	{
		$html = '';
		for($i = 0; $i < count($items); $i++)
		{
			$item = &$items[$i];
			// check code before item
			if($item['start'] > $start)
			{
				$html .= $this->process_text(substr($this->text, $start, $item['start'] - $start));
			}
			if ($clean_tags === true)
			{
				// clean tag
				$result = $this->clean_tag($item);
			}
			else
			{
				// process tag
				$result = $this->process_tag($item);
			}
			if($result['valid'] && !isset($result['html']))
			{
				$html .= $result['start'];
				if(!isset($result['allow_nested']) || $result['allow_nested'])
				{
					// process code inside tag
					$html .= $this->process($item['start'] + $item['start_len'], $item['end'], $item['items'], $clean_tags);
				}
				$html .= $result['end'];
			}
			elseif($result['valid'])
			{
				$html .= $result['html'];
			}
			else
			{
				// invalid tag. show html code for it and process nested tags
				$item['valid'] = false;
				if($item['start_len'])
				{
					$html .= $this->process_text(substr($this->text, $item['start'], $item['start_len']));
				}
				$html .= $this->process($item['start'] + $item['start_len'], $item['end'], $item['items']);
				if($item['end_len'])
				{
					$html .= $this->process_text(substr($this->text, $item['end'], $item['end_len']));
				}
			}
			$start = $item['end'] + $item['end_len'];
		}
		// process code after item
		if($start < $end)
		{
			$html .= $this->process_text(substr($this->text, $start, $end - $start));
		}
		return $html;
	}

	// Prepare smilies list
	function prepare_smilies()
	{
		if(!$this->allow_smilies)
		{
			return;
		}
		$this->replaced_smilies = array();
		for($i = 0; $i < count($this->allowed_smilies); $i++)
		{
			if(strpos($this->text, $this->allowed_smilies[$i]['code']) !== false)
			{
				$this->replaced_smilies[] = $this->allowed_smilies[$i];
			}
		}
	}

	// Process smilies
	function process_smilies()
	{
		$valid_chars_prev = array('', ' ', "\n", "\r", "\t", '>');
		$valid_chars_next = array('', ' ', "\n", "\r", "\t", '<');
		if(!$this->allow_smilies && !count($this->replaced_smilies))
		{
			return;
		}
		for($i = 0; $i < count($this->replaced_smilies); $i++)
		{
			$code = $this->replaced_smilies[$i]['code'];
			$text = $this->replaced_smilies[$i]['replace'];
			$code_len = strlen($code);
			$text_len = strlen($text);
			$pos = strpos($this->html, $code);
			while($pos !== false)
			{
				$valid = false;
				// check previous character
				$prev_char = $pos > 0 ? substr($this->html, $pos - 1, 1) : '';
				if(in_array($prev_char, $valid_chars_prev))
				{
					// check next character
					$next_char = substr($this->html, $pos + $code_len, 1);
					if(in_array($next_char, $valid_chars_next))
					{
						// make sure we aren't inside html code
						$pos1 = strpos($this->html, '<', $pos + $code_len);
						$pos2 = strpos($this->html, '>', $pos + $code_len);
						if($pos2 === false || ($pos1 && $pos1 < $pos2))
						{
							// make sure we aren't inside nosmilies zone
							$pos1 = strpos($this->html, BBCODE_NOSMILIES_START, $pos + $code_len);
							$pos2 = strpos($this->html, BBCODE_NOSMILIES_END, $pos + $code_len);
							if($pos2 === false || ($pos1 && $pos1 < $pos2))
							{
								$valid = true;
							}
						}
					}
				}
				if($valid)
				{
					$this->html = substr($this->html, 0, $pos) . $text . substr($this->html, $pos + $code_len);
					$pos += $text_len;
				}
				else
				{
					$pos ++;
				}
				$pos = strpos($this->html, $code, $pos);
			}
		}
	}

	// Make urls clickable
	function process_urls()
	{
		// characters allowed in email
		$chars = array();
		for($i = 224; $i < 256; $i++)
		{
			if($i != 247)
			{
				$chars .= chr($i);
			}
		}
		// search and replace arrays
		$search = array(
			"/([\s>])((https?|ftp):\/\/|www\.)([^ \r\n\(\)\^\$!`\"'\|\[\]\{\}<>]+)/si",
			"/([\s>])([_a-zA-Z0-9\-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9\-{$chars}]+(\.[a-zA-Z0-9\-{$chars}]+)*(\.[a-zA-Z]{2,}))/si",
		);
		$replace = array(
			"\\1[url autourl=" . AUTOURL . "]\\2\\4[/url autourl=" . AUTOURL . "]",
			"\\1[email autourl=" . AUTOURL . "]\\2[/email autourl=" . AUTOURL . "]",
		);
		$this->text = preg_replace($search, $replace, ' ' . $this->text . ' ');
		$this->text = substr($this->text, 1, strlen($this->text) - 2);
	}

	// Remove bbcode_uid from old posts
	function bbcuid_clean($text, $id = false)
	{
		if ($id != false)
		{
			$text = str_replace(':' . $id, '', $text);
		}
		else
		{
			$text = preg_replace("/\:([a-f0-9]{10})/s", '', $text);
			// phpBB 3
			//$text = preg_replace("/\:([a-z0-9]{8})/s", '', $text);
		}
		return $text;
	}

	// Converts text to html code
	function parse($text, $id = false, $light = false, $clean_tags = false)
	{
		if(defined('IN_ICYPHOENIX'))
		{
			// if you have an old phpBB based site with old posts, you may want to enable this BBCode UID strip REG EX Replace
			//$text = preg_replace("/\:([a-f0-9]{10})/s", '', $text);
			$search = array(
				$id ? ':' . $id : '',
				'code:1]',
				'list:o]',
				);
			$replace = array(
				'',
				'code]',
				'list]',
				);
			$text = str_replace($search, $replace, $text);
			// We need this after having removed bbcode_uid... but don't know why
			$text = undo_htmlspecialchars($text);
			/*
			if($id)
			{
				$text = undo_htmlspecialchars($text);
			}
			*/
		}
		// reset variables
		$this->text = $text;
		$this->data = array();
		$this->html = '';
		$this->prepare_smilies();
		if ($light == false)
		{
			$this->process_urls();
		}
		$this->code_counter = 0;
		// if bbcode and html are disabled then return unprocessed text
		if(!$this->allow_bbcode && !$this->allow_html)
		{
			$this->html = $this->text;
			$this->process_smilies();
			return $this->html;
		}
		// convert to tree structure
		$result = $this->push(0, 0, array());
		$this->data = $result['items'];

		/*
		ob_start();
		$this->debug($this->data);
		$str = ob_get_contents();
		ob_end_clean();
		$this->html = 'Debug:<br />' . $str;
		return $this->html;
		*/

		// add prev/next pointers and count iterations
		$this->add_pointers($this->data, array());
		if ($clean_tags !== false)
		{
			$clean_tags = true;
		}
		// convert to html
		$this->html = $this->process(0, strlen($this->text), $this->data, $clean_tags);
		$this->process_smilies();

		return $this->html;
	}

	function load_rainbow_colors()
	{
		return array(
			1 => 'red',
			2 => 'orange',
			3 => 'yellow',
			4 => 'green',
			5 => 'blue',
			6 => 'indigo',
			7 => 'violet'
		);
	}

	function rainbow($text)
	{
		// Returns text highlighted in rainbow colours
		$colors = $this->load_rainbow_colors();
		$text = trim(stripslashes($text));
		$length = strlen($text);
		$result = '';
		$color_counter = 0;
		$TAG_OPEN = false;
		for ($i = 0; $i < $length; $i++)
		{
			$char = substr($text, $i, 1);
			if (!$TAG_OPEN)
			{
				if ($char == '<')
				{
					$TAG_OPEN = true;
					$result .= $char;
				}
				elseif (preg_match("#\S#i", $char))
				{
					$color_counter++;
					$result .= '<span style="color: ' . $colors[$color_counter] . ';">' . $char . '</span>';
					$color_counter = ($color_counter == 7) ? 0 : $color_counter;
				}
				else
				{
					$result .= $char;
				}
			}
			else
			{
				if ($char == '>')
				{
					$TAG_OPEN = false;
				}
				$result .= $char;
			}
		}
		return $result;
	}

	function rand_color()
	{
		$color_code = mt_rand(0, 255);
		if ($color_code < 16)
		{
			return ('0' . dechex($color_code));
		}
		else
		{
			return dechex($color_code);
		}
	}

	function load_random_colors($iterations = 10)
	{
		$random_color = array();
		for ($i = 0; $i < $iterations; $i++)
		{
			$random_color[$i + 1] = '#' . $this->rand_color() . $this->rand_color() . $this->rand_color();
		}
		return $random_color;
	}

	function load_gradient_colors($color1, $color2, $iterations = 10)
	{
		$col1_array = array();
		$col2_array = array();
		$col_dif_array = array();
		$gradient_color = array();
		$col1_array[0] = hexdec(substr($color1, 1, 2));
		$col1_array[1] = hexdec(substr($color1, 3, 2));
		$col1_array[2] = hexdec(substr($color1, 5, 2));
		$col2_array[0] = hexdec(substr($color2, 1, 2));
		$col2_array[1] = hexdec(substr($color2, 3, 2));
		$col2_array[2] = hexdec(substr($color2, 5, 2));
		$col_dif_array[0] = ($col2_array[0] - $col1_array[0]) / ($iterations - 1);
		$col_dif_array[1] = ($col2_array[1] - $col1_array[1]) / ($iterations - 1);
		$col_dif_array[2] = ($col2_array[2] - $col1_array[2]) / ($iterations - 1);
		for ($i = 0; $i < $iterations; $i++)
		{
			$part1 = round($col1_array[0] + ($col_dif_array[0] * $i));
			$part2 = round($col1_array[1] + ($col_dif_array[1] * $i));
			$part3 = round($col1_array[2] + ($col_dif_array[2] * $i));
			$part1 = ($part1 < 16) ? ('0' . dechex($part1)) : (dechex($part1));
			$part2 = ($part2 < 16) ? ('0' . dechex($part2)) : (dechex($part2));
			$part3 = ($part3 < 16) ? ('0' . dechex($part3)) : (dechex($part3));
			$gradient_color[$i + 1] = '#' . $part1 . $part2 . $part3;
		}

		return $gradient_color;
	}

	function gradient($text, $color1, $color2, $mode = 'random', $iterations = 10)
	{
		// Returns text highlighted in random gradient colours
		if ($mode == 'random')
		{
			$colors = $this->load_random_colors();
		}
		else
		{
			$colors = $this->load_gradient_colors($color1, $color2, $iterations);
		}
		$text = trim(stripslashes($text));
		$length = strlen($text);
		$result = '';
		$color_counter = 0;
		$TAG_OPEN = false;
		for ($i = 0; $i < $length; $i++)
		{
			$char = substr($text, $i, 1);
			if (!$TAG_OPEN)
			{
				if ($char == '<')
				{
					$TAG_OPEN = true;
					$result .= $char;
				}
				elseif (preg_match("#\S#i", $char))
				{
					$color_counter++;
					$result .= '<span style="color: ' . $colors[$color_counter] . ';">' . $char . '</span>';
					$color_counter = ($color_counter == $iterations) ? 0 : $color_counter;
				}
				else
				{
					$result .= $char;
				}
			}
			else
			{
				if ($char == '>')
				{
					$TAG_OPEN = false;
				}
				$result .= $char;
			}
		}
		return $result;
	}

	/*
	* This function will strip common BBCodes tags, but some of them will be left there (such as CODE or QUOTE)
	*/
	function bbcode_killer($text, $id = false)
	{
		// Pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
		// This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
		$text = " " . $text;

		// First: If there isn't a "[" and a "]" in the message, don't bother.
		if (!(strpos($text, "[") && strpos($text, "]")))
		{
			// Remove padding, return.
			$text = substr($text, 1);
			return $text;
		}

		// Stripping out old bbcode_uid
		if (!empty($id))
		{
			$text = preg_replace("/\:(([a-z0-9]:)?)" . $id . "/s", "", $text);
		}

		// Strip simple tags
		$look_up_array = array(
			//"[code]", "[/code]",
			//"[php]","[/php]",
			//"[cpp]","[/cpp]",
			"[b]", "[/b]",
			"[u]", "[/u]",
			"[tt]", "[/tt]",
			"[i]", "[/i]",
			"[list]", "[/list]",
			"[list=1]",
			"[list=a]",
			"[*]",
			"[url]", "[/url]",
			"[email]", "[/email]",
			"[img]", "[img align=left]", "[img align=right]", "[/img]",
			"[imgl]", "[/imgl]",
			"[imgr]", "[/imgr]",
			"[albumimg]", "[/albumimg]",
			"[albumimgl]", "[/albumimgl]",
			"[albumimgr]", "[/albumimgr]",
			"[blur]", "[/blur]",
			"[fade]", "[/fade]",
			"[rainbow]", "[/rainbow]",
			"[gradient]", "[/gradient]",
			"[jiggle]", "[/jiggle]",
			"[pulse]", "[/pulse]",
			"[neon]", "[/neon]",
			"[updown]", "[/updown]",
			"[flipv]", "[/flipv]",
			"[fliph]", "[/fliph]",
			"[wave]", "[/wave]",
			"[offtopic]", "[/offtopic]",
			"[strike]", "[/strike]",
			"[sup]", "[/sup]",
			"[sub]", "[/sub]",
			"[spoil]", "[/spoil]",
			"[spoiler]", "[/spoiler]",
			"[table]", "[/table]",
			"[tr]", "[/tr]",
			"[td]", "[/td]",
			"[em]", "[/em]",
			"[strong]", "[/strong]",
			"[center]", "[/center]",
			"[hide]", "[/hide]",
			//"[]", "[/]",
			"[hr]",
		);

		$text = str_replace($look_up_array, "", $text);

		// Colours
		$color_code = "(\#[0-9A-F]{6}|[a-z]+)";
		$look_up_array = array(
			"/\[color=" . $color_code . "\]/si", "/\[\/color\]/si",
			"/\[glow=" . $color_code . "\]/si", "/\[\/glow\]/si",
			"/\[shadow=" . $color_code . "\]/si", "/\[\/shadow\]/si",
			"/\[highlight=" . $color_code . "\]/si", "/\[\/highlight\]/si",
			"/\[size=([\-\+]?[1-3]?[0-9])\]/si", "/\[\/size\]/si",
			"/\[url=([a-z0-9\-\.,\?!%\*_\/:;~\\&$@\/=\+]+)\]/si", "/\[\/url\]/si",
			"/\[web=([a-z0-9\-\.,\?!%\*_\/:;~\\&$@\/=\+]+)\]/si", "/\[\/web\]/si",
			"/\[font=(Arial|Arial Black|Arial Bold|Arial Bold Italic|Arial Italic|Comic Sans MS|Comic Sans MS Bold|Courier New|Courier New Bold|Courier New Bold Italic|Courier New Italic|Impact|Lucida Console|Lucida Sans Unicode|Microsoft Sans Serif|Symbol|Tahoma|Tahoma Bold|Times New Roman|Times New Roman Bold|Times New Roman Bold Italic|Times New Roman Italic|Traditional Arabic|Trebuchet MS|Trebuchet MS Bold|Trebuchet MS Bold Italic|Trebuchet MS Italic|Verdana|Verdana Bold|Verdana Bold Italic|Verdana Italic|Webdings|Wingdings|)\]/si", "/\[\/font\]/si",
			"/\[marq=(left|right|up|down)\]/si", "/\[\/marq\]/si",
			"/\[marquee direction=(left|right|up|down)\]/si", "/\[\/marquee\]/si",
			"/\[align=(left|center|right|justify)\]/si", "/\[\/align\]/si",
		);

		$text = preg_replace($look_up_array, "", $text);

		// [QUOTE] and [/QUOTE]
	 /*
		$text = str_replace("[quote]","", $text);
		$text = str_replace("[/quote]", "", $text);
		$text = preg_replace("/\[quote=(?:\"?([^\"]*)\"?)\]/si", "", $text);
	 */

		// Remove our padding from the string..
		$text = substr($text, 1);

		return $text;
	}

	function plain_message($text, $id)
	{
		// This function will strip from a message some BBCodes,
		// all BBCodes $uid, and some other formattings.
		// The result will be suitable for email sendings.
		$text = $this->bbcode_killer($text, $id);
		//$text = preg_replace("/\r\n/", "<br />", $text);
		$text = preg_replace("/\r\n/", "\n", $text);
		$text = str_replace('<br />', "\n", $text);

		return $text;
	}

	/*
	* This function will strip all specified BBCodes tags or all BBCodes tags
	*/
	function bbcode_clean($text, &$tags)
	{
		if (is_array($tags) && (count($tags) > 0))
		{
			for ($i = 0; $i < count($tags); $i++)
			{
				$tags[$i] = ($tags[$i] == '*') ? '\*' : $tags[$i];
				$text = ereg_replace("\[" . $tags[$i] . "[^]^[]*\]", '', $text);
				$text = ereg_replace("\[(/?)[^]^[]" . $tags[$i] . "\]", '', $text);
			}
		}
		else
		{
			$text = ereg_replace("\[(/?)[^]^[]*\]", '', $text);
		}

		$text = nl2br($text);
		return $text;
	}

	function acronym_sort($a, $b)
	{
		if (strlen($a['acronym']) == strlen($b['acronym']))
		{
			return 0;
		}

		return (strlen($a['acronym']) > strlen($b['acronym'])) ? -1 : 1;
	}

	function acronym_pass($text)
	{
		if (!defined('IS_ICYPHOENIX'))
		{
			return $text;
		}

		static $orig, $repl;

		if(!isset($orig))
		{
			global $db, $board_config;
			$orig = $repl = array();

			$sql = 'SELECT * FROM ' . ACRONYMS_TABLE;
			if(!$result = $db->sql_query($sql, false, 'acronyms_'))
			{
				message_die(GENERAL_ERROR, "Couldn't obtain acronyms data", "", __LINE__, __FILE__, $sql);
			}

			while ($row = $db->sql_fetchrow($result))
			{
				$acronyms[] = $row;
			}

			if(count($acronyms))
			{
				//usort($acronyms, 'acronym_sort');
				// This use acronym_sort calling it from within BBCode object
				usort($acronyms, array('BBCode', 'acronym_sort'));
			}

			for ($i = 0; $i < count($acronyms); $i++)
			{
				/* OLD CODE FOR ACRONYMS
				$orig[] = '#\b(' . phpbb_preg_quote($acronyms[$i]['acronym'], "/") . ')\b#';
				$orig[] = "/(?<=.\W|\W.|^\W)" . phpbb_preg_quote($acronyms[$i]['acronym'], "/") . "(?=.\W|\W.|\W$)/";
				*/
				$orig[] = '#\b(' . str_replace('\*', '\w*?', preg_quote(stripslashes($acronyms[$i]['acronym']), '#')) . ')\b#i';
				$repl[] = '<acronym title="' . $acronyms[$i]['description'] . '">' . $acronyms[$i]['acronym'] . '</acronym>'; ;
			}
		}

		if(count($orig))
		{

			$segments = preg_split('#(<acronym.+?>.+?</acronym>|<.+?>)#s' , $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			//<?php
			//Insert for formating purpose
			$text = '';

			foreach($segments as $seg)
			{
				if($seg[0] != '<' && $seg[0] != '[')
				{
					$text .= str_replace('\"', '"', substr(preg_replace('#(\>(((?>([^><]+|(?R)))*)\<))#se', "preg_replace(\$orig, \$repl, '\\0')", '>' . $seg . '<'), 1, -1));
				}
				else
				{
					$text .= $seg;
				}
			}
		}

		return $text;
	}

}

$bbcode = new BBCode();

if (defined('SMILIES_TABLE'))
{
	//$sql = "SELECT emoticon, code, smile_url FROM " . SMILIES_TABLE . " ORDER BY smilies_order";
	$sql = "SELECT code, smile_url FROM " . SMILIES_TABLE . " ORDER BY smilies_order";
	if ($result = $db->sql_query($sql, false, 'smileys_'))
	{
		$bbcode->allowed_smilies = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$arr = array(
				'code' => $row['code'],
				//'replace' => '<img src="http://' . $_SERVER['HTTP_HOST'] . $board_config['script_path'] . $board_config['smilies_path'] . '/' . $row['smile_url'] . '" alt="' . htmlspecialchars($row['emoticon']) . '" />'
				'replace' => '<img src="http://' . $_SERVER['HTTP_HOST'] . $board_config['script_path'] . $board_config['smilies_path'] . '/' . $row['smile_url'] . '" alt="" />'
			);
			$bbcode->allowed_smilies[] = $arr;
		}
		$db->sql_freeresult($result);
		unset($arr);
	}
	else
	{
		message_die(GENERAL_ERROR, 'Couldn\'t retrieve smilies list', '', __LINE__, __FILE__, $sql);
	}
}

// Need to initialize the random numbers only ONCE
mt_srand((double) microtime() * 1000000);

function make_bbcode_uid()
{
	// Unique ID for this message..
	$uid = dss_rand();
	$uid = substr($uid, 0, BBCODE_UID_LEN);
	return $uid;
}

function undo_htmlspecialchars($input, $full_undo = false)
{
	if($full_undo)
	{
		$input = str_replace('&nbsp;', '', $input);
	}
	$input = preg_replace("/&gt;/i", ">", $input);
	$input = preg_replace("/&lt;/i", "<", $input);
	$input = preg_replace("/&quot;/i", "\"", $input);
	$input = preg_replace("/&amp;/i", "&", $input);

	if($full_undo)
	{
		if(preg_match_all('/&\#([0-9]+);/', $input, $matches) && count($matches))
		{
			$list = array();
			for($i = 0; $i < count($matches[1]); $i++)
			{
				$list[$matches[1][$i]] = true;
			}
			$search = array();
			$replace = array();
			foreach($list as $var => $value)
			{
				$search[] = '&#' . $var . ';';
				$replace[] = chr($var);
			}
			$input = str_replace($search, $replace, $input);
		}
	}

	return $input;
}

function make_clickable($text)
{
	$text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:", $text);
	$text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1&#058;", $text);

	// pad it with a space so we can match things at the start of the 1st line.
	$ret = ' ' . $text;

	// matches an "xxxx://yyyy" URL at the start of a line, or after a space.
	// xxxx can only be alpha characters.
	// yyyy is anything up to the first space, newline, comma, double quote or <
	$ret = preg_replace("#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret);

	// matches a "www|ftp.xxxx.yyyy[/zzzz]" kinda lazy URL thing
	// Must contain at least 2 dots. xxxx contains either alphanum, or "-"
	// zzzz is optional.. will contain everything up to the first space, newline,
	// comma, double quote or <.
	$ret = preg_replace("#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret);


	// matches an email@domain type address at the start of a line, or after a space.
	// Note: Only the followed chars are valid; alphanums, "-", "_" and or ".".
	$ret = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);

	// Remove our padding..
	$ret = substr($ret, 1);

	return($ret);
}

// Autolinks - BEGIN
//
// Obtain list of autolink words and build preg style replacement arrays for use by the
// calling script, note that the vars are passed as references this just makes it easier
// to return both sets of arrays
//
// This is a copy of phpBB's obtain_word_list() function with slight changes.
//
function obtain_autolink_list(&$orig_autolink, &$replacement_autolink, $id)
{
	global $db;

	//$where = ($id) ? ' WHERE link_forum = 0 OR link_forum = ' . $id : ' WHERE link_forum = -1';
	$where = ($id) ? ' WHERE link_forum = 0 OR link_forum IN (' . $id . ')' : ' WHERE link_forum = -1';

	$sql = "SELECT * FROM  " . AUTOLINKS . $where;
	if(!($result = $db->sql_query($sql, false, 'autolinks_')))
	{
		message_die(GENERAL_ERROR, 'Could not get autolink data from database', '', __LINE__, __FILE__, $sql);
	}

	if($row = $db->sql_fetchrow($result))
	{
		do
		{
			// Munge word boundaries to stop autolinks from linking to
			// themselves or other autolinks in step 2 in the function below.
			$row['link_url'] = preg_replace('/(\b)/', '\\1ALSPACEHOLDER', $row['link_url']);
			$row['link_comment'] = preg_replace('/(\b)/', '\\1ALSPACEHOLDER', $row['link_comment']);

			if($row['link_style'])
			{
				$row['link_style'] = preg_replace('/(\b)/', '\\1ALSPACEHOLDER', $row['link_style']);
				$style = ' style="' . htmlspecialchars($row['link_style']) . '" ';
			}
			else
			{
				$style = ' ';
			}
			$orig_autolink[] = '/(?<![\/\w@\.:-])(?!\.\w)(' . phpbb_preg_quote($row['link_keyword'], '/'). ')(?![\/\w@:-])(?!\.\w)/i';
			if($row['link_int'])
			{
				$replacement_autolink[] = '<a href="' . append_sid(htmlspecialchars($row['link_url'])) . '" target="_self"' . $style . 'title="' . htmlspecialchars($row['link_comment']) . '">' . htmlspecialchars($row['link_title']) . '</a>';
			}
			else
			{
				$replacement_autolink[] = '<a href="' . htmlspecialchars($row['link_url']) . '" target="_blank"' . $style . 'title="' . htmlspecialchars($row['link_comment']) . '">' . htmlspecialchars($row['link_title']) . '</a>';
			}
		}
		while($row = $db->sql_fetchrow($result));
	}

	return true;
}

//
// Taken from the POST-NUKE pnuserapi.php Autolinks user API file with slight changes.
// Original Author - Jim McDonald.
//
function autolink_transform($message, $orig, $replacement)
{
	global $board_config;

	// Step 1 - move all tags out of the text and replace them with placeholders
	preg_match_all('/(<a\s+.*?\/a>|<[^>]+>)/i', $message, $matches);
	$matchnum = count($matches[1]);
	for($i = 0; $i < $matchnum; $i++)
	{
		$message = preg_replace('/' . preg_quote($matches[1][$i], '/') . '/', "ALPLACEHOLDER{$i}PH", $message, 1);
	}

	// Step 2 - s/r of the remaining text
	if($board_config['autolink_first'])
	{
		$message = preg_replace($orig, $replacement, $message, 1);
	}
	else
	{
		$message = preg_replace($orig, $replacement, $message);
	}

	// Step 3 - replace the spaces we munged in step 1
	$message = preg_replace('/ALSPACEHOLDER/', '', $message);

	// Step 4 - replace the HTML tags that we removed in step 1
	for($i = 0; $i <$matchnum; $i++)
	{
		$message = preg_replace("/ALPLACEHOLDER{$i}PH/", $matches[1][$i], $message, 1);
	}

	return $message;
}
// Autolinks - END

/*
* Get attachment details
*/
function get_attachment_details($attach_id)
{
	global $db;
	$sql = "SELECT a.*, d.*, s.*, p.forum_id
		FROM " . ATTACHMENTS_TABLE . " a, " . ATTACHMENTS_DESC_TABLE . " d, " . ATTACHMENTS_STATS_TABLE . " s, " . POSTS_TABLE . " p
		WHERE a.attach_id = " . $attach_id . "
			AND d.attach_id = a.attach_id
			AND s.attach_id = a.attach_id
			AND a.post_id > 0
			AND p.post_id = a.post_id
		LIMIT 1";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Cound not query attachment stats table', '', __LINE__, __FILE__, $sql);
	}

	if ($row = $db->sql_fetchrow($result))
	{
		$db->sql_freeresult($result);
		return $row;
	}
	else
	{
		return false;
	}
}

/*
* Get download details
*/
function get_download_details($file_id)
{
	global $db, $userdata;
	$sql = "SELECT f.*, c.*
		FROM " . PA_FILES_TABLE . " f, " . PA_CATEGORY_TABLE . " c
		WHERE file_id = " . $file_id . "
			AND file_approved = '1'
			AND c.cat_id = f.file_catid
		LIMIT 1";
	if (!($result = $db->sql_query($sql)))
	{
		message_die(GENERAL_ERROR, 'Cound not query attachment stats table', '', __LINE__, __FILE__, $sql);
	}

	if ($row = $db->sql_fetchrow($result))
	{
		$db->sql_freeresult($result);
		$allowed = false;
		if (($row['auth_view_file'] == AUTH_ALL) || ($userdata['user_level'] == ADMIN))
		{
			$allowed = true;
		}
		elseif (($row['auth_view_file'] == AUTH_REG) && $userdata['session_logged_in'])
		{
			$allowed = true;
		}
		return ($allowed ? $row : false);
	}
	else
	{
		return false;
	}
}

/*
* This function turns HTML into text... strips tags, comments spanning multiple lines including CDATA, and anything else that gets in it's way.
*/
function html2txt($document)
{
	$search = array(
						'@<script[^>]*?>.*?</script>@si',	// Strip out javascript
						'@<[\/\!]*?[^<>]*?>@si',					// Strip out HTML tags
						'@<style[^>]*?>.*?</style>@siU',	// Strip style tags properly
						'@<![\s\S]*?--[ \t\n\r]*>@'				// Strip multi-line comments including CDATA
					);
	$text = preg_replace($search, '', $document);
	return $text;
}

function nl2any($string, $tag = 'p', $feed = '')
{
	// making tags
	$start_tag = "<$tag" . ($feed ? ' '.$feed : '') . '>';
	$end_tag = "</$tag>";

	// exploding string to lines
	$lines = preg_split('`[\n\r]+`', trim($string));

	// making new string
	$string = '';
	foreach($lines as $line)
	$string .= "$start_tag$line$end_tag\n";

	return $string;
}

function any2nl($string, $tag = 'p')
{
	//exploding
	preg_match_all("`<" . $tag . "[^>]*>(.*)</" . $tag . ">`Ui", $string, $results);
	// reimploding without tags
	return implode("\n", array_filter($results[1]));
}

function br2nl($str)
{
	$str = preg_replace("/(\r\n|\n|\r)/", "", $str);
	return preg_replace("=<br */?>=i", "\n", $str);
}

function nl2br_mg($text)
{
	/*
	$text = preg_replace("/\r\n/", "\n", $text);
	$text = str_replace('<br />', "\n", $text);
	*/
	$text = preg_replace(array("/<br \/>\r\n/", "/<br>\r\n/", "/(\r\n|\n|\r)/"), array("\r\n", "\r\n", "<br />\r\n"), $text);
	return $text;
}

?>