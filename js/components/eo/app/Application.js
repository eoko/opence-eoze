/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 sept. 2012
 */
Oce.deps.wait([
	'eo.app.LoginManager.LegacyLoginManager',
	'eo.modules.prefs.Manager'
], function() {
	
Ext.ns('eo.app');

/**
 * Global state & events holder.
 */
eo.app.Application = Ext.extend(Ext.util.Observable, {

	/**
	 * @property {eo.app.LoginManager}
	 * @private
	 */
	loginManager: undefined
	
	/**
	 * @property {eo.modules.prefs.Manager}
	 * @private
	 */
	,prefsManager: undefined

	,constructor: function() {
		
		this.addEvents(
			'configured',
			'started',
			'datechanged'
		);
		
		eo.app.Application.superclass.constructor.apply(this, arguments);
		
		// set login manager
		this.loginManager = new eo.app.LoginManager.LegacyLoginManager;
		
		// pref manager
		this.prefsManager = new eo.modules.prefs.Manager;
	}
	
	/**
	 * @return eo.app.LoginManager
	 */
	,getLoginManager: function() {
		return this.loginManager;
	}

	,setEozeApplication: function(app) {
		this.app = app;

		app.onConfigure = Ext.Function.createSequence(app.onConfigure, function() {

			// fire event
			this.fireEvent('configured', this);

			// set year manager
			this.yearManager = Oce.mx.application.YearManager;
			this.relayEvents(this.yearManager, ['datechanged']);
		}, this);

		app.afterStart = Ext.Function.createSequence(app.afterStart, function() {
			this.fireEvent('started', this);
		}, this);
	}

	,getYearManager: function() {
		return this.yearManager;
	}

	,getDate: function() {
		return this.getYearManager().getDate();
	}
	
	,getPreferences: function(path, callback, scope) {
		this.prefsManager.get(path, callback, scope);
	}

});

(function() {
	var app = new eo.app.Application;
	/**
	 * @return {eo.opence.Application}
	 */
	eo.getApplication = function() {
		return app;
	};
	/**
	 * @return {Opence.Opence.model.Configuration}
	 */
	eo.getOpenceConfiguration = function() {
		return app.app.openceConfiguration;
	}
})(); // closure

Oce.deps.reg('eo.app.Application');

}); // deps
