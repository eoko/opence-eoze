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
					
					z.on('change', function() {
						this.dirty = true;
						this.fireEvent('change', this);
					}, this);
//					this.relayEvents(z, ['change']);
				}
		}, this);
			
		// Add month editors
		if (this.from || Ext.isArray(this.months)) {
			this.createMonthEditors();
		}
		
		spp.initComponent.call(this);
	}
	
	,lockMonthsBefore: true
	
	,lockable: true
	
	// private
	,onMonthLocked: function(monthEditor) {
		if (this.lockMonthsBefore) {
			var umb = []; // Unlocked Months Before
			Ext.each(this.monthEditors, function(m) {
				if (m == monthEditor) {
					return false;
				} else {
					if (!m.locked) {
						umb.push(m);
					}
				}
			});
			Ext.each(umb, function(ed) {
				ed.setLocked(true);
			});
		}
		this.dirty = true;
		this.fireEvent('change', this);
	}
	
	// private
	,createMonthEditor: function(config) {

		var ed = new eo.form.calendar.MonthEditor(Ext.apply({
			zones: this.zones
			,rowLabelWidth: this.rowLabelWidth
			,lockable: this.lockable
		}, cfg));

		ed.on({
			scope: this
			,locked: this.onMonthLocked
		});

		return ed;
	}
	
	// private
	,createMonthEditorsFromFrom: function() {
			
		var from = this.from,
		
			nMonths = from.months || this.months,
			month = from.month,
			year = from.year,
			
			mEds = this.monthEditors = [],
			monthEditor;

		for (var i=0; i<nMonths; i++) {
			
			monthEditor = this.createMonthEditor({
				year: year
				,month: month
			});
			
			mEds.push(monthEditor);
			
			if (++month === 13) {
				month = 1;
				year++;
			}
		}
	}
	
	// private
	,createMonthEditorsFromMonths: function() {
		var eds = this.monthEditors = [];
		Ext.each(this.months, function(month) {
			eds.push(this.createMonthEditor(month));
		}, this);
	}
	
	// private
	,createMonthEditors: function(render) {
		
		if (this.from) {
			this.createMonthEditorsFromFrom();
		} else if (Ext.isArray(this.months)) {
			this.createMonthEditorsFromMonths();
		} else {
			throw new Error('Months specification missing.');
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
		
		this.clear();
		
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
	
	,clear: function() {
		
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
	}
	
	,setMonths: function(months) {
		
		this.clear();
		
		delete this.from;
		this.months = months;
		
		this.createMonthEditors(true);
	}
	
	,setValue: function(v) {
		
		var DR = eo.form.calendar.DateRange,
			value = this.value = {},
			palette = this.palette;
		
		// Convert to date range
		Ext.iterate(v, function(zone, ranges) {
			var rr = [];
			if (ranges.length && !Ext.isArray(ranges[0])) {
//				rr.push(new DR(ranges));
				Ext.each(ranges, function(range) {
					var v = range.value;
					if (palette) {
						v = palette.getValueFor(v);
					}
					rr.push(new DR(range.from, range.to, v));
				})
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
		
		// Clean dirty state
		this.dirty = false;
	}
	
	,getValue: function() {
		var zones = {},
			months = [];
			
		Ext.iterate(this.zones, function(name, zone) {
			zones[name] = zone.getValue();
		});
		
		Ext.each(this.monthEditors, function(ed) {
			months.push({
				year: ed.year
				,month: ed.month
				,locked: ed.locked
			});
		});
		
		return {
			zones: zones
			,months: months
		};
	}

	,isDirty: function() {
		return this.dirty;
	}
//	,isDirty: function() {
//		
//		var s = function(v) {
//			if (Ext.isObject(v)) {
//				var r = [];
//				Ext.iterate(v, function(n, v) {
//					r.push(n + ':{' + String(v) + '}');
//				});
//				return r.join(';');
//			} else {
//				return String(v);
//			}
//		};
//		
//		return function() {
//			return false;
////			var r = s(this.getValue()) !== s(this.originalValue);
////			if (r) {
////				debugger
////			}
//			return s(this.getValue()) !== s(this.originalValue);
//		};
////		var r = spp.isDirty.apply(this, arguments);
////		if (r) {
////			debugger
////		}
////		return r;
//	}()
	
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