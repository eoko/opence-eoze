/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 déc. 2011
 */
Ext.ns('eo.form');

eo.form.JsonForm = Ext.extend(Ext.form.BasicForm, {
	
	submit: function(opts) {
		
		if (false === this.fireEvent('beforesave', this, opts)) {
			return;
		}

		var values = this.getSubmitFieldValues();
		
		eo.Ajax.request({
			
			url: opts.url
			,params: opts.params
			,jsonData: Ext.apply({
				data: values
			}, opts.jsonData)
			
			,scope: this
			
			,callback: function(options, success, data) {
				var scope = opts.scope;
				this.fireEvent('aftersave', this, success, data, opts);
				if (opts.callback) {
					opts.callback.call(scope, this, success, data, opts);
				}
				if (success) {
					if (data.success) {
						this.fireEvent('savecomplete', this, data, opts);
						if (opts.success) {
							opts.success.call(scope, this, data, opts);
						}
					} else {
						this.fireEvent('savefailed', this, data, opts);
						if (opts.failure) {
							opts.failure.call(scope, this, data, opts);
						}
					}
				} else {
					this.fireEvent('savefailed', this, null, opts);
					if (opts.failure) {
						opts.failure.call(scope, this, null, opts);
					}
				}
			}
		});
	}
	
    /**
     * Retrieves the value of the submittable fields in the form as a set of key/value 
	 * pairs, using the {@link Ext.form.Field#getValue getValue()} method. Won't be
	 * considered as submittable fields with {@link #Ext.form.Field#submitValue} to
	 * `false`.
	 * 
     * If multiple fields exist with the same name they are returned as an array.
     * 
	 * @param {Boolean} dirtyOnly (optional) `true` to return only fields that are dirty.
     * @return {Object} The values in the form
     */
	,getSubmitFieldValues: function(dirtyOnly){
		var o = {},
		n,
		key,
		val;
		this.items.each(function(f) {
			if (f.submitValue && !f.disabled && (dirtyOnly !== true || f.isDirty())) {
				n = f.getName();
				key = o[n];
				val = f.getValue();

				if(Ext.isDefined(key)){
					if(Ext.isArray(key)){
						o[n].push(val);
					}else{
						o[n] = [key, val];
					}
				}else{
					o[n] = val;
				}
			}
		});
		return o;
	}

	
});
