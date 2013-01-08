/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 déc. 2011
 */
Ext.ns('eo.form.calendar');

/**
 * @xtype eo.calendareditor
 */
eo.form.calendar.MonthEditor = Ext.extend(Ext.BoxComponent, {

	hideLabel: true
	
	,showWeekNumber: true
	
	,editable: true
	
	,lockable: false
	,locked: undefined
	
	,rowLabelWidth: 50
	
	,autoEl: {
		cls: 'eo-calendar-month-ct'
	}

	,onRender: function(ct, position) {
		
		var el = this.el = Ext.get(Ext.DomHelper.createDom(this.autoEl));
		
		if (this.editable) {
			el.addClass('editable');
		} else {
			el.addClass('readOnly');
		}
		
		if (this.lockable) {
			
			this.locked = !this.editable;
			
			el.addClass('lockable')

			var handle = this.lockHandle = el.createChild({
				cls: 'eo-calendar-month-handle'
			});
			
			handle.on({
				scope: this
				,click: function() {
					if (this.editable) {
						this.setLocked();
					}
				}
			});
			
			if (this.editable) {
				Ext.QuickTips.register({
					target: handle
					,text: 'Cliquez pour vérouiller ce mois et les précédents.'
				});
			}
		}

		var wrap = el.createChild({
			cls: 'eo-calendar-month-table-ct'
			,style: 'float: left'
		});
		
		var table = wrap.createChild({
			tag: 'table'
			,cls: 'eo-calendar-month'
		});
		
		var y = this.year,
			m = this.month - 1,
			nDays = this.getNumDays();
		
		var head = table.createChild({tag: 'thead'}),
			body = table.createChild({tag: 'tbody'});
			
		var days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
			months = ['JAN', 'FÉV', 'MAR', 'AVR', 'MAI', 'JUN', 'JUL', 'AOÛ', 'SEP', 'OCT',
					'NOV', 'DÉC'];
		
		var dates = [];
		for (var i=1; i <= nDays; i++) {
			dates.push(new Date(y, m, i));
		}

		// DisabledDays
		var disabledDays = this.disabledDays,
			disabledDayMap = {};
		if (disabledDays) {
			Ext.each(disabledDays, function(day) {
				if (Ext.isArray(day)) {
					for (var i=day[0], to=day[1]; i<=to; i++) {
						disabledDayMap[i] = true;
					}
				} else {
					disabledDayMap[day] = true;
				}
			});
		}
		
		// Dates
		var trDates = head.createChild({tag: 'tr'}),
			trDays = head.createChild({tag: 'tr'});
		
		trDates.createChild({
			tag:'th', 
			cls: 'row-label main', 
			rowspan: 2,
			html: String.format('{0}<br/>{1}', months[m], y)
		});

		Ext.each(dates, function(d) {
			
			var day = d.getDay(),
				date = d.getDate(),
				cls = 'day ' + (day === 0 || day === 6 ? 'week-end' : 'week');
				
			// Disabled days
			if (disabledDayMap[date]) {
				cls += ' disabled';
			}
				
			trDates.createChild({
				tag: 'th'
				,cls: cls
				,html: date
			});
			
			trDays.createChild({
				tag: 'th'
				,cls: cls
				,html: days[day]
			});
		});
		
		// Render zones
		Ext.iterate(this.zones, function(name, zone) {
			
			zone.setDisabledDayMap(disabledDayMap);
			
			zone.render(body, dates);
			if (handle) {
				this.mon(zone, 'visibilitychanged', function() {
					handle.setHeight(table.getHeight());
				});
			}
		}, this);
		
		// Set fixed width
		table.setWidth(nDays * 30 + this.rowLabelWidth);
		
		// Sync handle's height
		(function() {
			if (handle) {
				handle.setHeight(table.getHeight());
				el.setWidth(handle.getWidth() + table.getWidth());
			}
		}).defer(10);
		
		eo.form.calendar.MonthEditor.superclass.onRender.call(this, ct, position);
		
		this.mask = wrap.createChild({
			cls: 'eo-calendar-month-mask'
		});
        
        if (this.maskText) {
            this.mask.update(this.maskText);
        }
		
//		this.mask.setDisplayed(!this.editable);
	}
	
	,setLocked: function() {
		var el = this.el;
		el.editable = false;
		el.removeClass('editable');
		el.addClass('readOnly');
		
		this.locked = true;
		
		// Remove lock handle tooltip
		Ext.QuickTips.unregister(this.lockHandle);
		
		this.fireEvent('locked', this, this.locked);
	}
	
	,getNumDays: function() {
		return 32 - new Date(this.year, this.month-1, 32).getDate();
	}
});

Ext.reg('eo.montheditor', 'eo.form.calendar.MonthEditor');