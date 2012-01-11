/**
 * @author Éric Ortéga <eric@mail.com>
 */

Oce.Ajax = function() {

	var waitBox = null;

	function clearWaitBox() {
		if (waitBox !== null) {
			waitBox.hide();
			waitBox = null;
		}
	}

	function doRequest(opts, successCallback) {

		if (opts.waitMessage) opts.waitMsg = opts.waitMessage;

//		Ext.applyIf(opts, {
//			waitMsg: true
//		});

		var 
//			waitBox = null,
			waitTarget = null;

		var onComplete = 'onComplete' in opts ? opts.onComplete : null;

		opts.callback = function(options, success, response) {

			clearWaitBox();
//			if (waitBox !== null) waitBox.hide();
			if (waitTarget) waitTarget.unmask();

			if (success) {
				// TODO rx debug that ... successCallback should *always* be
				// a function. There is a problem with this method being called
				// in relation with forms (test case: membre's pdf personnal file)
				if (Ext.isFunction(successCallback)) {
					successCallback(response.responseText, response);
				}
			} else {
				handleError(opts, null, response);
			}

			if (onComplete !== null) onComplete();
		}

		if ('waitMsg' in opts) {
			if (!(typeof opts.waitMsg == 'boolean' || opts.waitMsg instanceof Boolean) || opts.waitMsg) {
				var waitMsg = typeof opts.waitMsg == 'boolean' ? 'Veuillez patienter...' : opts.waitMsg; // i18n

				if (opts.waitTarget) {
					waitTarget = opts.waitTarget instanceof Ext.Element ? opts.waitTarget :
						(opts.waitTarget.el instanceof Ext.Element ? opts.waitTarget.el : null);
					waitTarget.mask(waitMsg, 'x-mask-loading');
				} else {
					if (opts.waitTitle) waitBox = Ext.MessageBox.wait(waitMsg, opts.waitTitle);
					else waitBox = Ext.MessageBox.wait(waitMsg);
				}
			}
		}

		return Ext.Ajax.request(opts);
	}

	function handleErrorCause(obj, onCorrected) {
		if (!obj.cause) return false;
		switch(obj.cause) {
			case 'sessionTimeout':
				Oce.mx.Security.notifyDisconnection(obj);
				if (onCorrected) Oce.mx.Security.onOnce('login', onCorrected);
//				Oce.mx.Security.
				return true;

			default: return false;
		}
	}

	var errorHandlerVersions = {

		"0.10.11": function(opts, obj) {

			var onFailure = 'onFailure' in opts ? opts.onFailure : false;
			var e = {};

			if (onFailure !== false) {
				onFailure(obj, e);
			}

			var msg, passMsg = false;
			if ('message' in obj) {
				if (obj.message === false && !e.forceMsgBox) {
					passMsg = true;
				} else {
					msg = obj.message || obj.errorMessage;
					if (!msg) {
						// TODO build message
					}
				}
			} else {
				msg = "Erreur non identifiée"; // i18n
			}

			if (!passMsg) {
				Ext.MessageBox.show({
					title : obj.title || "Erreur", // i18n
					msg : msg,
					buttons: Ext.MessageBox.OK,
					bodyStyle: 'padding:10px;',
					minWidth: Ext.MessageBox.minWidth
				});
			}
		}
	}

	function handleError(opts, errors, response, obj) {
		
		if (errors === null && obj.details) {
			errors = obj.details;
		}

		clearWaitBox();
//REM		if (waitBox !== null) {
//			waitBox.hide();
//			waitBox = null;
//		}

		var onFailure = 'onFailure' in opts ? opts.onFailure : false,
			failureMessageHandler = 'failureMessageHandler' in opts ?
				opts.failureMessageHandler : false;

		if (failureMessageHandler !== false) {
			failureMessageHandler(errors, obj);
		} else {

			if (obj) {
				if (handleErrorCause(obj, function() { Oce.Ajax.request(opts); })) {
					return;
				} else if (obj.errorHandlerVersion) {
					errorHandlerVersions[obj.errorHandlerVersion](opts, obj);
					return;
				}
			}

			var reason = null, 
				system = true, 
				timestamp = null,
				title = 'Erreur';

			var message = "<p>Désolé, cette opération n'a pas pu être correctement "
				+ "exécutée (le serveur ne répond pas, ou sa réponse n'est pas valide).</p>";

			if (errors !== null) {
				var errorMessage = 'message' in errors ? errors.message :
					('msg' in errors ? errors.msg : null);
				reason = 'reason' in errors ? errors.reason : null;
				system = obj && obj.system !== undefined ? obj.system : (
					'system' in errors ? errors.system : true
				);
				timestamp = 'timestamp' in errors ? errors.timestamp : null;
				title = 'title' in errors ? errors.title : null;

				if (system) {
					message = "<p>Désolé, une erreur système est survenue "
						+ "et a empêché l'exécution correcte de cette opération.</p>";

					if (reason !== null || timestamp !== null) {
						// TODO
						message += "<p>Vous pouvez rapporter les informations suivantes"
							+ " au support technique pour aider à corriger cette erreur&nbsp;:</p>"
							+ "<p>";

						if (timestamp !== null) {
							message += "Erreur #" + timestamp + "<br />";
						}
						if (reason !== null) {
							message += reason;
						}
						message += "</p>";
					}
				} else {
					if (errorMessage !== null) {
						message = errorMessage;
					} else if (reason !== null) {
						message = reason;
					} else {
						message = "Désolé, une erreur non-identifiée est survenue.";
					}
				}
			}

			if (onFailure !== false) {
				if (false === onFailure(errors, response, obj)) {
					return;
				}
			}

			if (message !== false) {
				Ext.MessageBox.show({
					cls: "error-dlg",
					title : title,
					msg : message,
					buttons: Ext.MessageBox.OK,
					bodyStyle: 'padding:10px;',
					minWidth: Ext.MessageBox.minWidth
				});
			}
		}
	}

	Ext.Ajax.on('requestcomplete', function(conn, response, options) {
		if (!options.raw) {
			Oce.Ajax.handleRequestResponse.apply(Oce.Ajax, arguments);
		}
	});

	return {

		requestRaw: function(opts) {
			opts.raw = true;
			doRequest(opts, function(responseText) {
				if ('onSuccess' in opts) {
					opts.onSuccess(responseText);
				}
			})
		},

		defaultSuccessMsgHandler: function(msg, showMsgBox) {

			if (showMsgBox === undefined) showMsgBox = true;

			var boxMessage = null, boxTitle = null;

			if (Ext.isArray(msg)) {
				boxMessage = '<ul>';
				for (var i=0,l=msg.length; i<l; i++) {
					var s = arguments.callee(msg[i], false);
					if (s !== null) boxMessage += '<li>' + s + '</li>';
				}
				boxMessage += '</ul>';
			} else if (Ext.isObject(msg)) {
				if ('title' in msg) boxTitle = msg.title;
				boxMessage = arguments.callee(Oce.pickFirst(msg, 'message', 'msg'), false);
			} else if (Ext.isString(msg)) {
				boxMessage = msg;
			}

			if (boxMessage !== null) {

				clearWaitBox();

				if (showMsgBox) {
					Ext.Msg.alert(boxTitle, boxMessage);
				} else {
					if (boxTitle !== null) {
						return '<b>' + boxTitle + '</b> : ' + boxMessage;
					} else {
						return boxMessage;
					}
				}
			}

			return null;
		}

		,request: doRequest
//		,request: function(opts) {
//			doRequest(opts);
//			var onSuccess = 'onSuccess' in opts ? opts.onSuccess : null,
//				successMsgHandler = 'successMsgHandler' in opts ? opts.successMsgHander
//				: this.defaultSuccessMsgHandler;
//
//			doRequest(opts, function(responseText, response) {
//
//				var obj = response.decoded || Ext.util.JSON.decode(responseText);
//
//				if (obj.success) {
//					if (onSuccess !== null) {
//						if ('data' in obj) onSuccess(obj.data, obj);
//						else onSuccess(null, obj);
//					}
//					var msg = Oce.pickFirst(obj, ['message', 'msg', 'messages']);
//					if (msg !== undefined && successMsgHandler !== null) {
//						successMsgHandler(msg);
//					}
//				} else {
//					handleError(opts, 'errors' in obj ? obj.errors : null, response);
//				}
//			})
//		}

		,handleRequestResponse: function(conn, response, opts) {

			if (opts.raw || opts.form) {
				return;
			}

			var onSuccess = 'onSuccess' in opts ? opts.onSuccess : null,
				successMsgHandler = 'successMsgHandler' in opts ? opts.successMsgHander
				: this.defaultSuccessMsgHandler;

			var obj = response.decoded || Ext.util.JSON.decode(response.responseText);

			if (obj.success) {
				if (onSuccess !== null) {
					if ('data' in obj) onSuccess(obj.data, obj);
					else onSuccess(obj, obj);
				}
				var msg = Oce.pickFirst(obj, ['message', 'msg', 'messages']);
				if (msg !== undefined && successMsgHandler) {
					successMsgHandler(msg);
				}
			} else {
				handleError(opts, 'errors' in obj ? obj.errors : null, response, obj);
			}
		}

		,handleFormError: function(form, action, forceMessage) {
			switch (action.failureType) {
				case Ext.form.Action.CLIENT_INVALID:
					Ext.Msg.alert('Erreur', 'Certains champs ne sont pas correctement remplis.');
					break;
				case Ext.form.Action.CONNECT_FAILURE:
					Ext.Msg.alert('Erreur', 'Le serveur est innaccessible.');
					break;
				case Ext.form.Action.SERVER_INVALID:
					var result = action.result;
					// TODO
					if (handleErrorCause(action.result, function() { action.run() })) {
//						debugger;
					} else if (result.message !== undefined && result.message !== false) {
						var title = result.title !== undefined ? result.title : 'Erreur';
						Ext.Msg.alert(title, result.message);
					} else if (result.errors !== undefined) {
						if (forceMessage && result.errorMessage) {
							var title = result.title !== undefined ? result.title : 'Erreur';
							Ext.Msg.alert(title, result.errorMessage);
						}
						break;
//						handleError(
//							{},
//							action.result.errors,
//							action.response,
//							action.response.decoded || Ext.util.JSON.decode(action.response.responseText)
//						);
					} else {
						handleError({}, null, action.response);
					}
			}
		}
		
		,serializeForm: function(form, jsonParam) {
			if (jsonParam === undefined) {
				return Ext.lib.Ajax.serializeForm(form);
			}

			var fElements = form.elements || (document.forms[form] || Ext.getDom(form)).elements,
				hasSubmit = false,
				encoder = encodeURIComponent,
				element,
//				options,
				name,
//				val,
				data = {},
				type;

			Ext.each(fElements, function(element) {
				name = element.name;
				type = element.type;

				if (!element.disabled && name){
					if(/select-(one|multiple)/i.test(type)) {
						Ext.each(element.options, function(opt) {
							if (opt.selected) {
//								data += String.format("{0}={1}&", encoder(name), encoder((opt.hasAttribute ? opt.hasAttribute('value') : opt.getAttribute('value') !== null) ? opt.value : opt.text));
								data[name] = ((opt.hasAttribute ? opt.hasAttribute('value') : opt.getAttribute('value') !== null) ? opt.value : opt.text);
							}
						});
					} else if(!/file|undefined|reset|button/i.test(type)) {
						if (/checkbox/i.test(type)) {
							data[name] = element.checked ? 1 : 0;
						} else if (/radio/i.test(type)) {
							if (element.checked) {
									data[name] = element.value;
							}
//						if (/radio|checkbox/i.test(type)) {
//							data[name] = element.checked ? 1 : 0;
						} else if (!(type == 'submit' && hasSubmit)) {
//						if(!(/radio|checkbox/i.test(type) && !element.checked) && !(type == 'submit' && hasSubmit)){

//							data += encoder(name) + '=' + encoder(element.value) + '&';
							data[name] = element.value;
							hasSubmit = /submit/i.test(type);
						}
					}
				}
			});

			var r = encoder(Ext.util.JSON.encode(data));
			return Ext.isString(jsonParam) ? jsonParam + "=" + r : r;
//			return data.substr(0, data.length - 1);
		}
	}
}()

Ext.Ajax.request = function(o){
	var me = this,
		GET = 'GET',
		POST = 'POST';
//	if(me.fireEvent(BEFOREREQUEST, me, o)){
	if(me.fireEvent('beforerequest', me, o)){
		if (o.el) {
			if(!Ext.isEmpty(o.indicatorText)){
				me.indicatorText = ''+o.indicatorText+"";
			}
			if(me.indicatorText) {
				Ext.getDom(o.el).innerHTML = me.indicatorText;
			}
			o.success = (Ext.isFunction(o.success) ? o.success : function(){}).createInterceptor(function(response) {
				Ext.getDom(o.el).innerHTML = response.responseText;
			});
		}

		var p = o.params,
			url = o.url || me.url,
			method,
			cb = {success: me.handleResponse,
				  failure: me.handleFailure,
				  scope: me,
				  argument: {options: o},
				  timeout : o.timeout || me.timeout
			},
			form,
			serForm;


		if (Ext.isFunction(p)) {
			p = p.call(o.scope||WINDOW, o);
		}

		p = Ext.urlEncode(me.extraParams, Ext.isObject(p) ? Ext.urlEncode(p) : p);

		if (Ext.isFunction(url)) {
			url = url.call(o.scope || WINDOW, o);
		}

		if((form = Ext.getDom(o.form))){
			url = url || form.action;
			 if(o.isUpload || /multipart\/form-data/i.test(form.getAttribute("enctype"))) {
				 return me.doFormUpload.call(me, o, p, url);
			 }
// rx
			if (o.serializeForm) {
				serForm = o.serializeForm(form, o.jsonFormParam);
			} else if (o.jsonFormParam) {
				serForm = Oce.Ajax.serializeForm(form, o.jsonFormParam);
			} else {
				serForm = Ext.lib.Ajax.serializeForm(form);
			}
// rx.
			p = p ? (p + '&' + serForm) : serForm;
		}

		method = o.method || me.method || ((p || o.xmlData || o.jsonData) ? POST : GET);

		if(method === GET && (me.disableCaching && o.disableCaching !== false) || o.disableCaching === true){
			var dcp = o.disableCachingParam || me.disableCachingParam;
			url = Ext.urlAppend(url, dcp + '=' + (new Date().getTime()));
		}

		o.headers = Ext.apply(o.headers || {}, me.defaultHeaders || {});

		if(o.autoAbort === true || me.autoAbort) {
			me.abort();
		}

		if((method == GET || o.xmlData || o.jsonData) && p){
			url = Ext.urlAppend(url, p);
			p = '';
		}
		return (me.transId = Ext.lib.Ajax.request(method, url, cb, p, o));
	}else{
		return o.callback ? o.callback.apply(o.scope, [o,UNDEFINED,UNDEFINED]) : null;
	}
}
