(function() {
	'use strict';

	var rte;

	var TopicForumPanel = this.TopicForumPanel = this.ForumPanel.extend({
		minWidth: 800,
		maxWidth: 800,

		events: {
			'click .postreply': 'addReply',
			'click .postedit': 'addEdit',
			'submit .newtopicform': 'postNewTopic',
			'submit .replyform': 'postReply',
			'submit .editform': 'postEdit'
		},

		replyContainer: null,
		updateContent: function() {
			this.$('ul.topicwrapper ul.tree').parent('li').prepend('<span class="expcol col"></span>');
			this.replyContainer = this.$('.replycontainer');
			this.replyContainer.hide();
			var newtopic = this.$('.newtopic');
			if (newtopic.length) {
				newtopic.find('textarea.posttextbox').richTextEditor();
			}
			var editmessage = this.$('.edit');
			if (editmessage.length) {
				editmessage.find('textarea.posttextbox').autoResize({
					animateDuration : 200,
					extraSpace : 40
				});
			}
		},

		postReply: function(e) {
			var $target = $(e.currentTarget);

			// validate captcha
			var captchaEl = $target.find('input[name=captcha]');
			if (captchaEl.length && !captchaEl.val()) {
				alert("Please answer the anti-spam question.");
				e.preventDefault(); e.stopImmediatePropagation();
				captchaEl.focus();
				return;
			}

			// get/validate message
			var messageEl = $target.find('textarea.textbox');
			var messageRTE = messageEl.richTextEditor();
			var message = messageRTE.val();
			if (!message) {
				alert("Please enter a message.");
				e.preventDefault(); e.stopImmediatePropagation();
				messageRTE.focus();
				return;
			}
			messageEl.val(message);

			$target.find('button[type=submit]').html('Posting...')[0].disabled = true;
			return this.handleNavigation(e);
		},
		postEdit: function(e) {
			var $target = $(e.currentTarget);

			// get/validate title
			var titleEl = this.$('.edittitlearea input[name=title]');
			if (titleEl.length) {
				var titleVal = titleEl.val();
				if (!titleVal) {
					alert("Please enter a title.");
					e.preventDefault(); e.stopImmediatePropagation();
					titleEl.focus();
					return;
				}
				$target.find('input[name=title]').val(titleVal);
			}

			// get/validate message
			var messageEl = $target.find('textarea.textbox');
			var messageRTE = messageEl.richTextEditor();
			var message = messageRTE.val();
			if (!message) {
				alert("Please enter a message.");
				e.preventDefault(); e.stopImmediatePropagation();
				messageRTE.focus();
				return;
			}
			messageEl.val(message);

			$target.find('button[type=submit]').html('Editing...')[0].disabled = true;
			return this.handleNavigation(e);
		},
		postNewTopic: function(e) {
			var $target = $(e.currentTarget);

			// validate title
			var titleEl = $target.find('input[name=title]');
			if (!titleEl.val()) {
				alert("Please enter a title.");
				e.preventDefault(); e.stopImmediatePropagation();
				titleEl.focus();
				return;
			}

			// validate captcha
			var captchaEl = $target.find('input[name=captcha]');
			if (captchaEl.length && !captchaEl.val()) {
				alert("Please answer the anti-spam question.");
				e.preventDefault(); e.stopImmediatePropagation();
				captchaEl.focus();
				return;
			}

			// get/validate message
			var messageEl = $target.find('textarea.textbox');
			var messageRTE = messageEl.richTextEditor();
			var message = messageRTE.val();
			if (!message) {
				alert("Please enter a message.");
				e.preventDefault(); e.stopImmediatePropagation();
				messageRTE.focus();
				return;
			}
			messageEl.val(message);

			$target.find('button[type=submit]').html('Posting...')[0].disabled = true;
			return this.handleNavigation(e);
		},

		removeCurReply: function(e) {
			if (e) {
				e.preventDefault();
				e.stopPropagation();
			}
			this.$('.edittitlearea').remove();
			var curreply = this.$('ul.curreply');
			if (curreply.children('.treelast').length) {
				curreply.animate({opacity:'toggle', height:'toggle'},{complete: function(){curreply.remove()}});
			} else {
				curreply.find('.treeitem').animate({opacity:'toggle', height:'toggle'},{complete: function(){curreply.remove()}});
			}
		},
		fastRemoveCurReply: function(e) {
			if (e) {
				e.preventDefault();
				e.stopPropagation();
			}
			this.$('.edittitlearea').remove();
			this.$('ul.curreply').remove();
			var editform = this.$('.editform');
			if (editform.length) {
				editform.parent().find('.postcontent').show();
				editform.remove();
			}
		},

		addReply: function(e) {
			e.preventDefault();
			e.stopPropagation();
			var post = $(e.currentTarget).closest('div.post');
			var postid = post.data('postid');

			var parentli = post.closest('li');
			if (parentli.children('ul.curreply').length) {
				this.removeCurReply();
				return;
			}
			this.fastRemoveCurReply();

			var hasChildren = (parentli.find('ul.tree').length);
			var cur = $('<ul class="faketree curreply" style="display:none"><li class="tree'+(hasChildren?'':'treelast')+'">'+this.replyContainer.children().html()+'</li></ul>');
			post.closest('.treeitem, .rootitem').after(cur);

			// rte
			// Must come before the opacity toggle so the animation has the correct ending height
			cur.find('textarea.posttextbox').richTextEditor();

			cur.find('.message').append('<button data-action="removeCurReply">Cancel</button>');
			cur.find('form.replyform').append('<input type="hidden" name="replyto" class="replyto" value="'+postid+'" />');
			if (hasChildren) {
				cur.find('.treeitem').hide();
				cur.show();
				cur.find('.treeitem').animate({opacity:'toggle', height:'toggle'});
			} else {
				cur.animate({opacity:'toggle', height:'toggle'});
			}

			// focus rte
			cur.find('textarea.posttextbox').richTextEditor().focus();
			/* {
				var origscroll = this.$el.scrollTop();
				var curscroll = origscroll;
				var thispos = $(e.currentTarget).closest('li').offset().top;
				var thisheight = $(e.currentTarget).closest('.treeitem, .rootitem').height();
				var replyheight = cur.find('form').height()+30;
				var windowheight = this.$el.height();
				//alert(''+curscroll+'] '+thispos+' '+thisheight+' '+replyheight+' ['+windowheight+' ['+(thispos + thisheight + replyheight - windowheight));
				//curscroll += Math.floor(replyheight / 2);
				if (curscroll > thispos - 20) {
					curscroll = thispos - 20;
				}
				if (curscroll < thispos + thisheight + replyheight - windowheight + 20) {
					curscroll = thispos + thisheight + replyheight - windowheight + 20;
				}
				if (curscroll > thispos + thisheight - 20) {
					curscroll = thispos + thisheight - 20;
				}
				if (curscroll != origscroll) {
					this.$el.scrollTo(curscroll,400);
				}
			} */
			/* {
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
			} */
		},
		addEdit: function(e) {
			e.preventDefault();
			e.stopPropagation();
			var post = $(e.currentTarget).closest('div.post');
			var postid = post.data('postid');
			var topicid = post.data('topicid');

			if (post.find('.editbutton').length) {
				this.fastRemoveCurReply();
				return;
			}
			this.fastRemoveCurReply();

			var postcontent = post.find('.postcontent');
			postcontent.before(
				'<form method="post" action="topic-'+topicid+'-edit-'+postid+'" data-target="replace" class="editform">'+
				'<input type="hidden" name="title" value="" /><input type="hidden" name="act" value="editpost" /><input type="hidden" name="editpost" value="'+postid+'" />'+
				'<div class="textboxcontainer"><textarea class="textbox posttextbox" rows="6" cols="50" name="message">&lt;&lt;html&gt;&gt;'+postcontent.html()+'</textarea></div><button type="submit" class="editbutton"><strong>Edit</strong></button><button data-action="fastRemoveCurReply">Cancel</button>'+
				'</form>'
			);
			if (postid === topicid) {
				this.$('.edittitlearea').remove();
				this.$('.pfx-body').last().prepend('<div class="formarea edittitlearea"><div><button data-action="hardDelete" data-topicid="'+topicid+'">Hard delete</button></div><div class="formrow fullwidth"><label class="label">Title: <input name="title" type="text" size="50" class="textbox" value="'+post.data('title')+'"></label></div></div>');
			}

			post.find('textarea.posttextbox').richTextEditor(postcontent.html()).focus();

			postcontent.hide();
		},
		hardDelete: function(e) {
			e.currentTarget.disabled = true;
			$(e.currentTarget).text('Deleting...').data('topicid');
			this.post(null, {
				act: 'harddeletetopic',
				topicid: $(e.currentTarget).data('topicid')
			});
		}
	});

}).call(this);
