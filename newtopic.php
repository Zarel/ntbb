<?php

require_once 'lib/ntbb.lib.php';

if (!$forumid) $forumid = intval(@$_REQUEST['f']);

$topicdata = '<input type="hidden" name="f" value="'.$forumid.'" />';
$forum = $ntbb->getForumData($forumid);

/*
 * Start rendering
 */

if ($action && $actionsuccess) {
	header('Location: topic-'.$actionid.($ntbb->output == 'normal'?'':'&output='.$ntbb->output.'&acted=addtopic'));
	die();
}

$ntbb->setPageTitle('New Topic');
$ntbb->start();

?>
	<div class="pfx-panel">

		<div class="pfx-title titlebar">
			<a href="<?php echo $ntbb->root,'forum-',$forumid; ?>" class="pfx-backbutton" data-target="back">&laquo; Forum</a>
			<h2 class="pfx-body hasbackbutton">
				New Topic
			</h2>
		</div>

		<div class="pfx-body">
<?php
if ($action && !$actionsuccess) echo '<div class="error"><strong>Error:</strong> '.$actionerror.' '.$psdb->error().'</div>';

if (!$ntbb->canPost($forum)) {
?>
			<div class="padtext">
				<p>
					<em>You do not have permission to post a topic here.<?php if (!$curuser['loggedin']) { ?> Please log in.<?php } ?></em>
				</p>
			</div>
<?php
} else {
?>
		<form action="<?php echo $ntbb->root,'newtopic-',$forumid; ?>" method="post" class="newtopicform" data-target="replace">
		<div class="formarea"><div class="formrow fullwidth">
			<label class="label" for="title">Title: <input name="title" type="text" size="50" class="textbox" value="<?php if (@$_REQUEST['title']) echo htmlspecialchars($_REQUEST['title']); ?>" autofocus /></label>
		</div></div>
		<ul class="treeroot newtopic"><li class="tree"><div class="rootitem">
			<input type="hidden" name="act" value="addtopic" /><?php echo $topicdata; ?>
			<div class="post activepost">
				<div class="posthead">
<?php if ($curuser['userid'] === 'guest') { ?>
					<span class="name guestname"><span class="avatar"><img src="<?php echo $ntbb->root; ?>theme/guestav.png" width="50" height="50" /></span> Guest</span>
<?php } else { ?>
					<a href="user.php?u=<?php echo $curuser['userid']; ?>" class="name link-rightpanel"><span class="avatar"><img src="<?php echo $ntbb->root; ?>theme/defaultav.png" width="50" height="50" alt="" /></span> <?php echo $curuser['username']; ?></a>
<?php } ?>
				</div>
				<div class="message">
					<div class="textboxcontainer"><textarea class="textbox posttextbox" rows="6" cols="50" name="message"><?php if (@$_REQUEST['message']) echo htmlspecialchars($_REQUEST['message']); ?></textarea></div>
<?php if (!$curuser['loggedin']) { ?>
					<div class="formrow"><label class="label">Anti-spam question:</label><div class="textboxcontainer"><img src="/sprites/bw/pikachu.png" /><br />What's this?<br /><input type="text" class="textbox" name="captcha" placeholder="Required" size="12" /></div></div>
<?php } ?>
					<button type="submit"><strong>Post</strong></button>
				</div>
			</div>
		</div></li></ul></form>
<?php
}
?>
		</div>

	</div>
<?php

$ntbb->end();

