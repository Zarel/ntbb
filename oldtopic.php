<?php

require_once 'lib/ntbb.lib.php';

$topicmode = 'view';
if (!@$topicid) $topicid = intval(@$_REQUEST['t']);
if (!@$postid) $postid = intval(@$_REQUEST['p']);
if (@$_REQUEST['viewpost'])
{
	$postid = @$_REQUEST['viewpost'];
	$topicmode = 'viewpost';
}
if (@$_REQUEST['reply'])
{
	$postid = @$_REQUEST['reply'];
	$topicmode = 'reply';
}
if (@$_REQUEST['edit'])
{
	$postid = @$_REQUEST['edit'];
	$topicmode = 'edit';
}
if (@$_REQUEST['highlight'])
{
	$actionid = intval(@$_REQUEST['highlight']);
}
if (!$postid) $postid = $topicid;

$topicdata = '<input type="hidden" name="'.($postid!=$topicid?'p':'t').'" class="tid" value="'.($postid!=$topicid?$postid:$topicid).'" />';
$tid = 't='.$topicid.($postid!=$topicid?'&amp;'.($topicmode==='view'?'p':$topicmode).'='.$postid:'');

// actions

if ($act==='editpost' && $actionsuccess && $ntbb->output === 'normal')
{
	header('Location: topic.php?t='.$topicid.($ntbb->output == 'normal'?'':'&output='.$ntbb->output.'&acted=editpost')).'#post'.$actionid;
	die();
}

// all

if ($postid!=$topicid)
{
	$topic = $ntbb->getPostTopic($postid);
}
else
{
	$topic = $ntbb->getTopic($topicid);
}
$forum = $ntbb->getForumData($topic['forumid']);

/*
 * Start rendering
 */

$ntbb->setPageTitle($topic['title']);
$ntbb->setPageType('topic');
$ntbb->startRender();

if ($ntbb->output === 'normal')
{
?>
		<div class="titlebar" id="titlebar">
<?php if ($topicmode === 'view' && $postid == $topicid) { ?>
			<a href="forum.php?f=<?php echo $topic['forumid']; ?>" class="backbutton">&larr; Back</a>
			<h2 class="title hasbackbutton">
				<?php echo htmlspecialchars($topic['title']); ?>
			</h2>
<?php } else { ?>
			<a href="topic.php?t=<?php echo $topic['topicid']; ?>" class="backbutton">&larr; Back</a>
			<h2 class="title hasbackbutton">
				<?php
					if ($topicmode === 'reply') echo '<em>Reply to</em> ';
					if ($topicmode === 'edit') echo '<em>Edit</em> ';
				?><?php echo htmlspecialchars($topic['title']); ?>
			</h2>
<?php } ?>
		</div>
		<div id="pagewrapper">
<?php
}
if ($action && !$actionsuccess) echo '<div class="error"><strong>Error:</strong> '.$actionerror.'</div>';
?>
		<ul class="treeroot topicwrapper"><li class="tree">
<?php

$startdepth = strlen($topic['posts'][0]['treeindex'])/8 - 1;

$curtree = substr($topic['posts'][0]['treeindex'],0,$startdepth*8);
$curdepth = $startdepth;

$posttable = array();

if (!$topic['posts']) die('topic not found');

foreach ($topic['posts'] as $i => $post)
{
	$posttable[$post['postid']] = $i;
	if (isset($posttable[$post['parentpostid']]))
	{
		$topic['posts'][$posttable[$post['parentpostid']]]['lastchild'] = $post['postid'];
	}
}

if ($topic['posts'][0]['parentpostid'])
{
	echo '<div class="rootitem"><div class="minipost"><a href="topic.php?t='.$topic['topicid'].'" class="link-curpanel">Entire topic</a></div></div><ul class="tree"><li class="treelast">';
}

$firstchild = true;
foreach ($topic['posts'] as $i => $post)
{
	$depthchange = false;
	$lastchild = (isset($posttable[$post['parentpostid']]) && $topic['posts'][$posttable[$post['parentpostid']]]['lastchild'] == $post['postid']);
	$noparent = !isset($posttable[$post['parentpostid']]);
	
	while ($curtree != substr($post['treeindex'], 0, $curdepth*8))
	{
		$curdepth--; $curtree = substr($curtree, 0, $curdepth*8);
		echo '</li></ul>';
	}
	while ($curdepth < strlen($post['treeindex'])/8 - 1)
	{
		$curtree .= substr($post['treeindex'], $curdepth*8, 8); $curdepth++;
		echo '<ul class="tree" id="tree_'.hexdec(substr($post['treeindex'],$curdepth*8,8)).'"><li class="tree'.($lastchild||$noparent?' treelast':'').'">';
		$firstchild = true;
	}
	
	if (@$_REQUEST['reply'])
	{
		$topic['posts'];
	}
	if (!$firstchild)
	{
		echo '</li><li class="tree'.($lastchild||$noparent?' treelast':'').'">';
	}
	echo '<div class="'.($curdepth==0?'rootitem':'treeitem').'"'.($actionid == $post['postid']?' id="replied"':'').'>';
?>
		<div class="post<?php if ($actionid == $post['postid']) echo ' activepost'; if ($ntbb->canPost($forum)) echo ' hasmenubar'; ?>" id="post<?php echo $post['postid']; ?>"<?php echo ' data-postid="',$post['postid'],'" data-topicid="',$post['topicid'],'"'; if ($post['postid'] == $post['topicid']) echo ' data-title="',htmlspecialchars($post['title']),'"'; ?>>
			<div class="posthead">
<?php if ($post['authorid'] === 'guest') {?>
				<span class="name guestname"><img src="theme/guestav.png" width="50" height="50" alt="" /> Guest</span>
<?php } else {
		$author = $users->getUser($post['authorid']);
		$authorname = $author['username'];
		$authorgroup = '';
		switch($author['group'])
		{
		case 2:
			$authorgroup = '&';
			break;
		case 3:
			$authorgroup = '+';
			break;
		case 4:
			$authorgroup = '%';
			break;
		case 5:
			$authorgroup = '@';
			break;
		}
		if ($authorgroup)
		{
			$authorgroup = '<span class="namegroup">'.$authorgroup.'</span>';
		}
?>
				<a href="user.php?u=<?php echo $post['authorid']; ?>" class="name link-rightpanel"><img src="theme/defaultav.png" width="50" height="50" alt="" /> <?php echo $authorgroup,'<strong>',$authorname,'</strong>'; ?></a>
<?php } ?>
				<span class="sep">-</span> <?php echo $ntbb->date($post['date']); if ($post['editdate']) echo ' <abbr title="'.$ntbb->date($post['editdate']).'">(edited)</abbr>' ?>
<?php
	if ($topicmode !== 'edit')
	{
?>
				<span id="post<?php echo $post['postid']; ?>-menu"><?php if ($ntbb->canPost($forum)) { ?><span class="sep">-</span> <a href="topic.php?<?php echo 't='.$topicid.'&amp;reply='.$post['postid']; ?>#reply" class="postreply" data-postid="<?php echo $post['postid']; ?>">Reply</a> <?php } if ($ntbb->canEdit($post)) { ?><small class="postmenu"><span class="sep">-</span> <a href="topic.php?<?php echo 't='.$topicid.'&amp;edit='.$post['postid']; ?>#edit" class="postedit" data-postid="<?php echo $post['postid']; ?>">Edit</a></small><?php } ?></span>
<?php
	}
?>
			</div>
			<div class="message">
				<?php /* echo "Curdepth: $curdepth | Curtree: $curtree | Firstchild: $firstchild<br />"; */ ?>
<?php
	if ($topicmode !== 'edit')
	{
?>
				<div class="postcontent">
					<?php echo $post['messagehtml']; ?>
				</div>
					<!--div class="sig">
						<span class="sep">-- <br /></span>
						sig
					</div-->
<?php
	}
	else
	{
?>
				<form method="post" action="topic.php?t=<?php echo $topicid; ?>&amp;edit=<?php echo $postid; ?>" id="edit">
					<input type="hidden" name="act" value="editpost" />
					<input type="hidden" name="editpost" value="<?php echo $postid; ?>" />
					<div class="textboxcontainer"><textarea class="textbox posttextbox" rows="6" cols="50" name="message" id="messagebox-e"><?php echo htmlspecialchars($ntbb->postSource($post)); ?></textarea></div>
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
	if ($topicmode !== 'view')
	{
		if (count($topic['posts']) > 1)
		{
?>
		<ul class="minitree"><li class="tree treelast">
			<div class="treeitem">
				<small><a href="topic.php?t=<?php echo $topic['topicid']; if ($topic['topicid'] !== $topic['postid']) echo '&amp;p='.$topic['postid']; ?>">View <?php echo count($topic['posts'])-1; ?> replies</a></small>
			</div>
		</li></ul>
<?php
		}
		break;
	}
	$firstchild = false;
}
while ($curdepth > $startdepth)
{
	$curdepth--;
	echo '</li></ul>';
}
if ($topicmode !== 'edit')
{
	// reply form
?>
	<ul class="faketree" id="reply"><li class="tree treeadd"><div class="treeitem"><form action="topic.php?<?php echo $tid; ?>#replied" method="post" class="replyform">
		<input type="hidden" name="act" value="addreply" /><input type="hidden" name="replyto" value="<?php echo $postid; ?>" />
		<div class="post activepost">
			<div class="posthead">
<?php if ($curuser['userid'] === 'guest') {?>
				<span class="name guestname"><img src="theme/guestav.png" width="50" height="50" alt="" /> Guest</span>
<?php } else { ?>
				<a href="user.php?u=<?php echo $curuser['userid']; ?>" class="name link-rightpanel"><img src="theme/defaultav.png" width="50" height="50" alt="" /> <?php echo $curuser['username']; ?></a>
<?php } ?>
			</div>
			<div class="message">
				<!--label for="messagebox">Message:</label-->
				<div class="textboxcontainer"><textarea class="textbox posttextbox" rows="6" cols="50" name="message" id="messagebox"></textarea></div>
<?php if (!$curuser['loggedin']) { ?>
				<div class="textboxcontainer">What's 1+1? <input type="text" class="textbox" name="captcha" placeholder="Required" size="8" /></div>
<?php } ?>
				<button type="submit"><strong>Post</strong></button>
			</div>
		</div>
	</form></div></li></ul></li></ul>
<?php
}
if ($topic['posts'][0]['parentpostid'])
{
	echo '</li></ul>';
}
?>
			<input type="hidden" id="tid" value="<?php echo $tid; ?>" />
<?php
if ($ntbb->output == 'normal')
{
?>
		</div>
<?php
}

$ntbb->endRender();

