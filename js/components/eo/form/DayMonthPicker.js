/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 déc. 2011
 */
Ext.ns('eo.form');

/**
 * 
 * @xtype daymonthpicker
 */
eo.form.DayMonthPicker = Ext.extend(Ext.form.CompositeField, {
	
	isFormField: true
	,submitValue: true
	,combineErrors: false
	
	,initComponent: function() {
		
		var days = [];
		for (var i=1; i<=31; i++) {
			days.push(i);
		}
		
		var monthNames = [
			'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 
			'Septembre', 'Octobre', 'Novembre', 'Décembre'
		];

		var months = [];
		i = 1;
		Ext.each(monthNames, function(m) {
			months.push([i++, m]);
		});
		
		var mc, dc;
		
		this.items = [this.dayCombo = dc = Ext.create({
			xtype: 'combo'
			,emptyText: 'Jour'
			,flex: 1
			,store: days
			,triggerAction: 'all'
			,minChars: 30
			,forceSelection: true
			,allowBlank: this.allowBlank
			,submitValue: false
			,getErrors: function(v) {
				var m = mc.getValue(),
					d = this.getValue(),
					err = Ext.form.ComboBox.prototype.getErrors.call(this, v),
					days = (32 - new Date(2000, m-1, 32).getDate());
				if (v > days) {
					err.push(monthNames[m-1] + ' ne compte que ' + days + ' jours');
				}
				return err;
			}
		}), mc = this.monthCombo = Ext.create({
			xtype: 'combo'
			,emptyText: 'Mois'
			,flex: 1.5
			,store: months
			,triggerAction: 'all'
//			,minChars: 30
			,typeAhead: true
			,typeAheadDelay: 10
//			,queryDelay: 5000
			,forceSelection: true
			,submitValue: false
			,allowBlank: this.allowBlank
//			,setValue: function() {
//				dc.validate();
//				return Ext.form.ComboBox.prototype.setValue.apply(this, arguments);
//			}
			,listeners: {
				scope: dc
				,select: dc.validate
				,change: dc.validate
			}
		})];
	
		if (this.dayField) {
			Ext.apply(this.dayCombo, {
				name: this.dayField
				,submitValue: true
			});
			// submitValue is used in fix.CompositeField
			// to assess whether the composite is a field or a container
			this.submitValue = false;
		}
		if (this.monthField) {
			Ext.apply(this.monthCombo, {
				name: this.monthField
				,submitValue: true
			});
			// submitValue is used in fix.CompositeField
			// to assess whether the composite is a field or a container
			this.submitValue = false;
		}
		
		eo.form.DayMonthPicker.superclass.initComponent.call(this);
	}
	
	,getName: function() {
		return this.name || this.id;
	}
	
	,setValue: function(v) {
		if (v) {
			this.dayCombo.setValue(v[0]);
			this.monthCombo.setValue(v[1]);
		} else {
			this.dayCombo.setValue(v);
			this.monthCombo.setValue(v);
		}
	}
	
	,getValue: function() {
		var d = this.dayCombo.getValue(), 
			m = this.monthCombo.getValue();
		if (d && m) {
			return [d, m];
		} else {
			return undefined;
		}
	}
	
//	,markInvalid: function() {
//		this.dayCombo.markInvalid();
//		this.monthCombo.markInvalid();
//	}
	
	,clearInvalid: function() {
		this.dayCombo.clearInvalid();
		this.monthCombo.clearInvalid();
	}
	
	,isValid: function(preventMark) {
		var v = this.getValue();
		if (!v) {
			return !!this.allowBlank 
		} else {
			return this.dayCombo.isValid(preventMark) && this.monthCombo.isValid(preventMark);
		}
	}
	
	,validate: function() {
		return this.allowBlank || this.dayCombo.validate() && this.monthCombo.validate();
	}
	
	,initValue: function() {
		this.originalValue = this.getValue();
	}
	
	,reset: function() {
		this.setValue(this.originalValue);
		this.clearInvalid();
	}
	
});

Ext.reg('daymonthpicker', eo.form.DayMonthPicker);