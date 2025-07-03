<?php

require_once 'lib/ntbb.lib.php';

/*
 * Start rendering
 */

$ntbb->setPageTitle('Welcome');
$ntbb->start();

?>
	<div class="pfx-panel">

		<div class="pfx-title titlebar">
			<h2 class="pfx-body">
				Welcome
			</h2>
		</div>

		<div class="pfx-body">
<?php
if ($curuser['loggedin'])
{
?>
			<div class="padtext">
				<p>
					Welcome back, <strong><?php echo htmlspecialchars($curuser['username']); ?></strong>.
				</p>
				<form action="forum.php" method="post" id="logoutform">
					<input type="hidden" name="act" value="logout" />
					<button type="submit">Log Out</button>
				</form>
			</div>
<?php
}
else
{
?>
			<h3>Log in</h3>
<?php
	if ($action && !$actionsuccess) echo '<div class="error"><strong>Error:</strong> '.$actionerror.'</div>';
?>
			<form action="login.php" method="post" class="form" id="loginform">
				<input type="hidden" name="act" value="login" />
				<div class="formarea">
					<div class="formrow">
						<em class="label"><label for="username">Username: </label></em>
						<input id="username" name="username" type="text" size="20" class="textbox" />
					</div>
					<div class="formrow">
						<em class="label"><label for="password">Password: </label></em>
						<input id="password" name="password" type="password" size="20" class="textbox" />
					</div>
					<div class="buttonrow">
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