/**
 * @author Éric Ortéga
 */

Oce.EventManager = function(owner) {

	this.listeners = {};
	this.onceListeners = {};

	var me = this;

	var api = {

		addListener: function(name, listener) {
			if (name in me.listeners == false) me.listeners[name] = [];
			me.listeners[name].push(listener);
		},

		fire: function(name, infos) {
			Ext.each(me.listeners[name], function(l){
				l(owner, infos);
			})
			Ext.each(me.onceListeners[name], function(l){
				l(owner, infos);
			})
			me.onceListeners = {};
		}
	}

	owner.addListener = api.addListener;
	owner.onOnce = function(name, listener) {
		if (name in me.onceListeners == false) me.onceListeners[name] = [];
		me.onceListeners[name].push(listener);
	}

	return api;
}