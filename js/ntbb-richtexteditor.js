var rte_counter = 0;

// oldtextarea should be a jQuery element
function RichTextEditor(oldtextarea, defaultcontent, id)
{
	var self = this;
	this.textbox = null;
	
	this.noRTEsupport = (navigator.userAgent.toLowerCase().indexOf("android") > -1 || navigator.userAgent.match(/like Mac OS X/i))
	
	/* if (!id && oldtextarea.attr('id'))
	{
		id = 'rte-'+oldtextarea.attr('id');
	} */
	if (!id)
	{
		id = 'rte-textbox'+(rte_counter++);
	}
	/* if (oldtextarea.attr('id'))
	{
		$('label[for="'+oldtextarea.attr('id')+'"]').attr('for',id);
	} */
	if (!defaultcontent)
	{
		defaultcontent = '<div><br /></div>';
		if ($.browser.msie && $.browser.version < 10)
		{
			defaultcontent = '<div></div>';
		}
	}
	if (this.noRTEsupport)
	{
		this.textbox = oldtextarea;
		oldtextarea.autoResize({
			animateDuration : 200,
			extraSpace : 40
		});
	}
	else
	{
		oldtextarea.after('<div class="rte-textbox-wrapper"><div class="rte-toolbar"><img src="theme/spacer.png" alt="Bold" class="rte-toolbar-bold" id="'+id+'-bold" unselectable="on" /><img src="theme/spacer.png" alt="Italic" class="rte-toolbar-italic" id="'+id+'-italic" unselectable="on" /><img src="theme/spacer.png" alt="Strikethrough" class="rte-toolbar-strike" id="'+id+'-strike" unselectable="on" /></div><div class="textbox posttextbox" id="'+id+'" contenteditable="true">'+defaultcontent+'</div></div>');
		oldtextarea.hide();
		this.textbox = $('#'+id);
		this.textbox[0].contentEditable = true;
	}
	try
	{
		document.execCommand('styleWithCss',false,false);
	}
	catch(e) {}
	this.focus = function()
	{
		self.textbox.unbind('focus');
		if (!self.focused())
		{
			self.textbox.focus();
			self.updateButtons();
		}
		else
		{
			self.textbox.focus();
		}
		self.textbox.bind('focus',self.handlePaste);
	}
	this.focused = function()
	{
		return (self.textbox[0] === document.activeElement);
	}
	this.toolbar = function(button)
	{
		if (!button)
		{
			return $('#'+id+'-bold').parent();
		}
		return $('#'+id+'-'+button);
	}
	self.toolbar().css('opacity', 0.5);
	
	this.html = function() { self.filterFirstPass();self.filterSecondPass(); return self.textbox.html(); }
	this.text = function() { return self.textbox.text(); }
	this.val = function() {
		if (this.noRTEsupport)
		{
			return self.textbox.val();
		}
		if (self.textbox.text().trim() === '') return '';
		return '<<html>>'+self.textbox.html();
	};
	this.toolbar().mousedown(function(e){
		e.preventDefault();
		e.stopPropagation();
		self.focus();
	});
	this.toolbar('bold').click(function(e){
		e.preventDefault();
		e.stopPropagation();
		self.focus();
		document.execCommand('bold',false,null);
		self.updateButton('bold');
		self.fadeMessage('bold');
	});
	this.toolbar('italic').click(function(e){
		e.preventDefault();
		e.stopPropagation();
		self.focus();
		document.execCommand('italic',false,null);
		self.updateButton('italic');
		self.fadeMessage('italic');
	});
	this.toolbar('strike').click(function(e){
		e.preventDefault();
		e.stopPropagation();
		self.focus();
		document.execCommand('strikethrough',false,null);
		self.updateButton('strike');
		self.fadeMessage('strikethrough');
	});
	this.updateButton = function(button) {
		if (button==='blur')
		{
			self.toolbar('bold').removeClass('rte-on');
			self.toolbar('italic').removeClass('rte-on');
			self.toolbar('strike').removeClass('rte-on');
			self.toolbar().css('opacity', 0.5);
		}
		else
		{
			self.toolbar().css('opacity', 1);
			self.toolbar(button).toggleClass('rte-on');
		}
	}
	this.qcsUpdateButtons = function(button) {
		var sel = rangy.getSelection();
		if (!sel.rangeCount || button === blur)
		{
			self.toolbar('bold').removeClass('rte-on');
			self.toolbar('italic').removeClass('rte-on');
			self.toolbar('strike').removeClass('rte-on');
			self.toolbar().css('opacity', 0.5);
			return;
		}
		else
		{
			self.toolbar().css('opacity', 1);
		}
		if (document.queryCommandState('bold'))
		{
			self.toolbar('bold').addClass('rte-on');
		}
		else
		{
			self.toolbar('bold').removeClass('rte-on');
		}
		if (document.queryCommandState('italic'))
		{
			self.toolbar('italic').addClass('rte-on');
		}
		else
		{
			self.toolbar('italic').removeClass('rte-on');
		}
		if (document.queryCommandState('strikethrough'))
		{
			self.toolbar('strike').addClass('rte-on');
		}
		else
		{
			self.toolbar('strike').removeClass('rte-on');
		}
	}
	this.updateButtons = function() {
		//self.fadeMessage('updateButtons()');
		var sel = rangy.getSelection();
		if (!sel.rangeCount)
		{
			self.updateButton('blur');
			return;
		}
		self.toolbar().css('opacity', 1);
		var container = $(sel.getRangeAt(0).endContainer);
		if (container.closest('#'+id).length == 0)
		{
			self.updateButton('blur');
			return;
		}
		if (container.closest('b, strong, #'+id)[0] != self.textbox[0])
		{
			self.toolbar('bold').addClass('rte-on');
		}
		else
		{
			self.toolbar('bold').removeClass('rte-on');
		}
		if (container.closest('i, em, #'+id)[0] != self.textbox[0])
		{
			self.toolbar('italic').addClass('rte-on');
		}
		else
		{
			self.toolbar('italic').removeClass('rte-on');
		}
		if (container.closest('s, strike, del, #'+id)[0] != self.textbox[0])
		{
			self.toolbar('strike').addClass('rte-on');
		}
		else
		{
			self.toolbar('strike').removeClass('rte-on');
		}
	}
	if (document.queryCommandState)
	{
		this.updateButton = this.qcsUpdateButtons;
		this.updateButtons = this.qcsUpdateButtons;
	}
	this.keyPress = function(e)
	{
		var ctrlEquiv = ((e.ctrlKey || e.metaKey) && !e.cmdKey) || (!e.ctrlKey && (e.metaKey || e.cmdKey));
		if (e.keyCode == 66 && ctrlEquiv && !e.shiftKey && !e.altKey) // ctrl+b
		{
			e.preventDefault();
			e.stopPropagation();
			document.execCommand('bold',false,null);
			self.updateButton('bold');
			self.fadeMessage('bold');
		}
		else if (e.keyCode == 73 && ctrlEquiv && !e.shiftKey && !e.altKey) // ctrl+i
		{
			e.preventDefault();
			e.stopPropagation();
			document.execCommand('italic',false,null);
			self.updateButton('italic');
			self.fadeMessage('italic');
		}
		else if (e.keyCode == 75 && ctrlEquiv && e.shiftKey && !e.altKey) // ctrl+shift+k
		{
			e.preventDefault();
			e.stopPropagation();
			document.execCommand('strikethrough',false,null);
			self.updateButton('strike');
			self.fadeMessage('strikethrough');
		}
		else if ((e.keyCode >= 91 && e.keyCode <= 93) || (e.keyCode >= 16 && e.keyCode <= 20))
		{
			// cmd, shift, ctrl, alt, pause/break, caps lock
			// do nothing
		}
		else
		{
			setTimeout(self.updateButtons);
		}
	}
	this.fadeMessage = function(message)
	{
		// spaghetti code; I don't care, this is a debug function for now
		var messageElem = $('#rte-message');
		if (!messageElem.length)
		{
			this.textbox.parent().find('div.rte-toolbar').append($('<strong style="float:left;padding:3px 5px 0 3px;">'+message+'</strong>')).children().last().fadeOut(500,function(){$(this).remove();});
		}
	}
	this.textbox.keydown(this.keyPress);

	// filtering
	this.filterData = {pass: 0};
	this.filterLock = false;
	
	this.filterCheckpoint = function()
	{
		// return true if filtering has been taking too long and we need to stop
		return false;
	}
	this.filterFirstPass = function()
	{
		//self.fadeMessage('Filter');
		if (self.filterLock)
		{
			return;
		}
		self.filterLock = true;
		self.saveSelection();

		// The first pass of the valid rich-text filter makes sure that every node is enclosed by a div tag
		var rows = self.textbox.contents();
		var i;
		var appendElem = null;
		for (i=0; i<rows.length; i++)
		{
			if (rows[i].nodeType == 3 || rows[i].tagName.toLowerCase() != 'div')
			{
				if (appendElem)
				{
					appendElem.append(rows[i]);
				}
				else
				{
					appendElem = $(rows[i]).wrap($('<div />')).parent();
				}
			}
			else
			{
				// possible checkpoint
				appendElem = null;
			}
		}
		self.restoreSelection();
		self.filterData = {pass:2};
		self.filterLock = false;
	}
	this.container = function()
	{
		// debugging function
		return rangy.getSelection().getRangeAt(0).endContainer;
	}
	this.filterSecondPass = function()
	{
		if (self.filterLock)
		{
			return;
		}
		self.filterLock = true;
		
		var rows, i;
		if (self.filterData && self.filterData.pass == 2 && self.filterData.i)
		{
			rows = self.filterData.rows;
			i = self.filterData.i;
		}
		else
		{
			rows = self.textbox.contents();
			i = 0;
		}
		
		self.saveSelection();
		
		for (; i<rows.length; i++)
		{
			var row = $(rows[i]);
			if (row[0].nodeType == 3 || row[0].tagName.toLowerCase() != 'div')
			{
				// first pass isn't done; knock us back
				self.filterData = {pass: 0};
				return;
			}
			if (row.attr('style') || row.attr('class'))
			{
				// this isn't a raw div - wrap it in one
				row.wrap('<div />');
				// now get rid of the non-raw div - second-pass would have taken care
				// of it anyway, but this is faster
				row.replaceWith(row[0].childNodes);
			}
			
			// now the second pass filtering actually starts
			self.filterSecondPassContents(row);
			
			// second pass is done for this row
			if (self.filterCheckpoint())
			{
				self.restoreSelection();
				self.filterData = {pass:2, rows: rows, i: i};
				return;
			}
		}
		self.restoreSelection();
		self.filterData = {pass:3};
		self.filterLock = false;
	}
	this.filterUnwrap = function(node)
	{
		// jquery should really have this - unwrap a node, not its parent
		node.replaceWith(node[0].childNodes);
	}
	this.saveSelection = function()
	{
		if (self.filterData.savedSel)
		{
			rangy.removeMarkers(self.filterData.savedSel);
		}
		self.filterData.savedSel = rangy.saveSelection();
	}
	this.restoreSelection = function()
	{
		if (self.filterData && self.filterData.savedSel)
		{
			rangy.restoreSelection(self.filterData.savedSel,true);
		}
	}
	this.filterSecondPassContents = function(node, tags)
	{
		var subnodes = node.contents();
		var prevDisplay = 'block';
		if (!tags)
		{
			tags = {b:false, i:false, s:false, code:false};
		}
		for (var j=0; j<subnodes.length; j++)
		{
			if (subnodes[j].nodeType != 1)
			{
				// text node - remind ourselves that next block element will be on a new line
				prevDisplay = 'inline';
				continue;
			}
			var subnode = $(subnodes[j]);
			var tagName = subnode[0].tagName.toLowerCase();
			
			if (tagName === 'span' && subnode.attr('id').substr(0,18)==='selectionBoundary_')
			{
				// rangy marker node; leave it alone
				continue;
			}
			
			// We're not going to do everything in this switch, these are
			// just some special cases to filter out early
			switch(tagName)
			{
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
				if (subnode.attr('type') === 'hidden')
				{
					subnode.remove();
				}
				else
				{
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
				self.filterSecondPassContents(subnode);
				prevDisplay = 'inline';
				self.filterUnwrap(subnode);
				continue;
			case 'tr':
				subnode.before('|| ');
				subnode.after('<br />');
				self.filterSecondPassContents(subnode);
				prevDisplay = 'block';
				self.filterUnwrap(subnode);
				continue;
			case 'tbody':
			case 'thead':
			case 'tfoot':
				// we aren't even going to reset prevDisplay, since our outsides and insides
				// should have done that for us
				self.filterSecondPassContents(subnode);
				prevDisplay = 'block';
				self.filterUnwrap(subnode);
				continue;
			}
			
			// We're done with the special cases. Now time to insert linebreaks
			// as appropriate for block-level elements
			var subnodeDisplay = subnode.css('display');
			if (subnodeDisplay !== 'none' && (!subnodeDisplay || tagName === 'textarea' || tagName === 'table'))
			{
				// ye gods, we're going to have to figure out if it's
				// block or inline manually
				// we're also going to force a few things to block
				subnodeDisplay = 'inline';
				switch(tagName)
				{
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
			if (subnodeDisplay === 'inline-block' && tagName === 'button')
			{
				subnodeDisplay = 'inline';
			}
			if (subnodeDisplay !== 'inline' && subnodeDisplay !== 'none')
			{
				// should probably be treated as a block element
				// add linebreaks before or after, as appropriate
				if (prevDisplay === 'inline')
				{
					// this is why we keep track of prevDisplay
					subnode.before('<br />');
				}
				prevDisplay = 'block';
			}
			else
			{
				// should probably be treated as an inline element
				prevDisplay = 'inline';
			}
			
			// Now, if this is an unsanitary element, we're going to
			// unwrap it, and perhaps replace it with a <i><b><s><code>
			// tag if it had styling
			
			var unwrapNode = true;
			var restoreTags = {};
			if (subnode.css('font-style') === 'italic' && !tags.i)
			{
				if ((tagName === 'i' || tagName === 'em') &&
						 !subnode.attr('style') && !subnode.attr('class'))
				{
					// sanitary node, leave it untouched
					unwrapNode = false;
				}
				else
				{
					subnode.wrap('<i />');
				}
				tags.i = true;
				restoreTags.i = false;
			}
			if ((subnode.css('font-weight') === 'bold' || subnode.css('font-weight') === '700' || subnode.css('font-weight') === '401') && !tags.b)
			// wtf Firefox 3.6 sets font-weight to 401 for some reason. 
			{
				if ((tagName === 'b' || tagName === 'strong') &&
				    !subnode.attr('style') && !subnode.attr('class'))
				{
					// sanitary node, leave it untouched
					unwrapNode = false;
				}
				else
				{
					subnode.wrap('<b />');
				}
				tags.b = true;
				restoreTags.b = false;
			}
			if (subnode.css('text-decoration') === 'line-through' && !tags.s)
			{
				if ((tagName === 's' || tagName === 'del' || tagName === 'strike') &&
				    !subnode.attr('style') && !subnode.attr('class'))
				{
					// sanitary node, leave it untouched
					unwrapNode = false;
				}
				else
				{
					subnode.wrap('<s />');
				}
				tags.s = true;
				restoreTags.s = false;
			}
			if ((tagName === 'code' || tagName === 'tt' || tagName === 'kbd' || tagName === 'samp') && !tags.code)
			{
				// we don't check for CSS because we don't want all previously-monospace text
				// which is just as well, since it'd be pretty difficult to check for monospace text if we did
				if ((tagName === 'code' || tagName === 'tt') &&
				    !subnode.attr('style') && !subnode.attr('class'))
				{
					// sanitary node, leave it untouched
					unwrapNode = false;
				}
				else
				{
					subnode.wrap('<code />'); // honestly, I'd prefer tt, but the W3C isn't fond of it
				}
				tags.code = true;
				restoreTags.code = false;
			}
			if (tagName === 'span' &&
			    subnode.attr('class') === 'Apple-style-span')
			{
				// technically, this isn't a sanitary node,
				// but we leave it untouched because it's close enough
				unwrapNode = false;
				subnode.attr('style','');
			}
			// k, now we know what we want to wrap in.
			// these next three commands must be done in order
			var filterres = self.filterSecondPassContents(subnode, tags);
			$.extend(tags, restoreTags);
			if (tagName === 'p')
			{
				// paragraphs get an extra ending-linebreak
				subnode.after('<br />');
			}
			if (prevDisplay !== 'inline')
			{
				// remember: filterres is true if the node doesn't end in <br />
				if (filterres)
				{
					subnode.after('<br />');
				}
				tagName = 'br';
			}
			if (unwrapNode)
			{
				self.filterUnwrap(subnode);
			}
		}
		return (tagName !== 'br');
	}
	
	// events
	this.handlePaste = function() {
		setTimeout(function() { self.filterFirstPass(); self.filterSecondPass(); self.updateButtons(); });
	}
	if (!this.noRTEsupport)
	{
		this.textbox.bind('click',this.updateButtons);
		this.textbox.bind('paste drop',this.handlePaste);
		this.textbox.bind('focus',this.handlePaste);
		this.textbox.bind('blur',function(){self.updateButton('blur');});
	}
	//this.textbox.bind('dragover',function(e){e.preventDefault();});
	//this.textbox.bind('dragenter',function(e){e.preventDefault();});
}
