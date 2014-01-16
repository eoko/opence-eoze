/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 févr. 2012
 */
(function() {
	
Ext.ns('eo.form.calendar');

eo.form.calendar.Palette = Ext.extend(Ext.ButtonGroup, {
	
	initComponent: function() {
		
		var items = this.items = [],
			lu = this.valueLookup = {};
		
		var pressed = true;
		
		Ext.each(this.values, function(v) {
			if (!Ext.isObject(v)) {
				v = {
					value: v
					,text: v
				};
			}
			
			if (false == v instanceof PaletteValue) {
				v = new PaletteValue(v);
			}
			
			if (pressed) {
				this.currentValue = v;
			}
			
			items.push({
				
				value: v
				,text: v.getText()
				,iconCls: v.getIconCls()
				
				,enableToggle: true
				,toggleGroup: this.id
				,pressed: pressed
				,scope: this
				,toggleHandler: this.onButtonPress
			});
			
			lu[v.getValue()] = v;
			
			// Next ones are not pressed
			pressed = false;
		}, this);
		
		eo.form.calendar.Palette.superclass.initComponent.call(this);
	}
	
	// private
	,onButtonPress: function(button, state) {
		if (state) {
			this.currentValue = button.value;
		}
	}
	
	/**
	 * Gets the currently selected value in the palette.
	 * @return {eo.form.calendar.PaletteValue}
	 */
	,getCurrentValue: function() {
		return this.currentValue;
	}
	
	/**
	 * Gets the PaletteValue for the specified raw value.
	 * @return {eo.form.calendar.PaletteValue/Mixed}
	 */
	,getValueFor: function(v) {
		return this.valueLookup[v] || v;
	}
});

eo.form.calendar.PaletteValue = Ext.extend(Object, {
	
	text: undefined
	
	,value: undefined

	/**
	 * @cfg {String} cellCls A CSS class string to apply to cell's with this value.
	 */
	,cellCls: undefined
	
	/**
	 * @cfg {String} iconCls A CSS class to be applied to this value's palette button.
	 */
	,iconCls: undefined
	
	,constructor: function(config) {
		if (Ext.isObject(config)) {
			Ext.apply(this, config);
		} else {
			this.value = config;
		}
	}
	
	,getText: function() {
		return Ext.isDefined(this.text) ? this.text : this.value;
	}
	
	,getValue: function() {
		return this.value;
	}
	
	,getCellCls: function() {
		return this.cellCls;
	}
	
	,getIconCls: function() {
		return this.iconCls;
	}
});

var PaletteValue = eo.form.calendar.PaletteValue;

})(); // closure