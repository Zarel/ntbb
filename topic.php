<?php

require_once 'lib/pbb.lib.php';
require_once 'lib/pbb-topic.lib.php';

$topic_mode = 'view';
if (!@$topic_id) $topic_id = intval(@$_REQUEST['t']);
if (!@$post_id) $post_id = intval(@$_REQUEST['p']);
if (!@$forum_id) $forum_id = intval(@$_REQUEST['f']);
if (@$_REQUEST['viewpost']) {
	$post_id = @$_REQUEST['viewpost'];
	$topic_mode = 'viewpost';
}
if (@$_REQUEST['reply']) {
	$post_id = @$_REQUEST['reply'];
	$topic_mode = 'reply';
}
if (@$_REQUEST['edit']) {
	$post_id = @$_REQUEST['edit'];
	$topic_mode = 'edit';
}
if (isset($_REQUEST['unread'])) {
	$topic_mode = 'unread';
}
if (@$_REQUEST['highlight']) {
	$actionid = intval($_REQUEST['highlight']);
}

$page = 1;
if (@$_REQUEST['page']) {
	$page = intval($_REQUEST['page']);
}

$topicdata = '<input type="hidden" name="'.($post_id!=$topic_id?'p':'t').'" class="tid" value="'.($post_id!=$topic_id?$post_id:$topic_id).'" />';
$tid = 't='.$topic_id.($post_id!=$topic_id?'&amp;'.($topic_mode==='view'?'p':$topic_mode).'='.$post_id:'');

// actions

if ($act==='editpost' && $actionsuccess && $ntbb->output === 'normal') {
	header('Location: topic.php?t='.$topic_id.($ntbb->output == 'normal'?'':'&output='.$ntbb->output.'&acted=editpost')).'#post'.$actionid;
	die();
}

// all

if ($post_id) {
	$topic = $ntbb->getPostTopic($post_id);
} else {
	$topic = $ntbb->getTopic($topic_id, $topic_mode, $page, $forum_id);
}
$forum = $ntbb->getForumData($topic['forumid']);

if (!$topic['posts']) {
	header("Status: 404 Not Found");
	die('topic not found');
}

/*
 * Start rendering
 */

$ntbb->setPageTitle($topic['title']);
$ntbb->start();

?>
	<div class="pfx-panel">

		<div class="pfx-title titlebar">
<?php if ($topic_mode === 'view') { ?>
			<a href="<?php echo $ntbb->root,'forum-',$topic['forum_id']; ?>" class="pfx-backbutton" data-target="back">&laquo; Forum</a>
			<h2 class="pfx-body hasbackbutton">
				<?php echo htmlspecialchars($topic['title']); ?>
			</h2>
<?php } else { ?>
			<a href="<?php echo $ntbb->root,'topic-',$topic['topic_id']; ?>" class="pfx-backbutton" data-target="back">&larr; Topic</a>
			<h2 class="pfx-body hasbackbutton">
				<?php
					if ($topic_mode === 'reply') echo '<em>Reply to</em> ';
					if ($topic_mode === 'edit') echo '<em>Edit</em> ';
				?><?php echo htmlspecialchars($topic['title']); ?>
			</h2>
<?php } ?>
		</div>

		<div class="pfx-body">
<?php
if ($action && !$actionsuccess) echo '<div class="error"><strong>Error:</strong> '.$actionerror.'</div>';
if ($topic['page'] != 1) {
	echo '<div class="pagenav-up"><a href="',$ntbb->root,'topic-',$topic_id,($topic['page']!=2?'-p'.($topic['page']-1):''),'" data-target="replace">Page ',($topic['page']-1),'</a></div>';
	echo '<h3>Page ',$topic['page'],'</h3>';
}
?>
		<ul class="treeroot topicwrapper"><li class="tree">
<?php

$startdepth = strlen($topic['posts'][0]['treeindex'])/8 - 1;

$curtree = substr($topic['posts'][0]['treeindex'],0,$startdepth*8);
$curdepth = $startdepth;

$posttable = array();

foreach ($topic['posts'] as $i => $post) {
	$posttable[$post['post_id']] = $i;
	if (isset($posttable[$post['parentpostid']])) {
		$topic['posts'][$posttable[$post['parentpostid']]]['lastchild'] = $post['post_id'];
	}
}

if ($topic['posts'][0]['parentpostid']) {
	echo '<div class="rootitem"><div class="minipost"><a href="topic-'.$topic['topic_id'].'" class="link-curpanel">Entire topic</a></div></div><ul class="tree"><li class="treelast">';
}

$firstchild = true;
$flat = ($topic['layout'] === 'flat');
foreach ($topic['posts'] as $i => $post) {
	$depthchange = false;
	$lastchild = (isset($posttable[$post['parentpostid']]) && $topic['posts'][$posttable[$post['parentpostid']]]['lastchild'] == $post['post_id']);
	$noparent = !isset($posttable[$post['parentpostid']]);
	
	while ($curtree != substr($post['treeindex'], 0, $curdepth*8)) {
		$curdepth--; $curtree = substr($curtree, 0, $curdepth*8);
		echo '</li></ul>';
	}
	while ($curdepth < strlen($post['treeindex'])/8 - 1) {
		$curtree .= substr($post['treeindex'], $curdepth*8, 8); $curdepth++;
		echo '<ul class="tree" id="tree_'.hexdec(substr($post['treeindex'],$curdepth*8,8)).'"><li class="tree'.($lastchild||$noparent?' treelast':'').'">';
		$firstchild = true;
	}
	
	if (@$_REQUEST['reply']) {
		$topic['posts'];
	}
	if (!$firstchild) {
		echo '</li><li class="tree'.($lastchild||$noparent?' treelast':'').'">';
	}
	echo '<div class="'.($flat||$curdepth==0?'rootitem':'treeitem').'"'.($actionid == $post['post_id']?' id="replied"':'').'>';
?>
		<div class="post<?php if ($actionid == $post['post_id']) echo ' activepost'; if ($post['can_reply']) echo ' hasmenubar'; ?>" id="post<?php echo $post['post_id']; ?>"<?php echo ' data-post_id="',$post['post_id'],'" data-topic_id="',$post['topic_id'],'"'; if ($post['post_id'] == $post['topic_id']) echo ' data-title="',htmlspecialchars($post['title']),'"'; ?>>
			<div class="posthead">
<?php if ($post['authorid'] === 'guest') {?>
				<span class="name guestname"><img src="<?php echo $ntbb->root; ?>theme/guestav.png" width="50" height="50" alt="" /> Guest</span>
<?php } else { ?>
				<a href="<?php echo $ntbb->root,'~',$post['authorid']; ?>" class="name" data-target="push"><?php 
				if ($post['author_avatar']) {
					echo '<span class="avatar">',$post['author_avatar'],'</span>';
				} else {
					echo '<span class="avatar"><img src="',$ntbb->root,'theme/defaultav.png" width="50" height="50" alt="" /></span>';
				} ?><?php echo '<strong>',$post['author'],'</strong>'; ?></a>
<?php } ?>
				<span class="sep">-</span> <?php echo $ntbb->date($post['date']); if ($post['editdate']) echo ' <abbr title="'.$ntbb->date($post['editdate']).'">(edited)</abbr>' ?>
<?php
	if ($topic_mode !== 'edit') {
?>
				<span>
<?php if ($post['can_reply']) { ?>
					<span class="sep">-</span> <a href="topic.php?<?php echo 't='.$topic_id.'&amp;reply='.$post['post_id']; ?>#reply" class="postreply">Reply</a>
<?php } if ($post['can_edit']) { ?>
					<small class="postmenu"><span class="sep">-</span> <a href="topic-<?php echo $topic_id.'-edit-'.$post['post_id']; ?>#edit" class="postedit">Edit</a></small>
<?php } ?>
				</span>
<?php
	}
?>
			</div>
			<div class="message">
				<?php /* echo "Curdepth: $curdepth | Curtree: $curtree | Firstchild: $firstchild<br />"; */ ?>
<?php
	if ($topic_mode !== 'edit') {
?>
				<div class="postcontent">
					<?php echo $post['message_html']; ?>
				</div>
<?php
		if ($post['signature_html']) {
?>
				<div class="sig">
					<span class="sep">-- <br /></span>
					<?php echo $post['signature_html']; ?>
				</div>
<?php
		}
	} else {
?>
				<form method="post" action="<?php echo $ntbb->root,'topic-',$topic_id; ?>-edit-<?php echo $post_id; ?>" class="edit">
					<input type="hidden" name="act" value="editpost" />
					<input type="hidden" name="editpost" value="<?php echo $post_id; ?>" />
					<div class="textboxcontainer"><textarea class="textbox posttextbox" rows="6" cols="50" name="message" autofocus><?php echo htmlspecialchars($ntbb->postSource($post)); ?></textarea></div>
					<button type="submit"><strong>Edit</strong></button>
				</form>
				<input type="hidden" name="messagesrc" value="<?php echo htmlspecialchars(str_replace("\r",'',str_replace("\n",'',$post['messagehtml']))); ?>" />
<?php
	}
?>
			</div>
		</div>
<?php
	echo '</div>';
	if ($topic_mode !== 'view') {
		if (count($topic['posts']) > 1) {
?>
		<ul class="minitree"><li class="tree treelast">
			<div class="treeitem">
				<small><a href="topic.php?t=<?php echo $topic['topic_id']; if ($topic['topic_id'] !== $topic['post_id']) echo '&amp;p='.$topic['post_id']; ?>">View <?php echo count($topic['posts'])-1; ?> replies</a></small>
			</div>
		</li></ul>
<?php
		}
		break;
	}
	$firstchild = false;
}
while ($curdepth > $startdepth) {
	$curdepth--;
	echo '</li></ul>';
}
if ($topic['page'] != $topic['page_count']) {
	echo '<div class="pagenav-down"><a href="',$ntbb->root,'topic-',$topic_id,'-p',($topic['page']+1),'" data-target="replace">Page ',($topic['page']+1),'</a></div>';
}
if ($topic_mode !== 'edit' && $topic['can_reply']) {
	// reply form
?>
	<ul class="faketree replycontainer"><li class="tree treeadd"><div class="treeitem"><form action="<?php echo $ntbb->root,'topic-',$tid; ?>#replied" method="post" class="replyform" data-target="replace">
		<input type="hidden" name="act" value="addreply" /><input type="hidden" name="replyto" value="<?php echo $post_id; ?>" />
		<div class="post activepost">
			<div class="posthead">
<?php if (!$curuser['loggedin']) {?>
				<span class="name guestname"><img src="<?php echo $ntbb->root; ?>theme/guestav.png" width="50" height="50" alt="" /> Guest</span>
<?php } else { ?>
				<a href="<?php echo $ntbb->root,'~',$curuser['userid']; ?>" class="name" data-target="push"><?php 
				if ($curuser['avatar']) {
					echo '<span class="avatar">',$curuser['avatar'],'</span>';
				} else {
					echo '<span class="avatar"><img src="',$ntbb->root,'theme/defaultav.png" width="50" height="50" alt="" /></span>';
				} ?><?php echo '<strong>',$curuser['username'],'</strong>'; ?></a>
<?php } ?>
			</div>
			<div class="message">
				<!--label for="messagebox">Message:</label-->
				<div class="textboxcontainer"><textarea class="textbox posttextbox" rows="6" cols="50" name="message" id="messagebox"></textarea></div>
<?php if (!$curuser['loggedin']) { ?>
					<div class="formrow"><label class="label">Anti-spam question:</label><div class="textboxcontainer"><img src="/sprites/bw/pikachu.png" /><br />What's this?<br /><input type="text" class="textbox" name="captcha" placeholder="Required" size="12" /></div></div>
<?php } ?>
				<button type="submit"><strong>Post</strong></button>
			</div>
		</div>
	</form></div></li></ul></li></ul>
<?php
}
if ($topic['posts'][0]['parentpostid']) {
	echo '</li></ul>';
}
?>
			<input type="hidden" id="tid" value="<?php echo $tid; ?>" />
<?php
?>
		</div>

	</div>
<?php

$ntbb->end();

