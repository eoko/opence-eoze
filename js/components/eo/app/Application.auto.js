/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 sept. 2012
 */
Oce.deps.wait('eo.app.LoginManager.LegacyLoginManager', function() {
	
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

	,constructor: function() {
		
		this.addEvents(
			'configured',
			'started',
			'datechanged'
		);
		
		eo.app.Application.superclass.constructor.apply(this, arguments);
		
		// set login manager
		this.loginManager = new eo.app.LoginManager.LegacyLoginManager;
	}
	
	/**
	 * @return eo.app.LoginManager
	 */
	,getLoginManager: function() {
		return this.loginManager;
	}

	,setEozeApplication: function(app) {
		this.app = app;

		app.onConfigure = app.onConfigure.createSequence(function() {

			// fire event
			this.fireEvent('configured', this);

			// set year manager
			this.yearManager = Oce.mx.application.YearManager;
			this.relayEvents(this.yearManager, ['datechanged']);
		}, this);

		app.afterStart = app.afterStart.createSequence(function() {
			this.fireEvent('started', this);
		}, this);
	}

	,getYearManager: function() {
		return this.yearManager;
	}

	,getDate: function() {
		return this.getYearManager().getDate();
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
})(); // closure

Oce.deps.reg('eo.app.Application');

}); // deps