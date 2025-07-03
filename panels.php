<?php

require_once 'lib/ntbb.lib.php';

if ($_REQUEST['_escaped_fragment_'])
{
	// get ntbb.lib.php first, for the magic quotes fix
	header('Location: '.$_REQUEST['_escaped_fragment_']));
	die();
}

/*
 * Start rendering
 */

$ntbb->ajaxMode = true;
$ntbb->setPageTitle('');
$ntbb->setPageType('all');
$ntbb->startRender();

include 'forum.php';

?>
		<div class="titlebar" id="welcometitle">
			<h2 class="title">
				Welcome
			</h2>
		</div>
		<div id="welcome">
			<p>
				Welcome.
			</p>
		</div>
<?php

$ntbb->endRender();

