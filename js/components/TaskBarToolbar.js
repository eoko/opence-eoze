
Oce.TaskBarToolbar = Ext.extend(Ext.Toolbar, {

	constructor: function(config) {
		Oce.TaskBarToolbar.superclass.constructor.call(this, config);
		this.displayed = !this.hidden;
	}

	,show: function(cb) {

		if (this.displayed) {
			if (cb) cb();
			return;
		}

		this.displayed = true;
		
		var show = Oce.TaskBarToolbar.superclass.show.createDelegate(this),
			me = this;

		show();
		this.el.slideIn("b", {
			callback: function() {
				me.ownerCt.doLayout();
				if (cb) cb();
			}
			,duration: .2
			,easing: "easeNone"
		});
	}

	,hide: function() {

		if (!this.displayed) return;
		this.displayed = false;

		var show = Oce.TaskBarToolbar.superclass.show.createDelegate(this),
			hide = Oce.TaskBarToolbar.superclass.hide.createDelegate(this);

		hide();
		this.ownerCt.doLayout();

		show();
		this.el.slideOut("b", {
			callback: function() {
				hide();
			}
			,duration: .2
			,easing: "easeNone"
		});
	}
	
	,createWindowButton: function(win, handler) {
		var button = new Ext.Button({
			text: win.title
			,handler: handler
			,enableToggle: true
		});
		
		button.mon(win, 'titlechange', function() {
			button.setText(win.title);
		});

		return button;
	}

	,addWindowPermanent: function(win) {
		this.show();

		var button = this.createWindowButton(win, function() {
			win.toggleMinimize();
		});

		this.add(button);
		this.doLayout();

//		win.showAnimDuration = 5;
		win.hideAnimDuration = 5;
//		win.setAnimateTarget(button.el);

		win.minimize = function() {
			win.hide(button.el);
		}

		win.toggleMinimize = function() {
			if (win.hidden) win.show(button.el);
//			else win.minimize();
			else win.hide(button.el);
		}
	}

	,addWindowTemporary: function(win) {

		var me = this;

		win.minimize = function() {
			var once = false;
			var handler = function() {
				if (once) return; else once = true;
				win.show(button.el);
				me.remove(button);
				me.doLayout();
				if (me.items.length === 0) {
					me.hide();
				}
			};

			var button = me.createWindowButton(win, handler);

			button.mon(win, 'show', handler);

			me.add(button);
			me.doLayout();

			me.show(function() {
				win.hide(button.el);
			});
		}
	}

	,addWindow: function(win) {
		if (this.permanent) {
			this.addWindowPermanent(win);
		} else {
			this.addWindowTemporary(win);
		}
	}

	,minimizeWindow: function(win) {

	}
});

