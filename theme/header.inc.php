<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php if ($ntbb->pagetitle) echo $ntbb->pagetitle.' - '; ?>Showdown Forums</title>
		<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=9" /><![endif]-->
		<link rel="stylesheet" type="text/css" href="theme/default.css" />

		<!-- Workarounds for IE bugs to display trees correctly. -->
		<!--[if lte IE 6]>
		<style> li.tree { height: 1px; } </style>
		<![endif]-->
		<!--[if IE 7]>
		<style> li.tree { zoom: 1; } </style>
		<![endif]-->
		<script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>
		<script src="js/autoresize-ntbb.jquery.js" type="text/javascript"></script>
		<script src="js/scrollTo-1.4.2.jquery.min.js" type="text/javascript"></script>
		<script src="js/ntbb-global.js" type="text/javascript"></script>
		<script src="js/rangy-core-selectionsaverestore.js" type="text/javascript"></script>
		<script src="js/ntbb-richtexteditor.js" type="text/javascript"></script>
<?php if ($ntbb->ajaxMode) { ?>
		<script src="js/ntbb-panels.js" type="text/javascript"></script>
<?php } ?>
	</head>
	<body>
	<div id="userbar" class="userbar">
<?php
echo $ntbb->getUserbar();
?>
	</div>
	<script>
	<!--
		ntbb_userbar_init();
	//-->
	</script>
	<h1 id="header"><span style="font-variant:small-caps;">Showdown!</span> Forums</h1>
		<script>
		<!--
			var ntbb = null;
			var pagepanel = null;
			var pagepanel2 = null;
<?php if ($ntbb->ajaxMode) { ?>
			if (ajaxSupport())
			{
				ntbb = new NTBB();
				ntbb.htmlBegin();
			}
<?php } ?>
		//-->
		</script>
