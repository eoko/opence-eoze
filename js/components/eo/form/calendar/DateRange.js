/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 déc. 2011
 */
Ext.ns('eo.form.calendar');

eo.form.calendar.DateRange = Ext.extend(Object, {
	
	from: undefined
	,to: undefined
	
	,value: null
	
	,constructor: function(from, to, value) {
		if (Ext.isArray(from)) {
			if (from.length === 2) {
				to = from[1];
				from = from[0];
			} else {
				throw new Error('Invalid range spec: ' + String(from));
			}
		}
		if (!(from instanceof Date)) {
			from = new Date(from);
		}
		if (!(to instanceof Date)) {
			to = new Date(to);
		}
		
		if (from > to) {
			throw new Error('Date from must be before date to');
		}
		
		from.clearTime();
		to.clearTime();
		
		this.from = from;
		this.to = to;
		this.value = value || null;
	}

	// To work as expeced with Field.isDirty()
	,toString: function() {
		var f = 'Y-m-d';
		return String.format('[{0}, {1}]', this.from.format(f), this.to.format(f));
	}
	
	,each: function(fn, scope) {
		var from = this.from,
			to = this.to.add(Date.SECOND, 1),
			cursor = new Date(from),
			f = 'Y-m-d';
		while (cursor < to) {
			fn.call(scope, cursor.format(f));
			cursor = cursor.add(Date.DAY, 1);
		}
	}
	
	,isEmpty: function() {
		var f = this.from,
			t = this.to;
		return !f || !t || f.format('Ymd') === t.format('Ymd');
	}
	
});