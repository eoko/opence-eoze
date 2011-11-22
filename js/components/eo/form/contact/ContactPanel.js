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
eo.form.contact.ContactPanel = Ext.extend(Ext.Panel, {

//	fieldSets: [
//		{xtype: 'phonenumbersfieldset', name: 'phoneNumbers', allowBlank: false},
//		{xtype: 'emailsfieldset', name: 'emails'},
//		{xtype: 'addressesfieldset', name: 'addresses'},
//		{xtype: 'organisationsfieldset', name: 'organisations'}
//	]
	fieldSets: undefined
	
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

		// Don't touch the original array! The array passed to the parent's
		// initComponent method must be a copy.
		// var items = this.items = this.items || [], 
		var items = [],
			configFieldSets = [],
			tbar = [];
		
		if (this.items) {
			Ext.each(this.items, function(item) {
				if (item.xtypeChildren) {
					configFieldSets.push(item);
				} else {
					items.push(item);
				}
			});
		}
		
		var fieldSets = [],
			hasFieldSetWithPrimarySelection = false;
			
		if (this.fieldSets) {
			configFieldSets = configFieldSets.concat(this.fieldSets);
		}

		Ext.each(configFieldSets, function(fs) {
			if (fs === '-' || fs === '->') {
				tbar.push(fs);
			} else {
				if (Ext.isString(fs) && Ext.ComponentMgr.isRegistered(fs)) {
					fs = {xtype: fs};
				} else if (!fs.xtype) {
					fs = Ext.apply({
						xtype: 'contactfieldset'
					}, fs);
				}
				
				var fieldSet = Ext.create(fs);
				
				if (fieldSet.hasPrimaryFieldSelection()) {
					hasFieldSetWithPrimarySelection = true;
				}
				
				if (this.markInvalid) {
					fieldSet.clearInvalid = fieldSet.markInvalid = false;
				}
				
				fieldSets.push(fieldSet);

				items.push(fieldSet);
				tbar.push(fieldSet.createAddButton());
			}
		}, this);
		
		if (hasFieldSetWithPrimarySelection) {
			Ext.each(fieldSets, function(fs) {
				fs.defaults = Ext.apply(fs.defaults, {
					defaultReservePrimaryButtonSpace: true
				});
			});
		}
		
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