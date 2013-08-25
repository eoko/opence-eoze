/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 7 juin 2012
 */

Ext.ns('eo');

/**
 * Tries to handle in the best possible way an error response.
 * @param {Object} responseData 
 * 
 * @param {Object} options
 * @param {Ext.Component} options.sourceComponent The source component, that will be
 * masked if required.
 * @param {Function} options.callback A function that will be called after the user
 * has acknowledged the issue.
 * @param {Object} options.scope The scope in which the acknowledge callback will be
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
		,okHandler: function() {
			win.close();
			if (cb) {
				cb.call(scope);
			}
		}
	});
}
