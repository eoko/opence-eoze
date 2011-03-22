// http://www.sencha.com/forum/showthread.php?15967-2.0-Ext.ux.TwinComboBox-to-Clear-Field&p=76664#post76664
Ext.namespace('Ext.ux.form');

Ext.ux.form.TwinComboBox = Ext.extend(Ext.form.ComboBox, {

	initComponent: Ext.form.TwinTriggerField.prototype.initComponent,
	getTrigger: Ext.form.TwinTriggerField.prototype.getTrigger,
	initTrigger: Ext.form.TwinTriggerField.prototype.initTrigger,
	onTrigger2Click: Ext.form.ComboBox.prototype.onTriggerClick,
	trigger1Class : 'x-form-clear-trigger'
//	trigger1Class: Ext.form.ComboBox.prototype.triggerClass

//  getTrigger : Ext.form.TwinTriggerField.prototype.getTrigger,
//  initTrigger : Ext.form.TwinTriggerField.prototype.initTrigger,

	,clearable: false
	,hideTrigger1 : true

	,constructor: function(config) {
		if (config.clearable === undefined) {
			this.clearable = 'allowBlank' in config ? config.allowBlank : true;
		}
		Ext.ux.form.TwinComboBox.superclass.constructor.call(this, config);
	}

	,reset : function() {
		Ext.ux.form.TwinComboBox.superclass.reset.apply(this, arguments);
		if (!this.clearable) {
			if (this.el) {
				this.triggers[0].hide();
			} else {
				this.hideTrigger1 = true;
			}
		}
	}

	,setValue: function() {
		Ext.ux.form.TwinComboBox.superclass.setValue.apply(this, arguments);
		if (this.clearable) {
			if (this.value) {
				if (this.el) this.triggers[0].show();
				else this.hideTrigger1 = false;
			} else {
				if (this.el) this.triggers[0].hide();
				else this.hideTrigger1 = true;
			}
		}
	}

	,onTrigger1Click : function() {
		this.clearValue();
		this.triggers[0].hide();
		this.fireEvent('clear', this);
		this.fireEvent('select', this);
	}
});

Ext.ux.form.TwinComboBox = Ext.form.ComboBox;

Ext.ComponentMgr.registerType('twincombo', Ext.ux.form.TwinComboBox);

Oce.deps.reg('Ext.ux.form.TwinComboBox')