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
Ext4.define('Eoze.GridModule.EditRecordParams', {

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

	statics: {
		/**
		 * @param params
		 * @throw Error If the passed params are not valid (integrity check).
		 * @return {Eoze.GridModule.EditRecordParams}
		 */
		parseParams: function (params) {
			var Params = Eoze.GridModule.EditRecordParams;
			if (!(params instanceof Params)) {
				params = new Params(params);
			}
			if (params.testIntegrity()) {
				return params;
			} else {
				throw new Error('Illegal argument: record id is required.');
			}
		}

		/**
		 * @param {Objects} args The arguments to parse from.
		 * @return {Eoze.GridModule.EditRecordParams}
		 */
		,parseArguments: function() {

			function parseConfig(record, startTab, callback, scope, sourceEl) {

				var Params = Eoze.GridModule.EditRecordParams,
					config = {};

				if (arguments.length > 0) {

					// Message or Record syntax
					if (Ext.isObject(record)) {
						// Params form
						if (record instanceof Params) {
							return records;
						}
						// Record form
						else if (record instanceof Ext.data.Record) {
							config.record = record;
							config.recordId = record.id;
						}
						// Message form: extract arguments
						else {
							return new Params(record);
						}
					}
					// Record id as first argument (other arguments are left untouched)
					else {
						config.recordId = record;
					}

					return new Params(Ext.apply(config, {
						startTab: startTab
						,callback: callback
						,scope: scope
						,sourceEl: sourceEl
					}));
				}
				// No arguments
				else {
					return new Params;
				}
			}

			return function(args) {
				return new Eoze.GridModule.EditRecordParams(parseConfig.apply(this, args));
			};
		}()
	}

	,constructor: function(config) {
		Ext.apply(this, config);
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
	 * @return {Eoze.GridModule.EditRecordParams} this
	 */
	,setRecordId: function(id) {
		this.recordId = id;
		return this;
	}
//
//	/**
//	 * @return {Boolean}
//	 */
//	,hasRecord: function() {
//		return this.record !== undefined;
//	}

	/**
	 * @return {Ext.data.Record/undefined}
	 */
	,getRecord: function() {
		return this.record;
	}

	/**
	 * @params {Ext.data.Record} record
	 * @return {Eoze.GridModule.EditRecordParams} this
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
	 * @return {Eoze.GridModule.EditRecordParams} this
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
	 * @return {Eoze.GridModule.EditRecordParams} this
	 */
	,setSourceEl: function (sourceEl) {
		this.sourceEl = sourceEl;
		return this;
	}

});
