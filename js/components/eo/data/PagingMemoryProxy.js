
/* Fix for Opera, which does not seem to include the map function on Array's */
if (!Array.prototype.map) {
    Array.prototype.map = function(fun){
        var len = this.length;
        if (typeof fun != 'function') {
            throw new TypeError();
        }
        var res = new Array(len);
        var thisp = arguments[1];
        for (var i = 0; i < len; i++) {
            if (i in this) {
                res[i] = fun.call(thisp, this[i], i, this);
            }
        }
        return res;
    };
}

Ext.ns('eo.data');

eo.data.PagingMemoryProxy = Ext.extend(Ext.data.MemoryProxy, {
    
	constructor : function(data){
        eo.data.PagingMemoryProxy.superclass.constructor.call(this);
        this.data = data;
    }

	/**
	 * Applies the query request params to the given records set.
	 * 
	 * @param {String} query The string being searched (the value typed in the
	 * combo).
	 * @param {Object} records The data block containing the records to filter.
	 * 
	 * @return {Object} The data block containging the records that have been 
	 * accepted. The returned `Object` must be returned by the `filter` method
	 * of the passed `records` object.
	 * 
	 * @protected
	 */
	,applyQuery: function(query, records) {
		return records.filter(this.createQueryFilter(query));
	}
	
	/**
	 * Create the filter function to be used by the {@link #applyQuery} method.
	 * 
	 * @param {String} query The string being search (e.g. the value typed
	 * in the combo).
	 * 
	 * @return {Object} The data block containging the records that have been 
	 * accepted. The returned `Object` must be returned by the `filter` method
	 * of the passed `records` object.
	 *
	 * @protected
	 * @method
	 */
	,createQueryFilter: undefined

    ,processRequest: function(action, rs, params, reader, callback, scope, options) {
        params = params || {};
        var result;
        try {
            result = reader.readRecords(this.data);
        } 
        catch (e) {
            this.fireEvent('loadexception', this, options, null, e);
	        return null;
        }
		
		// query
		if (params.query !== undefined) {
			if (this.applyQuery) {
				result.records = this.applyQuery(params.query, result.records);
				result.totalRecords = result.records.length;
			} else {
				throw new Error('Unsupported request param: query (applyQuery method'
						+ ' must be implemented)');
			}
		}
        
        // filtering
        if (params.filter !== undefined) {
            result.records = result.records.filter(function(el){
                if (typeof(el) == 'object') {
                    var att = params.filterCol || 0;
                    return String(el.data[att]).match(params.filter) ? true : false;
                }
                else {
                    return String(el).match(params.filter) ? true : false;
                }
            });
            result.totalRecords = result.records.length;
        }
		
        return result;
    }
    
	,doRequest : function(action, rs, params, reader, callback, scope, options){
		var result = this.processRequest.apply(this, arguments);
		if (result) {
			
			// paging must be done after custom processings, because result.records.length
			// will be used as totalRecords
        
			// sorting
			if (params.sort !== undefined) {
				// use integer as params.sort to specify column, since arrays are not named
				// params.sort=0; would also match a array without columns
				var dir = String(params.dir).toUpperCase() == 'DESC' ? -1 : 1;
				var fn = function(v1, v2){
					return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
				};
				result.records.sort(function(a, b){
					var v = 0;
					if (typeof(a) == 'object') {
						v = fn(a.data[params.sort], b.data[params.sort]) * dir;
					}
					else {
						v = fn(a, b) * dir;
					}
					if (v == 0) {
						v = (a.index < b.index ? -1 : 1);
					}
					return v;
				});
			}
			// paging (use undefined cause start can also be 0 (thus false))
			if (params.start !== undefined && params.limit !== undefined) {
				result.records = result.records.slice(params.start, params.start + params.limit);
			}
			
			callback.call(scope, result, options, true);
		} else {
			callback.call(scope, null, options, false);
		}
    }
});

eo.data.CachingHttpProxy = Ext.extend(eo.data.PagingMemoryProxy, {
	
	constructor: function(conn) {
		
		this.dataProvider = conn.dataProvider || this.dataProvider;
		
		if (!this.dataProvider) {
			this.dataProvider = new eo.data.CachingHttpProxy.DataProvider({
				url: this.url
				,params: this.params
			});
		}
		
		// url is needed, or the proxy will crash on requesting
		if (!conn.url) {
			conn.url = this.dataProvider.url;
		}
		
		eo.data.CachingHttpProxy.superclass.constructor.call(this, conn);

		this.relayEvents(this.dataProvider, ['datachanged']);
	}
	
	/**
	 * Applies the given query to the given records set. If this method is not
	 * overridden (see {@link eo.data.PagingMemoryProxy#applyQuery}), this 
	 * implementation will default on the dataProvider's own `applyQuery` method.
	 */
	,applyQuery: function(query, records) {
		return this.dataProvider.applyQuery(query, records);
	}
	
	,createQueryFilter: function(query) {
		return this.dataProvider.createQueryFilter(query);
	}
	
    ,doRequest : function(action, rs, params, reader, callback, scope, options) {
		var args = arguments;
		this.dataProvider.getData(function(success, data) {
			if (success) {
				this.data = data;
				eo.data.PagingMemoryProxy.prototype.doRequest.apply(this, args);
			} else {
				// TODO OCU-80
				// data references the error
				throw new Error('Not implemented yet');
			}
		}, this);
	}
	
	,getRecordById: function(id, reader) {
		if (!this.dataProvider.getRecordDataById) {
			throw new Error('Data provider does not have indexing capabilities');
		}
		var data = this.dataProvider.getRecordDataById(id);
		if (data) {
			return reader.extractData([data], true)[0];
		} else {
			return null;
		}
	}
});

eo.data.CachingHttpProxy.DataProvider = Ext.extend(Ext.util.Observable, {
	
	/**
	 * @cfg {String} keplerReloadEvent The name of a {@link eo.Kepler} event
	 * on which the data cache must be reloaded.
	 */
	
	constructor: function(config) {
		Ext.apply(this, config);
		
		eo.data.CachingHttpProxy.DataProvider.superclass.constructor.call(this, config);
		
		if (this.keplerReloadEvent) {
			eo.Kepler.on(this.keplerReloadEvent, function() {
				// invlid the cache
				delete this.data;
				this.fireEvent('datachanged', this);
			}, this);
		}
	}
	
	/**
	 * {Boolean} loadingCache When an AJAX request has been made to
	 * retrieve the data but has not returned yet, this property will
	 * be set to `true`, else it will be set to `false`.
	 * @private
	 */
	,loadingCache: false
	
	/**
	 * {Object} data The raw data Object, as decoded from the server
	 * response.
	 * @private
	 */
	,data: undefined
	
	/**
	 * @private
	 */
	,processWaitingRequests: function(success, data) {
		var queue = this.waitingRequests;
		if (queue) {
			Ext.each(queue, function(callback) {
				callback(success, data);
			})
			delete this.waitingRequests;
		}
	}
	
	,applyQuery: function(query, records) {
		return records.filter(this.createQueryFilter(query));
	}
	
	/**
	 * Store the loaded data into the cached data object. This method
	 * is provided to allow overridding.
	 * @param {Boolean} success
	 * @param {Object} data the cached data object as returned from the
	 * server (decoded).
	 * @protected
	 */
	,setData: function(success, data) {
		this.data = data;
		this.processWaitingRequests(success, data);
	}
	
	,getData: function(callback, scope) {
		
		scope = scope || this;

		// If the cache has already been loaded and is still valid,
		// return immediatly
		if (this.data && !this.loadingCache) {
			callback.call(scope, true, this.data);
			return;
		}

		// Add requests to waiting list
		var queue = this.waitingRequests = this.waitingRequests || [];
		queue.push(callback.createDelegate(scope));
		
		// Starts the cache loading process if needed
		if (!this.loadingCache) {
			this.loadingCache = true;

			eo.Ajax.request({
				url: this.url
				,params: Ext.apply({
					caching: true
				}, this.params)
				,scope: this
				,success: function(data) {
					this.loadingCache = false;
					try {
						this.setData(data.success, data);
					} catch (e) {
						this.setData(data.success, data);
						this.processWaitingRequests(false, e);
					}
				}
			});
		}
	}
});

eo.data.CachingHttpProxy.IndexedDataProvider = Ext.extend(eo.data.CachingHttpProxy.DataProvider, {
	
	dataMap: undefined
	
	,setData: function(success, data) {
		var id = this.idProperty,
			map = this.dataMap = {};
		// map data
		if (success) {
			if (!id) {
				throw new Error('idProperty must be defined');
			}
			Ext.each(data.data, function(item) {
				map[item[id]] = item;
			});
		}
		// super
		eo.data.CachingHttpProxy.IndexedDataProvider.superclass.setData.call(this, success, data);
	}
	
	,getRecordDataById: function(id) {
		return this.dataMap && this.dataMap[id];
	}
});

/**
 * A {@link Ext.data.JsonStore JsonStore} that allows retrieving of records by id, even
 * if the records have been filtered out of the store. This is possible through the use
 * of an 
 * A {@link Ext.data.JsonStore JsonStore} using an {@link eo.data.IndexedCachingHttpProxy
 * IndexedCachingHttpProxy}, that can retrieve a record by its id independantly of the
 * store's last request (the record needs to have been loaded into the underlying
 * {@link eo.data.CachingHttpProxy.DataProvider DataProvider}, though). The store also
 * needs to have already been loaded before the {#getById} method is called.
 */
eo.data.ProxyJsonStore = Ext.extend(Ext.data.JsonStore, {
	
	constructor: function(config) {
		
		// Automatically creating proxy from dataProvider
		var proxy = config.proxy || this.proxy,
			dataProvider = config.dataProvider || this.dataProvider;
		if (!proxy && dataProvider) {
			this.proxy = new eo.data.CachingHttpProxy({
				dataProvider: dataProvider
				,createStoreFilter: config.createStoreFilter || this.createStoreFilter
			});
		}
		
		eo.data.ProxyJsonStore.superclass.constructor.call(this, config);
		
		this.relayEvents(this.proxy, ['datachanged']);
	}
	
	,getById: function(id) {
		var record = eo.data.ProxyJsonStore.superclass.getById.call(this, id);
		if (!record) {
			return this.proxy.getRecordById(id, this.reader);
		} else {
			return record;
		}
	}
});
