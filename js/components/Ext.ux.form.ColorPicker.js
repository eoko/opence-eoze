/**
 * Ext.ux.form.ColorPicker
 *
 * @author  Éric Ortéga <eric@planysphere.fr>
 * @version 0.0.0.1
 * @date    4/15/11 1:19 PM
 *
 * @copyright © 2011 Éric Ortéga <eric@planysphere.fr>
 * @license GPLv3 http://www.gnu.org/licenses/gpl.html
 * 
 * @todo when the user has started entering a value and the value string does
 *       not begin with a #, the maxlength of the input should be set to 6
 */

(function() {
	
Ext.ns("Ext.ux.form");	

var sp = Ext.form.TriggerField,
	spp = sp.prototype;
	
/**
 * @class Ext.ux.form.ColobComboBox
 * @extends Ext.form.TriggerField
 */
Ext.ux.form.ColorPicker = sp.extend({
	
	value: "#FFFFFF"
	
	,initComponent: function() {
		
		this.autoCreate = Ext.applyIf({
			maxlength: 7
		}, this.defaultAutoCreate);
		
		this.maskRe = /[0-9a-fA-F]/;
		this.stripCharsRe = /[^0-9a-fA-F#]/gi;
		this.maxLength = 7;
		
		spp.initComponent.call(this);
		
		this.value = this.parseValue(this.value);
		
		this.menu = new Ext.menu.ColorMenu({
			handler: this.onColorPick
			,scope: this
			
			,listeners: {
				hide: function(menu) {
					menu.justHidden = true;
					setTimeout(function() {
						menu.justHidden = false;
					}, 100);
				}
			}
		});
		
		this.menu.palette.select = this.menuPaletteSelect;
	}
	
	// overridden to allow selection of color not present in the palette
	// private
	,menuPaletteSelect: function(color, suppressEvent){
		color = color.replace('#', '');
		if(color != this.value || this.allowReselect){
			var el = this.el;
			if (el) {
				var c;
				if(this.value){
					c = el.child('a.color-'+this.value);
					if (c) c.removeClass('x-color-palette-sel');
				}
				c = el.child('a.color-'+color);
				if (c) c.addClass('x-color-palette-sel');
			}
			this.value = color;
			if(suppressEvent !== true){
				this.fireEvent('select', this, color);
			}
		}
	}
	
	,parseValue: function(value) {
		if (!Ext.isString(value)) return '#FFFFFF';
		if (value.substr(0,1) === "#") value = value.substr(1);
		var l = value.length;
		if (l > 6) {
			value = value.substr(0,6);
			l = value.length;
		}
		if (l === 3) {
			var tmp = "";
			for (var i=0; i<l; i++) {
				tmp += value[i];
				tmp += value[i];
			}
			value = tmp;
			l = value.length;
		}
		if (l === 6) {
			return "#" + value.toUpperCase();
		} else {
			return "";
		}
	}
	
	,beforeBlur: function() {
		var v = this.parseValue(this.getRawValue());
		if (!Ext.isEmpty(v)) this.setValue(v);
	}
	
	,onTriggerClick: function() {
		// If already visible, the menu will autohide when the trigger is 
		// clicked; by the time the event get here, the menu will be hidden.
		if (!this.menu.justHidden) {
			this.menu.show(this.wrap);
		}
	}
	
	,setValue: function(v) {
		var cb = this.colorBox,
			menu = this.menu;
		if (cb) cb.setStyle("background-color", v);
		if (menu) menu.palette.select(v, true);
		spp.setValue.call(this, v);
	}
	
	,getValue: function() {
		return this.parseValue(spp.getValue.call(this));
	}
	
	,onColorPick: function(cp, color) {
		this.setValue("#" + color);
	}
	
	,onRender: function() {
		spp.onRender.apply(this, arguments);
		
		var el = this.colorBox = new Ext.Element(Ext.DomHelper.createDom({
			tag: "div"
			,cls: "x-form-colorcombo-box"
		}));
		
		this.wrap.insertFirst(el);
		
		if (this.value) this.setValue(this.value);
	}
	
	,getTriggerWidth: function() {
		return spp.getTriggerWidth.call(this) + this.colorBox.getWidth();
	}
	
});

Ext.reg('colorpicker', Ext.ux.form.ColorPicker);

})(); // closure


// demo
//Ext.onReady(function() {(new Ext.Window({
//	items: new Ext.FormPanel({
//		items: [{
//			xtype: 'colorpicker'
//			,fieldLabel: "Firld"
//			,value: "000"
//		},{
//			xtype: "numberfield"
//			,fieldLabel: "Firld"
//		}]
//		,defaults: {
//			width: 150
//		}
//	})
//})).show()});
