function ntbb_topic_init(panel)
{

	if (!panel)
	{
		panel = new Panel();
		pagetype = 'topic';
	}
	P = panel.panelcontent; // the element to start searches from

	var postReply = function(e)
	{
		e.preventDefault();
		var message = '';
		if (rte)
		{
			message = rte.val();
		}
		else
		{
			message = $(this).find('textarea[name=message]').val();
		}
		if (!message.trim())
		{
			alert('Please enter a message.');
			return;
		}
		$(e.target).find('button[type=submit]').html('Posting...')[0].disabled = true;
		$.ajax({
			type: 'POST',
			url: 'topic.php?'+panel.findID('tid').val()+'&output=html',
			data: {
				act: 'addreply',
				message: message,
				replyto: $(this).find('input.replyto').val(),
				captcha: $(this).find('input[name=captcha]').val()
			},
			success: function(data){
				//alert(data);
				panel.content(data);
				{
					var origscroll = panel.scrollTop();
					var curscroll = origscroll;
					var thispos = panel.findID('replied').offset().top;
					var thisheight = panel.findID('replied').height();
					var windowheight = panel.height();
					if (curscroll < thispos + thisheight - windowheight + 20)
					{
						curscroll = thispos + thisheight - windowheight + 20;
					}
					if (curscroll > thispos - 20)
					{
						curscroll = thispos - 20;
					}
					if (curscroll != origscroll)
					{
						panel.scrollTo(curscroll,400);
					}
				}
			},
			error: function(data){
				alert('error');
				$(e.target).find('button[type=submit]').html('<strong>Post</strong>')[0].disabled = false;
			},
			dataType: 'html'
		});
	};
	var postEdit = function(e)
	{
		e.preventDefault();
		var message = '';
		if (rte)
		{
			message = rte.val();
		}
		else
		{
			message = $(this).find('textarea[name=message]').val();
		}
		if (!message.trim())
		{
			alert('Please enter a message.');
			return;
		}
		$(e.target).find('button[type=submit]').html('Editing...')[0].disabled = true;
		$.ajax({
			type: 'POST',
			url: 'topic.php?'+panel.findID('tid').val()+'&output=html',
			data: {
				act: 'editpost',
				editpost: $(this).find('input[name=editpost]').val(),
				message: message,
				title: panel.findID('title').val(),
				captcha: $(this).find('input[name=captcha]').val()
			},
			success: function(data){
				//alert(data);
				panel.content(data);
			},
			error: function(data){
				alert('error');
				$(e.target).find('button[type=submit]').html('<strong>Edit</strong>')[0].disabled = false;
			},
			dataType: 'html'
		});
	};
	var postNewTopic = function(e)
	{
		e.preventDefault();
		var message = '';
		var title = $(this).find('input[name=title]').val();
		if (rte)
		{
			message = rte.val();
		}
		else
		{
			message = $(this).find('textarea[name=message]').val();
		}
		if (!title.trim())
		{
			alert('Please enter a title.');
			return;
		}
		else if (!message.trim())
		{
			alert('Please enter a message.');
			return;
		}
		$(e.target).find('button[type=submit]').html('Posting...')[0].disabled = true;
		$.ajax({
			type: 'POST',
			url: 'newtopic.php?output=html',
			data: {
				act: 'addtopic',
				message: message,
				title: title,
				f: $(this).find('input[name=f]').val(),
				captcha: $(this).find('input[name=captcha]').val()
			},
			success: function(data){
				//alert(data);
				panel.content(data);
				{
					var origscroll = panel.scrollTop();
					var curscroll = origscroll;
					var thispos = panel.findID('replied').offset().top;
					var thisheight = panel.findID('replied').height();
					var windowheight = panel.height();
					if (curscroll < thispos + thisheight - windowheight + 20)
					{
						curscroll = thispos + thisheight - windowheight + 20;
					}
					if (curscroll > thispos - 20)
					{
						curscroll = thispos - 20;
					}
					if (curscroll != origscroll)
					{
						panel.scrollTo(curscroll,400);
					}
				}
			},
			error: function(data){
				alert('error');
				$(e.target).find('button[type=submit]').html('<strong>Post</strong>')[0].disabled = false;
			},
			dataType: 'html'
		});
	};
	
	var removeCurReply = function(e)
	{
		if (e)
		{
			e.preventDefault();
		}
		panel.findID('edittitlearea').remove();
		curreply = panel.findID('curreply');
		if (curreply.children('.treelast').length)
		{
			curreply.animate({opacity:'toggle', height:'toggle'},{complete: function(){curreply.remove()}});
		}
		else
		{
			curreply.find('.treeitem').animate({opacity:'toggle', height:'toggle'},{complete: function(){curreply.remove()}});
		}
	};
	var fastRemoveCurReply = function(e)
	{
		if (e)
		{
			e.preventDefault();
		}
		panel.findID('edittitlearea').remove();
		panel.findID('curreply').remove();
		var curedit = panel.findID('curedit');
		if (curedit.length)
		{
			curedit.parent().find('.postcontent').show();
			curedit.remove();
		}
	};
	
	var addReply = function(e)
	{
		e.preventDefault();
		e.stopPropagation();
		var parentli = $(this).closest('li');
		if (parentli.children('ul.curreply').length)
		{
			removeCurReply();
			return;
		}
		fastRemoveCurReply();
		var haschildren = (parentli.find('ul.tree').length);
		var cur = $(this).closest('.treeitem, .rootitem').after('<ul class="faketree curreply" id="'+panel.prefix+'curreply" style="display:none"><li class="tree'+(haschildren?'':'treelast')+'">'+panel.findID('reply').children().html().replace(/"messagebox"/g,"messagebox-2")+'</li></ul>');
		var curreply = panel.findID('curreply');
		/* curreply.find('textarea.posttextbox').autoResize({
			animateDuration : 200,
			extraSpace : 40
		}); */
		// rte testing
		rte = new RichTextEditor(curreply.find('textarea.posttextbox'));
		curreply.find('.message').append('<input type="button" class="cancelbutton" value="Cancel" />');
		curreply.find('form.replyform').append('<input type="hidden" name="replyto" class="replyto" value="'+(this.getAttribute("data-postid"))+'" />');
		if (haschildren)
		{
			curreply.find('.treeitem').hide();
			curreply.show();
			curreply.find('.treeitem').animate({opacity:'toggle', height:'toggle'});
		}
		else
		{
			curreply.animate({opacity:'toggle', height:'toggle'});
		}
		curreply.find('form.replyform').submit(postReply);
		curreply.find('input.cancelbutton').click(removeCurReply);
		if (rte)
		{
			rte.focus();
		}
		else
		{
			curreply.find('.posttextbox').focus();
		}
		{
			var origscroll = panel.scrollTop();
			var curscroll = origscroll;
			var thispos = $(this).closest('li').offset().top;
			var thisheight = $(this).closest('.treeitem, .rootitem').height();
			var replyheight = panel.findID('curreply').find('form').height()+30;
			var windowheight = panel.height();
			//alert(''+curscroll+'] '+thispos+' '+thisheight+' '+replyheight+' ['+windowheight+' ['+(thispos + thisheight + replyheight - windowheight));
			//curscroll += Math.floor(replyheight / 2);
			if (curscroll > thispos - 20)
			{
				curscroll = thispos - 20;
			}
			if (curscroll < thispos + thisheight + replyheight - windowheight + 20)
			{
				curscroll = thispos + thisheight + replyheight - windowheight + 20;
			}
			if (curscroll > thispos + thisheight - 20)
			{
				curscroll = thispos + thisheight - 20;
			}
			if (curscroll != origscroll)
			{
				panel.scrollTo(curscroll,400);
			}
		}
	};
	var addEdit = function(e)
	{
		e.preventDefault();
		e.stopPropagation();
		var postid = $(this).attr('data-postid');
		var post = panel.findID('post'+postid);
		var topicid = post.attr('data-topicid');
		if (post.find('.editbutton').length)
		{
			fastRemoveCurReply();
			return;
		}
		fastRemoveCurReply();
		var postcontent = post.find('.postcontent');
		postcontent.before(
			'<form id="'+panel.prefix+'curedit" method="post" action="topic.php?t='+panel.findID('tid').val()+'&amp;edit='+postid+'"><input type="hidden" name="act" value="editpost" /><input type="hidden" name="editpost" value="'+postid+'" /><div class="textboxcontainer"><textarea class="textbox posttextbox" rows="6" cols="50" name="message" id="'+panel.prefix+'messagebox-e">&lt;&lt;html&gt;&gt;'+postcontent.html()+'</textarea></div><button type="submit" class="editbutton"><strong>Edit</strong></button><input type="button" class="cancelbutton" value="Cancel" /></form>'
		);
		if (postid === topicid)
		{
			panel.findID('edittitlearea').remove();
			panel.panelcontent.prepend('<div class="formarea" id="'+panel.prefix+'edittitlearea"><div class="formrow fullwidth"><label class="label" for="'+panel.prefix+'title">Title: </label> <input id="'+panel.prefix+'title" name="title" type="text" size="50" class="textbox" value="'+post.attr('data-title')+'"></div></div>');
		}
		rte = new RichTextEditor(panel.findID('messagebox-e'),postcontent.html());
		rte.focus();
		postcontent.hide();
		panel.findID('curedit').submit(postEdit);
		post.find('input.cancelbutton').click(fastRemoveCurReply);
	};

	panel.findID('reply').hide();
	var newtopic = panel.findID('newtopic');
	if (newtopic.length)
	{
		/* newtopic.find('textarea.posttextbox').autoResize({
			animateDuration : 200,
			extraSpace : 40
		}); */
		rte = new RichTextEditor(newtopic.find('textarea.posttextbox'));
		panel.findID('title').focus();
		panel.find('form.replyform').submit(postNewTopic);
	}
	var editmessage = panel.findID('messagebox-e');
	if (editmessage.length)
	{
		editmessage.autoResize({
			animateDuration : 200,
			extraSpace : 40
		});
		editmessage.focus();
	}
	panel.find('.postreply').click(addReply);
	panel.find('.postedit').click(addEdit);
	panel.find('ul.topicwrapper ul.tree').parent('li').prepend('<span onclick="toggle(event)" class="expcol col"></span>');
	
	return panel;
}
