	<p class="footer" id="footer">
		&copy; Guangcong Luo 2006-2012
<?php if ($this->ajaxMode) { ?>| <a href="forum.php">Turn off panels</a><?php } else { ?>| <a href="./"><strong>Turn on panels</strong></a><?php } ?>
	</p>
<?php if ($this->ajaxMode) { ?>
	<script src="js/ntbb-topic.js"></script>
	<script src="js/ntbb-forum.js"></script>
	<script src="js/ntbb-welcome.js"></script>
	<script src="js/ntbb-settings.js"></script>
		<script>
		<!--
			if (ntbb)
			{
				ntbb.htmlEnd();
			}
			else
			{
				pagepanel = ntbb_forum_init();
				pagepanel2 = ntbb_welcome_init();
			}
		//-->
		</script>
<?php } else if ($this->pagetype == 'forum') { ?>
	<script src="js/ntbb-forum.js"></script>
	<script>
	<!--
		pagepanel = ntbb_forum_init();
	//-->
	</script>
<?php } else if ($this->pagetype == 'topic') { ?>
	<script src="js/ntbb-topic.js"></script>
	<script>
	<!--
		pagepanel = ntbb_topic_init();
	//-->
	</script>
<?php } else if ($this->pagetype == 'settings') { ?>
	<script src="js/ntbb-settings.js"></script>
	<script>
	<!--
		pagepanel = ntbb_settings_init();
	//-->
	</script>
<?php } else if ($this->pagetype == 'welcome') { ?>
	<script src="js/ntbb-welcome.js"></script>
	<script>
	<!--
		pagepanel = ntbb_welcome_init();
	//-->
	</script>
<?php } else if ($this->pagetype == 'all') { ?>
	<script src="js/ntbb-forum.js"></script>
	<script src="js/ntbb-welcome.js"></script>
	<script>
	<!--
		pagepanel = ntbb_forum_init();
		pagepanel2 = ntbb_welcome_init();
	//-->
	</script>
<?php } ?>
	</body>
</html>
