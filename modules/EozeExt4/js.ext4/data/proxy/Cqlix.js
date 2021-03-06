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
 * Proxy for reading for Cqlix REST controllers.
 *
 * Comes preconfigured to work out of the box with Eoze server's EozeExt4\Rest\Cqlix executors response
 * format (i.e. JSON, root node, meta properties).
 *
 * @since 2013-04-19 11:29
 */
Ext4.define('Eoze.data.proxy.Cqlix', {
	extend: 'Ext.data.proxy.Ajax'

	,alias: 'proxy.cqlix'

	,mixins: {
		httpCache: 'Eoze.data.proxy.mixin.AjaxHttpCache'
	}

	,constructor: function(config) {

		config = config || {};

		var url = config.url || 'api?';

		config.url = url + 'controller=' + config.controller;

		config.actionMethods = Ext.apply({
			create: 'POST'
			,read: 'GET'
			,update: 'PUT'
			,destroy: 'DELETE'
		});

		if (!config.reader) {
			config.reader = {
				type: 'json'
				,root: 'data'
			};
		}

		if (!config.writer) {
			config.writer = {
				type: 'json'
				,root: 'data'
			};
		}

		this.callParent([config]);

		this.initHttpCache(config);
	}

//	/**
//	 * @inheritdoc
//	 */
//	,buildUrl: function(request) {
//		var url = this.callParent(arguments),
//			operation = request.operation,
////			action = operation.action,
//			records = operation.records || [],
//			record = records[0],
//			id = record ? record.getId() : operation.id;
//
//		if (!Ext.isEmpty(id)) {
//			if (!/\/$/.test(url)) {
//				url += '/';
//			}
//			url += id;
//		}
//
//		request.url = url;
//
//		return url;
//	}

});
