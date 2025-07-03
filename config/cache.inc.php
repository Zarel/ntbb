<?php

$ntbb_cache = array(
	'forums' => array(
		array(
			'forumid' => 2,
			'title' => 'General',
			'type' => 'category',
			'depth' => 1,
			'lastchild' => false,
			'desc' => '',
		),
		array(
			'forumid' => 1,
			'title' => 'General Discussion',
			'type' => 'forum',
			'depth' => 2,
			'lastchild' => true,
			'desc' => 'This is a forum for general discussion. Colonel discussion and lieutenant discussion should occur elsewhere.',
		),
		array(
			'forumid' => 7,
			'title' => 'Secrets everywhere!',
			'type' => 'forum',
			'depth' => 2,
			'lastchild' => true,
			'permissions' => array('view' => array(4=>1, 5=>1, 6=>1)),
			'desc' => 'This is the moderator forums.',
		),
		array(
			'forumid' => 3,
			'title' => 'Test Category',
			'type' => 'category',
			'depth' => 1,
			'lastchild' => true,
			'desc' => '',
		),
		array(
			'forumid' => 4,
			'title' => 'Test Forum 1',
			'type' => 'forum',
			'depth' => 2,
			'lastchild' => false,
			'desc' => 'First test forum.',
		),
		array(
			'forumid' => 5,
			'title' => 'Test Subforum',
			'type' => 'forum',
			'depth' => 3,
			'lastchild' => true,
			'desc' => 'A test of subforums.',
		),
		array(
			'forumid' => 6,
			'title' => 'Test Forum 2',
			'type' => 'forum',
			'depth' => 2,
			'lastchild' => true,
			'desc' => 'Second test forum. Guest posting allowed.',
			'guestposting' => true,
		),
	),
	'groups' => array(
		array(
			'name' => 'Guest',
			'symbol' => '',
		),
		array(
			'name' => '',
			'symbol' => '',
		),
		array(
			'name' => 'Administrator',
			'symbol' => '~',
		),
		array(
			'name' => 'Voice',
			'symbol' => '+',
		),
		array(
			'name' => 'Driver',
			'symbol' => '%',
		),
		array(
			'name' => 'Moderator',
			'symbol' => '@',
		),
		array(
			'name' => 'Leader',
			'symbol' => '&',
		),
	),
);