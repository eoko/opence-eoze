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
	
	,rowLabelWidth: 50
	
	,autoEl: {
		tag: 'table'
		,cls: 'eo-calendar-month'
	}

	,onRender: function(ct, position) {
		
		var table = this.el = Ext.get(Ext.DomHelper.createDom(this.autoEl));
		
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
			zone.render(body, dates);
		});
		
		// Set fixed width
		table.setWidth(nDays * 30 + this.rowLabelWidth);
		
		eo.form.calendar.MonthEditor.superclass.onRender.call(this, ct, position);
	}
	
	,getNumDays: function() {
		return 32 - new Date(this.year, this.month-1, 32).getDate();
	}
});

Ext.reg('eo.montheditor', eo.form.calendar.MonthEditor);