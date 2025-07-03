<?php

$DEBUG = true;
$ctime = time();

include_once dirname(__FILE__).'/../config/cache.inc.php';
include_once dirname(__FILE__).'/ntbb-database.lib.php';
include_once dirname(__FILE__).'/ntbb-session.lib.php';
include_once dirname(__FILE__).'/json.lib.php';

@include_once dirname(__FILE__).'/../theme/wrapper.inc.php';

date_default_timezone_set('America/Chicago');

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

	function getForum($forumid = false) {
		global $psdb;
		$forum = $this->getForumData($forumid);
		if (!$forum) {
			return null;
		}
		if ($forum['type'] == 'category') {
			return $forum;
		}
		$result = $psdb->query("SELECT * FROM `{$psdb->prefix}topics` WHERE `forumid` = ".intval($forumid)." ORDER BY `lastpostid` DESC LIMIT 101");
		$forum['moretopics'] = false;
		while ($row = $psdb->fetch_assoc($result)) {
			if (count($forum['topics']) > 100) {
				$forum['moretopics'] = true;
				break;
			}
			$forum['topics'][] = $row;
		}
		return $forum;
	}
	function getForumData($forumid = false) {
		global $ntbb_cache, $curuser;
		$forum = null;
		if (!$forumid) {
			$forum = array(
				'depth' => 0,
				'title' => '',
				'type' => 'category',
				'forumid' => 0
			);
		} else if (is_array($forumid)) {
			return $forumid;
		}
		foreach ($ntbb_cache['forums'] as $curforum) {
			if (@$curforum['permissions'] && $curforum['permissions']['view']) {
				if (@$curuser['group'] != 2 && !@$curforum['permissions']['view'][$curuser['group']]) {
					continue;
				}
			}
			if ($forum) {
				if ($curforum['depth'] > $forum['depth']) {
					$forum['subforums'][] = $curforum;
				} else {
					break;
				}
			} else if ($curforum['forumid'] == $forumid) {
				$forum = $curforum;
				$forum['subforums'] = array();
				$forum['topics'] = array();
			}
		}
		if (!$forum) {
			return null;
		}
		return $forum;
	}

	function getTopic($topicid) {
		global $psdb;
	
		$result = $psdb->query("SELECT * FROM `{$psdb->prefix}topics` WHERE `topicid` = ".intval($topicid)." LIMIT 1");
		$topic = $psdb->fetch_assoc($result);
		$topic['posts'] = array();
		$result = $psdb->query("SELECT * FROM `{$psdb->prefix}posts` WHERE `topicid` = ".intval($topicid)." ORDER BY `treeindex`");
		while ($row = $psdb->fetch_assoc($result)) {
			if (!$topic['posts']) { //first post
				$topic['title'] = $row['title'];
			}
			$topic['posts'][] = $row;
		}
		return $topic;
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

	function canPost($forum, $user=false) {
		if (!$user) $user = $GLOBALS['curuser'];
		$forum = $this->getForumData($forum);
		if (!@$forum['guestposting'] && (!$user['loggedin'] || $user['group'] == 1)) {
			// forum doesn't allow guests to post
			return false;
		}
		return true;
	}

	function bbcodeParse($message) {
		if (substr($message,0,8) === '<<html>>') {
			return $this->htmlparse(substr($message,8));
		}
		$message = htmlspecialchars($message);
		$message = preg_replace('/\\*\\*(.*?)\\*\\*/','<b>$1</b>',$message);
		$message = preg_replace('/\\_\\_(.*?)\\_\\_/','<i>$1</i>',$message);
		$message = preg_replace('/\\~\\~(.*?)\\~\\~/','<s>$1</s>',$message);
		$message = preg_replace('/\\`\\`(.*?)\\`\\`/','<code>$1</code>',$message);
		return nl2br($message);
	}
	function htmlParse_callback($res) {
		$tag = strtolower($res[1]);
		switch ($tag) {
		case 'strong':
		case 'b':
			return '<b>';
		case '/strong':
		case '/b':
			return '</b>';
		case 'em':
		case 'i':
			return '<i>';
		case '/em':
		case '/i':
			return '</i>';
		case 's':
		case 'strike':
		case 'del':
			return '<s>';
		case '/s':
		case '/strike':
		case '/del':
			return '</s>';
		case 'code':
		case 'tt':
			return '<code>';
		case '/code':
		case '/tt':
			return '</code>';
		case 'br':
			return '<br />';
		case '/div':
			return '<endblock />';
		case 'div':
			return '<startblock />';
		}
		return '';
	}
	function htmlParse($message) {
		global $ntbb;
		$message = str_replace("\r",'',$message);
		$message = str_replace("\n",'',$message);
		$message = preg_replace('/<!--.*?-->/','',$message);
		$message = preg_replace('/\\s+/',' ',$message);
		$message = preg_replace_callback('|\<([^> ]*)[^>]*\>|', array($ntbb,'htmlParse_callback'), $message); // lol this is php's idea of a function pointer
	
		// very carefully determine where linebreaks should go
		// startblock should be converted _after_ endblock, so that </div><div> is
		// exactly one linebreak.
	
		// This somewhat accommodates ie9's contentEditable bugs, which treat <br />s
		// entirely differently than every other browser, including ie9 itself
		// out of contentEditable mode.
	
		$message = str_replace('<br /><endblock />','<br />',$message);
		$message = str_replace('<br /> <endblock />','<br />',$message);
		$message = str_replace('<endblock />','<br />',$message);
		$message = str_replace('<br /><startblock />','<br />',$message);
		$message = str_replace('<br /> <startblock />','<br />',$message);
		$message = str_replace('<startblock />','<br />',$message);
	
		$message = str_replace('<br /> ','<br />',$message);
		$message = str_replace(' <br />','<br />',$message);
		while (substr($message,0,6) === '<br />') $message = substr($message,6);
		while (substr($message,-6) === '<br />') $message = substr($message,0,-6);
		$message = trim($message);
		return $message;
	}
	function postSource($post) {
		if (substr($post['message'],0,8) === '<<html>>') {
			$message = $post['messagehtml'];
			$message = str_replace('&nbsp;',' ',$message);
			$message = str_replace('<br />',"\r\n",$message);
			$message = str_replace('<b>','**',$message);
			$message = str_replace('</b>','**',$message);
			$message = str_replace('<i>','__',$message);
			$message = str_replace('</i>','__',$message);
			$message = str_replace('<s>','~~',$message);
			$message = str_replace('</s>','~~',$message);
			$message = str_replace('<code>','``',$message);
			$message = str_replace('</code>','``',$message);
			return $message;
		}
		return $post['message'];
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
	function canEdit($post, $user=false) {
		global $curuser;
		if (!$user) {
			$user = $curuser;
		}
		if ($this->isGuest($user)) {
			return false;
		}
		return $user['userid'] === $post['authorid'] || $user['group'] == 2;
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
$json = new Services_JSON();

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

include_once dirname(__FILE__).'/../action.php';
