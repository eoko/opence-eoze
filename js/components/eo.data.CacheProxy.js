Ext.ns('eo.data');

eo.data.CacheProxy = Ext.extend(Ext.data.MemoryProxy, {

	doRequest : function(action, rs, params, reader, callback, scope, arg) {
		// No implementation for CRUD in MemoryProxy.  Assumes all actions are 'load'
		params = params || {};
		var result;
		try {
			result = reader.readRecords(this.getData(params));
		}catch(e){
			// @deprecated loadexception
			this.fireEvent("loadexception", this, null, arg, e);

			this.fireEvent('exception', this, 'response', action, arg, null, e);
			callback.call(scope, null, arg, false);
			return;
		}
		callback.call(scope, result, arg, true);
	}

	,getData: function(params) {
		var start = params.start || 0,
			limit = params.limit || 50,
			data = this.data.data.slice(start, start+limit);
		
		return {
			data: data
			,count: this.data.count
			,success: true
		}
	}
});