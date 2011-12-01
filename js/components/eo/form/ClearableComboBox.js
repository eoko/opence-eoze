/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 nov. 2011
 */
Ext.ns('eo.form');

(function() {
	
var sppCombo = Ext.form.ComboBox.prototype,
	sppTwin  = Ext.form.TwinTriggerField.prototype;
	
eo.form.ClearableComboBox = Ext.extend(Ext.form.ComboBox, {
    
	clearable: true
	
	,trigger1Class : 'x-form-clear-trigger'
	,trigger2Class : 'x-form-select-trigger'
	
	,initComponent: function(){
		
		if (!this.allowBlank || !this.clearable) {
			this.hideTrigger1 = true;
		}
		
		if (this.hideTrigger) {
			this.hideTrigger = false;
			this.hideTrigger2 = true;
		}
		
		sppCombo.initComponent.call(this);

        this.triggerConfig = {
            tag:'span', cls:'x-form-twin-triggers', cn:[
            {tag: "img", src: Ext.BLANK_IMAGE_URL, alt: "", cls: "x-form-trigger " + this.trigger1Class},
            {tag: "img", src: Ext.BLANK_IMAGE_URL, alt: "", cls: "x-form-trigger " + this.trigger2Class}
        ]};
    }
	
    ,getTrigger: sppTwin.getTrigger
    
    ,afterRender: sppTwin.afterRender

    ,initTrigger: sppTwin.initTrigger

    ,getTriggerWidth: sppTwin.getTriggerWidth
	
	,onTrigger1Click : function() {
		this.setValue();
		this.focus();
	}
	
	,onTrigger2Click : sppCombo.onTriggerClick
});
})(); // closure