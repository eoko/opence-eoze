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
 * 
 * This class builds upon {@link Ext.data.Connection} (but does not extend it),
 * to offer higher level operations. Specifically, this class returns decodes
 * the data from the server before returning them to the requester. It can also
 * perform buffering and grouping of near simultaneous requests.
 * 
 * This class behaviour is closely related to Ext.data.Connection; keep in mind
 * however that **it is slightly different in many regards**. It cannot be assumed 
 * safely that the documentation or API of {@link Ext.data.Connection} matches
 * this class' own behaviour.
 */
eo.data.Connection = Ext.extend(Ext.util.Observable, {
	
	/**
	 * @cfg {String} [url=undefined]
	 * The default URL to be used for request to the server.
	 */
	
	/**
	 * @private
	 */
	connection: null
	
	/**
	 * @cfg {Integer/Boolean} buffer
	 * The number of millisecond to wait before firing a request, to see if 
	 * another one comes. In this case, the wait delay will be repeated, and
	 * all the request that have caught in this interval will be sent in one
	 * multipart request.
	 * 
	 * If this option is set to `false`, then request buffering will not be
	 * used at all.
	 */
	,buffer: false
	
	/**
	 * @private
	 */
	,buffers: null
	
	/**
	 * @cfg {String} accept The type of data that must be decoded from the server
	 * response. For now, the only accepted value is `json`. Any other value will
	 * result in using the raw `responseText` property of the response as the data.
	 */
	,accept: undefined
	
	,constructor: function(config) {
		
		this.isDebug = /[?&]debug(?:&|$)/.test(location.search);
		
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
			 * 
			 * @param {Connection} conn This {@link eo.data.Connection Connection} object.
			 * 
			 * @param {Object} options The options config object passed to the 
			 * {@link #request} method.
			 */
			'beforerequest',
			/**
			 * @event requestcomplete
			 * Fires if the request was successfully completed.
			 * 
			 * @param {Connection} conn This {@link eo.data.Connection Connection} object.
			 * 
			 * @param {Object} data The data object returned by the server, decoded according
			 * to the {@link #accept} option set in this Connection or passed to the 
			 * {@link #request} method.
			 * 
			 * @param {Object} options The options config object passed to the 
			 * {@link #request} method.
			 */
			'requestcomplete',
			/**
			 * @event requestexception
			 * Fires if an error HTTP status was returned from the server. See 
			 * [HTTP Status Code Definitions][1] for details of HTTP 
			 * status codes.
			 * [1]: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
			 * 
			 * @param {Connection} conn This {@link eo.data.Connection Connection} object.
			 * 
			 * @param {Object} response The XHR object containing the response data.
			 * 
			 * If the request had been bufferized with other requests, this XHR object will
			 * be the same for all theses requests.
			 * 
			 * See [The XMLHttpRequest Object] [2] for details.
			 * [2]: http://www.w3.org/TR/XMLHttpRequest
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
	 * @param {Object} options An object which may contains the following properties:<ul>
	 * 
	 * <li>**url** : String/Function (Optional)<div class="sub-desc">
	 * The URL to which to send the request, or a function to call which returns a URL 
	 * string. The scope of the function is specified by the `scope` option. Defaults 
	 * to the configured `url`.
	 * </div></li>
	 * 
	 * <li>**accept** : String (Optional)<div class="sub-desc">
	 * The type of data that must be decoded from the serverresponse. For now, the 
	 * only accepted value is `json`. Any other value will result in using the raw 
	 * `responseText` property of the response as the data.
	 * </div></li>
	 * 
	 * <li>**params** : Object (Optional)<div class="sub-desc">
	 * An object containing properties which are used as parameters to the request.
	 * </div></li>
	 * 
	 * <li>**callback** : Function (Optional)<div class="sub-desc">
	 * The function to be called upon receipt of the HTTP response. The callback is 
	 * called regardless of success or failure and is passed the following parameters:<ul>
	 *    
	 *   <li>**options** : Object<div class="sub-desc">
	 *   The parameter to the request call.
	 *   </div></li>
	 *    
	 *   <li>**success** : Boolean<div class="sub-desc">
	 *   `true` if the request succeeded.
	 *   </div></li>
	 *    
	 *   <li>**data** : Object<div class="sub-desc">
	 *   If the request was _successful_: The *decoded* data from the server response. 
	 *   The decoding method depends of the `accept` option (or the same {@link #accept} 
	 *   config option of the {@link #eo.data.Connection} object).
	 *   
	 *   Else, if the request has _failed_: The XMLHttpRequest object containing the 
	 *   response data. If the request has been {@link #buffer} buffered with other 
	 *   requests, this response object will be shared amongst all of them.
	 *   </div></li>
	 * </ul></div></li>
	 * 
	 * <li>**success** : Function (Optional)<div class="sub-desc">
	 * The function to be called upon success of the request. The callback is passed the
	 * following parameters:<ul>
	 *    
	 *   <li>**data** : Object<div class="sub-desc">
	 *   The data object {@link #accept decoded} from the server response.
	 *   </div></li>
	 *   
	 *   <li>**options** : Object<div class="sub-desc">
	 *   The parameter to the request call.
	 *   </div></li>
	 * </ul></div></li>
	 * 
	 * <li>**failure** : Function (Optional)<div class="sub-desc">
	 * The function to be called upon failure of the request, that is if the server
	 * cannot be reached or if it returns an error HTTP status. If the data decoded
	 * from the response indicates a higher level error, that won't trigger the
	 * failure callback, but the success one. The callback is passed the following 
	 * parameters:<ul>
	 * 
	 *   <li>**response** : Object<div class="sub-desc">
	 *   The XMLHttpRequest object containing the response data. If the request has 
	 *   been {@link #buffer buffered} with other requests, this response object will 
	 *   be shared amongst all of them.
	 *   </div></li>
	 *   
	 *   <li>**options** : Object<div class="sub-desc">
	 *   The parameter to the request call.
	 *   </div></li>
	 * </ul></div></li>
	 * 
	 * <li>**jsonData** : Object (Optional)<div class="sub-desc">
	 * JSON data to use as the post. Note: This will be used instead of params for the 
	 * post data. Any params will be appended to the URL.
	 * </div></li>
	 * 
	 * <li>**bufferizable** : Boolean (Optional)<div class="sub-desc">
	 * `false` to prevent the Connection from trying to buffer the request with other
	 * near simultaneous requests (this is the same as calling {@link #directRequest}
	 * directly. Defaults to `undefined`.
	 * </div></li>
	 * 
	 * </ul>
	 */
	,request: function(options) {
		if (this.fireEvent('beforerequest', this, options) !== false) {
			if (!this.bufferizeRequest(options)) {
				this.directRequest(options);
			}
		}
	}

	/**
	 * Sends a request that won't be bufferized. This method accepts exactly the
	 * same options as the {@link #request} method.
	 */
	,directRequest: function(options) {
		
		var accept = options.accept || this.accept,
			jsonData = options.jsonData;
		
		var opts = {
			scope: this
			,callback: this.handleDirectResponse
			,originalOptions: options
			
			,url: options.url || this.url
			
			,jsonData: jsonData || (Ext.isChrome ? {requestType: 'AJAX'} : undefined)
		};
		
		if (accept) {
			opts.params = Ext.apply({
				accept: accept
			}, options.params);
		}
		
		else {
			opts.params = options.params
		}
		
		if (jsonData) {
			opts.params = Ext.apply({
				accept: accept
				,contentType: 'json'
			}, options.params);
		}

		// Converting jsonData back to params, for firefox to be able to repeat it
		// TODO this is a debuggging facility that should be removed
//		if (opts.jsonData && Ext.isGecko) {
		if (opts.jsonData && this.isDebug) {
			opts.params = opts.params || {};
			Ext.iterate(opts.jsonData, function(k, v) {
				opts.params['json_' + k] = Ext.encode(v);
			});
			delete opts.jsonData;
			if (Ext.isChrome) {
				opts.jsonData = {requestType: 'AJAX'};
			}
		}
		
		this.connection.request(opts);
	}

	// private
	,handleDirectResponse: function(options, succeeded, response) {
		
		var original = options.originalOptions;
		
		// this is needed to access the response, in case of failure
		// in `handleBufferedResponse`
		this.lastResponse = response;
		
		this.handleResponse(
			original, 
			succeeded, 
			succeeded ? this.extractResponseData(response, original) : response
		);
		
		delete this.lastResponse;
	}
	
	// private
	,handleResponse: function(options, succeeded, data) {
		
		var callback = options.callback,
			success  = options.success,
			onSuccess = options.onSuccess,
			failure  = options.failure,
			scope    = options.scope || this;
			
		if (succeeded) {
			if (false !== this.fireEvent('requestcomplete', this, data, options)) {
				if (callback) {
					callback.call(scope, options, true, data);
				}
				if (success) {
					success.call(scope, data, options);
				}
				if (onSuccess) {
					if (data.success) {
						onSuccess.call(scope, data, options);
					} else {
						this.onFailure.call(scope, data, options, this);
					}
				}
			}
		} else {
			if (false !== this.fireEvent('requestexception', this, data, options)) {
				if (callback) {
					callback.call(scope, options, false, data);
				}
				if (failure) {
					failure.call(scope, data, options);
				}
			}
		}
	}
	
	,onFailure: function(data, options, connection) {
		throw new Error('Request error');
	}
	
	// private
	,handleBufferedResponse: function(options, success, data) {

		if (success) {
			
			// Happy path
			if (data.success) {
				var requests = options.originalRequests,
					results = data.results,
					// number of results that do not match a sent request
					excessResults = 0,
					unbuffered = [];

				Ext.each(results, function(result) {
					var id = result.id,
						data = result.data,
						request = requests[id];

					// Result not matching any request
					if (!request) {
						excessResults++;
					}
					// Request not bufferizable => retry
					else if (result.cannotBuffer) {
						unbuffered.push(request);
					}
					// Individual request fail
					else if (result.error) {
						// TODO if sent as direct, the request would end up as a
						// connection error, but we know for sure that this is a
						// system error, since the server has responded... Maybe
						// the error message could reflect that better.
						//
						// That could also spare the use of the flacky global
						// response in the requestexception.
						
						// `lastResponse` is necessarily accurate, since we remains
						// in the same thread from `handleDirectResponse` (for the
						// multipart request), to here.
						this.handleResponse(request, false, this.lastResponse);
					}
					// Success
					else {
						this.handleResponse(request, true, data);
					}

				}, this);

				// Check for requests that did not receive a response
				if (requests.length !== results.length - excessResults) {
					Ext.Msg.alert(
						'Erreur système',
						"Une erreur est survenue, veuillez contacter le responsable"
						+ " de la maintenance du système pour résoudre ce problème."
						+ "<p>Code de l'erreur : 4c2e2</p>"
					);
					debugger; // ERROR
				}
				
				// Launch unbefferizable requests in direct
				Ext.each(unbuffered, function(options) {
					this.directRequest(options);
				}, this);
			}

			// System error
			else {
				Ext.Msg.alert(
					'Erreur système',
					"Une erreur est survenue, veuillez contacter le responsable"
					+ " de la maintenance du système pour résoudre ce problème."
					+ "<p>Code de l'erreur : d4e78</p>"
				);
				debugger; // ERROR
			}
		}
		
		// Infrastructural (connection, HTTP) error
		else {
			// TODO handle hard fail
			// That should be done by the error manager => the todo is
			// then implementing that...
			
			// Retry all requests individually... Maybe only one of them
			// crashed the whole lot (e.g. a PHP error that the server
			// failed to catch).
			//
			// TODO The general error handler should be disabled here, since
			// individual requests will raise their own errors.
			Ext.each(requests, function(options) {
				this.directRequest(options);
			}, this);
		}
	}
	
	// private
	,bufferizeRequest: function(opts) {
		
		if (this.buffer === false) {
			return false;
		}
		
		if (opts.bufferizable === false) {
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
				,buffer: this.buffer // buffer delay
			});
		}
		
		buffer.add(opts);
		
		return true;
	}
	
	// private
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
	
	// private
	,extractResponseData: function(response, options) {
		switch (options.accept || this.accept) {
			case 'json':
				return Ext.decode(response.responseText);
			default:
				return response.responseText;
		}
	}
});

// private
eo.data.Connection.Buffer = Ext.extend(Object, {
	
	buffer: 10
	
	,currentTimer: undefined
	
	,requests: undefined
	
	,callback: undefined
	
	,scope: undefined
	
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
			var requests = me.requests;
			delete me.currentTimer;
			me.requests = [];
			me.callback.call(me.scope, requests);
		}, this.buffer);
	}
});

Buffer = eo.data.Connection.Buffer;

/**
 * @class eo.Ajax
 * @extends eo.data.Connection
 * @singleton
 */
eo.Ajax = new eo.data.Connection({
	url: 'index.php'
	,accept: 'json'
	
	// Buffer is currently still posing problems, at least:
	// - multiple requests can fire from forms submit action :/
	// - detection of disconnexion fails
	,buffer: false
	
	,onFailure: function(data, options, connection) {
		Ext.Msg.alert(
			data.title || 'Erreur',
			data.errorMessage || "Impossible de charger les données."
		);
	}
});

eo.Ajax.on('requestexception', function(conn, response, options) {
	Ext.Msg.alert(
		'Erreur de connection',
		"Vérifiez l'état de votre connection internet. Si le problème "
		+ "persiste, il peut s'agir d'un problème avec le serveur ; "
		+ "dans ce cas veuillez contacter la personne responsable de la "
		+ "maintenance du système."
		+ "<p>Code d'erreur : f0c93<p>"
	);
	debugger; // ERROR
	// TODO implement retry
	// Let's say, 2s 4s 8s 16s & 32s silent retries, then ask user action to retry
});

eo.deps.reg('eo.Ajax');

//eo.Ajax.request({
//	
//	jsonData: {
//		controller: 'countries.json'
//		,action: 'autoComplete'
//	}
//	
//	,success: function(data) {
//		debugger
//	}
//});
//
//eo.Ajax.request({
//	
//	params: {
//		controller: 'countries.json'
//		,action: 'autoCompleteo'
//	}
//	
//	,success: function(data) {
//		debugger
//	}
//	
//	,failure: function(data) {
//		debugger
//	}
//});
//
//eo.Ajax.request({
//	
//	params: {
//		controller: 'languages.json'
//		,action: 'autoComplete'
//	}
//	
//	,success: function(data) {
//		debugger
//	}
//});

// Chrome wants to have some param in the url (index.php?...) to correctly
// interpret the Content-type...
if (Ext.isChrome) {
	Ext.Ajax.on('beforerequest', function(conn, opts) {
		if (Ext.isChrome && !opts.jsonData) {
			opts.jsonData = {requestType: 'AJAX'};
		}
	});
}

})(); // closure

