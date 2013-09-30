(function() {

	var NS = Ext.ns('eo.task');

	var TaskManager = NS.TaskManager = Ext.extend(Ext.util.Observable, {

	});
	
	var Events = {
		// Operation events
		BEFORE_RUN: 'beforerun'
		,RUN: 'run'
		,SUCCESS: 'success'
		,FAILURE: 'failure'
		,COMPLETE: 'complete'
		// Enable events
		,BEFORE_ENABLE: 'beforeenable'
		,ENABLE: 'enabled'
		,BEFORE_DISABLE: 'beforedisable'
		,DISABLED: 'disabled'
	};

	var Task = eo.extendMultiple(
		Ext.util.Observable
		,eo.aspects.Runnable
		,eo.aspects.Disableable
	);
	Task = Ext.extend(Task, eo.aspects.Disableable);

	var Task = NS.Task = Ext.extend(Ext.Observable, {

		constructor: function(config) {
			Task.superclass.constructor.call(this, config);

			Ext.each(Events, function(e) {
				this.addEvents(e);
			});

			Ext.apply(this, config);

			if (this.plugins) Ext.each(this.plugins, function(plugin) {
				plugin = this.createPlugin(plugin);
				plugin.init(this);
			}, this);
		}

		// private final
		,run: function() {

			if (!this.fireEvent(Events.BEFORE_RUN, this)) return;

			this.fireEvent(Events.RUN, this);
			var result = this.doRun.call(this.scope || this);

			var success = this.processResult(result)
			
			if (success) {
				this.fireEvent(Events.SUCCESS, this, result);
				if (this.onSuccess) this.onSuccess(result);
			} else {
				this.fireEvent(Events.FAILURE, this, result);
				if (this.onFailure) this.onFailure(result);
			}

			this.fireEvent(Events.COMPLETE, this, success, result);
			if (this.onComplete) this.onComplete(success, result);
		}

		,processResult: function(result) {
			return result === false ? false : true;
		}

		,doRun: function() {}

		,onSuccess: undefined
		,onFailure: undefined
		,onComplete: undefined

	});

	eo.aspects.Disableable(Task, {
		events: true
	});

	Task.plugins.ComponentDisabler = eo.Object.create({

		constructor: function(config) {
			Ext.apply(this, config);
//			this.components =
		}

		,init: function(task) {
			task.on(Events.RUN, function() {
				
			}, this);
		}
	});


}); // debug -- deactivated -- closure
//})(); // closure