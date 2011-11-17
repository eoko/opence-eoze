/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'locale', function(NS) {

var sp  = Ext.Panel,
	spp = sp.prototype;

/**
 * A convenience container Panel for contact FieldSets, which provides a top toolbar
 * that will contain the FieldSets add field buttons.
 */
NS.ContactPanel = Ext.extend(sp, {
	
	fieldSets: [
		{xtype: 'phonenumbersfieldset', name: 'phoneNumbers', allowBlank: false},
		{xtype: 'emailsfieldset', name: 'emails'},
		{xtype: 'addressesfieldset', name: 'addresses'},
		{xtype: 'organisationsfieldset', name: 'organisations'}
	]
	
	,autoScroll: true
	,bodyStyle: 'padding: 5px 2px; background: transparent;'
	
	,constructor: function(config) {
		if (config.name || this.name) {
			this.markInvalid = this.clearInvalid = Ext.emptyFn;
		}
		spp.constructor.apply(this, arguments);
	}
	
	,initComponent: function() {
		
		this.defaults = Ext.apply({
			anchor: '100%'
		}, this.defaults);
		
		var items = this.items = this.items || [], 
			tbar = [];
		
		var fieldSets = [];

		Ext.each(this.fieldSets, function(fs) {
			if (fs === '-' || fs === '->') {
				tbar.push(fs);
			} else {
				if (Ext.isString(fs) && Ext.ComponentMgr.isRegistered(fs)) {
					fs = {xtype: fs};
				}
				
				var fieldSet = Ext.create(fs);
				
				if (this.markInvalid) {
					fieldSet.clearInvalid = fieldSet.markInvalid = false;
				}
				
				fieldSets.push(fieldSet);

				items.push(fieldSet);
				tbar.push(fieldSet.createAddButton());
			}
		}, this);
		
		this.fieldSets = fieldSets;
		
		Ext.apply(this, {
			layout: 'form'
			,items: items
			,tbar: tbar
		});
		
		spp.initComponent.call(this);
	}
	
	,afterRender: function() {
		spp.afterRender.apply(this, arguments);
		if (this.value) {
			this.setValue(this.value);
		}
	}
	
	,getFieldSet: function(name) {
		var index = Ext.each(this.fieldSets, function(fs) {
			return fs.name !== name;
		});
		return this.fieldSets[index];
	}
	
	,getValue: function() {
		var data = {};
		Ext.each(this.fieldSets, function(fs) {
			data[fs.name] = fs.getValue();
		});
		return data;
	}
	
	,setValue: function(data) {
		Ext.iterate(data, function(name, value) {
			this.getFieldSet(name).setValue(value);
		}, this);
	}
	
	// Make this a recognized FormPanel.isField
//	,markInvalid: Ext.emptyFn
//	,clearInvalid: Ext.emptyFn
	,getName: function() {
		return this.name;
	}
	
	,validate: function() {
		var valid = true;
		Ext.each(this.fieldSets, function(fs) {
			if (!fs.validate()) {
				valid = false;
				return false;
			}
		});
		return valid;
	}
	
	,reset: function() {
		Ext.each(this.fieldSets, function(fs) {
			fs.reset();
		});
	}
	
});

Ext.reg('contactpanel', NS.ContactPanel);

}); // deps