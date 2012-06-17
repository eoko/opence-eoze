/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 déc. 2011
 */
Ext.ns('eo.form');

/**
 * Extends {@link Ext.form.BasicForm} to change the submit method to a json
 * request that is easier to handle and customize than the default form
 * submission method of Ext.
 * 
 * This class also adds some useful events about form lifecycle.
 */
eo.form.JsonForm = Ext.extend(Ext.form.BasicForm, {

	/**
	 * @cfg {Boolean} trackResetOnSubmit
	 * 
	 * If set to `true`, {@link #reset} resets to the last saved data instead
	 * of when the form was first created. This will similarly affect the result
	 * of the {#isDirty} method.
	 */
	trackResetOnSubmit: true
	
	,constructor: function() {
		eo.form.JsonForm.superclass.constructor.apply(this, arguments);
		
		this.addEvents(
			/**
			 * @event beforesave
			 * 
			 * Fires before a request to save the form is sent to the server.
			 * 
			 * Returning `false` from this event will cancel the submitting of
			 * the form.
			 * 
			 * @param {eo.form.JsonForm} this
			 * @param {Object} options The options object that will be passed
			 * to the {@link eo.Ajax} object to do the request.
			 */
			'beforesave',
			/**
			 * @event aftersave
			 * 
			 * Fires when a save request is completed, whether it is successful or not.
			 * 
			 * @param {eo.form.JsonForm} this
			 * @param {Object} data
			 *   If the request was _successful_: The *decoded* data from the server response. 
			 *   The decoding method depends of the `accept` option (or the same {@link #accept} 
			 *   config option of the {@link #eo.data.Connection} object).
			 *   
			 *   Else, if the request has _failed_: The XMLHttpRequest object containing the 
			 *   response data. If the request has been {@link eo.data.Connection#buffer} 
			 *   buffered with other requests, this response object will be shared amongst 
			 *   all of them.
			 * @param {Object} options The options object that was passed to the 
			 * {@link eo.Ajax} object to do the request.
			 */
			'aftersave',
			/**
			 * @event savecomplete
			 * 
			 * Fires when a save request returns successfuly from the server
			 * (successfuly means that the HTTP request did not fail, and the
			 * server has returned a valid json response; the response data
			 * itself can indicate that the operation was a failure).
			 * 
			 * @param {eo.form.JsonForm} this
			 * @param {Object} data The data object decoded from the server response.
			 * @param {Object} options The options object that was passed
			 * to the {@link eo.Ajax} object to do the request.
			 */
			'savecomplete',
			/**
			 * @event savefailed
			 * 
			 * Fires when a save request failed, that is the HTTP request itself
			 * was not successful (the server returned an error code, or did not
			 * respond at all).
			 * 
			 * @param {eo.form.JsonForm} this
			 * @param {Object} response The XMLHttpRequest object containing the 
			 * response data. If the request has been {@link eo.data.Connection#buffer 
			 * buffered} with other requests, this response object will be shared 
			 * amongst all of them.
			 * @param {Object} options The options object that was passed
			 * to the {@link eo.Ajax} object to do the request.
			 */
			'savefailed'
		);
	}
	
	,submit: function(opts) {

		// Form date must be loaded before the beforesave event, in order to give 
		// the listeners the opportunity to act on it.
		
		// Preserve existing jsonData
		opts.jsonData = Ext.apply({
			// Preserve existing data
			data: Ext.apply(
				this.getSubmitFieldValues(), 
				opts.jsonData && opts.jsonData.data || null
			)
		}, opts.jsonData);
		
		// beforesave event
		if (false === this.fireEvent('beforesave', this, opts)) {
			return;
		}

		eo.Ajax.request({
			
			url: opts.url
			,params: opts.params
			,jsonData: opts.jsonData
			
			,scope: this
			
			,callback: function(options, success, data) {
				var scope = opts.scope;
				this.fireEvent('aftersave', this, success, data, opts);
				if (opts.callback) {
					opts.callback.call(scope, this, success, data, opts);
				}
				if (success) {
					// Reset fields original value
					if (this.trackResetOnSubmit) {
						this.items.each(function(f) {
							f.originalValue = f.getValue();
						});
					}
					// Callbacks
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
					this.fireEvent('savefailed', this, data, opts);
					if (opts.failure) {
						opts.failure.call(scope, this, data, opts);
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
	 * @param {Boolean} [dirtyOnly=false] `true` to return only fields that are dirty.
     * @return {Object} The values in the form
     */
	,getSubmitFieldValues: function(dirtyOnly) {
		var o = {},
		key;
		
		var pushValue = function(n, val) {
			key = o[n];
			if (Ext.isDefined(key)) {
				if (Ext.isArray(key)) {
					o[n].push(val);
				} else {
					o[n] = [key, val];
				}
			} else {
				o[n] = val;
			}
		};
		
		this.items.each(function(f) {
			if (f.submitValue !== false && !f.disabled && (dirtyOnly !== true || f.isDirty())) {
				// checkboxgroup
				if (f instanceof Ext.form.CheckboxGroup) {
					f.eachItem(function(cb) {
						pushValue(cb.getName(), cb.getValue());
					});
				}
				// Most probably, radiogroup should be handled specifically, too.
				// Maybe even other field types...
				
				// all other field types
				else {
					pushValue(f.getName(), f.getValue());
				}
			}
		});
		return o;
	}
});
