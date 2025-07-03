<?php

/********************************************************************
 * Header
 ********************************************************************/

function NTBBHeaderTemplate() {
	global $ntbb, $curuser;
?>
<!DOCTYPE html>
<html><head>

	<meta charset="utf-8" />
	<title><?php if ($ntbb->pagetitle) echo $ntbb->pagetitle.' - '; echo $ntbb->name; ?></title>
	<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=10" /><![endif]-->
	<link rel="stylesheet" type="text/css" href="<?php echo $ntbb->root; ?>theme/panels.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo $ntbb->root; ?>theme/rte.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo $ntbb->root; ?>theme/forum.css" />

	<!-- Workarounds for IE bugs to display trees correctly. -->
	<!--[if lte IE 6]><style> li.tree { height: 1px; } </style><![endif]-->
	<!--[if IE 7]><style> li.tree { zoom: 1; } </style><![endif]-->

<!-- Google Analytics -->
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-26211653-1', 'pokemonshowdown.com', {'allowLinker': true});
ga('require', 'linker');

ga('linker:autoLink', ['pokemonshowdown.com', 'play.pokemonshowdown.com', 'replay.pokemonshowdown.com']);
ga('send', 'pageview');

</script>
<!-- End Google Analytics -->
</head><body>

	<div class="pfx-topbar">
		<div id="userbar" class="userbar">
<?php
		if ($curuser['loggedin']) {
			// form must be method="post" for logout button to work correctly
?>
			<form action="<?php echo $ntbb->root; ?>action.php" method="post" style="vertical-align:middle;"><input type="hidden" name="act" value="btn" />
				<a href="<?php echo $ntbb->root,'~',htmlspecialchars($curuser['userid']) ?>" class="name"><img src="<?php echo $ntbb->root; ?>theme/defaultav.png" width="25" height="25" alt="" /> <?php echo htmlspecialchars($curuser['username']) ?></a> <button type="submit" name="actbtn" value="logout">Log Out</button>
			</form>
<?php
		} else {
?>
			<form action="<?php echo $ntbb->root; ?>action.php" method="get"><input type="hidden" name="act" value="btn" />
				<button type="submit" name="actbtn" value="go-login">Log In</button> <button type="submit" name="actbtn" value="go-register">Register</button>
			</form>
<?php
		}
?>
		</div>
		<div class="header">
			<ul class="nav">
				<li><a class="button" href="/"><img src="//play.pokemonshowdown.com/pokemonshowdownbeta.png" alt="Pokemon Showdown! (beta)" /> Home</a></li>
				<li><a class="button" href="/dex/">Pok&eacute;dex</a></li>
				<li><a class="button" href="/replay/">Replays</a></li>
				<li><a class="button" href="/ladder/">Ladder</a></li>
				<li><a class="button cur" href="/forum/">Forum</a></li>
			</ul>
			<ul class="nav nav-play">
				<li><a class="button greenbutton nav-first nav-last" href="//play.pokemonshowdown.com/">Play</a></li>
			</ul>
			<div style="clear:both"></div>
		</div>
	</div>
<?php
}

/********************************************************************
 * Footer
 ********************************************************************/

function NTBBFooterTemplate() {
	global $ntbb, $curuser;
?>
	<p class="footer" id="footer">
		&copy; Guangcong Luo 2006-2012
	</p>

<!--[if lt IE 9]>
	<script src="<?php echo $ntbb->root; ?>js/lib/jquery-1.10.1.min.js"></script>
<![endif]-->
<!--[if gte IE 9]><!-->
	<script src="<?php echo $ntbb->root; ?>js/lib/jquery-2.0.2.min.js"></script>
<!--[endif]-->

	<script src="<?php echo $ntbb->root; ?>js/autoresize-ntbb.jquery.js"></script>
	<script src="<?php echo $ntbb->root; ?>js/rangy-core-selectionsaverestore.js"></script>
	<script src="<?php echo $ntbb->root; ?>js/jquery-richtexteditor.js"></script>
	<script src="<?php echo $ntbb->root; ?>js/underscore.js"></script>
	<script src="<?php echo $ntbb->root; ?>js/backbone.js"></script>
	<script src="<?php echo $ntbb->root; ?>js/panels.js"></script>

	<script src="<?php echo $ntbb->root; ?>js/app.js"></script>
	<script src="<?php echo $ntbb->root; ?>js/app-topic.js"></script>
	<script>
	<!--
		var app = new App({root:'<?php echo $ntbb->root; ?>'});
	//-->
	</script>

</body></html>
<?php
}
