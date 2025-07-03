<?php

require_once 'lib/ntbb.lib.php';

$forum = $ntbb->getforum(0);

/*
 * Start rendering
 */

$ntbb->setPageTitle('Administrate Forums');
$ntbb->setPageType('none');
$ntbb->startRender();

if ($action && !$actionsuccess) echo '<strong>error</strong>';

if ($ntbb->output == 'normal')
{
?>
		<div class="titlebar admin-titlebar" id="titlebar">
			<a href="./" class="backbutton">&laquo; Back</a>
			<h2 class="title hasbackbutton">
				Administrate forums
			</h2>
		</div>
		<div id="pagewrapper">
<?php
}
if ($forum['subforums'])
{
	$startdepth = $forum['depth'];
	$curdepth = $startdepth;
?>
			<form action="admin-forum.php" method="post"><div class="forumlist<?php if ($curdepth != 0) echo ' subforumlist' ?>">
<?php
	foreach ($forum['subforums'] as $subforum)
	{
		$lastchild = $subforum['lastchild'];
		echo '			';
		while ($subforum['depth'] < $curdepth)
		{
			$curdepth--;
			echo '</li></ul>';
		}
		if ($subforum['depth'] > $curdepth)
		{
			$curdepth++;
			echo '<ul class="'.($curdepth==1?'treeroot':'tree').'"><li class="tree'.($lastchild?' treelast':'').'">';
			$firstchild = true;
		}
		if (!$firstchild)
		{
			echo '</li><li class="tree'.($lastchild?' treelast':'').'">';
		}
		echo '<div class="'.($curdepth==1?'rootitem':'treeitem').'">';
		
		// insert node here
		if ($subforum['type'] == 'forum')
		{
			if ($forumid)
			{
?>
				<div class="forum">
					<div class="formarea">
						<div class="formrow fullwidth">
							<label class="label" for="title">Title: </label> <input id="title" name="title" type="text" size="50" class="textbox" value="<?php if (@$_REQUEST['title']) echo htmlspecialchars($_REQUEST['title']); ?>" />
						</div>
						<div class="formrow">
							<button type="submit" value="admin-editforum"><strong>Edit</strong></button> <button name="actbtn" value="admin-go-adminforum">Cancel</button>
						</div>
					</div>
				</form></div>
<?php
			}
			else
			{
?>

				<div class="forum">
					<span class="controls"><button name="act" value="edit-<?php echo $subforum['forumid']; ?>">Edit</button></span>
					<strong><?php echo $subforum['title']; ?></strong>
					<small><?php echo $subforum['desc']; ?></small>
				</div>
<?php
			}
		}
		else
		{
?>

				<div class="category">
					<span class="controls"><button name="act" value="edit-<?php echo $subforum['forumid']; ?>">Edit</button></span>
					<strong><?php echo $subforum['title']; ?></strong>
				</div>
<?php
		}
		
		echo '</div>';
		$firstchild = false;
	}
	while ($curdepth > $startdepth)
	{
		$curdepth--;
		echo '</li></ul>';
	}
?>
			</div></form>
<?php
}
?>
		</div>
<?php

$ntbb->endRender();

