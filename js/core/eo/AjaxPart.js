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

(function() {
	return; // disabled

	var makeRequest = Ext.lib.Ajax.makeRequest;

//	Ext.data.HttpProxy.

	/**
	 *
	 * @since 2013-12-19 17:57
	 */
	Ext.apply(Ext.lib.Ajax, {
		makeRequest: function(method, uri, callback, postData, options) {
			var args = arguments;
			if (options.useRanges || options.request && options.request.arg.useRanges) {
				makeRequest.call(this, 'HEAD', uri, {
					scope: this
					,failure: function() {
						// TODO
						debugger
					}
					,success: function(response) {
						var me = this,
							maxTime = 25000,
							chunkSize = 200000,
							eTag = response.getResponseHeader('ETag');

						chunkSize = 999999999;
						function next(callback) {
							var from = cursor,
								start = + new Date;
							cursor += chunkSize;
							me.initHeader('Range', 'bytes=' + from + '-' + (Math.min(length, cursor) - 1));
							console.log('MPXHR Reading bytes=' + from + '-' + (Math.min(length, cursor) - 1));
							return makeRequest.call(me, method, uri, {
								success: function(response) {
									if (eTag === response.getResponseHeader('ETag')) {
										callback(response.responseText);
									} else {
										makeRequest.apply(this, args);
									}
								}
								,failure: function(response) {
									// 206 Partial content
									if (response.status === 206) {
										var time = new Date - start;
										if (eTag === response.getResponseHeader('ETag')) {
											content.push(response.responseText);
											if (cursor < length - 1) {
												chunkSize += Math.floor(chunkSize * (maxTime - time) / maxTime);
												next(callback);
											} else {
												callback(content.join(''));
											}
										} else {
											makeRequest.apply(this, args);
										}
									} else {
										// TODO (real failure)
										debugger
									}
								}
							}, postData, options);
						}

						if (eTag != null && response.getResponseHeader('Accept-Ranges') === 'bytes') {
							var length = response.getResponseHeader('Content-Length'),
								cursor = 0,
								content = [];
							return next(function(responseText) {
								var conn = {
									status: 200
									,responseText: responseText
								};
//								me.createResponseObject({
//									options: options
//									,conn: conn
//									,status: me.getHttpStatus(conn, false, false)
//									,getResponseHeader: Ext.bind(response.getResponseHeader, response)
//									,getAllResponseHeaders: Ext.bind(response.getAllResponseHeaders, response)
//								}, false, false);

								me.handleTransactionResponse({
									options: options
									,conn: conn
									,status: me.getHttpStatus(conn, false, false)
									,getResponseHeader: Ext.bind(response.getResponseHeader, response)
									,getAllResponseHeaders: Ext.bind(response.getAllResponseHeaders, response)
								}, callback, false, false);
							});
						} else {
							return makeRequest.apply(this, args);
						}
					}
				}, postData, options);
			} else {
				return makeRequest.apply(this, arguments);
			}
		}
	});
}());
