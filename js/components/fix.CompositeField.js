// Fixes the broken findParentBy method of CompositeField's children items
Ext.form.CompositeField.prototype.initComponent = 
	Ext.form.CompositeField.prototype.initComponent.createSequence(function(){

	if (!this.innerCt.ownerCt) {
		this.innerCt.ownerCt = this;
	} else {
		// The bug has been fixed, this hack is no longer needed
		debugger;
	}
});

// Fixes CompositeField ignoring its children fields' preventMark value
(function() {
	var uber = {
		onFieldMarkInvalid: Ext.form.CompositeField.prototype.onFieldMarkInvalid
		,validateValue: Ext.form.CompositeField.prototype.validateValue
	};
	Ext.override(Ext.form.CompositeField, {
		onFieldMarkInvalid: function(field, message) {
			if (!field.preventMark) {
				uber.onFieldMarkInvalid.apply(this, arguments);
			}
		},
		validateValue: function() {
			var valid = true;

			this.eachItem(function(field) {
				if (field.el && field.el.dom && !field.isValid(this.preventMark)) valid = false;
			});

			return valid;
		}
	});
})();
