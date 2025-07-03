<?php

require_once 'lib/ntbb.lib.php';

/*
 * Start rendering
 */

$ntbb->setPageTitle('Home');
$ntbb->start();

include 'forum.php';

$ntbb->end();
