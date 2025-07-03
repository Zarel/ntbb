function ntbb_forum_init(panel)
{
	if (!panel)
	{
		panel = new Panel();
		pagetype = 'forum';
	}
	panel.find('div.forumlist ul').parent('li').prepend('<span onclick="toggle(event)" class="expcol col"></span>');
	
	return panel;
}
