Ext.ns('eo.form');

eo.form.EnumDisplayField = Ext.extend(Ext.form.DisplayField, {

	setValue: function(date) {
		return eo.form.EnumDisplayField.superclass.setValue.call(
			this,
			this.enumField.getValueLabel('readonly','form')
		);
	}
});

Ext.reg('enumdisplayfield', eo.form.EnumDisplayField);