<?php

require_once 'lib/ntbb.lib.php';

/*
 * Start rendering
 */

if ($action && $actionsuccess) {
	header('Location: /');
	die();
}

$ntbb->setPageTitle('Log In');
$ntbb->start();

?>
	<div class="pfx-panel">

		<div class="pfx-title titlebar">
			<a href="<?php echo $ntbb->root; ?>" class="pfx-backbutton" data-target="back">&laquo; Home</a>
			<h2 class="pfx-body hasbackbutton">
				Log In
			</h2>
		</div>

		<div class="pfx-body">
<?php
if ($action && !$actionsuccess) echo '<div class="error"><strong>Error:</strong> '.$actionerror.'</div>';

if ($curuser['loggedin']) {
?>
			<div class="padtext">
				<p>
					<em>You are already logged in.</em>
				</p>
			</div>
<?php
} else {
?>
			<form action="<?php echo $ntbb->root; ?>login" method="post" class="form">
				<input type="hidden" name="act" value="login" />
				<div class="formarea">
					<div class="formrow">
						<label class="label" for="username">Username: </label> <input id="username" name="username" type="text" size="30" class="textbox"<?php echo (@$_REQUEST['username'])?' value="'.htmlspecialchars($_REQUEST['username']).'"':' autofocus="autofocus"' ?> />
					</div>
					<div class="formrow">
						<label class="label" for="password">Password: </label> <input id="password" name="password" type="password" size="30" class="textbox"<?php echo (@$_REQUEST['username'])?' autofocus="autofocus"':'' ?> />
					</div>
					<div class="formrow fullwidth">
						<button type="submit"><strong>Log In</strong></button>
					</div>
				</div>
			</form>
<?php
}
?>
		</div>
	</div>
<?php
$ntbb->end();
?>