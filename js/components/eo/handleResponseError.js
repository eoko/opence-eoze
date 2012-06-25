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
		scope = options.scope;
	
	if (responseData.errorMessage) {
		var title = responseData.title || "Erreur", // i18n
			msg = responseData.errorMessage;

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
		
	} else {
		debugger // TODO
		throw new Error('TODO');
	}
}
