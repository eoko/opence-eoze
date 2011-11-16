/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'locale', function(NS) {

var sp  = Ext.Panel,
	spp = sp.prototype;

NS.ContactPanel = Ext.extend(sp, {
	
	fieldSets: [
		{xtype: 'phonenumbersfieldset', name: 'phoneNumbers', allowBlank: false},
		{xtype: 'emailsfieldset', name: 'emails'},
		{xtype: 'addressesfieldset', name: 'addresses'},
		{xtype: 'organisationsfieldset', name: 'organisations'}
	]
	
	,initComponent: function() {
		
		var items = [], tbar = [];
		
		var fieldSets = [];

		Ext.each(this.fieldSets, function(fs) {
			if (fs === '-' || fs === '->') {
				tbar.push(fs);
			} else {
				if (Ext.isString(fs) && Ext.ComponentMgr.isRegistered(fs)) {
					fs = {xtype: fs};
				}
				
				var fieldSet = Ext.create(fs);
				
				fieldSets.push(fieldSet);

				items.push(fieldSet);
				tbar.push(fieldSet.createAddButton());
			}
		});
		
		this.fieldSets = fieldSets;
		
		Ext.apply(this, {
			
			layout: 'form'
			,items: items
			
			,bodyStyle: 'padding: 0px; background: transparent;'

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
	
});

Ext.reg('contactpanel', NS.ContactPanel);

}); // deps