<?php

$in_ntbb = (@$ntbb?true:false);

require_once 'lib/ntbb.lib.php';

$action = false;
$actionsuccess = false;
$actionerror = '';
$actionid = 0;

$act = ''.@$_POST['act'];

if (@$_POST['actbtn'] || $act === 'btn') {
	$act = ''.@$_POST['actbtn'];
}

if ($act) {
	if (!$users->csrfCheck()) {
		die('Access denied.');
	}
}

$acts = explode('-',$act);
$act = $acts[0];

// session

if ($act === 'login') {
	$action = true;
	$users->login($_POST['username'], $_POST['password']);
	if ($curuser['loggedin']) {
		$actionsuccess = true;
	} else {
		$actionerror = 'Username or password incorrect.';
	}
}

if ($act === 'go') {
	switch ($acts[1]) {
	case 'login':
		header('Location: login');
		break;
	case 'register':
		header('Location: register');
		break;
	default:
		die('Unknown redirect.');
		break;
	}
	die();
}

if ($act === 'register') {
	$action = true;
	$user = array();
	$user['username'] = $_POST['username'];
	$userid = $users->userid($user['username']);
	if ($curuser['loggedin']) {
		$actionerror = 'You are already logged in.';
	} else if ((strlen($userid) < 1) || is_numeric($userid)) {
		$actionerror = 'Your username must contain at least one letter.';
	} else if (substr($userid, 0, 5) === 'guest') {
		$actionerror = 'Your username cannot start with \'guest\'.';
	} else if (strlen($user['username']) > 18) {
		$actionerror = 'Your username must be less than 19 characters long.';
	} else if (strlen($_POST['password']) < 5) {
		$actionerror = 'Your password must be at least 5 characters long.';
	} else if ($_POST['password'] !== $_POST['cpassword']) {
		$actionerror = 'Your passwords do not match.';
	} else if (strtolower($_POST['captcha']) !== 'pikachu') {
		$actionerror = 'Please answer the anti-spam question given.';
	} else if (($registrationcount = $users->getRecentRegistrationCount()) === false) {
		$actionerror = 'A database error occurred. Please try again.';
	} else if ($registrationcount >= 2) {
		$actionerror = 'You can\'t register more than two usernames every two hours. Try again later.';
	} else if (!$users->addUser($user, $_POST['password'])) {
		$actionerror = 'Your username is already taken.';
	} else {
		$actionsuccess = true;
	}
}

if ($act === 'modifyuser' && false) {
	$action = true;
	$user = array();
	$user['userid'] = $_POST['userid'];
	if ($_POST['newpassword']) {
		// $user['password'] = $_POST['newpassword'];
	}
	if ($curuser['group'] === '2' && $_POST['group']) {
		$user['group'] = $_POST['group'];
		if (intval($_POST['group']) == 2) die('denied');
	}

	if ($curuser['group'] === '2' && $user['group'] !== '2') {
		$password = false;
	} else {
		$password = $_POST['password'];
		if ($password === false) {
			die('This should be impossible.');
		}
	}

	if (!$curuser['loggedin']) {
		$actionerror = 'You are not logged in.';
	} else if ($curuser['userid'] !== $user['userid'] && $curuser['group'] !== '2') {
		$actionerror = 'Access denied.';
	}

	if (@$user['password']) {
		if (strlen($_POST['newpassword']) < 5) {
			$actionerror = 'Your new password must be at least 5 characters long.';
		} else if ($_POST['newpassword'] !== $_POST['cnewpassword']) {
			$actionerror = 'Your new passwords do not match.';
		}
	}

	if (!$actionerror) {
		if (($password !== false) && !$users->passwordVerify($user['userid'], $password)) {
			$actionerror = 'Your old password was incorrect.';
		} else if (!$users->modifyUser($user['userid'], $user)) {
			$actionerror = 'A database error occurred. Please try again.';
		} else {
			$actionsuccess = true;
		}
	}
}

if ($act === 'resetpasslink' && false) {
	$user = $users->getUser($_POST['userid']);
	if ($curuser['group'] !== '2' || $user['group'] === '2') {
		$actionerror = 'Access denied.';
	} else {
		$userid = $_POST['userid'];
		$token = $users->createPasswordResetToken($userid);
		$actionsuccess = $token;
	}
}

if (@$_POST['act'] === 'logout' || @$_POST['actbtn'] === 'logout') {
	// To prevent URL injection, this must be POSTed
	$action = true;
	$actionsuccess = true;
	$users->logout();
}

// forum

if ($act === 'addreply') {
	$action = true;
	$replypostid = intval(@$_REQUEST['replyto']);
	if (!$replypostid) $replypostid = intval(@$_REQUEST['p']);
	if (!$replypostid) $replypostid = intval(@$_REQUEST['t']);
	
	if (!$_POST['message']) {
		$actionerror = 'You did not enter a message.';
	} else if (!$replypostid) {
		$actionerror = 'No post ID was given.';
	} else if (strtolower($_POST['captcha']) !== 'pikachu' && !$curuser['loggedin']) {
		$actionerror = 'Please answer the anti-spam question given.';
	} else {
		$newpost = array(
			'authorid' => $curuser['userid'],
			'authorname' => $curuser['username'],
			'message' => $_POST['message'],
			'title' => @$_POST['title']
		);
		$actionsuccess = $ntbb->addReply($newpost, $replypostid);
		$actionid = $actionsuccess;
		if (!$actionsuccess) {
			if (!$newpost['messagehtml']) {
				$actionerror = 'You did not enter a message.';
			} else {
				$actionerror = 'Access denied.';
			}
		}
		if (!intval(@$_REQUEST['p']) && !intval(@$_REQUEST['t'])) {
			$topicid = $newpost['topicid'];
		}
	}
}

if ($act === 'addtopic') {
	$action = true;
	if (!$forumid) $forumid = intval(@$_REQUEST['f']);
	if (strtolower($_POST['captcha']) !== 'pikachu' && !$curuser['loggedin']) {
		$actionerror = 'Please answer the anti-spam question given.';
	} else if (!$_POST['title']) {
		$actionerror = 'Your title shouldn\'t be blank.';
	} else if (!$_POST['message']) {
		$actionerror = 'Your message shouldn\'t be blank.';
	} else if ($forumid) {
		$newpost = array(
			'authorid' => $curuser['userid'],
			'authorname' => $curuser['username'],
			'message' => $_POST['message'],
			'title' => $_POST['title']
		);
		$actionsuccess = $ntbb->addTopic($newpost, $forumid);
		$actionid = $actionsuccess;
		if (!$actionsuccess) {
			if (!$newpost['messagehtml']) {
				$actionerror = 'You did not enter a message.';
			} else {
				$actionerror = 'Access denied.';
			}
		}
		$topicid = $actionid;
	}
}
if ($act === 'harddeletetopic') {
	$action = true;
	if ($curuser['group'] != 2) {
		$actionerror = 'Access denied.';
	} else {
		if (!$ntbb->hardDeleteTopic($_POST['topicid'])) {
			$actionerror = 'Topic '.$_POST['topicid'].' could not be deleted.';
		} else {
			$actionsuccess = true;
		}
	}
}
if ($act === 'editpost') {
	$action = true;
	$postid = intval(@$_REQUEST['editpost']);
	if (!$postid) $postid = intval(@$_REQUEST['p']);
	if (!$postid) $postid = intval(@$_REQUEST['t']);
	$newpost = array(
		'authorid' => $curuser['userid'],
		'authorname' => $curuser['username'],
		'message' => $_POST['message'],
		'title' => @$_POST['title'],
		'postid' => $postid
	);
	if (!$newpost['message']) {
		$actionerror = 'You did not enter a message.';
	} else if (!$postid) {
		$actionerror = 'No post ID was given.';
	} else if (!$curuser['loggedin']) {
		$actionerror = 'You have logged out; please log in again to edit your post.';
	} else if (!$ntbb->canEdit($newpost)) {
		$actionerror = 'You do not have permission to edit this post.';
	} else {
		$actionsuccess = $ntbb->editPost($newpost);
		$actionid = $actionsuccess;
		if (!$actionsuccess) {
			if (!$newpost['messagehtml']) {
				$actionerror = 'You did not enter a message.';
			} else {
				$actionerror = 'Access denied.';
			}
		}
		if (!intval(@$_REQUEST['p']) && !intval(@$_REQUEST['t'])) {
			$topicid = $newpost['topicid'];
		}
	}
	$postid=0;
}

if ($ntbb->output === 'json') {
	$returnval = array(
		'act' => $act,
		'action' => $action,
		'actionsuccess' => $actionsuccess,
		'actionerror' => $actionerror,
		'curuser' => $curuser,
		'userbar' => $ntbb->getUserbar()
	);
	header('Content-type: application/json');
	echo $json->encode($returnval);
	die();
}

if (!$in_ntbb) {
	header('Location: forum.php');
}
