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
 * @since 2013-06-18 15:49
 */
Ext4.define('Eoze.util.LzString', {
	singleton: true

	,requires: [
		'Ext.ux.WebWorker',
		'Deft.promise.Deferred'
	]

	,waitingPromises: {}
	,lastId: 0

	/**
	 * @return {Ext.ux.WebWorker}
	 * @private
	 */
	,getWorker: function() {
		var worker = this.worker;
		if (worker) {
			return worker;
		} else {
			return this.worker = this.createWorker();
		}
	}

	/**
	 * @return {Ext.ux.WebWorker}
	 * @private
	 */
	,createWorker: function() {
		return Ext4.create('Ext.ux.WebWorker', {
			file: 'api?controller=LzString.Worker'
			,listeners: {
				scope: this
				,message: this.onMessage
			}
		});
	}

	/**
	 * @return {Integer}
	 * @private
	 */
	,nextId: function() {
		return ++this.lastId;
	}

	/**
	 * @param {String} string
	 * @return {Deft.Promise}
	 */
	,compress: function(string) {
		var id = this.nextId(),
			deferred = Ext4.create('Deft.Deferred');

		this.waitingPromises[id] = deferred;

		this.getWorker().send('compress', {
			id: id
			,data: string
		});

		return deferred.getPromise();
	}

	/**
	 * @param {String} string
	 * @return {Deft.Promise}
	 */
	,decompress: function(string) {
		var id = this.nextId(),
			deferred = Ext4.create('Deft.Deferred');

		this.waitingPromises[id] = deferred;

		this.getWorker().send('decompress', {
			id: id
			,data: string
		});

		return deferred.getPromise();
	}

	/**
	 * @param {Ext.ux.WebWorker} worker
	 * @param {Object} msg
	 * @private
	 */
	,onMessage: function(worker, msg) {
		var id = msg.id,
			data = msg.data,
			waiters = this.waitingPromises,
			deferred = waiters[id];

		if (deferred) {
			delete this.waitingPromises[id];
			deferred.resolve(data);
		}
	}
});
