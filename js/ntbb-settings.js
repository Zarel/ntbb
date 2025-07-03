function ntbb_settings_init(panel)
{

	if (!panel)
	{
		panel = new Panel();
		pagetype = 'settings';
	}
	P = panel.panelcontent; // the element to start searches from
	
	return panel;
}
