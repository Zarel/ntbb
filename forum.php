<?php
function exceptions_error_handler($severity, $message, $filename, $lineno) {
    switch ($errno) {
    case E_USER_ERROR:
	die($message . "\n\n" . $filename . $lineno);
    default:
        break;
    }
}

set_error_handler('exceptions_error_handler');

require_once 'lib/pbb.lib.php';
echo $phpbb_root_path;
die($phpbb_root_path);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

$forum_id = intval(@$_REQUEST['f']);

$forum = $ntbb->getForum($forum_id);

/*
 * Start rendering
 */

if ($forum_id) {
	$ntbb->setPageTitle($forum['title']);
} else {
	$ntbb->setPageTitle('Home');
}
$ntbb->start();

if ($action && !$actionsuccess) echo '<strong>error</strong>';

?>
	<div class="pfx-panel">

		<div class="pfx-title titlebar">
<?php
if ($forum_id) {
?>
			<a href="<?php echo $ntbb->root; ?>forum" class="pfx-backbutton" data-target="back">&laquo; Home</a>
			<h2 class="pfx-body hasbackbutton">
				<?php echo $forum['title']; ?>
			</h2>
<?php
} else {
?>
			<h2 class="pfx-body">
				Forum Home
			</h2>
<?php
}
?>
		</div>

		<div class="pfx-body pfx-inner">
<?php
if (!$forum_id) {
?>
			<div class="padtext">
				<p>
					<strong style="color:red">This is the beta forum</strong> - 
					<a href="http://pokemonshowdown.com/forums/">The official forum is here</a>
				</p>
			</div>
<?php
}
if ($forum['subforums']) {
	if ($forum_id) {
?>
		<h3 style="margin-bottom:0">Subforums</h3>
<?php
	}
	$startdepth = $forum['depth'];
	$curdepth = $startdepth;
?>
			<div class="forumlist<?php if ($curdepth != 0) echo ' subforumlist' ?>">
<?php
	$depthstack = array(-1);
	foreach ($forum['subforums'] as $i => $subforum) {
		$depth = $subforum['depth'] - $startdepth;
		if (isset($depthstack[$depth])) {
			$forum['subforums'][$depthstack[$depth]]['lastchild'] = false;
		}
		array_splice($depthstack, $depth);
		$forum['subforums'][$i]['lastchild'] = true;
		$depthstack[$depth] = $i;

		// echo $subforum['title'].' '.($depth)."\n";
		// foreach ($depthstack as $sub) echo $sub['title'].'|';
		// echo "\n\n";
	}
	foreach ($forum['subforums'] as $subforum) {
		$lastchild = $subforum['lastchild'];
		echo '			';
		while ($subforum['depth'] < $curdepth) {
			$curdepth--;
			echo '</li></ul>';
		}
		if ($subforum['depth'] > $curdepth) {
			$curdepth++;
			echo '<ul class="'.($curdepth==1?'treeroot':'tree').'"><li class="tree'.($lastchild?' treelast':'').'">';
			$firstchild = true;
		}
		if (!$firstchild) {
			echo '</li><li class="tree'.($lastchild?' treelast':'').'">';
		}
		echo '<div class="'.($curdepth==1?'rootitem':'treeitem').'">';
		
		// insert node here
		if ($subforum['type'] === 'forum') {
?>

				<a href="<?php echo $ntbb->root,'forum-',$subforum['forum_id']; ?>" data-target="push">
					<strong><?php echo htmlspecialchars($subforum['title']); ?></strong>
					<small><?php echo $subforum['desc_html']; ?></small>
				</a>
<?php
		} else {
?>

				<strong><?php echo htmlspecialchars($subforum['title']); ?></strong>
<?php
		}

		echo '</div>';
		$firstchild = false;
	}
	while ($curdepth > $startdepth) {
		$curdepth--;
		echo '</li></ul>';
	}
?>
			</div>
<?php
}
if ($forum['type'] === 'forum') {
?>
		<h3>Topics</h3>
		<ul class="topiclist">
<?php
	if ($forum['can_post']) {
?>
			<li class="newtopic"><div class="topic">
				<a href="<?php echo $ntbb->root,'newtopic-',$forum_id; ?>" data-target="push"><strong class="newtopic">+ <em>New topic</em></strong></a>
			</div></li>
<?php
	}

	$firstchild = true;
	if (!$forum['topics']) {
?>
			<li><div class="topic">
				<p class="padtext placeholdertext"><em>No topics have been posted in this forum yet.</em></p>
			</div></li>
<?php
	} else foreach ($forum['topics'] as $i => $topic) {
		//  post<?php echo $topic['post_count']==1?'':'s' ? >
		$authorgroup = $users->getGroupSymbol($topic['authorid']);
		if ($authorgroup) {
			$authorgroup = '<span class="namegroup">'.$authorgroup.'</span>';
		}
?>
			<li><div class="topic<?php if ($actionid == $topic['topic_id']) echo ' activetopic'; ?>" id="topic<?php echo $topic['topic_id']; ?>">
				<a href="<?php echo $ntbb->root,'topic-',$topic['topic_id'] ?>" data-target="push"><strong><?php echo htmlspecialchars($topic['title']); ?></strong> <span class="sep"><br /></span>
				<em class="numposts">(<?php echo $topic['post_count'] ?>)</em>
				<em class="user"><?php echo $authorgroup,$topic['authorname']; ?></em>
				<span class="sep">-</span> <small class="date"><?php echo $ntbb->date($topic['date']); ?></small></a>
			</div></li>
<?php
	}

?>
		</ul>
<?php
}

?>
		</div>
	</div>
<?php

if (!$forum_id) {
	//include 'welcome.php';
}

$ntbb->end();

?>
