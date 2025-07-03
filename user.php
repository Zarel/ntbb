<?php

require_once 'lib/ntbb.lib.php';

if (empty($userid)) $userid = $users->userid(@$_REQUEST['u']);

$user = $users->getUser($userid);

/*
 * Start rendering
 */

$ntbb->setPageTitle(htmlspecialchars($user['username']));
$ntbb->start();

?>
	<div class="pfx-panel">

		<div class="pfx-title titlebar">
			<a href="<?php echo $ntbb->root; ?>" class="pfx-backbutton" data-target="back">&laquo; Home</a>
			<h2 class="pfx-body hasbackbutton">
				<?php if ($user) echo htmlspecialchars($user['username']); else echo $userid; ?>
			</h2>
		</div>

		<div class="pfx-body">
<?php
if ($action && !$actionsuccess) echo '<div class="error"><strong>Error:</strong> '.$actionerror.'</div>';
?>
			<div class="padtext">
<?php
if (!$user) {
?>
				<p>
					User not found.
				</p>
<?php
} else {
	$groupName = $users->getGroupName($user);
	if ($groupName) {
		$groupSymbol = $users->getGroupSymbol($user);
		if ($user['userid'] === 'cathy') {
			$groupSymbol = '~';
			$groupName = 'Administrator';
		}
?>
				<p>
					<strong><?php echo $groupSymbol,' ',$groupName; ?></strong>
				</p>
<?php
	}
?>
				<p>
					<em>Joined:</em> <?php echo $ntbb->date($user['registertime']); ?>
				</p>
<?php
	if ($curuser['group'] == 2) {
?>
				<p>
					<a href="<?php echo $ntbb->root,'~',$user['userid'] ?>/settings" data-target="push">[Edit user]</a>
				</p>
<?php
	}
}
?>
			</div>
		</div>

	</div>
<?php

$ntbb->end();

?>
