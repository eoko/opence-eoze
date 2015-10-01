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
	singleton: true

	,running: false
	
	,failureCount: 0
	
	,conn: new eo.data.Connection({
		url: 'api'
		,accept: 'json'
	})
	
	,constructor: function() {
		var me = this;

		Oce.deps.wait('Oce.Bootstrap.start', function() {
			var service = Deft.Injector.resolve('auth');
			service.on('login', me.poll, me, {delay: 1});
		});

		eo.Kepler.superclass.constructor.apply(this, arguments);
	}
	
	,poll: function() {
		if (!this.polling) {
			this.polling = true;
			this.running = true;
			this.conn.request({
				params: {
					controller: 'kepler' // TODO configurable controller
//					,timeout: 5
				}
				,jsonData: {
					type: 'json'
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
				var args = [].concat(event.args);
				args.unshift(event); // the raw event as the first arg
				args.unshift(c); // name of the event to fire
				this.fireEvent.apply(this, args);
			} else {
				this.fireEvent(c, event);
			}
		}, this);
	}
	
	,onPollFailure: function() {
		this.running = false;
		this.polling = false;
		if (++this.failureCount < 10) {
			var me = this;
			setTimeout(function() {
				me.resumePolling();
			}, 10000);
		} else {
			debugger
			// TODO handle errors
		}
	}
	
	,onPollSuccess: function(data) {
		this.polling = false;
		var entries = data.entries;
		if (data.success && entries) {
			if (entries.events) {
				try {
					this.processEvents(entries.events);
				} catch (e) {
					// TODO handle errors
					debugger
				}
			}
		}
		this.resumePolling();
	}
	
	,resumePolling: function() {
		this.polling = false;
		this.poll();
	}
	
	,isRunning: function() {
		return this.running;
	}
});

eo.Kepler = new eo.Kepler;
