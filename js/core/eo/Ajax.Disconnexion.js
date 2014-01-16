/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 déc. 2011
 */
eo.deps.wait('eo.Ajax', function() {
	
	eo.Ajax.on('requestcomplete', function(conn, data, options) {
		if (data.success === false && data.cause === 'sessionTimeout') {
			
			// Will pop up the message
			Oce.mx.Security.notifyDisconnection(data);
			
			Oce.mx.Security.onOnce("login", function() {
				eo.Ajax.request(options);
			});
			
			data.errorProcessed = true;
			
			return false;
		}
	});
});