<?php

require_once 'lib/ntbb.lib.php';

$parsed = $ntbb->htmlParse($_REQUEST['blah']);

?>
<form method="post">

<textarea name="blah" rows="30" cols="90"></textarea>
<button type="submit">ok</button>

<pre><?php echo htmlspecialchars($parsed); ?></pre>

<div><?php echo $parsed ?></div>
