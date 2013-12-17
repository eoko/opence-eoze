eo.Sjax = eo.Class({

	waiting: false
	,queue: []
	
	,constructor: function() {
		var me = this;
		this.next = function() {
			eo.Sjax.prototype.next.call(me);
		};
	}
	
	// private
	,next: function() {
		var q = this.queue;
		if (q.length) {
			Oce.Ajax.request(this.wrapCallbacks(q.shift()));
		} else {
			this.waiting = false;
		}
	}

	,request: function(opts) {
		if (this.waiting) {
			this.queue.push(opts);
		} else {
			Oce.Ajax.request(this.wrapCallbacks(opts));
		}
	}
	
	// private
	,wrapCallbacks: function(opts) {
		var onComplete = opts.onComplete;
			
		if (onComplete) {
			opts.onComplete = Ext.Function.createSequence(onComplete, this.next);
		} else {
			opts.onComplete = this.next;
		}
		
		return opts;
	}
});