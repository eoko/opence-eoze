/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 déc. 2011
 */
Ext.ns('eo.form.calendar');

eo.form.calendar.Zone = Ext.extend(Ext.util.Observable, {
	
	hidden: false
	
	,zone: undefined
	
	/**
	 * The TR elements rendered by this zone.
	 * @private
	 */
	,tableRows: undefined
	
	/**
	 * The {@link eo.form.calendar.Zone.Cell}s representing this zone's days.
	 */
	,days: undefined
	
	,constructor: function(config) {

		// Init instance variables
		this.clear();
		
		Ext.apply(this, config);
		
		eo.form.calendar.Zone.superclass.constructor.call(this, config);
	}
	
	,hide: function() {
		this.setVisible(false);
	}
	
	,show: function() {
		this.setVisible(true);
	}
	
	,setVisible: function(visible) {
		this.hidden = !visible;
		Ext.each(this.tableRows, function(el) {
			el.setDisplayed(visible);
		});
	}
	
	,render: function(tbody, dates) {
		
		var name = this.name,
			title = this.title || name,
			editable = !!this.editable;
			
		var cls = 'zone ' + name;
		
		if (this.groupFirst) {
			cls += ' group-first';
		}
		if (editable) {
			cls += ' editable';
		}

		var tr = tbody.createChild({tag: 'tr', cls: cls});
		
		this.tableRows.push(tr);

		var th = tr.createChild({
			tag:'th'
			,cls: 'row-label'
			,html: String.format('<div>{0}</div>', title)
		});

		// hidden
		if (this.hidden) {
			tr.setDisplayed(false);
		}
		
		var days = this.days,
			Cell = eo.form.calendar.Zone.Cell,
			cells = [];
		
		Ext.each(dates, function(d) {

			var day = d.getDay(),
				date = d.getDate(),
				cls = 'day ' + (day === 0 || day === 6 ? 'week-end' : 'week');
				
			var td = tr.createChild({
				tag: 'td'
				,cls: cls
			});

			// Save ref
			var key = d.format('Ymd'),
				cell = days[key];
			
			if (!cell) {
				cell = days[key] = new Cell(this);
			}

			cell.add(td);
			cells.push(cell);
		}, this);
		
		// Add row head listener
		if (this.editable) {
			th.on('click', function() {
				var i = cells.length,
					v = false;
				while (i--) {
					if (!cells[i].value) {
						v = true;
						break;
					}
				}
				i = cells.length;
				while (i--) {
					cells[i].setValue(v);
				}
			});
		}
	}
	
	,setValue: function(ranges) {
		var days = this.days;
		Ext.each(ranges, function(range) {
			range.each(function(ymd) {
				var cell = days[ymd];
				if (cell) {
					cell.setOn();
				}
			});
		});
	}
	
	,clear: function() {
		this.days = {};
		this.tableRows = [];
	}
	
	,getEditor: function() {
		return this.editor;
	}
	
	,getSelectingValue: function(previous) {
		var palette = this.getEditor().getPalette();
		if (palette) {
			return palette.getCurrentValue();
		} else {
			return !previous;
		}
	}
	
});

eo.form.calendar.Zone.Cell = Ext.extend(Object, {

	value: false

	,constructor: function(zone) {
		this.zone = zone;
		this.els = [];
	}

	,add: function(td) {

		// Save ref
		this.els.push(td);

		// Create marker
		td.createChild({
			tag: 'div'
			,cls: 'marker'
		});

		// Add listeners
		if (this.zone.editable) {
			td.on({
				scope: this
				,mousedown: this.onMouseDown
				,mouseover: this.onMouseIn
			});
		}
	}

	// private
	,onMouseDown: function() {
		var z = this.zone,
			v = z.getSelectingValue(this.value);
		// Register out event
		Ext.getDoc().on({
			scope: this
			,single: true
			,mouseup: this.onMouseUp
		});
		// Put the zone in selecting mode
		z.selecting = v;
		// Change value for the clicked cell
		this.setValue(v);
	}

	// private
	,onMouseUp: function() {
		this.zone.selecting = null;
	}

	// private
	,onMouseIn: function() {
		var s = this.zone.selecting;
		if (s !== null) {
			this.setValue(s);
		}
	}
	
	// private
	,getMarkerCssClass: function(value) {
		if (value instanceof eo.form.calendar.PaletteValue) {
			return value.getCellCls();
		} else if (value) {
			return 'on';
		}
	}

	,setValue: function(value) {
		if (this.value !== value) {
			var els = this.els,
				i = els.length;

			while (i--) {
				var mel = els[i].down('.marker'),
					cls;
				// previous value
				if ((cls = this.getMarkerCssClass(this.value))) {
					mel.removeClass(cls);
				}
				// new value
				if ((cls = this.getMarkerCssClass(value))) {
					mel.addClass(cls);
				}
			}
			
			this.value = value;
		}
	}

	,setOn: function() {
		this.setValue(true);
	}

	,setOff: function() {
		this.setValue(false);
	}
});