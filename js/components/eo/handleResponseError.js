/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 7 juin 2012
 */

Ext.ns('eo');

(function() {
	var leaving = false;
	window.addEventListener('beforeunload', function() {
		leaving = true;
	}, false);

	eo.handleRequestException = function(conn, response, options) {
		if (!leaving) {

			// Ext.form.Action replaces the options object it has been given
			// with its own; thus losing all the custom property we may have
			// set. Fortunately, we can access it back fromt he Action object
			// that is itself used as the scope of the Ajax call.
			if (options.scope instanceof Ext.form.Action) {
				options = options.scope.options;
			}

			function handleError(userError) {
				var data;
				try {
					data = Ext.decode(response.responseText);
					eo.handleResponseError(data, options);
				} catch (e) {
					Oce.Modules.GridModule.AlertWindow.show({
						title: "Erreur" // i18n
						,message: msg

						,modalTo: options && options.sourceComponent || options.win

						,okHandler: function() {
							this.close();
						}
					});
//					Ext.Msg.alert(
//						"Erreur" + (userError ? "" : " serveur"),
//						"Une erreur " + (userError ? "" : "serveur ") + "a empêcher l'exécution correcte de cette opération."
//						+ "Nous sommes désolé pour le désagrément, vous pouvez signaler cette erreur au support "
//						+ "technique pour " + (userError ? "obtenir de l'aide" : "aider à la résoudre") + "."
//					);
				}
			}

			if (response.status >= 400 && response.status < 500) {
				if (response.status === 401) {
					Oce.mx.Security.notifyDisconnection();
				} else {
					// Hope that an error handler further down the road will
					// handle it... Give them 200ms!
					setTimeout(function() {
						if (!options || !options.errorMessageDisplayed) {
							handleError("Erreur");
						}
					}, 200);
				}
			} else if (response.status === 0) {
				var warn = function(msg) {
					if (window.console) {
						if (console.warn) {
							console.warn(msg);
						} else if (console.log) {
							console.log("WARNING: " + msg);
						}
					}
				};
				// retries
				var retries = options.maxRetries || conn.maxRetries;
				options.retryCount = options.retryCount || 0;
				if (retries && options.retryCount < retries) {
					options.retryCount++;
					conn.request(options);
					warn("Retrying failed request ("+options.retryCount+"/"+retries+")...");
					return false;
				}
				// no more retry
				else {
					// info
					warn("Aborting request after " + options.retryCount + " trials: "
						+ Ext.encode(options.params));
					// message
					Ext.Msg.alert(
						'Erreur de connection',
						"Vérifiez l'état de votre connection internet. Si le problème "
							+ "persiste, il peut s'agir d'un problème avec le serveur ; "
							+ "dans ce cas veuillez contacter la personne responsable de la "
							+ "maintenance du système."
							+ "<p>Code d'erreur : f0c93<p>"
					);
					debugger; // ERROR
				}
			} else {
				handleError();
			}
		}
	};

	/**
	 * Tries to handle in the best possible way an error response.
	 * @param {Object} responseData
	 *
	 * @param {Object} options
	 * @param {Ext.Component} [options.sourceComponent] The source component, that will be
	 * masked if required.
	 * @param {Function} [options.callback] A function that will be called after the user
	 * has acknowledged the issue.
	 * @param {Object} [options.scope] The scope in which the acknowledge callback will be
	 * called.
	 */
	eo.handleResponseError = function(responseData, options) {

		var sc = options.sourceComponent,
			cb = options.callback,
			scope = options.scope,
			title, msg;

		if (responseData.errorMessage) {
			title = responseData.title || "Erreur"; // i18n
			msg = responseData.errorMessage;
		}

		else if (responseData.details) {
			var d = responseData.details,
				rid = d.requestId,
				ts = d.timestamp,
				msg = d.msg || d.message;

			title = d.title || "Erreur"; // i18n

			if (!msg) {
				// i18n
				msg = "<p>Désolé, une erreur système est survenue "
					+ "et a empêché l'exécution correcte de cette opération.</p>";
			}

			if (rid) {
				msg += "<li>Requête #" + rid + "</li>";
			} else if (ts) {
				msg += "<li>Erreur #" + ts + "</li>";
			}
		}

		else {
			debugger // TODO
			throw new Error('TODO');
		}

		var win = Oce.Modules.GridModule.AlertWindow.show({
			title: title
			,message: msg
			,modalTo: sc
			,modal: !sc
			,okHandler: function() {
				win.close();
				options.errorMessageDisplayed = true;
				Ext.callback(cb, scope, [options, false, responseData]);
			}
		});
	};

	Oce.deps.reg('eo.handleResponseException');
	Oce.deps.reg('eo.handleResponseError');

})(); // closure
