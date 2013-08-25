/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 sept. 2012
 */
Ext.ns('eo.modules.prefs');

eo.modules.prefs.Manager = Ext.extend(Ext.util.Observable, {
	
	defaultCommitDelay: 5000
	
	,constructor: function(config) {
		
		this.addEvents('loaded');
		
		this.callParent(arguments);
		
		// create commit task
		this.commitTask = new eo.util.DelayedTask(this.doCommit, this);
		
		// init
		this.data = {};
		this.queue = [];

		// load
		var me = this;
		
		eo.app(function(app) {
			app.getLoginManager().on('logged', function() {
				eo.Ajax.request({
					params: {
						controller: 'UserPreferences'
						,action: 'getUserPreferences'
					}
					,success: function(data) {
						// decode
						var o = data.preferences
								? Ext.decode(data.preferences)
								: {};
						// store data
						me.data = o;
						// handle queue
						Ext.each(me.queue, function(cb) {
							this.get.apply(me, cb);
						}, me);
						// flush
						me.queue = [];
						
						me.initEvents();
					}
				});
			});
		});
	}
	
	// private
	,initEvents: function() {
		Ext.EventManager.on(window, 'beforeunload', function() {
			if (this.commitTask.isDelayed()) {
				this.commitTask.cancel();
				this.doCommit(false);
			}
		}, this);
	}
	
	,get: function(path, callback, scope) {
		if (this.data) {
			callback.call(scope, this, this.node(path));
		} else {
			this.queue.push([path, callback, scope]);
		}
	}
	
	,set: function(path, value, commit) {
		// update
		if (Ext.isEmpty(path)) {
			throw new Error('Overridding root node is forebidden.');
		} else {
			var cursor = this.data,
				steps = path.split('.'),
				last = steps.pop();
			Ext.each(steps, function(step) {
				var next = cursor[step];
				if (!Ext.isDefined(next)) {
					next = cursor[step] = {};
				}
				cursor = next;
			});
			cursor[last] = value;
		}

		// commit
		if (!Ext.isDefined(commit)) {
			this.commit(this.defaultCommitDelay);
		} else if (Ext.isNumber(commit)) {
			this.commit(commit);
		} else if (commit === true) {
			this.commit(0);
		} else {
			throw new Error('Illegal argument: commit must be boolean or integer');
		}
	}
	
	/**
	 * Handler for commit DelayedTask.
	 * @private
	 */
	,doCommit: function(async) {
		// Using basex request, for synchronous request support.
		// Synchronous requests in beforeunload event will be fired by most browsers,
		// will even return in some cases.
		Ext.Ajax.request({
			jsonData: {
				controller: 'UserPreferences'
				,action: 'saveUserPreferences'
				,jsonPreferences: Ext.encode(this.data)
			}
			,async: async
			,success: function(data) {
//				debugger
			}
		});
	}
	
	/**
	 * Commit.
	 * @param {Integer} [delay=0]
	 * @see {#doCommit}
	 */
	,commit: function(delay) {
		if (!Ext.isDefined(delay)) {
			delay = 0;
		}
		this.commitTask.delay(delay);
	}
	
	// private
	,node: function(path) {
		if (!path) {
			return this.data;
		} else {
			var cursor = this.data;
			if (Ext.isString(path)) {
				path = path.split('.');
			}
			Ext.each(path, function(step) {
				var next = cursor[step];
				if (!Ext.isDefined(next)) {
					next = cursor[step] = {};
				}
				cursor = next;
			});
			return cursor;
		}
	}
	
});

Oce.deps.reg('eo.modules.prefs.Manager');