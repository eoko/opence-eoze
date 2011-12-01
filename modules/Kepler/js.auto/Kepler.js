/**
 * Simple singleton for watching comet messages.
 * 
 * @singleton
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 nov. 2011
 */
Ext.ns('eo');

eo.Kepler = Ext.extend(Ext.util.Observable, {
	
	running: false
	
	,failureCount: 0
	
	,constructor: function() {
		var me = this;
		Oce.deps.wait('Oce.Bootstrap.start', function() {
			if (Oce.mx.Security.isIdentified()) {
//				userId = Oce.mx.Security.getLoginInfos().userId;
				me.poll();
			}
			Oce.mx.Security.addListener('login', function(info) {
//				userId = info.userId;
				me.poll();
			});
		});
		eo.Kepler.superclass.constructor.apply(this, arguments);
	}
	
	,poll: function() {
		if (!this.polling) {
			this.polling = true;
			this.running = true;
			Ext.Ajax.request({
//				url: "comet.php"
				params: {
					controller: 'kepler' // TODO configurable controller
//					,timeout: 5
				}
				,scope: this
				,success: this.onPollSuccess
				,failure: this.onPollFailure
			});
		}
	}
	
	,processEvents: function(events) {
		Ext.each(events, function(event) {
			var c = event['class'] + ':' + event.name;
			if (event.args) {
				var args = event.args;
				args.unshift(event); // the raw event as the first arg
				args.unshift(c); // name of the event to fire
				this.fireEvent.apply(this, args);
			} else {
				this.fireEvent(c, event);
			}
		}, this);
	}
	
	,onPollFailure: function() {
		if (++this.failureCount < 10) {
			var me = this;
			setTimeout(function() {
				me.continuePolling();
			}, 2000);
		} else {
			this.running = false;
			debugger
			// TODO handle errors
		}
	}
	
	,onPollSuccess: function(response) {
		this.processResponse(Ext.decode(response.responseText));
	}
	
	,processResponse: function(response) {
		var entries = response.entries;
		if (response.success && entries) {
			if (entries.events) {
				try {
					this.processEvents(entries.events);
				} catch (e) {
					// TODO handle errors
					debugger
				}
			}
		}
		this.continuePolling();
	}
	
	,continuePolling: function() {
		this.polling = false;
		this.poll();
	}
	
	,isRunning: function() {
		return this.running;
	}
});

eo.Kepler = new eo.Kepler;

Oce.deps.wait('Oce.Bootstrap.start', function() {

//	debugger
//	eo.Kepler.start();

//	var userId;
//	if (Oce.mx.Security.isIdentified()) {
//		userId = Oce.mx.Security.getLoginInfos().userId;
//		poll();
//	}
//	Oce.mx.Security.addListener('login', function(info) {
//		userId = info.userId;
//		poll();
//	});
//
//	function poll() {
//		Ext.Ajax.request({
//			url: "comet.php"
//			,params: {
//				id: userId
//			}
//			,success: function(response) {
//				var o = Ext.decode(response.responseText);
//				if (o.alert) {
//					alert(o.alert);
//				}
//				poll();
//			}
//			,failure: function() {
//				poll()
//			}
//		});
//	}
});
