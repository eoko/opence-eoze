/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 déc. 2011
 */
(function() {

Ext.ns('eo.form.calendar');

/**
 * @xtype calendarzoneseditor
 */
eo.form.calendar.ZonesEditor = Ext.extend(Ext.form.Field, {
	
	months: 12
	
	,rowLabelWidth: 50
	
	,hideLabel: true
	
	,initComponent: function() {
		
		var MonthEditor = eo.form.calendar.MonthEditor;
		
		var from = this.from,
			nMonths = this.months,
			rlw = this.rowLabelWidth;
			
		var month = from.month,
			year = from.year;
			
		var mes = this.months = [],
			monthEditor, i;
			
		// Build zones
		var Zone = eo.form.calendar.Zone,
			zones = this.zones = {},
			nextIsGroup = false;
		
		Ext.each(this.initialConfig.zones, function(zone) {
				if (zone === '-') {
					nextIsGroup = true;
				} else {
					var z = new Zone(zone);
					if (nextIsGroup) {
						z.groupFirst = true;
						nextIsGroup = false;
					}
					zones[zone.name] = z;
				}
		});
			
		// Add month editors
		for (i=0; i<nMonths; i++) {
			
			monthEditor = new MonthEditor({
				year: year
				,month: month
				,zones: zones
				,rowLabelWidth: rlw
			});
			
			mes.push(monthEditor);
			
			if (++month === 13) {
				month = 1;
				year++;
			}
		}
		
		spp.initComponent.call(this);
	}
	
	,setFrom: function(year, month, months) {
		
		Ext.each(this.months, function(med) {
			med.destroy();
		});
		
		Ext.iterate(this.zones, function(name, zone) {
			
		});
	}
	
	,setValue: function(v) {
		
		var DR = eo.form.calendar.DateRange,
			value = this.value = {};
		
		// Convert to date range
		Ext.iterate(v, function(zone, ranges) {
			var rr = [];
			Ext.each(ranges, function(range) {
				rr.push(new DR(range));
			});
			value[zone] = rr;
		});
		
		if (this.rendered) {
			this.applyValue();
//			Ext.each(this.months, function(editor) {
//				editor.setValue(v);
//			});
		}
	}
	
	,applyValue: function() {
		Ext.iterate(this.value, function(zone, ranges) {
			this.zones[zone].setValue(ranges);
		}, this);
	}
	
	,onRender: function(ct, position) {
		
		var el = this.el = Ext.DomHelper.createDom({
			tag: 'div'
			,cls: 'eo-calendar-editor'
		});
		
		Ext.each(this.months, function(me) {
			me.render(el);
		});
		
//		spp.superclass.onRender.call(this, ct, position);
		Ext.Component.prototype.onRender.call(this, ct, position);
	}
});

Ext.reg('calendarzoneseditor', eo.form.calendar.ZonesEditor);
	
var spp = eo.form.calendar.ZonesEditor.superclass;
})(); // closure