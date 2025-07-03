<?php

require_once 'lib/ntbb.lib.php';

if (!@$userid) $userid = $users->userid(@$_REQUEST['u']);

$user = $users->getUser($userid);

/*
 * Start rendering
 */

$ntbb->setPageTitle('Settings');
$ntbb->start();

?>

	<div class="pfx-panel">

		<div class="pfx-title titlebar">
<?php
if ($curuser['userid'] !== $user['userid']) {
?>
			<a href="/~<?php echo $user['userid'] ?>" class="pfx-backbutton" data-target="back">&laquo; User</a>
<?php
} else {
?>
			<a href="/" class="pfx-backbutton" data-target="back">&laquo; Home</a>
<?php
}
?>
			<h2 class="pfx-body hasbackbutton">
				Settings
			</h2>
		</div>

		<div class="pfx-body">
<?php

if ($action && !$actionsuccess) echo '<p class="error"><strong>Error:</strong> '.$actionerror.'</p>';

if (!$user['username']) {
?>
			<div class="padtext">
				<p>This user doesn't exist</p>
			</div>
<?php
} else if ($curuser['loggedin']) {
	if ($action && $actionsuccess) echo '<div class="success">User modified!</div>';
?>
			<form action="" method="post" class="form" id="passwordform formarea" data-target="replace">
				<input type="hidden" name="act" value="modifyuser" /><?php $users->csrfData(); ?>
				<input type="hidden" name="userid" value="<?php echo htmlspecialchars($user['userid']); ?>" />
				<div class="formarea">
					<div class="formrow">
						<em class="label"><label>Username: </label></em>
						<strong><?php echo htmlspecialchars($user['username']); ?></strong>
					</div>
<?php
	if ($curuser['userid'] === $user['userid']) {
?>
					<div class="formrow">
						<label class="label">Old password:
						<input name="password" type="password" size="20" class="textbox" /></label>
					</div>
<?php
	}
	if ($curuser['group'] == 2) {
?>
					<div class="formrow">
						<em class="label"><label>IP: </label></em>
						<a href="http://www.geoiptool.com/en/?IP=<?php echo $user['ip'] ?>"><?php echo $user['ip'] ?></a>
					</div>
					<div class="formrow">
						<label class="label">Group:
						<select name="group" class="textbox">
<?php
		foreach ($ntbb_cache['groups'] as $i => $group) {
			if (!$i) continue;
?>
							<option value="<?php echo $i ?>"<?php if ($user['group'] == $i) echo ' selected="selected"'; ?>><?php echo $group['name']; ?></option>
<?php
		}
?>
						</select></label>
					</div>
<?php
	}
	if ($curuser['userid'] === $user['userid']) {
?>
					<div class="formrow">
						<label class="label">New password:
						<input name="newpassword" type="password" size="20" class="textbox" /></label>
					</div>
					<div class="formrow">
						<label class="label">Confirm new password:
						<input name="cnewpassword" type="password" size="20" class="textbox" /></label>
					</div>
<?php
	}
?>
					<div class="buttonrow">
						<button type="submit"><strong>Modify user</strong></button>
					</div>
				</div>
			</form>
<?php
	if ($curuser['userid'] !== $user['userid'] && $curuser['group'] == 2) {
?>
			<form action="" method="post" class="form" id="passwordresetform" data-target="replace">
				<input type="hidden" name="act" value="resetpasslink" /><?php $users->csrfData(); ?>
				<input type="hidden" name="userid" value="<?php echo htmlspecialchars($user['userid']); ?>" />
				<div class="formarea">
<?php
		if ($act === 'resetpasslink' && $actionsuccess) {
?>
					<p>
						Success! Please use this link:
					</p>
					<p>
						<code>http://pokemonshowdown.com/resetpassword/<?php echo $actionsuccess; ?></code>
					</p>
<?php
		}
?>
					<div class="buttonrow">
						<button type="submit">Create password reset link</button>
					</div>
				</div>
			</form>
<?php
	}
} else {
?>
			<div class="padtext">
				<p>
					You're not logged in.
				</p>
			</div>
<?php
}
?>
		</div>

	</div>

<?php

$ntbb->end();

?>