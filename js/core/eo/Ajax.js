/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 déc. 2011
 */
(function() {

Ext.ns('eo.data');

var Buffer;

/**
 * @class eo.data.Connection
 */
eo.data.Connection = Ext.extend(Ext.util.Observable, {
	
	url: 'index.php'
	
	,connection: null
	
	,buffers: null
	
	/**
	 * @cfg {String} accept The type of data that must be decoded from the server
	 * response. For now, the only accepted value is `json`. Any other value will
	 * result in using the raw `responseText` property of the response as the data.
	 */
	,accept: undefined
	
	,constructor: function(config) {
		
		// Init instance
		
		this.buffers = {};
		
		this.connection = new Ext.data.Connection({
			url: this.url
		});
		
		// Apply config
		
		Ext.apply(this, config);

		// Init events
		this.addEvents(
			/**
			 * @event beforerequest
			 * Fires before a network request is made to retrieve a data object.
			 * @param {Connection} conn This {@link eo.data.Connection Connection} object.
			 * @param {Object} options The options config object passed to the {@link #request} method.
			 */
			'beforerequest',
			/**
			 * @event requestcomplete
			 * Fires if the request was successfully completed.
			 * @param {Connection} conn This {@link eo.data.Connection Connection} object.
			 * @param {Object} response The data object returned by the server, decoded according
			 * to the {@link #accept} option set in this Connection or passed to the {@link #request}
			 * method.
			 * @param {Object} options The options config object passed to the {@link #request} method.
			 */
			'requestcomplete',
			/**
			 * @event requestexception
			 * Fires if an error HTTP status was returned from the server.
			 * See <a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html">HTTP 
			 * Status Code Definitions</a> for details of HTTP status codes.
			 * 
			 * @param {Connection} conn This {@link eo.data.Connection Connection} object.
			 * @param {Object} response The XHR object containing the response data.
			 * 
			 * If the request had been bufferized with other requests, this XHR object will
			 * be the same for all theses requests.
			 * 
			 * See <a href="http://www.w3.org/TR/XMLHttpRequest/">The XMLHttpRequest Object</a>
			 * for details.
			 * 
			 * @param {Object} options The options config object passed to the {@link #request} 
			 * method.
			 */
			'requestexception'
		);
		
		eo.data.Connection.superclass.constructor.call(this, config);
	}
	
	,requestJson: function(opts) {
		return this.request(Ext.apply(opts, {
			accept: 'json'
		}));
	}
	
	/**
	 * Sends a HTTP request to a remote server, **and decodes the result**. In this, 
	 * this method behaves differently from the {@link Ext.data.Connection#request} 
	 * method that does not do any processing on the server's response.
	 * 
	 * The operation used to decode the data from the server response is determined
	 * by the `accept` option (or the same {@link #accept} config option in the
	 * {@link #eo.data.Connection} object).
	 * 
	 * @param {Object} options An object which may contains the following properties:
	 * 
	 * - **url** : String/Function (Optional)<div class="sub-desc">
	 * The URL to which to send the request, or a function to call which returns a URL 
	 * string. The scope of the function is specified by the `scope` option. Defaults 
	 * to the configured `url`.</div>
	 * 
	 * - **accept** : String (Optional)<div class="sub-desc">
	 * The type of data that must be decoded from the serverresponse. For now, the 
	 * only accepted value is `json`. Any other value will result in using the raw 
	 * `responseText` property of the response as the data.</div>
	 * 
	 * - **params** : Object (Optional)<div class="sub-desc">
	 * An object containing properties which are used as parameters to the request.
	 * </div>
	 * 
	 * - **callback** : Function (Optional)<div class="sub-desc">
	 * The function to be called upon receipt of the HTTP response. The callback is 
	 * called regardless of success or failure and is passed the following parameters:
	 *   - **options** : Object<div class="sub-desc">The parameter to the request call.</div>
	 *   - **success** : Boolean<div class="sub-desc">`true` if the request succeeded.
	 *   - **data** : Object<div class="sub-desc">The *decoded* data from the server
	 *   response. The decoding method depends of the `accept` option (or the same
	 *   {@link #accept} config option of the {@link #eo.data.Connection} object).</div>
	 * </div>
	 * 
	 * - **success** : Function (Optional)<div class="sub-desc">
	 * The function to be called upon success of the request. The callback is passed the
	 * following parameters:
	 *   - **data** : Object<div class="sub-desc">The data object {@link #accept decoded}
	 *   from the server response.</div>
	 *   - **options** : Object<div class="sub-desc">The parameter to the request call.</div>
	 * </div>
	 * 
	 * - **failure** : Function (Optional)<div class="sub-desc">
	 * The function to be called upon failure of the request. The callback is passed the
	 * following parameters:
	 *   - **response** : Object<div class="sub-desc">The XMLHttpRequest object containing 
	 *   the response data. If the request has been {@link #buffer} bufferized with other
	 *   requests, this response object will be shared amongst all of them.</div>
	 * </div>
	 * 
	 * - **jsonData** : Object (Optional)<div class="sub-desc">
	 * JSON data to use as the post. Note: This will be used instead of params for the 
	 * post data. Any params will be appended to the URL.</div>

	 * 
	 */
	,request: function(options) {
		if (this.fireEvent('beforerequest', this, options) !== false) {
			if (!this.bufferizeRequest(options)) {
				this.directRequest(options);
			}
		}
	}
	
	,directRequest: function(options) {
		
		var accept = options.accept || this.accept,
			jsonData = options.jsonData;
		
		var opts = {
			scope: this
			,callback: this.handleDirectResponse
			,originalOptions: options
			
			,url: options.url || this.url
			
			,jsonData: jsonData
		};
		
		if (accept) {
			opts.params = Ext.apply({
				accept: accept
			}, options.params);
		} else {
			opts.params = options.params
		}
		
		if (jsonData) {
			opts.params = Ext.apply({
				accept: accept
				,contentType: 'json'
			}, options.params);
		}
		
		this.connection.request(opts);
	}
	
	,handleDirectResponse: function(options, succeeded, response) {
		var original = options.originalOptions,
			callback = original.callback,
			success  = original.success,
			failure  = original.failure,
			scope    = original.scope || this;
			
		this.fireEvent('requestcomplete', this, data, original);
		
		if (succeeded) {
//			try {
				var data = this.extractResponseData(response, original);

				if (callback) {
					callback.call(scope, original, true, data);
				}
				if (success) {
					success.call(scope, data, original);
				}
//			} catch (e) {
//				this.fireEvent('requestexception', this, response, original, e);
//				if (callback) {
//					callback.call(scope, original, false, e);
//				}
//				if (failure) {
//					failure.call(scope, original, e);
//				}
//			}
		} else {
			this.fireEvent('requestexception', this, response, original);
			if (callback) {
				callback.call(scope, original, false);
			}
			if (failure) {
				failure.call(scope, original);
			}
		}
	}
	
	
	,doHandleDirectResponse: function(options, succeeded, data, serverResponse) {
		
		var original = options.originalOptions,
			callback = original.callback,
			success  = original.success,
			failure  = original.failure,
			scope    = original.scope || this;
			
		this.fireEvent('requestcomplete', this, data, original);
		
		if (succeeded) {
			if (callback) {
				callback.call(scope, original, true, data);
			}
			if (success) {
				success.call(scope, data, original);
			}
		} else {
			this.fireEvent('requestexception', this, serverResponse, original);
			if (callback) {
				callback.call(scope, serverResponse, original);
			}
			if (failure) {
				failure.call(scope, serverResponse, original);
			}
		}
	}
	
	,handleBufferedResponse: function(options, succeeded, data) {
		
		if (data.success) {
			var requests = options.originalRequests,
				results = data.results,
				// number of results that do not match a sent request
				excessResults = 0;
				
			Ext.each(results, function(result) {
				var id = result.id,
					data = result.data,
					request = requests[id];
				
				if (!request) { // server result match no sent request
					excessResults++;
				}
				
				else {
					this.doHandleDirectResponse(request, succeeded, results[request.id], serverResponse)
				}
					
			}, this);

			// Check for requests that did not receive a response
			if (requests.length !== results.length - excessResults) {
				debugger // TODO
			}
		}
		
		else {
			// TODO handle soft failure...
		}
	}
	
	,bufferizeRequest: function(opts) {
		
		if (opts.bufferize === false) {
			return false;
		}
		
		var url = opts.url || this.url,
			buffer;
		
		if (Ext.isFunction(url)) {
			url = url.call(opts.scope);
		}
		
		buffer = this.buffers[url];
			
		if (!buffer) {
			buffer = this.buffers[url] = new Buffer({
				url: url
				,scope: this
				,callback: this.doMultipartRequest
			});
		}
		
		buffer.add(opts);
		
		return true;
	}
	
	,doMultipartRequest: function(requests) {
		
		var n = requests.length;
		
		if (n === 1) {
			this.directRequest(requests[0]);
		}

		else {
			
			var options = [];
			
			for (var i=0; i<n; i++) {
				var o = requests[i],
					data = {
						accept: o.accept || this.accept
					};
				
				Ext.apply(data, o.params);
				Ext.apply(data, o.jsonData);
				
				options.push({
					id: i
					,data: data
				});
			}
			
			this.directRequest({
				accept: 'json'
				,params: {
					controller: 'root.multipart'
				}
				,jsonData: {
					requests: options
				}
				,scope: this
				,callback: this.handleBufferedResponse
				,originalRequests: requests
			});
		}
	}
	
	,extractResponseData: function(response, options) {
		switch (options.accept || this.accept) {
			case 'json':
				return Ext.decode(response.responseText);
			default:
				return response.responseText;
		}
	}
});

eo.data.Connection.Buffer = Ext.extend(Object, {
	
	delay: 10
	
	,currentTimer: null
	
	,requests: null
	
	,callback: null
	
	,scope: window
	
	,constructor: function(config) {
		Ext.apply(this, config);
		this.requests = [];
	}
	
	,add: function(request) {
		
		this.requests.push(request);
		
		if (this.currentTimer) {
			clearTimeout(this.currentTimer);
		}
		
		var me = this;
		this.currentTimer = setTimeout(function() {
			me.callback.call(me.scope, me.requests);
		}, this.bufferDelay);
	}
});

Buffer = eo.data.Connection.Buffer;

eo.Ajax = new eo.data.Connection({
	url: 'index.php'
	,accept: 'json'
});

eo.Ajax.on('requestexception', function() {
	Ext.Msg.alert(
		'Erreur de connection',
		"Une erreur est survenu. Vérifiez votre connection internet. Si le "
		+ "problème persiste, il peut s'agir d'un problème avec le serveur, "
		+ "dans ce cas veuillez contacter la personne responsable de la "
		+ "maintenance du système."
	);
});

eo.Ajax.request({
	
	jsonData: {
		controller: 'countries.json'
		,action: 'autoComplete'
	}
	
	,success: function(data) {
		debugger
	}
});

eo.Ajax.request({
	
	params: {
		controller: 'countries.json'
		,action: 'autoComplete'
	}
	
	,success: function(data) {
		debugger
	}
});

//var spp = Ext.data.Connection.prototype;
//
//eo.Ajax = new Ext.data.Connection({
//	
//	bufferedRequests: []
//	,bufferTimerId: null
//	
//	,url: 'index.php'
//	
//	,bufferDelay: 20
//	
//	,request: function(opts) {
//		
//		var me = this;
//
//		// Buffer only simple requests
//		if (opts.url && opts.url !== me.url 
//			|| opts.method 
//			|| opts.timeout 
//			|| opts.form 
//			|| opts.isUpload !== undefined 
//			|| opts.headers
//			|| opts.xmlData 
//			|| opts.disabled !== undefined
//		) {
//			return me.directRequest.call(this, opts);
////			return spp.request.call(this, opts);
//		}
//		
//		this.bufferedRequests.push(opts);
//		
//		if (this.bufferTimerId) {
//			clearTimeout(this.bufferTimerId);
//		}
//		
//		this.bufferTimerId = setTimeout(function() {
//			
//			var requests = me.bufferedRequests,
//				n = requests.length;
//			
//			delete me.bufferTimerId;
//			delete me.bufferedRequests;
//			
//			if (n === 1) {
//				return me.directRequest(requests[0]);
////				var opts = requests[0];
////				if (opts.jsonData) {
////					opts.params = Ext.apply({
////						'Content-Type': 'application/json'
////					}, opts.params);
////				}
////				spp.request.call(me, opts);
//			}
//			
//			else {
//				var data = [],
//					i = 0;
//
//				Ext.each(requests, function(opts) {
//					data.push({
//						id: i++
//						,params: opts.params
//						,data: opts.jsonData
//					});
//				});
//
////				Ext.Ajax.request({
//				me.directRequest({
//					url: me.url
//					,params: {
//						controller: 'root.multipart'
//						,'Content-Type': 'application/json'
//					}
//					,jsonData: {
//						requests: data
//					}
//					,success: function(response) {
//						var json = response.responseJson;
//						if (json.success) {
//							debugger
//							Ext.each(json.results, function(result) {
//								var opts = requests[result.id],
//									response = {
//										responseJson: result.response
//										,responseJSON: result.response
//									},
//									scope = opts.scope || window;
//									
//								me.fireEvent('requestcomplete', me, response, opts);
//									
//								if (opts.callback) {
//									opts.callback.call(scope, opts, true, response);
//								}
//								if (opts.success) {
//									opts.success.call(scope, response, opts);
//								}
//							});
//						}
////						var o = Ext.util.JSON.decode(response.)
//					}
//				});
//			}
//		}, this.bufferDelay);
//	}
//	
//	,directRequest: function(opts) {
//		
//		// Add content-type param for json request
//		if (opts.jsonData) {
//			opts.params = Ext.apply({
//				'Content-Type': 'application/json'
//			}, opts.params);
//		}
//		
//		spp.request.call(this, opts);
//	}
//	
//});
//
//eo.Ajax.on('requestcomplete', function(conn, response, options) {
//	if (!response.responseJson 
//			&& response.getResponseHeader('Content-type') === 'application/json') {
//		response.responseJson = response.responseJSON = Ext.util.JSON.decode(response.responseText);
//	}
//});
//
//eo.Ajax.on('requestcomplete', function(conn, response, options) {
//	response.getResponseHeader('Content-type')
//})
//
//eo.Ajax.request({
//	url: 'index.php'
//	,params: {
//		controller: 'countries.json'
//		,action: 'autoComplete'
//	}
//	,jsonData: [1,2,3]
//	,callback: function() {
//		debugger
//	}
//});
//	
//eo.Ajax.request({
//	url: 'index.php'
//	,params: {
//		controller: 'countries.json'
//		,action: 'autoComplete'
//	}
//	,callback: function() {
//		debugger
//	}
//});

})(); // closure