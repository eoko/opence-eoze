
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

/**
 * @class Ext.ux.data.PagingMemoryProxy
 * @extends Ext.data.MemoryProxy
 * <p>Paging Memory Proxy, allows to use paging grid with in memory dataset</p>
 */
eo.data.PagingMemoryProxy = Ext.extend(Ext.data.MemoryProxy, {
    
	constructor : function(data){
        eo.data.PagingMemoryProxy.superclass.constructor.call(this);
        this.data = data;
    }

	/**
	 * Creates the store filtering function. The default is to search
	 * the provided query string in the beginning of the {@link #displayField},
	 * case-insensitively.
	 * @param {String} query The string being searched (the value typed in the
	 * combo).
	 * @return {Function} The filtering function, which is passed a 
	 * {@link #Ext.data.Record} from the combo's store as its first argument.
	 * The function must return `true` to accept the record, else `false`,
	 * to filter it out.
	 * @protected
	 */
	,createStoreFilter: function(query) {
		var re = new RegExp('^' + query, 'i'),
			df = this.displayField;
		return function(r) {
			return r.data.name.match(re);
		};
//		return function(r) {
//			return re.test(r.data[df]);
//		};
	}
	
    ,doRequest : function(action, rs, params, reader, callback, scope, options){
        params = params ||
        {};
        var result;
        try {
            result = reader.readRecords(this.data);
        } 
        catch (e) {
            this.fireEvent('loadexception', this, options, null, e);
            callback.call(scope, null, options, false);
            return;
        }
		
		// query
		if (params.query !== undefined) {
			result.records = result.records.filter(this.createStoreFilter(params.query));
            result.totalRecords = result.records.length;
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
    }
});

eo.data.CachingHttpProxy = Ext.extend(Ext.data.DataProxy, {
	
	createStoreFilter: eo.data.PagingMemoryProxy.prototype.createStoreFilter
	
	,setData: function(data) {
		this.data = data;
	}
	
    ,doRequest : function(action, rs, params, reader, callback, scope, options) {
		var args = arguments;
		if (this.data) {
			eo.data.PagingMemoryProxy.prototype.doRequest.apply(this, args);
		} else {
			Ext.Ajax.request({
				url: this.url
				,scope: this
				,success: function(response) {
					var o = Ext.util.JSON.decode(response.responseText);
					if (o.success) {
						this.setData(o);
						eo.data.PagingMemoryProxy.prototype.doRequest.apply(this, args);
					} else {
						// TODO error message
						throw new Error('Not implemented yet');
					}
				}
			});
		}
	}	
});

eo.data.IndexedCachingHttpProxy = Ext.extend(eo.data.CachingHttpProxy, {

	idProperty: 'id'
	
	,setData: function(data) {
		var id = this.idProperty,
			map = this.dataMap = {};
		Ext.each(data.data, function(item) {
			map[item[id]] = item;
		});
		eo.data.IndexedCachingHttpProxy.superclass.setData.call(this, data);
	}
	
	,getDataById: function(id) {
		return this.dataMap && this.dataMap[id];
	}
});
