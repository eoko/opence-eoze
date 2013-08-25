/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 sept. 2012
 */
Ext.ns('eo.app');

eo.app.LoginManager = Ext.extend(Ext.util.Observable, {
	
	constructor: function(config) {
		
		this.addEvents(
			'logged',
			'login',
			'logout'
		);
		
		this.callParent(arguments);
	}
});

eo.app.LoginManager.LegacyLoginManager = Ext.extend(eo.app.LoginManager, {
	
	constructor: function() {
		this.callParent(arguments);
		
		Oce.deps.wait('Oce.Bootstrap.start', this.onBootstrap.createDelegate(this));
	}
	
	/**
	 * @private
	 */
	,onBootstrap: function() {
		var	me = this,
			s = Oce.mx.Security;
		s.whenIdentified(function() {
			me.fireEvent('logged', me);
		});
		s.addListener('login', function() {
			me.fireEvent('login', me);
		});
		s.addListener('logout', function() {
			me.fireEvent('login', me);
		});
	}
});

Oce.deps.reg('eo.app.LoginManager.LegacyLoginManager');