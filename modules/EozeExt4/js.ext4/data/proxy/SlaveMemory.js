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
 * @since 2013-05-30 10:56
 */
Ext4.define('Eoze.data.proxy.SlaveMemory', {
	extend: 'Ext.data.proxy.Memory'

	,alias: 'proxy.slavememory'

	,requires: [
		'Eoze.Ext.data.AbstractStore.IsLoaded'
	]

	/**
	 * @inheritdoc
	 */
	,constructor: function() {
		this.callParent(arguments);

		// init
		this.waitingReadOperations = [];

		// create store
		var store = Ext4.data.StoreManager.lookup(this.masterStore);
		this.masterStore = store;

		// initial data
		if (store.isLoaded()) {
			this.data = store.getRange();
		}

		// install load event handler
		this.mon(store, {
			scope: this
			,load: function(store, records, success) {
				if (success) {
					this.data = records;
					this.flushReadOperations(true);
				} else {
					this.flushReadOperations(false);
				}
			}
		});
	}

	,setModel: function(model) {
		this.masterStore.proxy.setModel(model);
		this.callParent(arguments);
	}

	/**
	 * @inheritdoc
	 */
	,read: function(operation, callback, scope) {
		var store = this.masterStore,
			operationHash = this.hashOperation(operation),
			currentHash = this.currentOperationHash,
			data = this.data;
		if (data) {
			return this.callParent(arguments);
		} else {
			this.waitingReadOperations.push(Array.prototype.slice.call(arguments));
			// start loading if needed
			if (!store.isLoading()) {
				store.load(operation);
//				store.load(Ext.apply(operation, {
//					callback: function(records, operation, success) {
//						debugger
//					}
//				}));
			}
		}
	}

	,hashOperation: function(operation) {
		if (operation.hash) {
			return operation.hash;
		}

		var options = ['action', 'filters', 'groupers', 'limit', 'params', 'sorters', 'start'],
			buffer = [];

		options.forEach(function(name) {
			buffer.push(operation[name]);
		}, this);

		return operation.hash = buffer.join(';');
	}

	/**
	 * Flushes pending read operations.
	 *
	 * @param {Boolean} success
	 * @private
	 */
	,flushReadOperations: function(success) {
		if (success) {
			this.waitingReadOperations.forEach(function(args) {
				var operation = args[0];
				operation.setCompleted();
				operation.setSuccessful();
				this.read.apply(this, args);
			}, this);
		} else {
			this.waitingReadOperations.forEach(function(args) {
				var operation = args[0],
					callback = args[1],
					scope = args[2],
					resultSet = this.getReader().read();

				resultSet.success = false;

				operation.setCompleted();

				this.fireEvent('exception', this, null, operation);

				Ext4.callback(callback, scope || this, [operation]);
			}, this);
		}

		this.waitingReadOperations.clear();
	}
});
