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
 * A memory proxy that binds to a master store for its data source.
 *
 * @todo This class is just not finished, and not working.
 *
 * @since 2013-05-30 10:56
 */
Ext4.define('Eoze.data.proxy.SlaveMemory', {
	extend: 'Ext.data.proxy.Memory'

	,alias: 'proxy.slavememory'

	,requires: [
		'Ext.data.AbstractStore'
//		'Eoze.Ext.data.AbstractStore.IsLoaded'
	]

	,isSynchronous: false

	/**
	 * @inheritdoc
	 */
	,constructor: function() {
		this.callParent(arguments);

		// init
		this.waitingReadOperations = [];
		this.loading = false;

		// create proxy
		if (this.proxy) {
			this.setProxy(this.proxy);
		}

//		// create store
//		var store = Ext4.data.StoreManager.lookup(this.masterStore);
//		this.masterStore = store;
//
//		// initial data
//		if (store.isLoaded()) {
//			this.data = store.getRange();
//		}
//
//		// install load event handler
//		this.mon(store, {
//			scope: this
//			,load: function(store, records, success) {
//				if (success) {
//					this.data = records;
//					this.flushReadOperations(true);
//				} else {
//					this.flushReadOperations(false);
//				}
//			}
//		});
	}

	,setProxy: function(proxy) {

		var previousProxy = this.proxy;

		// unbind previous proxy
		if (previousProxy && previousProxy.isProxy) {
			if (previousProxy === proxy) {
				return;
			}
		}

		// uses AbstractStore implementation to auto create proxy
		var proxy = Ext4.data.AbstractStore.prototype.setProxy.call(this, proxy);

		// relay proxy events
		proxy.relayEvents(this.proxy, ['metachange']);

		this.isSynchronous = proxy.isSynchronous;

		return proxy;
	}

	,getProxy: function() {
		return this.proxy;
	}

	,setModel: function(model) {
		var proxy = this.proxy;

		if (proxy) {
			proxy.setModel(model);
		} else {
			this.setProxy(Ext4.ModelManager.getModel(model).getProxy());
		}

		this.callParent(arguments);
	}

	/**
	 * @inheritdoc
	 */
	,read: function(operation, callback, scope) {
		var proxy = this.getProxy(),
			operationHash = this.hashOperation(operation),
			currentHash = this.currentOperationHash,
			data = this.data;
		if (data && (!currentHash || currenthash === operationhash)) {
			this._read.apply(this, arguments);
		} else {
			this.currentHash = operationHash;
			this.waitingReadOperations.push(Array.prototype.slice.call(arguments));
			// start loading if needed
			if (!this.loading) {
				this.loading = true;

				var loadingOperation = Ext4.create('Ext.data.Operation', {
					action: 'read'
					,limit: false
					,start: 0
					,params: operation.params
				});

				this.lastRequest = proxy.read(loadingOperation, function(operation) {
//					this.data = operation.getRecords();
					this.setData(operation.getRecords());

					var queue = this.waitingReadOperations;
					this.waitingReadOperations = [];
					this.loading = false;

					queue.forEach(function(args) {
						this.read.apply(this, args);
					}, this);
				}, this);
			}
		}

		return this.lastRequest;
	}

	// private
	,setData: function(data) {
		var me = this;
		me.data = me.reader.read(data);
	}

	// private
	,getData: function() {
		var data = this.data;
		return Ext4.create('Ext.data.ResultSet', {
			count: data.count
			,message: data.message
			,records: data.records ? data.records.slice(0) : []
			,success: data.success
			,total: data.total
			,totalRecords: data.totalRecords
		});
	}

	,_read: function(operation, callback, scope) {
		var me = this,
			resultSet = operation.resultSet = me.getData(),
			records = resultSet.records,
			sorters = operation.sorters,
			groupers = operation.groupers,
			filters = operation.filters;

		operation.setCompleted();

		// Apply filters, sorters, and start/limit options
		if (resultSet.success) {

			// Filter the resulting array of records
			if (filters && filters.length) {
				records = resultSet.records = Ext4.Array.filter(records, Ext4.util.Filter.createFilterFn(filters));
				resultSet.total = resultSet.totalRecords = records.length;
			}

			// Remotely, groupers just mean top priority sorters
			if (groupers && groupers.length) {
				Ext4.Array.insert(sorters||[], 0, groupers);
			}

			// Sort by the specified groupers and sorters
			if (sorters && sorters.length) {
				resultSet.records = Ext4.Array.sort(records, Ext4.util.Sortable.createComparator(sorters));
			}

			// Reader reads the whole passed data object.
			// If successful and we were given a start and limit, slice the result.
			if (me.enablePaging && operation.start !== undefined && operation.limit !== undefined) {

				// Attempt to read past end of memory dataset - convert to failure
				if (operation.start >= resultSet.total) {
					resultSet.success = false;
					resultSet.count = 0;
					resultSet.records = [];
				}
				// Range is valid, slice it up.
				else {
					resultSet.records = Ext4.Array.slice(resultSet.records, operation.start, operation.start + operation.limit);
					resultSet.count = resultSet.records.length;
				}
			}
		}

		if (resultSet.success) {
			operation.setSuccessful();
		} else {
			me.fireEvent('exception', me, null, operation);
		}
		Ext4.callback(callback, scope || me, [operation]);
	}

	,hashOperation: function(operation) {
		if (operation.hash) {
			return operation.hash;
		}

		var Ext = Ext4,
			getKeys = Ext.Object.getKeys;

//		var options = ['action', 'filters', 'groupers', 'limit', 'params', 'sorters', 'start'],
		var options = ['params'],
			buffer = [];

		function hashObject(o) {
			var keys = getKeys(o).sort(),
				buffer = {};
			keys.forEach(function(key) {
				buffer[key] = o[key];
			});
			return Ext.encode(buffer);
		}

		options.forEach(function(name) {
			var data = operation[name];
			if (Ext.isObject(data)) {
				data = hashObject(data);
			} else {
				data = String(data);
			}
			buffer.push(data);
		}, this);

		return operation.hash = buffer.join(';');
	}

//	/**
//	 * Flushes pending read operations.
//	 *
//	 * @param {Boolean} success
//	 * @private
//	 */
//	,flushReadOperations: function(success) {
//		if (success) {
//			this.waitingReadOperations.forEach(function(args) {
//				var operation = args[0];
//				operation.setCompleted();
//				operation.setSuccessful();
//				this.read.apply(this, args);
//			}, this);
//		} else {
//			this.waitingReadOperations.forEach(function(args) {
//				var operation = args[0],
//					callback = args[1],
//					scope = args[2],
//					resultSet = this.getReader().read();
//
//				resultSet.success = false;
//
//				operation.setCompleted();
//
//				this.fireEvent('exception', this, null, operation);
//
//				Ext4.callback(callback, scope || this, [operation]);
//			}, this);
//		}
//
//		this.waitingReadOperations.clear();
//	}

});
