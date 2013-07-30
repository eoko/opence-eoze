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
 * A proxy configured with Eoze GridModule API.
 *
 * @since 2013-02-25 21:03
 */
Ext4.define('Eoze.data.proxy.GridModule', {
	extend: 'Ext.data.proxy.Ajax'

	,alias: 'proxy.gridmodule'

	,constructor: function(config) {

		config = config || {};

		var url = config.url || 'api?';

		url += 'controller=' + config.controller;

		config.api = Ext.apply({
			read: url + '&action=load'
			,update: url + '&action=mod'
		}, config.api);

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
	}

});
