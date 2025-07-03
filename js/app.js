(function() {
	'use strict';

	function htmlEscape(str) {
	    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	var ForumPanel = this.ForumPanel = Panels.StaticPanel.extend({
		minWidth: 400,
		constructor: function(el, fragment, args) {
			if (!this.events) this.events = {};
			if (!this.events['click .expcol']) this.events['click .expcol'] = 'toggleExpandCollapse';

			var initialize = this.initialize;
			this.initialize = function() {};
			Panels.StaticPanel.prototype.constructor.apply(this, arguments);

			// constructor
			initialize.apply(this, args);
		},
		rightOffset: -1,
		toggleExpandCollapse: function(e) {
			e.preventDefault();
			e.stopPropagation();
			var $that = $(e.currentTarget);
			if ($that.hasClass('col')) {
				$that.parent().children('ul.tree').animate({opacity:'toggle', height:'toggle'}, 200);
				$that.attr('class', 'expcol exp');
			} else {
				$that.parent().children('ul.tree').animate({opacity:'toggle', height:'toggle'}, 200);
				$that.attr('class', 'expcol col');
			}
		},
		html: function(content) {
			Panels.StaticPanel.prototype.html.call(this, content);
		},
		updateContent: function() {
			this.$('div.forumlist ul').parent('li').prepend('<span class="expcol col"></span>');
		}
 	});

	var NarrowForumPanel = this.NarrowForumPanel = ForumPanel.extend({
		minWidth: 320,
		maxWidth: 480
	});	

	var Topbar = this.Topbar = Panels.Topbar.extend({
		height: 51,
		events: {
			'click button': 'topbarClick'
		},
		topbarClick: function(e) {
			switch (e.currentTarget.value) {
			case 'logout':
				e.preventDefault(); e.stopPropagation();
				this.showUserMessage('<em>Logging out...</em>');
				$.ajax(this.app.root+'login?output=json', {
					type: 'post',
					dataType: 'json',
					data: 'act=logout'
				}).done(function(response){
					this.app.reloadPanels();
					this.updateUser(null);
				}.bind(this));
				break;
			case 'go-login':
				e.preventDefault(); e.stopPropagation();
				this.app.addPopup(LoginPopup, {source: e.currentTarget});
				break;
			case 'go-register':
				// e.preventDefault(); e.stopPropagation();
				// alert('register');
				break;
			}
		},
		updateUser: function(user) {
			if (user && user.username && user.userid !== 'guest') {
				this.$('.userbar').html('<form action="'+this.app.root+'action.php" method="post" style="vertical-align:middle;">' +
					'<input type="hidden" name="act" value="btn" />' +
					'<a href="/~'+user.userid+'" class="name"><img src="/theme/defaultav.png" width="25" height="25" alt="" /> '+htmlEscape(user.username)+'</a> <button type="submit" name="actbtn" value="logout">Log Out</button>' +
					'</form>');
			} else {
				this.$('.userbar').html('<form action="'+this.app.root+'action.php" method="get">' +
					'<input type="hidden" name="act" value="btn" />' +
					'<button type="submit" name="actbtn" value="go-login">Log In</button> <button type="submit" name="actbtn" value="go-register">Register</button>' +
					'</form>');
			}
		},
		showUserMessage: function(message) {
			this.$('.userbar').html(message);
		}
	});

	var App = this.App = Panels.App.extend({
		states: {
			'*path': ForumPanel, // catch-all default

			'': ForumPanel,
			'~:user': NarrowForumPanel,
			'~:user/settings': NarrowForumPanel,
			'topic-:id': 'TopicForumPanel',
			'newtopic-:id': 'TopicForumPanel',
			'settings': NarrowForumPanel
		},
		topbarView: Topbar
	});

	var LoginPopup = this.LoginPopup = Panels.Popup.extend({
		events: {
			'submit form': 'submit'
		},
		initialize: function(username, error) {
			if (!username) username = this.options.username;
			if (!error) error = this.options.error;
			this.$el.html('<form action="'+this.app.root+'login" method="post" class="form" data-target="replace">' +
				'<input type="hidden" name="act" value="login">' +
				(error?'<div class="error"><strong>Error:</strong> '+error+'</div>':'') +
				'<div class="formrow"><label class="label">Username: <input name="username" type="text" size="30" class="textbox"'+(username?' value="'+htmlEscape(username)+'"':' autofocus')+'></label></div>' +
				'<div class="formrow"><label class="label">Password: <input name="password" type="password" size="30" class="textbox"'+(username?' autofocus':'')+'></label></div>' +
				'<div class="buttonrow"><button type="submit"><strong>Log In</strong></button> <button data-action="close">Cancel</button></div>' +
				'</form>');
		},
		submit: function(e) {
			e.preventDefault(); e.stopPropagation();
			var data = $(e.currentTarget).serialize();
			var username = $(e.currentTarget).find('input[name=username]').val();
			var app = this.app;
			this.close();
			app.topbar.showUserMessage('<em>Logging in...</em>');

			$.ajax(app.root+'login?output=json', {
				type: 'post',
				dataType: 'json',
				data: data
			}).done(function(response){
				app.topbar.updateUser(response.curuser);
				if (!response.curuser.loggedin) {
					app.addPopup(LoginPopup, {username: username, source: app.topbar.$('button[value="go-login"]'), error: response.actionerror});
				} else {
					app.reloadPanels();
				}
			});
		}
	});

}).call(this);
