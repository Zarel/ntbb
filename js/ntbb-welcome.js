function ntbb_welcome_init(panel)
{
	var panelsOn = true;
	if (!panel)
	{
		panel = new Panel($('#welcome'), $('#welcometitle'));
		pagetype = 'welcome';
		panelsOn = false;
	}
	P = panel.panelcontent; // the element to start searches from
	
	if (ntbb)
	{
		panel.panelcontent.append('<p class="footer padtext">'+$('#footer').html()+'</p>');
	}

	var welcomeLogin = function(e)
	{
		e.preventDefault();
		var ousername = panel.findID('username').val();
		var opassword = panel.findID('password').val();
		$('#userbar').html('<em>Logging in...</em>');
		//alert(ousername);
		$.ajax({
			type: 'POST',
			url: 'action.php?output=json',
			data: {
				act: 'login',
				username: ousername,
				password: opassword
			},
			success: function(data){
				$('#userbar').html(data.userbar);
				if (data.actionerror)
				{
					ntbb_userbar_init(false);
					panel.panelcontent.prepend('<p><em>Error: '+data.actionerror+'</em></p>');
					$(e.target).find('button[type=submit]')[0].disabled = false;
				}
				else
				{
					ntbb_userbar_init(true);
				}
			},
			error: function(data){
				alert('error');
			},
			dataType: 'json'
		});
		$(e.target).find('button[type=submit]')[0].disabled = true;
	};
	
	panel.findID('loginform').submit(welcomeLogin);
	panel.findID('logoutform').submit(logout);

	if (!panelsOn)
	{
		panel.findID('username').focus();
	}
	
	//panel.find('ul.topicwrapper ul.tree').parent('li').prepend('<span onclick="toggle(event)" class="expcol col"></span>');
	return panel;
}
