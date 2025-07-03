/**
 * jQuery rich text editor
 *
 * A rich text editor would have been extremely difficult to write if
 * not for the massively useful DOM abstractions of jQuery and the
 * extremely useful Rangy.js selection library.
 *
 * @author Guangcong Luo
 * @license http://opensource.org/licenses/MIT MIT License
 */

(function($, undefined){
	'use strict';

	// IE 9 and below
	var oldIE = (function(){
		var div = document.createElement('div');
		div.innerHTML = '<!--[if IE]><i></i><![endif]-->';
		return !!div.getElementsByTagName('i')[0];
	})();

	// old versions of Android and iOS don't support contentEditable,
	// despite pretending that they do
	var noRTEsupport = (function(){
		var ua = navigator.userAgent;
		var android_version = ua.match(/Android ([0-9\.]+)/);

		return document.body.contentEditable === undefined ||
			(/(iPhone|iPod|iPad)/i.test(ua) && /OS [1-4]_\d like Mac OS X/i.test(ua)) ||
			(android_version != null && parseFloat(android_version[1]) < 3) ||
			(/(Opera Mobi)/i.test(ua));
	})();

	// first element is null so no RTE has a falsy index
	var RTEs = [null];

	/**
	 * Create (or get) a rich text editor for this textarea.
	 *
	 * @param  string defaultContent
	 * @return RichTextEditor
	 */
	$.fn.richTextEditor = function(defaultContent) {
		if (this.data('rte')) {
			// There's already a rich text editor associated with this
			return RTEs[this.data('rte')];
		}

		this.data('rte', RTEs.length);
		RTEs.push(new RichTextEditor(this, defaultContent));

		return RTEs[RTEs.length-1];
	};

	function RichTextEditor(textarea, defaultContent) {

		if (noRTEsupport) {
			// This browser doesn't support contentEditable
			// we fall back to an auto-resizing textarea courtesy of jQuery autoResize.
			this.textarea = textarea;
			this.$textarea = $(textarea);
			this.$textarea.autoResize({
				animateDuration : 200,
				extraSpace : 40
			});
			return;
		}

		if (!defaultContent) {
			// Default content is an empty line, or some browsers won't
			// show a caret.
			defaultContent = '<div><br /></div>';
			if (oldIE) {
				// Internet Explorer 9 has a bug where <br /> adds an
				// extra line in contentEditable mode.
				defaultContent = '<div></div>';
			}
		}

		// set up our text box
		this.$el = $('<div class="rte-textbox-wrapper"><div class="rte-toolbar"><img src="theme/spacer.png" alt="Bold" class="rte-toolbar-bold" unselectable="on" /><img src="theme/spacer.png" alt="Italic" class="rte-toolbar-italic" unselectable="on" /><img src="theme/spacer.png" alt="Strikethrough" class="rte-toolbar-strike" unselectable="on" /></div><div class="rte-textbox" contenteditable="true">'+defaultContent+'</div></div>');
		$(textarea).after(this.$el).hide();

		this.$textarea = this.$('.rte-textbox').first();
		this.textarea = this.$textarea[0];
		this.$textarea[0].contentEditable = true;

		this.$toolbar = this.$('.rte-toolbar').first();
		this.$buttons = {
			'bold': this.$toolbar.find('.rte-toolbar-bold').first(),
			'italic': this.$toolbar.find('.rte-toolbar-italic').first(),
			'strike': this.$toolbar.find('.rte-toolbar-strike').first(),
		};

		// use <i> instead of <span style="font-style:italic;">
		try {
			document.execCommand('styleWithCss',false,false);
		} catch(e) {}

		// set up events
		this.$toolbar.mousedown(function(e) {
			e.preventDefault(); e.stopPropagation();
			this.focus();
		}.bind(this));
		this.$buttons['bold'].click(function(e) {
			e.preventDefault(); e.stopPropagation();
			this.bold();
		}.bind(this));
		this.$buttons['italic'].click(function(e) {
			e.preventDefault(); e.stopPropagation();
			this.italic();
		}.bind(this));
		this.$buttons['strike'].click(function(e) {
			e.preventDefault(); e.stopPropagation();
			this.strike();
		}.bind(this));

		this.$textarea.keydown(this.handleKeyPress.bind(this));
		this.$textarea.on('click',this.updateButtons.bind(this));
		this.$textarea.on('paste drop',this.handlePaste.bind(this));
		this.$textarea.on('focus',this.handlePaste.bind(this));
		this.$textarea.on('blur',this.blurButtons.bind(this));
		//this.$textarea.on('dragover',function(e){e.preventDefault();});
		//this.$textarea.on('dragenter',function(e){e.preventDefault();});

		// initialize filterData
		this.filterData = {pass: 0};
	}

	RichTextEditor.prototype.$ = function(selector) {
		return this.$el.find(selector);
	};

	/**
	 * Focus the text box
	 * @return RichTextEditor
	 */
	RichTextEditor.prototype.focus = function() {
		if (!this.$el) {
			// no rich-text editor mode
			this.$textarea.focus();
			return this;
		}
		this.$textarea.off('focus');
		if (!this.focused()) {
			this.$textarea.focus();
			this.updateButtons();
		} else {
			this.$textarea.focus();
		}
		this.$textarea.on('focus', this.handlePaste.bind(this));
		return this;
	};

	RichTextEditor.prototype.focused = function() {
		return (this.$textarea[0] === document.activeElement);
	};

	/**
	 * Get/set HTML.
	 * Acts like the corresponding jQuery method.
	 */
	RichTextEditor.prototype.html = function(content) {
		if (content !== undefined) {
			if (!this.$el) {
				// no rich-text editor mode
				this.$textarea.val('<<html>>'+content);
				return this;
			}
			this.$textarea.html(content);
			this.filterFirstPass();
			this.filterSecondPass();
			return this;
		}
		if (!this.$el) {
			// no rich-text editor mode
			return this.$textarea.val();
		}
		this.filterFirstPass();
		this.filterSecondPass();
		return this.$textarea.html();
	};

	/**
	 * Get/set text.
	 * Acts like the corresponding jQuery method.
	 */
	RichTextEditor.prototype.text = function(content) {
		if (!this.$el) {
			// no rich-text editor mode
			return this.$textarea.val(content);
		}
		if (content !== undefined) {
			this.$textarea.text(content);
			this.filterFirstPass();
			this.filterSecondPass();
			return this;
		}
		return this.$textarea.text();
	};

	/**
	 * Get/set value.
	 * Acts like the corresponding jQuery method.
	 */
	RichTextEditor.prototype.val = function(content) {
		if (!this.$el) {
			// no rich-text editor mode
			return this.$textarea.val(content);
		}
		if (content) {
			return this.$textarea.text(content);
		}
		if (this.$textarea.text().trim() === '') return '';
		return '<<html>>'+this.$textarea.html();
	};

	/**
	 * Toggle bold text.
	 * @return RichTextEditor
	 */
	RichTextEditor.prototype.bold = function() {
		this.focus();
		document.execCommand('bold',false,null);
		this.updateButton('bold');
		this.fadeMessage('bold');
		return this;
	};
	/**
	 * Toggle italic text.
	 * @return RichTextEditor
	 */
	RichTextEditor.prototype.italic = function() {
		this.focus();
		document.execCommand('italic',false,null);
		this.updateButton('italic');
		this.fadeMessage('italic');
		return this;
	};
	/**
	 * Toggle strikethrough text.
	 * @return RichTextEditor
	 */
	RichTextEditor.prototype.strike = function() {
		this.focus();
		document.execCommand('strikethrough',false,null);
		this.updateButton('strike');
		this.fadeMessage('strikethrough');
		return this;
	};

	RichTextEditor.prototype.blurButtons = function() {
		this.$buttons['bold'].removeClass('rte-on');
		this.$buttons['italic'].removeClass('rte-on');
		this.$buttons['strike'].removeClass('rte-on');
		this.$toolbar.css('opacity', 0.5);
		return this;
	};
	RichTextEditor.prototype.updateButton = function(button) {
		this.$toolbar.css('opacity', 1);
		this.$buttons[button].toggleClass('rte-on');
		return this;
	};
	RichTextEditor.prototype.updateButtons = function() {
		// this.fadeMessage('updateButtons()');
		var sel = rangy.getSelection();
		if (!sel.rangeCount) {
			return this.blurButtons();
		}
		this.$toolbar.css('opacity', 1);
		var container = $(sel.getRangeAt(0).endContainer);
		if (container.closest('.rte-textbox').length == 0) {
			return this.blurButtons();
		}
		if (container.closest('b, strong, .rte-textbox')[0] != this.textarea) {
			this.$buttons['bold'].addClass('rte-on');
		} else {
			this.$buttons['bold'].removeClass('rte-on');
		}
		if (container.closest('i, em, .rte-textbox')[0] != this.textarea) {
			this.$buttons['italic'].addClass('rte-on');
		} else {
			this.$buttons['italic'].removeClass('rte-on');
		}
		if (container.closest('s, strike, del, .rte-textbox')[0] != this.textarea) {
			this.$buttons['strike'].addClass('rte-on');
		} else {
			this.$buttons['strike'].removeClass('rte-on');
		}
	};
	RichTextEditor.prototype.qcsUpdateButtons = function(button) {
		// Faster implementation of updateButtons that uses queryCommandState.
		//
		// Don't call this manually; updateButtons will automatically be
		// replaced with this when queryCommandState is available.

		var sel = rangy.getSelection();
		if (!sel.rangeCount) {
			return this.blurButtons();
		} else {
			this.$toolbar.css('opacity', 1);
		}
		if (document.queryCommandState('bold')) {
			this.$buttons['bold'].addClass('rte-on');
		} else {
			this.$buttons['bold'].removeClass('rte-on');
		}
		if (document.queryCommandState('italic')) {
			this.$buttons['italic'].addClass('rte-on');
		} else {
			this.$buttons['italic'].removeClass('rte-on');
		}
		if (document.queryCommandState('strikethrough')) {
			this.$buttons['strike'].addClass('rte-on');
		} else {
			this.$buttons['strike'].removeClass('rte-on');
		}
	};

	// if queryCommandState is available, this is much faster
	if (document.queryCommandState) {
		RichTextEditor.prototype.updateButton = RichTextEditor.prototype.qcsUpdateButtons;
		RichTextEditor.prototype.updateButtons = RichTextEditor.prototype.qcsUpdateButtons;
	}

	RichTextEditor.prototype.handleKeyPress = function(e) {
		// Ctrl on Windows/Linux and Cmd on OS X
		var ctrlEquiv = ((e.ctrlKey || e.metaKey) && !e.cmdKey) || (!e.ctrlKey && (e.metaKey || e.cmdKey));

		if (e.keyCode == 66 && ctrlEquiv && !e.shiftKey && !e.altKey) { // ctrl+b
			e.preventDefault(); e.stopPropagation();
			document.execCommand('bold',false,null);
			this.updateButton('bold');
			this.fadeMessage('bold');
		} else if (e.keyCode == 73 && ctrlEquiv && !e.shiftKey && !e.altKey) { // ctrl+i
			e.preventDefault(); e.stopPropagation();
			document.execCommand('italic',false,null);
			this.updateButton('italic');
			this.fadeMessage('italic');
		} else if (e.keyCode == 75 && ctrlEquiv && e.shiftKey && !e.altKey) { // ctrl+shift+k
			e.preventDefault(); e.stopPropagation();
			document.execCommand('strikethrough',false,null);
			this.updateButton('strike');
			this.fadeMessage('strikethrough');
		} else if ((e.keyCode >= 91 && e.keyCode <= 93) || (e.keyCode >= 16 && e.keyCode <= 20)) {
			// cmd, shift, ctrl, alt, pause/break, caps lock
			// do nothing
		} else {
			setTimeout(this.updateButtons.bind(this));
		}
	};

	RichTextEditor.prototype.handlePaste = function() {
		setTimeout(function() { this.filterFirstPass(); this.filterSecondPass(); this.updateButtons(); }.bind(this));
	};

	RichTextEditor.prototype.fadeMessage = function(message) {
		// spaghetti code; I don't care, this is a debug function for now
		this.$toolbar.append($('<strong style="float:left;padding:3px 5px 0 3px;">'+message+'</strong>')).children().last().fadeOut(500,function(){$(this).remove();});
	};

	// filtering
	RichTextEditor.prototype.filterData = null;
	RichTextEditor.prototype.filterLock = false;

	RichTextEditor.prototype.filterCheckpoint = function() {
		// return true if filtering has been taking too long and we need to stop
		return false;
	};
	RichTextEditor.prototype.filterFirstPass = function() {
		//this.fadeMessage('Filter');
		if (this.filterLock) {
			return;
		}
		this.filterLock = true;
		this.saveSelection();

		// The first pass of the valid rich-text filter makes sure that every node is enclosed by a div tag
		var rows = this.$textarea.contents();
		var i;
		var appendElem = null;
		for (i=0; i<rows.length; i++) {
			if (rows[i].nodeType == 3 || rows[i].tagName.toLowerCase() != 'div') {
				if (appendElem) {
					appendElem.append(rows[i]);
				} else {
					appendElem = $(rows[i]).wrap($('<div />')).parent();
				}
			} else {
				// possible checkpoint
				appendElem = null;
			}
		}
		this.restoreSelection();
		this.filterData = {pass: 2};

		this.filterLock = false;
	};
	RichTextEditor.prototype.container = function() {
		// debugging function
		return rangy.getSelection().getRangeAt(0).endContainer;
	};
	RichTextEditor.prototype.filterSecondPass = function() {
		if (this.filterLock) {
			return;
		}
		this.filterLock = true;

		var rows, i;
		if (this.filterData && this.filterData.pass == 2 && this.filterData.i) {
			rows = this.filterData.rows;
			i = this.filterData.i;
		} else {
			rows = this.$textarea.contents();
			i = 0;
		}

		this.saveSelection();

		for (; i<rows.length; i++) {
			var row = $(rows[i]);
			if (row[0].nodeType == 3 || row[0].tagName.toLowerCase() != 'div') {
				// first pass isn't done; knock us back
				this.filterData = {pass: 0};
				return;
			}
			if (row.attr('style') || row.attr('class')) {
				// this isn't a raw div - wrap it in one
				row.wrap('<div />');
				// now get rid of the non-raw div - second-pass would have taken care
				// of it anyway, but this is faster
				row.replaceWith(row[0].childNodes);
			}

			// now the second pass filtering actually starts
			this.filterSecondPassContents(row);

			// second pass is done for this row
			if (this.filterCheckpoint()) {
				this.restoreSelection();
				this.filterData = {pass: 2, rows: rows, i: i};
				return;
			}
		}

		this.restoreSelection();
		this.filterData = {pass: 3};
		this.filterLock = false;
	};
	RichTextEditor.prototype.filterUnwrap = function(node) {
		// jquery should really have this - unwrap a node, not its parent
		node.replaceWith(node[0].childNodes);
	};
	RichTextEditor.prototype.saveSelection = function() {
		if (this.filterData.savedSel) {
			rangy.removeMarkers(this.filterData.savedSel);
		}
		this.filterData.savedSel = rangy.saveSelection();
	};
	RichTextEditor.prototype.restoreSelection = function() {
		if (this.filterData && this.filterData.savedSel) {
			rangy.restoreSelection(this.filterData.savedSel,true);
		}
	};
	RichTextEditor.prototype.filterSecondPassContents = function(node, tags) {
		var subnodes = node.contents();
		var prevDisplay = 'block';
		if (!tags) {
			tags = {b: false, i: false, s: false, code: false};
		}
		for (var j=0; j<subnodes.length; j++) {
			if (subnodes[j].nodeType != 1) {
				// text node - remind ourselves that next block element will be on a new line
				prevDisplay = 'inline';
				continue;
			}
			var subnode = $(subnodes[j]);
			var tagName = subnode[0].tagName.toLowerCase();

			if (tagName === 'span' && subnode.attr('id').substr(0,18)==='selectionBoundary_') {
				// rangy marker node; leave it alone
				continue;
			}

			// We're not going to do everything in this switch, these are
			// just some special cases to filter out early
			switch (tagName) {
			case 'style':
			case 'script':
			case 'link':
			case 'meta':
			case 'title':
			case 'head':

			case 'iframe': // seriously, you want to post an iframe?!
			case 'object':
			case 'param':
			case 'select':
			case 'option':
				// we don't want these at all
				subnode.remove();
				continue;
			case 'input':
				if (subnode.attr('type') === 'hidden') {
					subnode.remove();
				} else {
					prevDisplay = 'inline';
					subnode.replaceWith(subnode.val());
				}
				continue;

			case 'br':
				// linebreaks are handled elsewhere
				continue;

			case 'img':
				subnode.replaceWith(subnode.attr('alt')); // close enough
				continue;
			case 'hr':
				subnode.replaceWith('<br />-----<br />'); // close enough
				continue;

			// we're going to replace tables with some table markdown
			case 'td':
			case 'th':
				subnode.after(' || ');
				this.filterSecondPassContents(subnode);
				prevDisplay = 'inline';
				this.filterUnwrap(subnode);
				continue;
			case 'tr':
				subnode.before('|| ');
				subnode.after('<br />');
				this.filterSecondPassContents(subnode);
				prevDisplay = 'block';
				this.filterUnwrap(subnode);
				continue;
			case 'tbody':
			case 'thead':
			case 'tfoot':
				// we aren't even going to reset prevDisplay, since our outsides and insides
				// should have done that for us
				this.filterSecondPassContents(subnode);
				prevDisplay = 'block';
				this.filterUnwrap(subnode);
				continue;
			}

			// We're done with the special cases. Now time to insert linebreaks
			// as appropriate for block-level elements
			var subnodeDisplay = subnode.css('display');
			if (subnodeDisplay !== 'none' && (!subnodeDisplay || tagName === 'textarea' || tagName === 'table')) {
				// ye gods, we're going to have to figure out if it's
				// block or inline manually
				// we're also going to force a few things to block
				subnodeDisplay = 'inline';
				switch (tagName) {
				case 'div':
				case 'p':
				case 'form':
				case 'textarea':
				case 'table':
				case 'tr':
				case 'td':
				case 'li': // we're going to ignore ul's since 
					subnodeDisplay = 'block';
					break;
				}
			}
			if (subnodeDisplay === 'inline-block' && tagName === 'button') {
				subnodeDisplay = 'inline';
			}
			if (subnodeDisplay !== 'inline' && subnodeDisplay !== 'none') {
				// should probably be treated as a block element
				// add linebreaks before or after, as appropriate
				if (prevDisplay === 'inline') {
					// this is why we keep track of prevDisplay
					subnode.before('<br />');
				}
				prevDisplay = 'block';
			} else {
				// should probably be treated as an inline element
				prevDisplay = 'inline';
			}

			// Now, if this is an unsanitary element, we're going to
			// unwrap it, and perhaps replace it with a <i><b><s><code>
			// tag if it had styling

			var unwrapNode = true;
			var restoreTags = {};
			if (subnode.css('font-style') === 'italic' && !tags.i) {
				if ((tagName === 'i' || tagName === 'em') &&
						 !subnode.attr('style') && !subnode.attr('class')) {
					// sanitary node, leave it untouched
					unwrapNode = false;
				} else {
					subnode.wrap('<i />');
				}
				tags.i = true;
				restoreTags.i = false;
			}
			if ((subnode.css('font-weight') === 'bold' || subnode.css('font-weight') === '700' || subnode.css('font-weight') === '401') && !tags.b) {
				// wtf Firefox 3.6 sets font-weight to 401 for some reason. 
				if ((tagName === 'b' || tagName === 'strong') &&
				    !subnode.attr('style') && !subnode.attr('class')) {
					// sanitary node, leave it untouched
					unwrapNode = false;
				} else {
					subnode.wrap('<b />');
				}
				tags.b = true;
				restoreTags.b = false;
			}
			if (subnode.css('text-decoration') === 'line-through' && !tags.s) {
				if ((tagName === 's' || tagName === 'del' || tagName === 'strike') &&
				    !subnode.attr('style') && !subnode.attr('class')) {
					// sanitary node, leave it untouched
					unwrapNode = false;
				} else {
					subnode.wrap('<s />');
				}
				tags.s = true;
				restoreTags.s = false;
			}
			if ((tagName === 'code' || tagName === 'tt' || tagName === 'kbd' || tagName === 'samp') && !tags.code) {
				// we don't check for CSS because we don't want all previously-monospace text
				// which is just as well, since it'd be pretty difficult to check for monospace text if we did
				if ((tagName === 'code' || tagName === 'tt') &&
				    !subnode.attr('style') && !subnode.attr('class')) {
					// sanitary node, leave it untouched
					unwrapNode = false;
				} else {
					subnode.wrap('<code />'); // honestly, I'd prefer tt, but the W3C isn't fond of it
				}
				tags.code = true;
				restoreTags.code = false;
			}
			if (tagName === 'span' &&
			    subnode.attr('class') === 'Apple-style-span') {
				// technically, this isn't a sanitary node,
				// but we leave it untouched because it's close enough
				unwrapNode = false;
				subnode.attr('style','');
			}
			// k, now we know what we want to wrap in.
			// these next three commands must be done in order
			var filterres = this.filterSecondPassContents(subnode, tags);
			$.extend(tags, restoreTags);
			if (tagName === 'p') {
				// paragraphs get an extra ending-linebreak
				subnode.after('<br />');
			}
			if (prevDisplay !== 'inline') {
				// remember: filterres is true if the node doesn't end in <br />
				if (filterres) {
					subnode.after('<br />');
				}
				tagName = 'br';
			}
			if (unwrapNode) {
				this.filterUnwrap(subnode);
			}
		}
		return (tagName !== 'br');
	};

})(jQuery);
