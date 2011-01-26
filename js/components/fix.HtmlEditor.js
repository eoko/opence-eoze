// Fixes HtmlEditor toolbar overflow
Ext.form.HtmlEditor.prototype.createToolbar = function(editor){
	var items = [];
	var tipsEnabled = Ext.QuickTips && Ext.QuickTips.isEnabled();


	function btn(id, toggle, handler){
		return {
			itemId : id,
			cls : 'x-btn-icon',
			iconCls: 'x-edit-'+id,
			enableToggle:toggle !== false,
			scope: editor,
			handler:handler||editor.relayBtnCmd,
			clickEvent:'mousedown',
			tooltip: tipsEnabled ? editor.buttonTips[id] || undefined : undefined,
			overflowText: editor.buttonTips[id].title || undefined,
			tabIndex:-1
		};
	}


	if(this.enableFont && !Ext.isSafari2){
		var fontSelectItem = new Ext.Toolbar.Item({
		   autoEl: {
				tag:'select',
				cls:'x-font-select',
				html: this.createFontOptions()
		   }
		});

		items.push(
			fontSelectItem,
			'-'
		);
	}

	if(this.enableFormat){
		items.push(
			btn('bold'),
			btn('italic'),
			btn('underline')
		);
	}

	if(this.enableFontSize){
		items.push(
			'-',
			btn('increasefontsize', false, this.adjustFont),
			btn('decreasefontsize', false, this.adjustFont)
		);
	}

	if(this.enableColors){
		items.push(
			'-', {
				itemId:'forecolor',
				cls:'x-btn-icon',
				iconCls: 'x-edit-forecolor',
				clickEvent:'mousedown',
				tooltip: tipsEnabled ? editor.buttonTips.forecolor || undefined : undefined,
				tabIndex:-1,
				menu : new Ext.menu.ColorMenu({
					allowReselect: true,
					focus: Ext.emptyFn,
					value:'000000',
					plain:true,
					listeners: {
						scope: this,
						select: function(cp, color){
							this.execCmd('forecolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
							this.deferFocus();
						}
					},
					clickEvent:'mousedown'
				})
			}, {
				itemId:'backcolor',
				cls:'x-btn-icon',
				iconCls: 'x-edit-backcolor',
				clickEvent:'mousedown',
				tooltip: tipsEnabled ? editor.buttonTips.backcolor || undefined : undefined,
				tabIndex:-1,
				menu : new Ext.menu.ColorMenu({
					focus: Ext.emptyFn,
					value:'FFFFFF',
					plain:true,
					allowReselect: true,
					listeners: {
						scope: this,
						select: function(cp, color){
							if(Ext.isGecko){
								this.execCmd('useCSS', false);
								this.execCmd('hilitecolor', color);
								this.execCmd('useCSS', true);
								this.deferFocus();
							}else{
								this.execCmd(Ext.isOpera ? 'hilitecolor' : 'backcolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
								this.deferFocus();
							}
						}
					},
					clickEvent:'mousedown'
				})
			}
		);
	}

	if(this.enableAlignments){
		items.push(
			'-',
			btn('justifyleft'),
			btn('justifycenter'),
			btn('justifyright')
		);
	}

	if(!Ext.isSafari2){
		if(this.enableLinks){
			items.push(
				'-',
				btn('createlink', false, this.createLink)
			);
		}

		if(this.enableLists){
			items.push(
				'-',
				btn('insertorderedlist'),
				btn('insertunorderedlist')
			);
		}
		if(this.enableSourceEdit){
			items.push(
				'-',
				btn('sourceedit', true, function(btn){
					this.toggleSourceEdit(!this.sourceEditMode);
				})
			);
		}
	}

	// build the toolbar
	var tb = new Ext.Toolbar({
		renderTo: this.wrap.dom.firstChild,
		items: items,
		enableOverflow: true // rx+
	});

	if (fontSelectItem) {
		this.fontSelect = fontSelectItem.el;

		this.mon(this.fontSelect, 'change', function(){
			var font = this.fontSelect.dom.value;
			this.relayCmd('fontname', font);
			this.deferFocus();
		}, this);
	}

	// stop form submits
	this.mon(tb.el, 'click', function(e){
		e.preventDefault();
	});

	this.tb = tb;
	this.tb.doLayout();
};
