<?php

@include_once dirname(__FILE__).'/../theme/wrapper.inc.php';

define('IN_PHPBB', true);
define('PHPBB_ROOT_PATH', '/home/mia/psclient/forum/forums/');
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../forums/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// session
$user->session_begin();
$auth->acl($user->data);
$user->setup();

class NTBB {
	var $output = 'normal';
	var $depth = 0;
	var $ajaxMode = false;

	var $name = 'Forum';
	var $root = '/';

	function NTBB() {
		global $psconfig;

		if (@$psconfig['root']) $this->root = $psconfig['root'];
		if (@$psconfig['name']) $this->name = $psconfig['name'];

		switch (@$_REQUEST['output']) {
			case 'html':
			case 'json':
				$this->output = $_REQUEST['output'];
				break;
		}
	}

	function getForum($forum_id = false, $sortByCreationDate = false) {
		global $db, $auth, $user;
		global $phpbb_root_path, $phpEx, $config;

		$retval = $this->getForumData($forum_id);
		if (!$retval) return $retval;

		if ($retval['type'] === 'forum') {

			$retval['can_post'] = $auth->acl_get('f_post', $forum_id);
			$retval['forums'] = array();

			$sort_days	= (!empty($user->data['user_topic_show_days'])) ? $user->data['user_topic_show_days'] : 0;
			$sort_key	= (!empty($user->data['user_topic_sortby_type'])) ? $user->data['user_topic_sortby_type'] : 't';
			$sort_dir	= (!empty($user->data['user_topic_sortby_dir'])) ? $user->data['user_topic_sortby_dir'] : 'd';

			if ($sortByCreationDate) $sort_key = 'c';

			// Topic ordering options
			$limit_days = array(0 => $user->lang['ALL_TOPICS'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);

			$sort_by_text = array('a' => $user->lang['AUTHOR'], 't' => $user->lang['POST_TIME'], 'r' => $user->lang['REPLIES'], 's' => $user->lang['SUBJECT'], 'v' => $user->lang['VIEWS'], 'c' => "creation date");
			$sort_by_sql = array('a' => 't.topic_first_poster_name', 't' => 't.topic_last_post_time', 'r' => 't.topic_replies', 's' => 't.topic_title', 'v' => 't.topic_views', 'c' => 't.topic_time');

			// Grab all topic data
			$rowset = $announcement_list = $topic_list = $global_announce_list = array();

			$sql_array = array(
				'SELECT'	=> 't.*',
				'FROM'		=> array(
					TOPICS_TABLE		=> 't'
				),
				'LEFT_JOIN'	=> array(),
			);

			$sql_approved = ($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND t.topic_approved = 1';

			if ($user->data['is_registered'])
			{
				if ($config['load_db_track'])
				{
					$sql_array['LEFT_JOIN'][] = array('FROM' => array(TOPICS_POSTED_TABLE => 'tp'), 'ON' => 'tp.topic_id = t.topic_id AND tp.user_id = ' . $user->data['user_id']);
					$sql_array['SELECT'] .= ', tp.topic_posted';
				}

				if ($config['load_db_lastread'])
				{
					$sql_array['LEFT_JOIN'][] = array('FROM' => array(TOPICS_TRACK_TABLE => 'tt'), 'ON' => 'tt.topic_id = t.topic_id AND tt.user_id = ' . $user->data['user_id']);
					$sql_array['SELECT'] .= ', tt.mark_time';

					if ($s_display_active && sizeof($active_forum_ary))
					{
						$sql_array['LEFT_JOIN'][] = array('FROM' => array(FORUMS_TRACK_TABLE => 'ft'), 'ON' => 'ft.forum_id = t.forum_id AND ft.user_id = ' . $user->data['user_id']);
						$sql_array['SELECT'] .= ', ft.mark_time AS forum_mark_time';
					}
				}
			}

			if ($forum_data['forum_type'] == FORUM_POST)
			{
				// Obtain announcements ... removed sort ordering, sort by time in all cases
				$sql = $db->sql_build_query('SELECT', array(
					'SELECT'	=> $sql_array['SELECT'],
					'FROM'		=> $sql_array['FROM'],
					'LEFT_JOIN'	=> $sql_array['LEFT_JOIN'],

					'WHERE'		=> 't.forum_id IN (' . $forum_id . ', 0)
						AND t.topic_type IN (' . POST_ANNOUNCE . ', ' . POST_GLOBAL . ')',

					'ORDER_BY'	=> 't.topic_time DESC',
				));
				$result = $db->sql_query($sql);

				while ($row = $db->sql_fetchrow($result))
				{
					if (!$row['topic_approved'] && !$auth->acl_get('m_approve', $row['forum_id']))
					{
						// Do not display announcements that are waiting for approval.
						continue;
					}

					$rowset[$row['topic_id']] = $row;
					$announcement_list[] = $row['topic_id'];

					if ($row['topic_type'] == POST_GLOBAL)
					{
						$global_announce_list[$row['topic_id']] = true;
					}
					else
					{
						$topics_count--;
					}
				}
				$db->sql_freeresult($result);
			}

			// If the user is trying to reach late pages, start searching from the end
			$store_reverse = false;
			$sql_limit = $config['topics_per_page'];
			if ($start > $topics_count / 2)
			{
				$store_reverse = true;

				if ($start + $config['topics_per_page'] > $topics_count)
				{
					$sql_limit = min($config['topics_per_page'], max(1, $topics_count - $start));
				}

				// Select the sort order
				$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'ASC' : 'DESC');
				$sql_start = max(0, $topics_count - $sql_limit - $start);
			}
			else
			{
				// Select the sort order
				$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');
				$sql_start = $start;
			}

			if ($forum_data['forum_type'] == FORUM_POST || !sizeof($active_forum_ary))
			{
				$sql_where = 't.forum_id = ' . $forum_id;
			}
			else if (empty($active_forum_ary['exclude_forum_id']))
			{
				$sql_where = $db->sql_in_set('t.forum_id', $active_forum_ary['forum_id']);
			}
			else
			{
				$get_forum_ids = array_diff($active_forum_ary['forum_id'], $active_forum_ary['exclude_forum_id']);
				$sql_where = (sizeof($get_forum_ids)) ? $db->sql_in_set('t.forum_id', $get_forum_ids) : 't.forum_id = ' . $forum_id;
			}

			// Grab just the sorted topic ids
			$sql = 'SELECT t.topic_id
				FROM ' . TOPICS_TABLE . " t
				WHERE $sql_where
					AND t.topic_type IN (" . POST_NORMAL . ', ' . POST_STICKY . ")
					$sql_approved
					$sql_limit_time
				ORDER BY t.topic_type " . ((!$store_reverse) ? 'DESC' : 'ASC') . ', ' . $sql_sort_order;
			$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);

			while ($row = $db->sql_fetchrow($result))
			{
				$topic_list[] = (int) $row['topic_id'];
			}
			$db->sql_freeresult($result);

			// For storing shadow topics
			$shadow_topic_list = array();

			if (sizeof($topic_list))
			{
				// SQL array for obtaining topics/stickies
				$sql_array = array(
					'SELECT'		=> $sql_array['SELECT'],
					'FROM'			=> $sql_array['FROM'],
					'LEFT_JOIN'		=> $sql_array['LEFT_JOIN'],

					'WHERE'			=> $db->sql_in_set('t.topic_id', $topic_list),
				);

				// If store_reverse, then first obtain topics, then stickies, else the other way around...
				// Funnily enough you typically save one query if going from the last page to the middle (store_reverse) because
				// the number of stickies are not known
				$sql = $db->sql_build_query('SELECT', $sql_array);
				$result = $db->sql_query($sql);

				while ($row = $db->sql_fetchrow($result))
				{
					if ($row['topic_status'] == ITEM_MOVED)
					{
						$shadow_topic_list[$row['topic_moved_id']] = $row['topic_id'];
					}

					$rowset[$row['topic_id']] = $row;
				}
				$db->sql_freeresult($result);
			}

			// If we have some shadow topics, update the rowset to reflect their topic information
			if (sizeof($shadow_topic_list))
			{
				$sql = 'SELECT *
					FROM ' . TOPICS_TABLE . '
					WHERE ' . $db->sql_in_set('topic_id', array_keys($shadow_topic_list));
				$result = $db->sql_query($sql);

				while ($row = $db->sql_fetchrow($result))
				{
					$orig_topic_id = $shadow_topic_list[$row['topic_id']];

					// If the shadow topic is already listed within the rowset (happens for active topics for example), then do not include it...
					if (isset($rowset[$row['topic_id']]))
					{
						// We need to remove any trace regarding this topic. :)
						unset($rowset[$orig_topic_id]);
						unset($topic_list[array_search($orig_topic_id, $topic_list)]);
						$topics_count--;

						continue;
					}

					// Do not include those topics the user has no permission to access
					if (!$auth->acl_get('f_read', $row['forum_id']))
					{
						// We need to remove any trace regarding this topic. :)
						unset($rowset[$orig_topic_id]);
						unset($topic_list[array_search($orig_topic_id, $topic_list)]);
						$topics_count--;

						continue;
					}

					// We want to retain some values
					$row = array_merge($row, array(
						'topic_moved_id'	=> $rowset[$orig_topic_id]['topic_moved_id'],
						'topic_status'		=> $rowset[$orig_topic_id]['topic_status'],
						'topic_type'		=> $rowset[$orig_topic_id]['topic_type'],
						'topic_title'		=> $rowset[$orig_topic_id]['topic_title'],
					));

					// Shadow topics are never reported
					$row['topic_reported'] = 0;

					$rowset[$orig_topic_id] = $row;
				}
				$db->sql_freeresult($result);
			}
			unset($shadow_topic_list);

			// Ok, adjust topics count for active topics list
			if ($s_display_active)
			{
				$topics_count = 1;
			}

			$topic_list = ($store_reverse) ? array_merge($announcement_list, array_reverse($topic_list)) : array_merge($announcement_list, $topic_list);
			$topic_tracking_info = $tracking_topics = array();

			// Okay, lets dump out the page ...
			if (sizeof($topic_list))
			{
				$mark_forum_read = true;
				$mark_time_forum = 0;

				// Active topics?
				if ($s_display_active && sizeof($active_forum_ary))
				{
					// Generate topic forum list...
					$topic_forum_list = array();
					foreach ($rowset as $t_id => $row)
					{
						$topic_forum_list[$row['forum_id']]['forum_mark_time'] = ($config['load_db_lastread'] && $user->data['is_registered'] && isset($row['forum_mark_time'])) ? $row['forum_mark_time'] : 0;
						$topic_forum_list[$row['forum_id']]['topics'][] = $t_id;
					}

					if ($config['load_db_lastread'] && $user->data['is_registered'])
					{
						foreach ($topic_forum_list as $f_id => $topic_row)
						{
							$topic_tracking_info += get_topic_tracking($f_id, $topic_row['topics'], $rowset, array($f_id => $topic_row['forum_mark_time']), false);
						}
					}
					else if ($config['load_anon_lastread'] || $user->data['is_registered'])
					{
						foreach ($topic_forum_list as $f_id => $topic_row)
						{
							$topic_tracking_info += get_complete_topic_tracking($f_id, $topic_row['topics'], false);
						}
					}

					unset($topic_forum_list);
				}
				else
				{
					if ($config['load_db_lastread'] && $user->data['is_registered'])
					{
						$topic_tracking_info = get_topic_tracking($forum_id, $topic_list, $rowset, array($forum_id => $forum_data['mark_time']), $global_announce_list);
						$mark_time_forum = (!empty($forum_data['mark_time'])) ? $forum_data['mark_time'] : $user->data['user_lastmark'];
					}
					else if ($config['load_anon_lastread'] || $user->data['is_registered'])
					{
						$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_list, $global_announce_list);

						if (!$user->data['is_registered'])
						{
							$user->data['user_lastmark'] = (isset($tracking_topics['l'])) ? (int) (base_convert($tracking_topics['l'], 36, 10) + $config['board_startdate']) : 0;
						}
						$mark_time_forum = (isset($tracking_topics['f'][$forum_id])) ? (int) (base_convert($tracking_topics['f'][$forum_id], 36, 10) + $config['board_startdate']) : $user->data['user_lastmark'];
					}
				}

				$s_type_switch = 0;
				foreach ($topic_list as $topic_id)
				{
					$row = &$rowset[$topic_id];

					$topic_forum_id = ($row['forum_id']) ? (int) $row['forum_id'] : $forum_id;

					// This will allow the style designer to output a different header
					// or even separate the list of announcements from sticky and normal topics
					$s_type_switch_test = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;

					// Replies
					$replies = ($auth->acl_get('m_approve', $topic_forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];

					if ($row['topic_status'] == ITEM_MOVED)
					{
						$topic_id = $row['topic_moved_id'];
						$unread_topic = false;
					}
					else
					{
						$unread_topic = (isset($topic_tracking_info[$topic_id]) && $row['topic_last_post_time'] > $topic_tracking_info[$topic_id]) ? true : false;
					}

					// Get folder img, topic status/type related information
					$folder_img = $folder_alt = $topic_type = '';
					topic_status($row, $replies, $unread_topic, $folder_img, $folder_alt, $topic_type);

					// Generate all the URIs ...
					$view_topic_url_params = 'f=' . $topic_forum_id . '&amp;t=' . $topic_id;
					$view_topic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", $view_topic_url_params);

					$topic_unapproved = (!$row['topic_approved'] && $auth->acl_get('m_approve', $topic_forum_id)) ? true : false;
					$posts_unapproved = ($row['topic_approved'] && $row['topic_replies'] < $row['topic_replies_real'] && $auth->acl_get('m_approve', $topic_forum_id)) ? true : false;
					$u_mcp_queue = ($topic_unapproved || $posts_unapproved) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=' . (($topic_unapproved) ? 'approve_details' : 'unapproved_posts') . "&amp;t=$topic_id", true, $user->session_id) : '';

					// Send vars to template
					// $template->assign_block_vars('topicrow', array(
					// 	'FORUM_ID'					=> $topic_forum_id,
					// 	'TOPIC_ID'					=> $topic_id,
					// 	'TOPIC_AUTHOR'				=> get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
					// 	'TOPIC_AUTHOR_COLOUR'		=> get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
					// 	'TOPIC_AUTHOR_FULL'			=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
					// 	'FIRST_POST_TIME'			=> $user->format_date($row['topic_time']),
					// 	'LAST_POST_SUBJECT'			=> censor_text($row['topic_last_post_subject']),
					// 	'LAST_POST_TIME'			=> $user->format_date($row['topic_last_post_time']),
					// 	'LAST_VIEW_TIME'			=> $user->format_date($row['topic_last_view_time']),
					// 	'LAST_POST_AUTHOR'			=> get_username_string('username', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
					// 	'LAST_POST_AUTHOR_COLOUR'	=> get_username_string('colour', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
					// 	'LAST_POST_AUTHOR_FULL'		=> get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),

					// 	'PAGINATION'		=> topic_generate_pagination($replies, $view_topic_url),
					// 	'REPLIES'			=> $replies,
					// 	'VIEWS'				=> $row['topic_views'],
					// 	'TOPIC_TITLE'		=> censor_text($row['topic_title']),
					// 	'TOPIC_TYPE'		=> $topic_type,

					// 	'TOPIC_FOLDER_IMG'		=> $user->img($folder_img, $folder_alt),
					// 	'TOPIC_FOLDER_IMG_SRC'	=> $user->img($folder_img, $folder_alt, false, '', 'src'),
					// 	'TOPIC_FOLDER_IMG_ALT'	=> $user->lang[$folder_alt],
					// 	'TOPIC_FOLDER_IMG_WIDTH'=> $user->img($folder_img, '', false, '', 'width'),
					// 	'TOPIC_FOLDER_IMG_HEIGHT'	=> $user->img($folder_img, '', false, '', 'height'),

					// 	'TOPIC_ICON_IMG'		=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['img'] : '',
					// 	'TOPIC_ICON_IMG_WIDTH'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['width'] : '',
					// 	'TOPIC_ICON_IMG_HEIGHT'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['height'] : '',
					// 	'ATTACH_ICON_IMG'		=> ($auth->acl_get('u_download') && $auth->acl_get('f_download', $topic_forum_id) && $row['topic_attachment']) ? $user->img('icon_topic_attach', $user->lang['TOTAL_ATTACHMENTS']) : '',
					// 	'UNAPPROVED_IMG'		=> ($topic_unapproved || $posts_unapproved) ? $user->img('icon_topic_unapproved', ($topic_unapproved) ? 'TOPIC_UNAPPROVED' : 'POSTS_UNAPPROVED') : '',

					// 	'S_TOPIC_TYPE'			=> $row['topic_type'],
					// 	'S_USER_POSTED'			=> (isset($row['topic_posted']) && $row['topic_posted']) ? true : false,
					// 	'S_UNREAD_TOPIC'		=> $unread_topic,
					// 	'S_TOPIC_REPORTED'		=> (!empty($row['topic_reported']) && $auth->acl_get('m_report', $topic_forum_id)) ? true : false,
					// 	'S_TOPIC_UNAPPROVED'	=> $topic_unapproved,
					// 	'S_POSTS_UNAPPROVED'	=> $posts_unapproved,
					// 	'S_HAS_POLL'			=> ($row['poll_start']) ? true : false,
					// 	'S_POST_ANNOUNCE'		=> ($row['topic_type'] == POST_ANNOUNCE) ? true : false,
					// 	'S_POST_GLOBAL'			=> ($row['topic_type'] == POST_GLOBAL) ? true : false,
					// 	'S_POST_STICKY'			=> ($row['topic_type'] == POST_STICKY) ? true : false,
					// 	'S_TOPIC_LOCKED'		=> ($row['topic_status'] == ITEM_LOCKED) ? true : false,
					// 	'S_TOPIC_MOVED'			=> ($row['topic_status'] == ITEM_MOVED) ? true : false,

					// 	'U_NEWEST_POST'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", $view_topic_url_params . '&amp;view=unread') . '#unread',
					// 	'U_LAST_POST'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", $view_topic_url_params . '&amp;p=' . $row['topic_last_post_id']) . '#p' . $row['topic_last_post_id'],
					// 	'U_LAST_POST_AUTHOR'	=> get_username_string('profile', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
					// 	'U_TOPIC_AUTHOR'		=> get_username_string('profile', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
					// 	'U_VIEW_TOPIC'			=> $view_topic_url,
					// 	'U_MCP_REPORT'			=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=reports&amp;mode=reports&amp;f=' . $topic_forum_id . '&amp;t=' . $topic_id, true, $user->session_id),
					// 	'U_MCP_QUEUE'			=> $u_mcp_queue,

					// 	'S_TOPIC_TYPE_SWITCH'	=> ($s_type_switch == $s_type_switch_test) ? -1 : $s_type_switch_test)
					// );
					$retval['topics'][] = $row + array(
						'title' => censor_text($row['topic_title']),
						'post_count' => $replies + 1,
						'authorname' => get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
						'date' => $row['topic_time'],
					);

					$s_type_switch = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;

					if ($unread_topic)
					{
						$mark_forum_read = false;
					}

					unset($rowset[$topic_id]);
				}
			}

		}

		return $retval;
	}

	function getForumData($root_forum_id = false) {
		global $db, $auth, $user;
		global $phpbb_root_path, $phpEx, $config;

		$forum_rows = $subforums = $forum_ids = $forum_ids_moderator = $forum_moderators = $active_forum_ary = array();
		$parent_id = $visible_forums = 0;
		$sql_from = '';

		// Mark forums read?
		$mark_read = request_var('mark', '');

		if ($mark_read == 'all')
		{
			$mark_read = '';
		}

		$root_data = null;
		if (!$root_forum_id)
		{
			if ($mark_read == 'forums')
			{
				$mark_read = 'all';
			}

			$root_data = array('forum_id' => 0);
			$sql_where = '';
		}
		else
		{
			$sql_from = FORUMS_TABLE . ' f';
			$lastread_select = '';

			// Grab appropriate forum data
			if ($config['load_db_lastread'] && $user->data['is_registered'])
			{
				$sql_from .= ' LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $user->data['user_id'] . '
					AND ft.forum_id = f.forum_id)';
				$lastread_select .= ', ft.mark_time';
			}

			if ($user->data['is_registered'])
			{
				$sql_from .= ' LEFT JOIN ' . FORUMS_WATCH_TABLE . ' fw ON (fw.forum_id = f.forum_id AND fw.user_id = ' . $user->data['user_id'] . ')';
				$lastread_select .= ', fw.notify_status';
			}
			$sql = "SELECT f.* $lastread_select
				FROM $sql_from
				WHERE f.forum_id = $root_forum_id";
			$result = $db->sql_query($sql);
			$root_data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$sql_where = 'left_id > ' . $root_data['left_id'] . ' AND left_id < ' . $root_data['right_id'];

			if (!$auth->acl_gets('f_list', 'f_read', $root_forum_id) || ($root_data['forum_type'] == FORUM_LINK && $root_data['forum_link'] && !$auth->acl_get('f_read', $root_forum_id)))
			{
				return false;
			}
		}

		$retval = $root_data + array(
			'type' => (!isset($root_data['forum_type']) || $root_data['forum_type'] == FORUM_CAT ? 'category': 'forum'),
			'title' => @$root_data['forum_name'],
			'subforums' => array(),
			'depth' => ($root_forum_id ? 1 : 0),
		);

		// Handle marking everything read
		if ($mark_read == 'all')
		{
			$redirect = build_url(array('mark', 'hash'));
			meta_refresh(3, $redirect);

			if (check_link_hash(request_var('hash', ''), 'global'))
			{
				markread('all');

				trigger_error(
					$user->lang['FORUMS_MARKED'] . '<br /><br />' .
					sprintf($user->lang['RETURN_INDEX'], '<a href="' . $redirect . '">', '</a>')
				);
			}
			else
			{
				trigger_error(sprintf($user->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>'));
			}
		}

		// Display list of active topics for this category?
		$show_active = (isset($root_data['forum_flags']) && ($root_data['forum_flags'] & FORUM_FLAG_ACTIVE_TOPICS)) ? true : false;

		$sql_array = array(
			'SELECT'	=> 'f.*',
			'FROM'		=> array(
				FORUMS_TABLE		=> 'f'
			),
			'LEFT_JOIN'	=> array(),
		);

		if ($config['load_db_lastread'] && $user->data['is_registered'])
		{
			$sql_array['LEFT_JOIN'][] = array('FROM' => array(FORUMS_TRACK_TABLE => 'ft'), 'ON' => 'ft.user_id = ' . $user->data['user_id'] . ' AND ft.forum_id = f.forum_id');
			$sql_array['SELECT'] .= ', ft.mark_time';
		}
		else if ($config['load_anon_lastread'] || $user->data['is_registered'])
		{
			$tracking_topics = (isset($_COOKIE[$config['cookie_name'] . '_track'])) ? ((STRIP) ? stripslashes($_COOKIE[$config['cookie_name'] . '_track']) : $_COOKIE[$config['cookie_name'] . '_track']) : '';
			$tracking_topics = ($tracking_topics) ? tracking_unserialize($tracking_topics) : array();

			if (!$user->data['is_registered'])
			{
				$user->data['user_lastmark'] = (isset($tracking_topics['l'])) ? (int) (base_convert($tracking_topics['l'], 36, 10) + $config['board_startdate']) : 0;
			}
		}

		if ($show_active)
		{
			$sql_array['LEFT_JOIN'][] = array(
				'FROM'	=> array(FORUMS_ACCESS_TABLE => 'fa'),
				'ON'	=> "fa.forum_id = f.forum_id AND fa.session_id = '" . $db->sql_escape($user->session_id) . "'"
			);

			$sql_array['SELECT'] .= ', fa.user_id';
		}

		$sql = $db->sql_build_query('SELECT', array(
			'SELECT'	=> $sql_array['SELECT'],
			'FROM'		=> $sql_array['FROM'],
			'LEFT_JOIN'	=> $sql_array['LEFT_JOIN'],

			'WHERE'		=> $sql_where,

			'ORDER_BY'	=> 'f.left_id',
		));

		$result = $db->sql_query($sql);

		$forum_tracking_info = array();
		$branch_root_id = $root_data['forum_id'];

		// Check for unread global announcements (index page only)
		$ga_unread = false;
		if ($root_data['forum_id'] == 0)
		{
			$unread_ga_list = get_unread_topics($user->data['user_id'], 'AND t.forum_id = 0', '', 1);

			if (!empty($unread_ga_list))
			{
				$ga_unread = true;
			}
		}

		while ($row = $db->sql_fetchrow($result))
		{
			$forum_id = $row['forum_id'];

			// Mark forums read?
			if ($mark_read == 'forums')
			{
				if ($auth->acl_get('f_list', $forum_id))
				{
					$forum_ids[] = $forum_id;
				}

				continue;
			}

			// Category with no members
			if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
			{
				continue;
			}

			// Skip branch
			if (isset($right_id))
			{
				if ($row['left_id'] < $right_id)
				{
					continue;
				}
				unset($right_id);
			}

			if (!$auth->acl_get('f_list', $forum_id))
			{
				// if the user does not have permissions to list this forum, skip everything until next branch
				$right_id = $row['right_id'];
				continue;
			}

			if ($config['load_db_lastread'] && $user->data['is_registered'])
			{
				$forum_tracking_info[$forum_id] = (!empty($row['mark_time'])) ? $row['mark_time'] : $user->data['user_lastmark'];
			}
			else if ($config['load_anon_lastread'] || $user->data['is_registered'])
			{
				if (!$user->data['is_registered'])
				{
					$user->data['user_lastmark'] = (isset($tracking_topics['l'])) ? (int) (base_convert($tracking_topics['l'], 36, 10) + $config['board_startdate']) : 0;
				}
				$forum_tracking_info[$forum_id] = (isset($tracking_topics['f'][$forum_id])) ? (int) (base_convert($tracking_topics['f'][$forum_id], 36, 10) + $config['board_startdate']) : $user->data['user_lastmark'];
			}

			// Count the difference of real to public topics, so we can display an information to moderators
			$row['forum_id_unapproved_topics'] = ($auth->acl_get('m_approve', $forum_id) && ($row['forum_topics_real'] != $row['forum_topics'])) ? $forum_id : 0;
			$row['forum_topics'] = ($auth->acl_get('m_approve', $forum_id)) ? $row['forum_topics_real'] : $row['forum_topics'];

			// Display active topics from this forum?
			if ($show_active && $row['forum_type'] == FORUM_POST && $auth->acl_get('f_read', $forum_id) && ($row['forum_flags'] & FORUM_FLAG_ACTIVE_TOPICS))
			{
				if (!isset($active_forum_ary['forum_topics']))
				{
					$active_forum_ary['forum_topics'] = 0;
				}

				if (!isset($active_forum_ary['forum_posts']))
				{
					$active_forum_ary['forum_posts'] = 0;
				}

				$active_forum_ary['forum_id'][]		= $forum_id;
				$active_forum_ary['enable_icons'][]	= $row['enable_icons'];
				$active_forum_ary['forum_topics']	+= $row['forum_topics'];
				$active_forum_ary['forum_posts']	+= $row['forum_posts'];

				// If this is a passworded forum we do not show active topics from it if the user is not authorised to view it...
				if ($row['forum_password'] && $row['user_id'] != $user->data['user_id'])
				{
					$active_forum_ary['exclude_forum_id'][] = $forum_id;
				}
			}

			//
			if ($row['parent_id'] == $root_data['forum_id'] || $row['parent_id'] == $branch_root_id)
			{
				if ($row['forum_type'] != FORUM_CAT)
				{
					$forum_ids_moderator[] = (int) $forum_id;
				}

				// Direct child of current branch
				$parent_id = $forum_id;
				$forum_rows[$forum_id] = $row;

				if ($row['forum_type'] == FORUM_CAT && $row['parent_id'] == $root_data['forum_id'])
				{
					$branch_root_id = $forum_id;
				}
				$forum_rows[$parent_id]['forum_id_last_post'] = $row['forum_id'];
				$forum_rows[$parent_id]['orig_forum_last_post_time'] = $row['forum_last_post_time'];
			}
			else if ($row['forum_type'] != FORUM_CAT)
			{
				$subforums[$parent_id][$forum_id]['display'] = ($row['display_on_index']) ? true : false;
				$subforums[$parent_id][$forum_id]['name'] = $row['forum_name'];
				$subforums[$parent_id][$forum_id]['orig_forum_last_post_time'] = $row['forum_last_post_time'];
				$subforums[$parent_id][$forum_id]['children'] = array();

				if (isset($subforums[$parent_id][$row['parent_id']]) && !$row['display_on_index'])
				{
					$subforums[$parent_id][$row['parent_id']]['children'][] = $forum_id;
				}

				if (!$forum_rows[$parent_id]['forum_id_unapproved_topics'] && $row['forum_id_unapproved_topics'])
				{
					$forum_rows[$parent_id]['forum_id_unapproved_topics'] = $forum_id;
				}

				$forum_rows[$parent_id]['forum_topics'] += $row['forum_topics'];

				// Do not list redirects in LINK Forums as Posts.
				if ($row['forum_type'] != FORUM_LINK)
				{
					$forum_rows[$parent_id]['forum_posts'] += $row['forum_posts'];
				}

				if ($row['forum_last_post_time'] > $forum_rows[$parent_id]['forum_last_post_time'])
				{
					$forum_rows[$parent_id]['forum_last_post_id'] = $row['forum_last_post_id'];
					$forum_rows[$parent_id]['forum_last_post_subject'] = $row['forum_last_post_subject'];
					$forum_rows[$parent_id]['forum_last_post_time'] = $row['forum_last_post_time'];
					$forum_rows[$parent_id]['forum_last_poster_id'] = $row['forum_last_poster_id'];
					$forum_rows[$parent_id]['forum_last_poster_name'] = $row['forum_last_poster_name'];
					$forum_rows[$parent_id]['forum_last_poster_colour'] = $row['forum_last_poster_colour'];
					$forum_rows[$parent_id]['forum_id_last_post'] = $forum_id;
				}
			}
		}
		$db->sql_freeresult($result);

		// Handle marking posts
		if ($mark_read == 'forums')
		{
			$redirect = build_url(array('mark', 'hash'));
			$token = request_var('hash', '');
			if (check_link_hash($token, 'global'))
			{
				// Add 0 to forums array to mark global announcements correctly
				$forum_ids[] = 0;
				markread('topics', $forum_ids);
				$message = sprintf($user->lang['RETURN_FORUM'], '<a href="' . $redirect . '">', '</a>');
				meta_refresh(3, $redirect);
				trigger_error($user->lang['FORUMS_MARKED'] . '<br /><br />' . $message);
			}
			else
			{
				$message = sprintf($user->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>');
				meta_refresh(3, $redirect);
				trigger_error($message);
			}

		}

		// Grab moderators ... if necessary
		if ($display_moderators)
		{
			if ($return_moderators)
			{
				$forum_ids_moderator[] = $root_data['forum_id'];
			}
			get_moderators($forum_moderators, $forum_ids_moderator);
		}

		// Used to tell whatever we have to create a dummy category or not.
		$last_catless = true;
		foreach ($forum_rows as $row)
		{
			// Empty category
			if ($row['parent_id'] == $root_data['forum_id'] && $row['forum_type'] == FORUM_CAT)
			{
				// $template->assign_block_vars('forumrow', array(
				// 	'S_IS_CAT'				=> true,
				// 	'FORUM_ID'				=> $row['forum_id'],
				// 	'FORUM_NAME'			=> $row['forum_name'],
				// 	'FORUM_DESC'			=> generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
				// 	'FORUM_FOLDER_IMG'		=> '',
				// 	'FORUM_FOLDER_IMG_SRC'	=> '',
				// 	'FORUM_IMAGE'			=> ($row['forum_image']) ? '<img src="' . $phpbb_root_path . $row['forum_image'] . '" alt="' . $user->lang['FORUM_CAT'] . '" />' : '',
				// 	'FORUM_IMAGE_SRC'		=> ($row['forum_image']) ? $phpbb_root_path . $row['forum_image'] : '',
				// 	'U_VIEWFORUM'			=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $row['forum_id']))
				// );
				$retval['subforums'][] = $row + array(
					'title' => $row['forum_name'],
					'type' => 'category',
					'desc_html' => generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
					'depth' => $retval['depth'] + 1,
				);

				continue;
			}

			$visible_forums++;
			$forum_id = $row['forum_id'];

			$forum_unread = (isset($forum_tracking_info[$forum_id]) && $row['orig_forum_last_post_time'] > $forum_tracking_info[$forum_id]) ? true : false;

			// Mark the first visible forum on index as unread if there's any unread global announcement
			if ($ga_unread && !empty($forum_ids_moderator) && $forum_id == $forum_ids_moderator[0])
			{
				$forum_unread = true;
			}

			$folder_image = $folder_alt = $l_subforums = '';
			$subforums_list = array();

			// Generate list of subforums if we need to
			if (isset($subforums[$forum_id]))
			{
				foreach ($subforums[$forum_id] as $subforum_id => $subforum_row)
				{
					$subforum_unread = (isset($forum_tracking_info[$subforum_id]) && $subforum_row['orig_forum_last_post_time'] > $forum_tracking_info[$subforum_id]) ? true : false;

					if (!$subforum_unread && !empty($subforum_row['children']))
					{
						foreach ($subforum_row['children'] as $child_id)
						{
							if (isset($forum_tracking_info[$child_id]) && $subforums[$forum_id][$child_id]['orig_forum_last_post_time'] > $forum_tracking_info[$child_id])
							{
								// Once we found an unread child forum, we can drop out of this loop
								$subforum_unread = true;
								break;
							}
						}
					}

					if ($subforum_row['display'] && $subforum_row['name'])
					{
						// $subforums_list[] = array(
						// 	'link'		=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $subforum_id),
						// 	'name'		=> $subforum_row['name'],
						// 	'unread'	=> $subforum_unread,
						// );
						$subforums_list[] = array(
							'forum_id' => $subforum_id,
							'title' => $subforum_row['name'],
							'type' => 'forum',
							'desc_html' => '',
							'depth' => $retval['depth'] + 3,
						);
					}
					else
					{
						unset($subforums[$forum_id][$subforum_id]);
					}

					// If one subforum is unread the forum gets unread too...
					if ($subforum_unread)
					{
						$forum_unread = true;
					}
				}

				$l_subforums = (sizeof($subforums[$forum_id]) == 1) ? $user->lang['SUBFORUM'] . ': ' : $user->lang['SUBFORUMS'] . ': ';
				$folder_image = ($forum_unread) ? 'forum_unread_subforum' : 'forum_read_subforum';
			}
			else
			{
				switch ($row['forum_type'])
				{
					case FORUM_POST:
						$folder_image = ($forum_unread) ? 'forum_unread' : 'forum_read';
					break;

					case FORUM_LINK:
						$folder_image = 'forum_link';
					break;
				}
			}

			// Which folder should we display?
			if ($row['forum_status'] == ITEM_LOCKED)
			{
				$folder_image = ($forum_unread) ? 'forum_unread_locked' : 'forum_read_locked';
				$folder_alt = 'FORUM_LOCKED';
			}
			else
			{
				$folder_alt = ($forum_unread) ? 'UNREAD_POSTS' : 'NO_UNREAD_POSTS';
			}

			// Create last post link information, if appropriate
			if ($row['forum_last_post_id'])
			{
				$last_post_subject = $row['forum_last_post_subject'];
				$last_post_time = $user->format_date($row['forum_last_post_time']);
				$last_post_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id_last_post'] . '&amp;p=' . $row['forum_last_post_id']) . '#p' . $row['forum_last_post_id'];
			}
			else
			{
				$last_post_subject = $last_post_time = $last_post_url = '';
			}

			// Output moderator listing ... if applicable
			$l_moderator = $moderators_list = '';
			if ($display_moderators && !empty($forum_moderators[$forum_id]))
			{
				$l_moderator = (sizeof($forum_moderators[$forum_id]) == 1) ? $user->lang['MODERATOR'] : $user->lang['MODERATORS'];
				$moderators_list = implode(', ', $forum_moderators[$forum_id]);
			}

			$l_post_click_count = ($row['forum_type'] == FORUM_LINK) ? 'CLICKS' : 'POSTS';
			$post_click_count = ($row['forum_type'] != FORUM_LINK || $row['forum_flags'] & FORUM_FLAG_LINK_TRACK) ? $row['forum_posts'] : '';

			$s_subforums_list = array();
			foreach ($subforums_list as $subforum)
			{
				$s_subforums_list[] = '<a href="' . $subforum['link'] . '" class="subforum ' . (($subforum['unread']) ? 'unread' : 'read') . '" title="' . (($subforum['unread']) ? $user->lang['UNREAD_POSTS'] : $user->lang['NO_UNREAD_POSTS']) . '">' . $subforum['name'] . '</a>';
			}
			$s_subforums_list = (string) implode(', ', $s_subforums_list);
			$catless = ($row['parent_id'] == $root_data['forum_id']) ? true : false;

			if ($row['forum_type'] != FORUM_LINK)
			{
				$u_viewforum = append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $row['forum_id']);
			}
			else
			{
				// If the forum is a link and we count redirects we need to visit it
				// If the forum is having a password or no read access we do not expose the link, but instead handle it in viewforum
				if (($row['forum_flags'] & FORUM_FLAG_LINK_TRACK) || $row['forum_password'] || !$auth->acl_get('f_read', $forum_id))
				{
					$u_viewforum = append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $row['forum_id']);
				}
				else
				{
					$u_viewforum = $row['forum_link'];
				}
			}

			// $template->assign_block_vars('forumrow', array(
			// 	'S_IS_CAT'			=> false,
			// 	'S_NO_CAT'			=> $catless && !$last_catless,
			// 	'S_IS_LINK'			=> ($row['forum_type'] == FORUM_LINK) ? true : false,
			// 	'S_UNREAD_FORUM'	=> $forum_unread,
			// 	'S_AUTH_READ'		=> $auth->acl_get('f_read', $row['forum_id']),
			// 	'S_LOCKED_FORUM'	=> ($row['forum_status'] == ITEM_LOCKED) ? true : false,
			// 	'S_LIST_SUBFORUMS'	=> ($row['display_subforum_list']) ? true : false,
			// 	'S_SUBFORUMS'		=> (sizeof($subforums_list)) ? true : false,
			// 	'S_FEED_ENABLED'	=> ($config['feed_forum'] && !phpbb_optionget(FORUM_OPTION_FEED_EXCLUDE, $row['forum_options']) && $row['forum_type'] == FORUM_POST) ? true : false,

			// 	'FORUM_ID'				=> $row['forum_id'],
			// 	'FORUM_NAME'			=> $row['forum_name'],
			// 	'FORUM_DESC'			=> generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
			// 	'TOPICS'				=> $row['forum_topics'],
			// 	$l_post_click_count		=> $post_click_count,
			// 	'FORUM_FOLDER_IMG'		=> $user->img($folder_image, $folder_alt),
			// 	'FORUM_FOLDER_IMG_SRC'	=> $user->img($folder_image, $folder_alt, false, '', 'src'),
			// 	'FORUM_FOLDER_IMG_ALT'	=> isset($user->lang[$folder_alt]) ? $user->lang[$folder_alt] : '',
			// 	'FORUM_IMAGE'			=> ($row['forum_image']) ? '<img src="' . $phpbb_root_path . $row['forum_image'] . '" alt="' . $user->lang[$folder_alt] . '" />' : '',
			// 	'FORUM_IMAGE_SRC'		=> ($row['forum_image']) ? $phpbb_root_path . $row['forum_image'] : '',
			// 	'LAST_POST_SUBJECT'		=> censor_text($last_post_subject),
			// 	'LAST_POST_TIME'		=> $last_post_time,
			// 	'LAST_POSTER'			=> get_username_string('username', $row['forum_last_poster_id'], $row['forum_last_poster_name'], $row['forum_last_poster_colour']),
			// 	'LAST_POSTER_COLOUR'	=> get_username_string('colour', $row['forum_last_poster_id'], $row['forum_last_poster_name'], $row['forum_last_poster_colour']),
			// 	'LAST_POSTER_FULL'		=> get_username_string('full', $row['forum_last_poster_id'], $row['forum_last_poster_name'], $row['forum_last_poster_colour']),
			// 	'MODERATORS'			=> $moderators_list,
			// 	'SUBFORUMS'				=> $s_subforums_list,

			// 	'L_SUBFORUM_STR'		=> $l_subforums,
			// 	'L_MODERATOR_STR'		=> $l_moderator,

			// 	'U_UNAPPROVED_TOPICS'	=> ($row['forum_id_unapproved_topics']) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=unapproved_topics&amp;f=' . $row['forum_id_unapproved_topics']) : '',
			// 	'U_VIEWFORUM'		=> $u_viewforum,
			// 	'U_LAST_POSTER'		=> get_username_string('profile', $row['forum_last_poster_id'], $row['forum_last_poster_name'], $row['forum_last_poster_colour']),
			// 	'U_LAST_POST'		=> $last_post_url)
			// );

			$retval['subforums'][] = $row + array(
				'title' => $row['forum_name'],
				'type' => 'forum',
				'desc_html' => generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
				'depth' => $retval['depth'] + 2,
			);

			// Assign subforums loop for style authors
			foreach ($subforums_list as $subforum)
			{
				// $template->assign_block_vars('forumrow.subforum', array(
				// 	'U_SUBFORUM'	=> $subforum['link'],
				// 	'SUBFORUM_NAME'	=> $subforum['name'],
				// 	'S_UNREAD'		=> $subforum['unread'])
				// );
				$retval['subforums'][] = $subforum;
			}

			$last_catless = $catless;
		}

		// $template->assign_vars(array(
		// 	'U_MARK_FORUMS'		=> ($user->data['is_registered'] || $config['load_anon_lastread']) ? append_sid("{$phpbb_root_path}viewforum.$phpEx", 'hash=' . generate_link_hash('global') . '&amp;f=' . $root_data['forum_id'] . '&amp;mark=forums') : '',
		// 	'S_HAS_SUBFORUM'	=> ($visible_forums) ? true : false,
		// 	'L_SUBFORUM'		=> ($visible_forums == 1) ? $user->lang['SUBFORUM'] : $user->lang['SUBFORUMS'],
		// 	'LAST_POST_IMG'		=> $user->img('icon_topic_latest', 'VIEW_LATEST_POST'),
		// 	'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'TOPICS_UNAPPROVED'),
		// ));

		if ($return_moderators)
		{
			$retval['moderators'] = $forum_moderators;
		}

		return $retval;

		// return array($active_forum_ary, array());
	}

	function getTopic($topic_id, $view, $forum_id = 0) {
		global $pbb_topic;
		return $pbb_topic->getTopic($topic_id, $view, $forum_id);
	}

	function getPostTopic($postid) {
		global $psdb;
	
		$result = $psdb->query("SELECT * FROM `{$psdb->prefix}posts` WHERE `postid` = ".intval($postid)." LIMIT 1");
		if ($topic = $psdb->fetch_assoc($result)) {
			$treeindex = $topic['treeindex'];
		} else {
			return NULL;
		}

		$topic['posts'] = array();
		if ($topic['topicid'] == $topic['postid']) {
			$result = $psdb->query("SELECT * FROM `{$psdb->prefix}posts` WHERE `topicid` = ".intval($topicid)." ORDER BY `treeindex`");
		} else {
			$result = $psdb->query("SELECT * FROM `{$psdb->prefix}posts` WHERE `treeindex` LIKE '".$psdb->escape($treeindex)."%' ORDER BY `treeindex`");
		}
		while ($row = $psdb->fetch_assoc($result)) {
			$topic['posts'][] = $row;
		}
		return $topic;
	}

	function getPost($postid) {
		global $psdb;
	
		$result = $psdb->query("SELECT * FROM `{$psdb->prefix}posts` WHERE `postid` = ".intval($postid)." LIMIT 1");
		if ($row = $psdb->fetch_assoc($result)) {
			return $row;
		}
		return NULL;
	}

	/*
	 * $post should contain: authorid, authorname, message
	 * 
	 */
	function addReply(&$post, $parentpostid = 0) {
		global $psdb;
	
		if (!@$post['message']) {
			return false;
		}
		if (!$parentpostid) {
			$parentpostid = $post['parentpostid'];
		}
		$parentpost = $this->getPost($parentpostid);
	
		if (!@$post['title']) {
			$post['title'] = $this->reply($parentpost['title']);
		}
		$post['majortitle'] = ($this->reply($parentpost['title']) != $post['title']);
		$post['topicid'] = $parentpost['topicid'];
		$post['messagehtml'] = $this->bbcodeParse($post['message']);
		if (!$post['messagehtml']) {
			return false;
		}
	
		$result = $psdb->query("INSERT INTO `{$psdb->prefix}posts` (`topicid`,`authorid`,`authorname`,`date`,`message`,`messagehtml`,`title`,`majortitle`,`parentpostid`,`treeindex`) VALUES (".intval($post['topicid']).",'".$psdb->escape($post['authorid'])."','".$psdb->escape($post['authorname'])."',".intval(time()).",'".$psdb->escape($post['message'])."','".$psdb->escape($post['messagehtml'])."','".$psdb->escape($post['title'])."',".intval($post['majortitle']).",".intval($parentpostid).",'".$psdb->escape($parentpost['treeindex'].'00000000')."')");
		if (!$result) {
			return false;
		}
	
		$newtreeindex = $parentpost['treeindex'].str_pad(dechex($psdb->insert_id()),8,'0',STR_PAD_LEFT);
		$postid = intval($psdb->insert_id());
		$result = $psdb->query("UPDATE `{$psdb->prefix}posts` SET `treeindex` = '".$psdb->escape($newtreeindex)."' WHERE `postid` = ".$postid."");
		if (!$result) {
			return false;
		}
		$result = $psdb->query("UPDATE `{$psdb->prefix}topics` SET `lastpostid` = ".$postid.", `numposts` = `numposts`+1 WHERE `topicid` = ".intval($parentpost['topicid'])."");
		if (!$result) {
			return false;
		}
		return $postid;
	}

	function editPost(&$post) {
		global $psdb, $curuser, $ctime;
	
		if (!@$post['message']) {
			return false;
		}
		$oldpost = $this->getPost($post['postid']);
		$post['messagehtml'] = $this->bbcodeParse($post['message']);
		if (!$post['messagehtml']) {
			return false;
		}
		if (!$this->canEdit($post)) {
			// no permission
			return false;
		}
	
		if (!$post['title']) {
			$post['title'] = $oldpost['title'];
		}
		$post['topicid'] = $oldpost['topicid'];
	
		$result = $psdb->query("UPDATE `{$psdb->prefix}posts` SET `message` = '".$psdb->escape($post['message'])."', `title` = '".$psdb->escape($post['title'])."', `messagehtml` = '".$psdb->escape($post['messagehtml'])."', `editdate` = ".intval($ctime)." WHERE `postid` = ".intval($post['postid'])." LIMIT 1");
		if (!$result) {
			return false;
		}
		if ($post['title'] !== $oldpost['title'] && $post['postid'] == $post['topicid']) {
			$result = $psdb->query("UPDATE `{$psdb->prefix}topics` SET `title` = '".$psdb->escape($post['title'])."' WHERE `topicid` = ".intval($post['topicid'])." LIMIT 1");
		}
		return $post['postid'];
	}
	function isGuest($user=false) {
		global $curuser;
		if (!$user) {
			$user = $curuser;
		}
		return $user['userid'] === 'guest';
	}

	function addTopic(&$post, $forumid) {
		global $psdb;
	
		$post['majortitle'] = true;
		$ctime = time();
		$post['messagehtml'] = $this->bbcodeParse($post['message']);
		if (!$post['messagehtml']) {
			return false;
		}
	
		$result = $psdb->query("INSERT INTO `{$psdb->prefix}posts` (`topicid`,`authorid`,`authorname`,`date`,`message`,`messagehtml`,`title`,`majortitle`,`parentpostid`,`treeindex`) VALUES (".intval(0).",'".$psdb->escape($post['authorid'])."','".$psdb->escape($post['authorname'])."',".intval($ctime).",'".$psdb->escape($post['message'])."','".$psdb->escape($post['messagehtml'])."','".$psdb->escape($post['title'])."',".intval($post['majortitle']).",0,'".$psdb->escape('00000000')."')");
		if (!$result) {
			return false;
		}
	
		$post['postid'] = $psdb->insert_id();
		if (!$post['postid']) {
			return false;
		}
		$newtreeindex = str_pad(dechex($post['postid']),8,'0',STR_PAD_LEFT);
	
		$result = $psdb->query("INSERT INTO `{$psdb->prefix}topics` (`postid`,`topicid`,`forumid`,`authorid`,`authorname`,`date`,`message`,`messagehtml`,`title`,`majortitle`,`parentpostid`,`treeindex`,`lastpostid`) VALUES (".intval($post['postid']).",".intval($post['postid']).",".intval($forumid).",'".$psdb->escape($post['authorid'])."','".$psdb->escape($post['authorname'])."',".intval($ctime).",'".$psdb->escape($post['message'])."','".$psdb->escape($post['messagehtml'])."','".$psdb->escape($post['title'])."',".intval($post['majortitle']).",0,'".$psdb->escape($newtreeindex)."',".intval($post['postid']).")");
		if (!$result) {
			return false;
		}
	
		$result = $psdb->query("UPDATE `{$psdb->prefix}posts` SET `treeindex` = '".$psdb->escape($newtreeindex)."', `topicid` = ".intval($post['postid'])." WHERE `postid` = ".intval($post['postid'])."");
		if ($result) {
			return $post['postid'];
		}
		return false;
	}

	function hardDeleteTopic($topicid) {
		global $psdb;

		$result = $psdb->query("DELETE FROM `{$psdb->prefix}topics` WHERE `topicid` = ".intval($topicid)."");
		if (!$result) return false;
		$result = $psdb->query("DELETE FROM `{$psdb->prefix}posts` WHERE `topicid` = ".intval($topicid)."");
		return true;
	}

	var $pagetitle = false;
	var $pagetype = false;
	function setPageTitle($title) {
		if ($this->pagetitle === false) {
			$this->pagetitle = $title;
		}
	}
	function setPageType($type) {
		if ($this->pagetype === false) {
			$this->pagetype = $type;
		}
	}

	function startRender() {
		global $ntbb, $act, $actionsuccess, $actionid, $curuser;
		$this->depth++;
		if ($this->depth != 1) {
			return;
		}
		if ($this->output == 'normal') {
			include 'theme/header.inc.php';
		} else if ($this->output == 'html') {
			$pagetitle = htmlspecialchars(str_replace('-','- ',$this->pagetitle));
			if (@$_REQUEST['acted'] === 'addtopic') {
				echo "<!-- [{$this->pagetype}|topic.php?t={$_REQUEST['t']}] $pagetitle -->\n";
			} else if ($actionsuccess && ($act === 'login' || $act === 'register' || $act === 'logout')) {
				echo "<!-- [{$this->pagetype}|ALL] $pagetitle -->\n";
			} else {
				echo "<!-- [{$this->pagetype}] $pagetitle -->\n";
			}
		}
	}

	function endRender() {
		global $ntbb, $act, $actionid, $curuser;
		$this->depth--;
		if ($this->depth != 0) {
			return;
		}
		if ($this->output == 'normal') {
			include 'theme/footer.inc.php';
		}
	}

	function start() {
		global $ntbb, $act, $actionsuccess, $actionid, $curuser;
		$this->depth++;
		if ($this->depth != 1) {
			return;
		}
		if ($this->output == 'normal') {
			NTBBHeaderTemplate();
		}
	}

	function end() {
		global $ntbb, $act, $actionid, $curuser;
		$this->depth--;
		if ($this->depth != 0) {
			return;
		}
		if ($this->output == 'normal') {
			NTBBFooterTemplate();
		}
	}

	function getUserbar() {
		global $curuser;
		if ($curuser['loggedin']) {
			// form must be method="post" for logout button to work correctly
			return '<form action="action.php" method="post" style="vertical-align:middle;"><input type="hidden" name="act" value="btn" />
					<span data-href="user.php?u='.$curuser['userid'].'" class="name"><img src="theme/defaultav.png" width="25" height="25" alt="" /> '.htmlspecialchars($curuser['username']).'</span> <button type="submit" name="actbtn" value="logout" id="userbarlogout">Log Out</button>
				</form>';
		} else {
			return '<form action="action.php" method="get"><input type="hidden" name="act" value="btn" />
					<button type="submit" name="actbtn" value="go-login" id="userbarlogin">Log In</button> <button type="submit" name="actbtn" value="go-register" id="userbarregister">Register</button>
				</form>';
		}
	}

	function reply($title) {
		if (substr($title,0,3) != 'Re:') {
			$title = 'Re: '.$title;
		}
		return $title;
	}

	function date($time=0) {
		if (!$time) {
			$time = time();
		}
		return date('M j, Y',$time);
	}
	function datetime($time=0) {
		if (!$time) {
			$time = time();
		}
		return date('g:i a \o\n M j, Y',$time);
	}
}

$ntbb = new NTBB();

// Magic quotes, the scourge of PHP
if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

// include_once dirname(__FILE__).'/../action.php';
