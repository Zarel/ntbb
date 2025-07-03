var panels = false;
var pagepanel = false;
var pagetype = '';

var dynamicPanels = {
	welcome:1, topic:1, forum:1, settings:1
};

function toggle(e)
{
	e.preventDefault();
	that = $(e.target);
	if (that.hasClass('col'))
	{
		that.parent().children('ul.tree').animate({opacity:'toggle', height:'toggle'}, 200);
		that.attr('class', 'expcol exp');
	}
	else
	{
		that.parent().children('ul.tree').animate({opacity:'toggle', height:'toggle'}, 200);
		that.attr('class', 'expcol col');
	}
}

function Panel(panelcontent, titlebar, scrollpanel, panelwrapper, prefix, panelid)
{
	var self = this;

	// defaults for single page
	if (!panelcontent)
	{
		// this is the element whose contents we replace to update a page or panel
		panelcontent = $('#pagewrapper');
	}
	if (!titlebar)
	{
		// the titlebar element for the panel
		titlebar = $('#titlebar');
	}
	if (!scrollpanel)
	{
		// this is what scrolls, if we need to scroll something into view
		scrollpanel = $(window);
	}
	if (!panelwrapper)
	{
		// this is the wrapper element
		panelwrapper = null;
	}
	if (!prefix)
	{
		// we add the page prefix to all IDs, so they don't conflict with other panels
		// the page prefix should be blank unless we have two pages of the same type
		prefix = '';
	}
	if (typeof panelid === 'undefined')
	{
		// 0 is a valid panelid
		panelid = -1;
	}
	this.panelcontent = panelcontent;
	this.titlebar = titlebar;
	this.scrollpanel = scrollpanel;
	this.panelwrapper = panelwrapper;
	this.prefix = prefix;
	this.panelid = panelid; // will be set elsewhere
	
	this.url = '';
	this.loadUrl = function(loc)
	{
		var localthis = this;
		this.url = loc;
		this.title('<em>Loading...</em>');
		this.content('<div class="padtext"><p><em>Loading...</em></p></div>');
		this.selected = null;
		$.ajax({
			type: 'POST',
			url: loc,
			data: {
				output: 'html'
			},
			success: function(data){
				localthis.content(data);
			},
			error: function(data){
				localthis.content('<p>Error</p>');
			},
			dataType: 'text'
		});
	}
	this.refresh = function(selectedloc)
	{
		var localthis = this;
		if (typeof selectedloc === 'undefined')
		{
			selectedloc = '';
			if (this.selected)
			{
				selectedloc = this.selected.attr('href');
			}
		}
		this.panelcontent.prepend('<div class="padtext"><p><em>Reloading...</em></p></div>');
		$.ajax({
			type: 'POST',
			url: this.url,
			data: {
				output: 'html'
			},
			success: function(data){
				localthis.content(data);
				localthis.setSelected(localthis.find('a[href='+selectedloc+']'));
			},
			error: function(data){
				localthis.panelcontent.prepend('<p>Error</p>');
			},
			dataType: 'text'
		});
	}
	
	this.panelType = '';
	this.type = function(panelType)
	{
		if (typeof panelType !== 'undefined')
		{
			var refreshLoc = '';
			var pipeIndex = panelType.indexOf('|')
			if (pipeIndex != -1)
			{
				refreshLoc = panelType.substr(pipeIndex+1);
				panelType = panelType.substr(0,pipeIndex);
			}
			if (refreshLoc === 'ALL' && panelid == 0)
			{
				// logging in or out, refresh every panel
				for (var i=1; i<panels.length; i++)
				{
					if (dynamicPanels[panels[i].type()])
					{
						panels[i].refresh();
					}
				}
			}
			else if (refreshLoc && panelid > 0)
			{
				// posted a new topic, refresh the topic list
				panels[panelid-1].refresh(refreshLoc);
			}
			this.panelType = panelType;
			switch (panelType)
			{
			case 'topic':
				ntbb_topic_init(self);
				break;
			case 'forum':
				ntbb_forum_init(self);
				break;
			case 'welcome':
				ntbb_welcome_init(self);
				break;
			case 'settings':
				ntbb_settings_init(self);
				break;
			default:
				break;
			}
		}
		return this.panelType;
	}
	
	this.selected = null;
	this.setSelected = function(newSelected)
	{
		if (this.selected)
		{
			this.selected.removeClass('cur');
		}
		this.selected = newSelected;
		if (this.selected)
		{
			this.selected.addClass('cur');
		}
	}
	
	this.title = function(newtitle)
	{
		return $('h2',this.titlebar).html(newtitle);
	}
	
	this.content = function(newcontent)
	{
		if (newcontent && this.prefix)
		{
			newcontent = newcontent.replace(/id="/g, 'id="'+this.prefix);
			newcontent = newcontent.replace(/for="/g, 'for="'+this.prefix);
		}
		var returnval = this.panelcontent.html(newcontent);
		if (newcontent.substr(0,6) === '<!-- [')
		{
			var metaindex1 = newcontent.indexOf('] ');
			var metaindex2 = newcontent.indexOf(' -->');
			var newcontenttype = newcontent.substr(6,metaindex1-6);
			var newcontenttitle = newcontent.substr(metaindex1+2, metaindex2-metaindex1-2)
			this.type(newcontenttype);
			this.title(newcontenttitle);
		}
		if (typeof newcontent !== 'undefined')
		{
			if (this.panelid && ntbb)
			{
				ntbb.setupPanel(this.panelid);
			}
		}
		return returnval;
	}
	this.find = function(data)
	{
		return this.panelcontent.find(data);
	}
	this.findID = function(data)
	{
		return this.panelcontent.find('#'+this.prefix+data);
	}
	
	this.scrollTop = function(newscroll)
	{
		return this.scrollpanel.scrollTop(newscroll);
	}
	this.scrollTo = function(newscroll, newtime)
	{
		return this.scrollpanel.scrollTo(newscroll, newtime);
	}
	this.height = function(newheight)
	{
		return this.scrollpanel.height(newheight);
	}
	this.width = function(newwidth)
	{
		if (typeof newwidth === 'undefined')
		{
			return this.panelwrapper.width()+1;
		}
		return this.panelwrapper.width(newwidth-1);
	}
	this.align = function(left, width)
	{
		this.panelwrapper.css('left', left).css('width', width-1);
		return this;
	}
}

function Overlay(content, position)
{
	$('#overlay, #overlay-bg').remove();
	if (!position)
	{
		position = 'top:30px;left:50px';
	}
	$('body').append('<div id="overlay-bg" class="overlay-bg"></div><div id="overlay" class="overlay" style="'+position+'">'+content+'</div>');
	var closeCallback = null;
	
	this.close = function(e)
	{
		if (e)
		{
			e.preventDefault();
		}
		$('#overlay, #overlay-bg').remove();
		if (closeCallback)
		{
			closeCallback();
		}
	}
	this.onClose = function(f) {
		closeCallback = f;
	}
	$('#overlay-ok').click(this.close);
	$('#overlay-bg').click(this.close);
}

var openLogin = function(e, prepend)
{
	if (e)
	{
		e.preventDefault();
	}
	if (!prepend)
	{
		prepend = '';
	}
	var loginWindow = new Overlay('<form action="login.php" method="post" class="form" id="o-loginform"><input type="hidden" name="act" value="login" />'+prepend+'<div class="formrow"><label class="label" for="o-username">Username: </label> <input id="o-username" name="username" type="text" size="30" class="textbox" /></div><div class="formrow"><label class="label" for="o-password">Password: </label> <input id="o-password" name="password" type="password" size="30" class="textbox" /></div><div class="buttonrow"><button type="submit"><strong>Log In</strong></button> <button id="o-cancel">Cancel</button></div></form>', 'top:40px;right:25px');
	loginWindow.onClose(function(){
		$('#userbarlogin')[0].disabled = false;
	})
	$('#userbarlogin')[0].disabled = true;
	$('#o-cancel').click(loginWindow.close);
	$('#o-loginform').submit(function(e){
		e.preventDefault();
		var ousername = $('#o-username').val();
		var opassword = $('#o-password').val();
		loginWindow.close();
		$('#userbar').html('<em>Logging in...</em>');
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
					openLogin(false, '<div class="error"><strong>Error:</strong> '+data.actionerror+'</div>');
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
	});
	$('#o-username').focus();
}
var openRegister = function(e, prepend)
{
	if (e)
	{
		e.preventDefault();
	}
	if (!prepend)
	{
		prepend = '';
	}
	if (window.registerDisabled)
	{
		new Overlay('<form action="register.php" method="post" class="form" id="o-registerform"><p>Registration is closed. You must have a beta key to register.</p><p>Log into the forums with your Showdown account.</p><div class="formarea"><div class="buttonrow"><button id="overlay-ok"><strong>OK</strong></button></div></div></form>', 'top:40px;right:25px');
		return;
	}
	var loginWindow = new Overlay('<form action="register.php" method="post" class="form" id="o-registerform"><input type="hidden" name="act" value="register" />'+prepend+'<div class="formarea"><div class="formrow"><label class="label" for="o-username">Username: <input id="o-username" name="username" type="text" size="30" class="textbox" /></label></div><div class="formrow"><label class="label" for="o-password">Password:  <input id="o-password" name="password" type="password" size="30" class="textbox" /></label></div><div class="formrow"><label class="label" for="o-cpassword">Confirm Password: </label> <input id="o-cpassword" name="cpassword" type="password" size="30" class="textbox" /></div><div class="formrow"><label class="label">Anti-spam question:</label><div class="textboxcontainer">1+1=<input type="text" class="textbox" name="captcha" id="o-captcha" placeholder="Required" size="8" /></div></div><div class="buttonrow"><button type="submit"><strong>Register</strong></button> <button id="o-cancel">Cancel</button></div></div></form>', 'top:40px;right:25px');
	loginWindow.onClose(function(){
		$('#userbarregister')[0].disabled = false;
	})
	$('#userbarregister')[0].disabled = true;
	$('#o-cancel').click(loginWindow.close);
	$('#o-registerform').submit(function(e){
		e.preventDefault();
		var ousername = $('#o-username').val();
		var opassword = $('#o-password').val();
		var ocpassword = $('#o-cpassword').val();
		var ocaptcha = $('#o-captcha').val();
		loginWindow.close();
		$('#userbar').html('<em>Registering...</em>');
		$.ajax({
			type: 'POST',
			url: 'action.php?output=json',
			data: {
				act: 'register',
				username: ousername,
				password: opassword,
				cpassword: ocpassword,
				captcha: ocaptcha
			},
			success: function(data){
				$('#userbar').html(data.userbar);
				if (data.actionerror)
				{
					ntbb_userbar_init(false);
					openRegister(false, '<div class="error"><strong>Error:</strong> '+data.actionerror+'</div>');
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
	});
	$('#o-username').focus();
}

var logout = function(e)
{
	e.preventDefault();
	$('#userbar').html('<em>Logging out...</em>');
	$.ajax({
		type: 'POST',
		url: 'action.php?output=json',
		data: {
			act: 'logout'
		},
		success: function(data){
			$('#userbar').html(data.userbar);
			ntbb_userbar_init(true);
		},
		error: function(data){
			alert('error');
		},
		dataType: 'json'
	});
}
function ntbb_userbar_init(refresh)
{
	$('#userbarlogin').click(openLogin);
	$('#userbarregister').click(openRegister);
	$('#userbarlogout').click(logout);
	if (!refresh)
	{
		return;
	}
	if (panels)
	{
		for (var i=0; i<panels.length; i++)
		{
			if (dynamicPanels[panels[i].type()])
			{
				panels[i].refresh();
			}
		}
	}
	else if (pagepanel)
	{
		if (dynamicPanels[pagetype])
		{
			window.location.reload();
		}
	}
}
