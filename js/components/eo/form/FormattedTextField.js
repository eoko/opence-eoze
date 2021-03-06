/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 16 janv. 2012
 */
Ext.ns('eo.form');

/**
 * @xtype formattedtextfield
 */
eo.form.FormattedTextField = Ext.extend(Ext.form.TriggerField, {
	
	triggerClass : 'x-form-cog-trigger'
	
	,disableFormatting: false
	,format: 'UCFirst'
	
	// private
	,formatUCFirst: function(s) {
		var format = function(s) {
			return s.substr(0,1).toUpperCase() + s.substr(1).toLowerCase();
		};
		var parts = s.split(' '),
			fv = [];
		Ext.each(parts, function(part) {
			var parts = part.split('-'),
				r;
			if (parts) {
				r = [];
				Ext.each(parts, function(part) {
					r.push(format(part));
				});
				r = r.join('-');
			} else {
				r = format(part);
			}
			fv.push(r);
		});
		return fv.join(' ');
	}
	
	// private
	,doFormat: function(s) {
		if (!s) {
			return s;
		}
		switch (this.format) {
			case 'uc':
			case 'UC':
				return s.toUpperCase();
			case 'UCFirst':
			default:
				return this.formatUCFirst(s);
		}
	}
	
	// private
	,applyFormat: function() {
		if (!this.disableFormatting) {
			var v = this.getValue(),
				fv = !v ? v : this.doFormat(v);
			if (fv !== v) {
				this.setValue(fv);
			}
		}
	}

	// private
	,onBlur: function() {
		this.applyFormat();
		eo.form.FormattedTextField.superclass.onBlur.apply(this, arguments);
	}
	
	/**
	 * Enable or disable automatic formatting.
	 * @param {Boolean} enabled
	 */
	,setFormattingEnabled: function(enabled) {
		this.disableFormatting = !enabled;
	}
	
	,getMenu: function() {
		var m = this.menu;
		if (!m) {
			this.menu = m = new Ext.menu.Menu({
				items: [new Ext.menu.CheckItem({
					text: 'Formatage automatique'
					,checked: !this.disableFormatting
					,scope: this
					,checkHandler: function(item, checked) {
						this.disableFormatting = !checked;
						if (checked) {
							this.applyFormat();
						}
					}
				})]
				,hide: function() {
					this.forceHide.defer(150, this);
				}
				,forceHide: function() {
					Ext.menu.Menu.prototype.hide.call(this);
				}
			});
		}
		return m;
	}

	,onTriggerClick: function() {
		var m = this.getMenu();
		if (!m.isVisible()) {
			m.show(this.trigger, 'tr-br');
		} else {
			m.forceHide();
		}
	}
});

Ext.reg('formattedtextfield', 'eo.form.FormattedTextField');
Oce.deps.reg('eo.form.FormattedTextField');