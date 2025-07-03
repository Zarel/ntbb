<?php

require_once 'lib/ntbb.lib.php';

/*
 * Start rendering
 */

if ($action && $actionsuccess) {
	header('Location: /');
	die();
}

$ntbb->setPageTitle('Register');
$ntbb->start();

?>
	<div class="pfx-panel">

		<div class="pfx-title titlebar">
			<a href="<?php echo $ntbb->root; ?>" class="pfx-backbutton" data-target="back">&laquo; Home</a>
			<h2 class="pfx-body hasbackbutton">
				Register
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
			<form action="<?php echo $ntbb->root; ?>register" method="post" class="form">
				<input type="hidden" name="act" value="register" />
				<div class="formarea">
					<div class="formrow">
						<label class="label" for="username">Username: </label> <input id="username" name="username" type="text" size="30" class="textbox" />
					</div>
					<div class="formrow">
						<label class="label" for="password">Password: </label> <input id="password" name="password" type="password" size="30" class="textbox" />
					</div>
					<div class="formrow">
						<label class="label" for="cpassword">Confirm Password: </label> <input id="cpassword" name="cpassword" type="password" size="30" class="textbox" />
					</div>
					<div class="formrow"><label class="label">Anti-spam question:</label><div class="textboxcontainer"><img src="/sprites/bw/pikachu.png" /><br />What's this?<br /><input type="text" class="textbox" name="captcha" placeholder="Required" size="12" /></div></div>
					<div class="formrow fullwidth">
						<button type="submit"><strong>Register</strong></button>
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