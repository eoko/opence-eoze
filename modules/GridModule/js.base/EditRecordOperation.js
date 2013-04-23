/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 *
 * @since 2013-03-19 11:07
 */
Ext4.define('Eoze.GridModule.EditRecordOperation', {

	/**
	 * @cfg {String} recordId
	 */

	/**
	 * @cfg {Ext.data.Record} [record]
	 */

	/**
	 * @cfg {String} [startTab]
	 */

	/**
	 * @cfg {Function} [callback]
	 */

	/**
	 * @cfg {Object} [scope]
	 */

	/**
	 * @cfg {Ext.Element} [sourceEl]
	 *
	 * The element from which the opening window (if any) will be animated.
	 */

	/**
	 * @cfg {Object} [options]
	 */

	mixins: {
		observable: 'Ext.util.Observable'
	}

	,statics: {

		/**
		 * @param {Objects} args The arguments to parse from.
		 * @return {Eoze.GridModule.EditRecordOperation}
		 */
		parseOperation: function() {

			function parseConfig(record, startTab, callback, scope, sourceEl) {

				var Operation = Eoze.GridModule.EditRecordOperation,
					config = {};

				if (arguments.length > 0) {

					// Message or Record syntax
					if (Ext.isObject(record)) {
						// Params form
						if (record instanceof Operation) {
							return record;
						}
						// Record form
						else if (record instanceof Ext.data.Record
								|| record instanceof Ext4.data.Record) {
							config.record = record;
							config.recordId = record.id;
						}
						// Message form: extract arguments
						else {
							return new Operation(record);
						}
					}
					// Record id as first argument (other arguments are left untouched)
					else {
						config.recordId = record;
					}

					return new Operation(Ext.apply(config, {
						startTab: startTab
						,callback: callback
						,scope: scope
						,sourceEl: sourceEl
					}));
				}
				// No arguments
				else {
					return new Operation;
				}
			}

			return function(args) {
				var Operation = Eoze.GridModule.EditRecordOperation;
				if (args instanceof Operation) {
					return args;
				} else {
					var config = parseConfig.apply(this, args);
					return config instanceof Operation
						? config
						: new Operation(config);
				}
			};
		}()
	}

	/**
	 * @event aftercreatewindow
	 *
	 * Fires when the edit window has been created.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} this
	 * @param {Ext.Window} window
	 */
	,EVENT_AFTER_CREATE_WINDOW: 'aftercreatewindow'
	/**
	 * @event windowready
	 *
	 * Fires when the window for the operation is available, if it was just created as well as if
	 * it already existed. At this point, the window will not have been loaded yet.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} this
	 * @param {Ext.Window} window
	 */
	,EVENT_WINDOW_READY: 'windowready'
	/**
	 * @event loaded
	 *
	 * Fires when a preparation loading has finished.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} this
	 * @param {Ext.Window} window
	 * @param {Boolean} first True if the loading was the initial one.
	 */
	,EVENT_LOADED: 'loaded'
	/**
	 * @event ready
	 *
	 * Fires when the edit form is ready (i.e. loaded). This event fires only once for each operation.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} this
	 */
	,EVENT_READY: 'ready'

	,constructor: function(config) {
		this.mixins.observable.constructor.call(this, config);

		this.addEvents(
			this.EVENT_AFTER_CREATE_WINDOW,
			this.EVENT_WINDOW_READY,
			this.EVENT_LOADED,
			this.EVENT_READY
		);
	}

	/**
	 * Returns `true` if these params are valid.
	 *
	 * @return {Boolean}
	 */
	,testIntegrity: function() {
		return !Ext.isEmpty(this.getRecordId());
	}

	/**
	 * Gets the record id. If record id is not configured, but a record is present, its id will be extracted
	 * and configured in these params.
	 *
	 * @return {String}
	 */
	,getRecordId: function() {
		if (Ext.isEmpty(this.recordId)) {
			var record = this.getRecord();
			this.recordId = record && record.id;
		}
		return this.recordId;
	}

	/**
	 * @params {String} id
	 * @return {Eoze.GridModule.EditRecordOperation} this
	 */
	,setRecordId: function(id) {
		this.recordId = id;
		return this;
	}

	/**
	 * @return {Ext.data.Record/undefined}
	 */
	,getRecord: function() {
		return this.record;
	}

	/**
	 * @params {Ext.data.Record} record
	 * @return {Eoze.GridModule.EditRecordOperation} this
	 */
	,setRecord: function(record) {
		this.record = record;
		return this;
	}

	/**
	 * @return {String/undefined}
	 */
	,getStartTab: function() {
		return this.startTab;
	}

	/**
	 * @param {String} startTab
	 * @return {String/undefined}
	 */
	,setStartTab: function (startTab) {
		this.startTab = startTab;
		return this;
	}

	/**
	 * Calls the configured callback with the passed arguments.
	 */
	,triggerCallback: function() {
		Ext.callback(this.callback, this.scope, arguments);
	}

	/**
	 * @params {Function} callback
	 * @params {Object} [scope]
	 * @return {Eoze.GridModule.EditRecordOperation} this
	 */
	,setCallback: function (callback, scope) {
		this.callback = callback;
		this.scope = scope;
		return this;
	}

	/**
	 * @return {Ext.Element/undefined}
	 */
	,getSourceEl: function() {
		return this.sourceEl;
	}

	/**
	 * @param {Ext.Element} sourceEl
	 * @return {Eoze.GridModule.EditRecordOperation} this
	 */
	,setSourceEl: function (sourceEl) {
		this.sourceEl = sourceEl;
		return this;
	}

	/**
	 * @return {Object}
	 */
	,getOptions: function() {
		if (!this.options) {
			this.options = {};
		}
		return this.options;
	}

	/**
	 * @param {String/Object} name
	 * @param {Mixed} [value]
	 */
	,setOption: Ext4.Function.flexSetter(function(name, value) {
		var options = this.getOptions();
		options[name] = value;
	})

	/**
	 * @param {String/String[]} name
	 */
	,unsetOption: function(name) {
		var options = this.options;
		if (options) {
			if (Ext.isArray(name)) {
				name.forEach(function(name) {
					delete options[name];
				});
			} else {
				delete options[name];
			}
		}
	}

	/**
	 * @param {Ext.Window} win
	 * @param {Boolean} [existing=false] True if the window already existed.
	 * @return {Eoze.GridModule.EditRecordOperation} this
	 */
	,setWindow: function(win, existing) {

		if (this.win) {
			throw new Error('Window already set.');
		}

		// Reference
		this.win = win;

		// Legacy callback (probably slightly broken, deprecate!)
		Ext.callback(this.callback, this.scope, win);

		// Event
		if (!existing) {
			this.fireEvent(this.EVENT_AFTER_CREATE_WINDOW, this, win);
		}

		this.fireEvent(this.EVENT_WINDOW_READY, this, win);

		return this;
	}

	,getWindow: function() {
		return this.win;
	}

	/**
	 * Notifies that the edit form is ready (loaded).
	 */
	,notifyReady: function() {
		if (!this.readyFired) {
			this.readyFired = true;
			this.fireEvent(this.EVENT_READY, this);
		}
	}

	/**
	 * Notifies that a preparation loading has finished.
	 *
	 * @param {Ext.Window} win
	 * @param {Boolean} first True if it was the initial loading.
	 */
	,notifyLoaded: function(win, first) {
		this.triggerCallback(win);

		if (first) {
			this.notifyReady();
		}

		this.fireEvent(this.EVENT_LOADED, this, win, first);
	}

	,notifyClosed: function() {

	}
});
