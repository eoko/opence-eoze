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

		var values = this.getFieldValues();
		
		eo.Ajax.request({
			
			url: opts.url
			,params: opts.params
			,jsonData: Ext.apply(values, opts.jsonData)
			
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
	
});
