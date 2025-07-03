var ntbb = null;
var panels = null;
var leftPanel = null;
var rightPanel = null;
var leftPanelid = 0;
var rightPanelid = 0;

function NTBB()
{
	this.htmlBegin = function()
	{
		document.write('<p id="htmlbegin-loading"><em>Loading...</em></p><div id="hiddenwrapper" style="display:none">');
		$('body').addClass('ntbb-body');
		$('#header').addClass('ntbb-header');
		$('#userbar').addClass('ntbb-userbar');
		$('html').css('overflow','hidden');
	}
	this.htmlEnd = function()
	{
		$('#htmlbegin-loading').remove();
		panels = [];
		document.write('</div>');
		$('#footer').appendTo($('body')).addClass('ntbb-footer');
		$('#footer').css('display','none'); //temp
		var firstpanel = $('<div></div>').append($('#welcometitle').addClass('ntbb-paneltitle')).append($('#welcome')).addClass('ntbb-panel').appendTo($('body'));
		var secondpanel = $('<div></div>').append($('#titlebar').addClass('ntbb-paneltitle')).append($('#pagewrapper')).addClass('ntbb-panel').appendTo($('body'));
		$('body').append('<div class="ntbb-panel" id="ntbb-go-left"><a id="ntbb-go-left-inner" href="#"></a></div>');
		$('body').append('<div class="ntbb-panel" id="ntbb-go-right"><a id="ntbb-go-right-inner" href="#"></a></div>');
		panels[0] = new Panel($('#welcome'),$('#welcometitle'),$('#welcome'),firstpanel, '', 0);
		panels[1] = new Panel($('#pagewrapper'),$('#titlebar'),$('#pagewrapper'),secondpanel, '', 1);
		panels[0].type('welcome');
		panels[1].type('forum');
		leftPanel = panels[0];
		rightPanel = panels[1];
		leftPanelid = 0;
		rightPanelid = 1;
		this.alignPanels();
		$(window).resize(ntbb.resize);
		this.setupPanel(0);
		this.setupPanel(1);
		leftPanel.url = 'welcome.php';
		rightPanel.url = 'forum.php';
	}
	this.alignPanels = function()
	{
		if (leftPanelid == 0)
		{
			leftPanel.align(-1, this.leftPanelWidth()+1);
		}
		else
		{
			leftPanel.align(24, this.leftPanelWidth()-24);
		}
		if (rightPanelid == panels.length-1)
		{
			rightPanel.align(this.leftPanelWidth(),this.rightPanelWidth());
		}
		else
		{
			rightPanel.align(this.leftPanelWidth(),this.rightPanelWidth()-25);
		}
		$('#ntbb-go-left-inner').height($('#ntbb-go-left').height()-1);
		$('#ntbb-go-right-inner').height($('#ntbb-go-right').height()-1);
	}
	this.leftPanelWidth = function()
	{
		return Math.floor($(window).width()/3);
	}
	this.rightPanelWidth = function()
	{
		return $(window).width() - this.leftPanelWidth();
	}
	this.resize = function()
	{
		ntbb.alignPanels();
	}
	this.setRightPanelid = function(panelid)
	{
		leftPanelid = panelid - 1;
		rightPanelid = panelid;
		leftPanel = panels[leftPanelid];
		rightPanel = panels[rightPanelid];
	}
	this.goLeft = function(e)
	{
		if (e)
		{
			e.preventDefault();
			e.stopPropagation();
		}
		if (rightPanelid == 1)
		{
			return; // at end
		}
		ntbb.setRightPanelid(rightPanelid-1);
		var localRightRightPanel = panels[rightPanelid+1];
		var localLeftPanel = leftPanel;
		var localRightPanel = rightPanel;
		localRightRightPanel.panelwrapper.animate(
			{left: $(window).width()-25},
			300,
			function() { localRightRightPanel.panelwrapper.hide(); }
		);
		$('#ntbb-go-right').css('right','auto').css('left',$(window).width()-20).animate(
			{left: $(window).width()-20+localRightRightPanel.width()+5},
			300,
			function() { ntbb.showSpatialNav(); }
		);
		$('#ntbb-go-left').hide();
		leftPanel.align(24-(ntbb.leftPanelWidth()-(rightPanelid == 1?0:25)),ntbb.leftPanelWidth()-1-(rightPanelid == 1?0:25)).panelwrapper.show().animate(
			{left: -1+(rightPanelid == 1?0:25)},
			300,
			function() { ntbb.alignPanels(); }
		);
		rightPanel.panelwrapper.animate(
			{left: ntbb.leftPanelWidth(), width: ntbb.rightPanelWidth()},
			300,
			function() { ntbb.alignPanels(); }
		);
	}
	this.goRight = function(e)
	{
		if (e)
		{
			e.preventDefault();
			e.stopPropagation();
		}
		if (rightPanelid == panels.length-1)
		{
			return; // at end
		}
		ntbb.setRightPanelid(rightPanelid+1);
		var localLeftLeftPanel = panels[leftPanelid-1];
		var localLeftPanel = leftPanel;
		var localRightPanel = rightPanel;
		localLeftLeftPanel.panelwrapper.animate(
			{left: 24-localLeftLeftPanel.width()},
			300,
			function() { localLeftLeftPanel.panelwrapper.hide(); }
		);
		$('#ntbb-go-left').animate(
			{left: 0-localLeftLeftPanel.width()},
			300,
			function() { ntbb.showSpatialNav(); }
		);
		$('#ntbb-go-right').hide();
		leftPanel.panelwrapper.animate(
			{left: 24, width: ntbb.leftPanelWidth()-24},
			300,
			function() { ntbb.alignPanels(); }
		);
		rightPanel.align($(window).width()-25,ntbb.rightPanelWidth()-(rightPanelid == panels.length-1?0:25)).panelwrapper.show().animate(
			{left: ntbb.leftPanelWidth()},
			300,
			function() { ntbb.alignPanels(); }
		);
	}
	this.addPanel = function()
	{
		var newpanelid = panels.length;
		$('body').append('<div id="panel'+newpanelid+'" style="display:none" class="ntbb-panel"><div class="titlebar ntbb-paneltitle" id="paneltitle'+newpanelid+'"><h2 class="title"><em>Loading...</em></h2></div><div id="panelcontent'+newpanelid+'"><div class="padtext"><p><em>Loading...</em></p></div></div></div>');
		panels[newpanelid] = new Panel($('#panelcontent'+newpanelid),$('#paneltitle'+newpanelid),$('#panelcontent'+newpanelid),$('#panel'+newpanelid),'',newpanelid);
		
		// panel transition is slightly different here
		this.setRightPanelid(newpanelid);
		var localLeftLeftPanel = panels[leftPanelid-1];
		var localLeftPanel = leftPanel;
		var localRightPanel = rightPanel;
		localLeftLeftPanel.panelwrapper.animate(
			{left: 24-localLeftLeftPanel.width()},
			300,
			function() { localLeftLeftPanel.panelwrapper.hide(); }
		);
		$('#ntbb-go-left').animate(
			{left: 0-localLeftLeftPanel.width()},
			300,
			function() { ntbb.showSpatialNav(); }
		);
		leftPanel.panelwrapper.animate(
			{left: 24, width: this.leftPanelWidth()-24},
			300,
			function() { localLeftPanel.align(24, ntbb.leftPanelWidth()-24); }
		);
		rightPanel.align($(window).width(),this.rightPanelWidth()).panelwrapper.show().animate(
			{left: this.leftPanelWidth()},
			300,
			function() { localRightPanel.align(ntbb.leftPanelWidth(), ntbb.rightPanelWidth()); }
		);
	}
	this.showSpatialNav = function()
	{
		$('#ntbb-go-left-inner').unbind('click');
		$('#ntbb-go-right-inner').unbind('click');
		$('#ntbb-go-left-inner').click(this.goLeft);
		$('#ntbb-go-right-inner').click(this.goRight);
		if (leftPanelid != 0)
		{
			$('#ntbb-go-left').css('left',0);
			$('#ntbb-go-left').show();
		}
		else
		{
			$('#ntbb-go-left').hide();
		}
		if (rightPanelid != panels.length-1)
		{
			$('#ntbb-go-right').css('left','auto').css('right',0);
			$('#ntbb-go-right').show();
		}
		else
		{
			$('#ntbb-go-right').hide();
		}
	}
	this.removeAfterPanel = function(panelid)
	{
		for (var i=panels.length-1; i>panelid; i--)
		{
			panels[i].panelwrapper.remove();
			panels.pop();
		}
		if (panelid == rightPanelid)
		{
			$('#ntbb-go-right').css('right','auto').css('left',$(window).width()-20).animate(
				{left: $(window).width()},
				300,
				function() { ntbb.showSpatialNav(); }
			);
			rightPanel.panelwrapper.animate(
				{width: ntbb.rightPanelWidth()},
				300,
				function() { ntbb.alignPanels(); }
			);
		}
	}
	this.openInPanel = function(loc, panelid)
	{
		this.removeAfterPanel(panelid);
		if (panels.length <= panelid)
		{
			this.addPanel();
		}
		if (panelid > rightPanelid)
		{
			this.goRight();
		}
		panels[panelid].loadUrl(loc);
	}
	this.processLinkClick = function(e, panelid)
	{
		var target = $(e.target).closest('a');
		if ((e.which == null && e.button != 1) || (e.which != null && e.button != 0))
		{
			return;
		}
		if (target.hasClass('link-rightpanel'))
		{
			e.stopPropagation();
			e.preventDefault();
			target.blur();
			panels[panelid].setSelected(target);
			this.openInPanel(target.attr('href'),panelid+1);
		}
		if (target.hasClass('link-curpanel'))
		{
			e.stopPropagation();
			e.preventDefault();
			target.blur();
			this.openInPanel(target.attr('href'),panelid);
		}
	}
	// this function probably shouldn't be used.
	this.findPanel = function(element)
	{
		var panelElem = element.closest('.ntbb-panel');
		for (var i=0; i<panels.length; i++)
		{
			if (panels[i].panelwrapper[0] == panelElem[0])
			{
				return panels[i];
			}
		}
		return null;
	}
	this.setupPanel = function(panelid)
	{
		var cpanelid = panelid;
		panels[panelid].panelid = panelid;
		panels[panelid].find('a').click(function(e){ntbb.processLinkClick(e,cpanelid)});
	}
}

function ajaxSupport()
{
	var ajax;
	var ajaxSupported = true;
	try {
	    ajax = new XMLHttpRequest();
	}catch (e){
	    try {
	        ajax = new ActiveXObject('Msxml2.XMLHTTP');
	    }catch (e){
	        try {
	            ajax = new ActiveXObject('Microsoft.XMLHTTP');
	        }catch (e){
	            ajaxSupported = false;
	        }
	    }
	}
	return ajaxSupported;
}
