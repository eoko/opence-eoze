/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 nov. 2011
 */
Oce.deps.wait('eo.form.ComboBox', function() {

Ext.ns('eo.form');
	
var sppCombo = Ext.form.ComboBox.prototype,
	sppTwin  = Ext.form.TwinTriggerField.prototype;

/**
 * @xtype clearablecombo
 */
eo.form.ClearableComboBox = Ext.extend(eo.form.ComboBox, {
    
	clearable: true
	
	,trigger1Class : 'x-form-clear-trigger'
	,trigger2Class : 'x-form-select-trigger'
	
	,initComponent: function(){
		
		this.addEvents(
			/**
			 * @event clear
			 * @param {eo.form.ClearableComboBox} this
			 */
			'clear'
		);
		
		if (!this.allowBlank || !this.clearable) {
			this.hideTrigger1 = true;
		}
		
		if (this.hideTrigger) {
			this.hideTrigger = false;
			this.hideTrigger2 = true;
		}
		
		sppCombo.initComponent.call(this);
		
		this.addClass('eo-clearable-combo');

        this.triggerConfig = {
            tag:'span', cls:'x-form-twin-triggers', cn:[
            {tag: "img", src: Ext.BLANK_IMAGE_URL, alt: "", cls: "x-form-trigger " + this.trigger1Class},
            {tag: "img", src: Ext.BLANK_IMAGE_URL, alt: "", cls: "x-form-trigger " + this.trigger2Class}
        ]};
    }
	
	,updateEditState: function() {
		sppCombo.updateEditState.call(this);
		if (this.rendered) {
			if (this.readOnly) {
				this.addClass('readOnly');
			} else {
				this.removeClass('readOnly');
			}
		}
	}
	
    ,getTrigger: sppTwin.getTrigger
    
    ,afterRender: sppTwin.afterRender

    ,initTrigger: sppTwin.initTrigger

    ,getTriggerWidth: sppTwin.getTriggerWidth
	
	,onTrigger1Click : function() {
		// The focus must be set *before* the setValue method is called,
		// because the `startValue` of the field will be updated when it
		// gets the focus; and after, when the field blur the `startValue`
		// will be compared to the current value. If setValue is called
		// before focus, `startValue` will be set to ''.
		this.focus();
		if (this.getValue()) {
			this.setValue();
			this.fireEvent('clear', this);
		}
	}
	
	,onTrigger2Click : sppCombo.onTriggerClick
});

Ext.reg('clearablecombo', eo.form.ClearableComboBox);
Oce.deps.reg('eo.form.ClearableComboBox');

}); // deps