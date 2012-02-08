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
		
		// Build zones
		var Zone = eo.form.calendar.Zone,
			zones = this.zones = {},
			nextIsGroup = false;
		
		Ext.each(this.initialConfig.zones, function(zone) {
				if (zone === '-') {
					nextIsGroup = true;
				} else {
					var z = new Zone(Ext.apply({
						editor: this
					}, zone));
					if (nextIsGroup) {
						z.groupFirst = true;
						nextIsGroup = false;
					}
					zones[zone.name] = z;
					
					this.relayEvents(z, ['change']);
				}
		}, this);
			
		// Add month editors
		if (this.from) {
			this.createMonthEditors();
		}
		
		spp.initComponent.call(this);
	}
	
	// private
	,createMonthEditors: function(render) {
		var MonthEditor = eo.form.calendar.MonthEditor;
		
		var from = this.from,
			nMonths = from.months || this.months,
			rlw = this.rowLabelWidth,
			zones = this.zones;
			
		var month = from.month,
			year = from.year;
			
		var mEds = this.monthEditors = [],
			monthEditor, i;
			
		for (i=0; i<nMonths; i++) {
			
			monthEditor = new MonthEditor({
				year: year
				,month: month
				,zones: zones
				,rowLabelWidth: rlw
			});
			
			mEds.push(monthEditor);
			
			if (++month === 13) {
				month = 1;
				year++;
			}
		}
		
		// Render
		if (render) {
			var el = this.el;
			Ext.each(this.monthEditors, function(me) {
				me.render(el);
			});
			// Reload the value into the new rendered elements
			if (this.value) {
				this.applyValue();
			}
		}
	}
	
	,setFrom: function(year, month, months) {
		
		if (Ext.isObject(year)) {
			month = year.month;
			months = year.months;
			year = year.year;
		}
		
		// Clear month editors
		if (this.monthEditors) {
			Ext.each(this.monthEditors, function(ed) {
				ed.destroy();
			});
		}
		
		// Reset zones
		Ext.iterate(this.zones, function(name, zone) {
			zone.clear();
		});
		
		// Init new values
		Ext.apply(this, {
			from: {
				year: year
				,month: month
			}
			,months: months || this.initialConfig.months
		});
		
		// And finaly, rebuild the month editors
		this.createMonthEditors(true);
	}
	
	,setValue: function(v) {
		
		var DR = eo.form.calendar.DateRange,
			value = this.value = {};
		
		// Convert to date range
		Ext.iterate(v, function(zone, ranges) {
			var rr = [];
			if (ranges.length && !Ext.isArray(ranges[0])) {
				rr.push(new DR(ranges));
			} else {
				Ext.each(ranges, function(range) {
					rr.push(new DR(range));
				});
			}
			value[zone] = rr;
		});
		
		if (this.rendered) {
			this.applyValue();
		}
	}
	
	,getValue: function() {
		var r = {};
		Ext.iterate(this.zones, function(name, zone) {
			r[name] = zone.getValue();
		});
		return r;
	}
	
	,isDirty: function() {
		
		var s = function(v) {
			if (Ext.isObject(v)) {
				var r = [];
				Ext.iterate(v, function(n, v) {
					r.push(n + ':{' + String(v) + '}');
				});
				return r.join(';');
			} else {
				return String(v);
			}
		};
		
		return function() {
//			var r = s(this.getValue()) !== s(this.originalValue);
//			if (r) {
//				debugger
//			}
			return s(this.getValue()) !== s(this.originalValue);
		};
//		var r = spp.isDirty.apply(this, arguments);
//		if (r) {
//			debugger
//		}
//		return r;
	}()
	
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
		
		var eds = this.monthEditors;
		if (eds) { // else the month editors haven't been created
			Ext.each(eds, function(me) {
				me.render(el);
			});
		}
		
//		spp.superclass.onRender.call(this, ct, position);
		Ext.Component.prototype.onRender.call(this, ct, position);
	}
	
	/**
	 * @return eo.form.calendar.Palette
	 */
	,getPalette: function() {
		var p = this.palette;
		if (p instanceof eo.form.calendar.Palette) {
			return p;
		} else {
			return undefined;
		}
	}
});

Ext.reg('calendarzoneseditor', eo.form.calendar.ZonesEditor);
	
var spp = eo.form.calendar.ZonesEditor.superclass;
})(); // closure