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

describe('Ext.ux.GroupTabPanel', function() {

	var win;

	beforeEach(function() {
		Ext4.syncRequire('Ext.ux.GroupTabPanel');
	});

	it('should work', function () {
		var Ext = Ext4;

		win = Ext.create('Ext.Window', {
			width: 900
			,height: 400
			,layout: 'fit'
			,items: [{
				xtype: 'grouptabpanel',
				cls: 'eo-clean-theme',
				itemId: 'dashboardPanel',
				activeGroup: 0,
				items: [{
					mainItem: 1,
					items: [{
						title: 'Tickets',
						iconCls: 'x-icon-tickets',
						tabTip: 'Tickets tabtip',
						//border: false,
						margin: '10',
						height: null
					}, {
						title: 'Dashboard',
						tabTip: 'Dashboard tabtip',
						border: false,
						items: [{
							flex: 1,
							items: [{
								title: 'Portlet 1',
								html: '<div class="portlet-content"></div>'
							}, {

								title: 'Stock Portlet',
								items: {
								}
							}, {
								title: 'Portlet 2',
								html: '<div class="portlet-content"></div>'
							}]
						}]
					}, {
						title: 'Subscriptions',
						hidden: true,
						iconCls: 'x-icon-subscriptions',
						tabTip: 'Subscriptions tabtip',
						style: 'padding: 10px;',
						border: false,
						layout: 'fit',
						items: [{
							xtype: 'tabpanel',
							activeTab: 1,
							items: [{
								title: 'Nested Tabs',
								html: 'Lorem'
							}]
						}]
					}, {
						title: 'Users',
						itemId: 'userPanel',
						iconCls: 'x-icon-users',
						tabTip: 'Users tabtip',
						style: 'padding: 10px;',
						html: 'Lorem'
					}]
				}, {
					expanded: false,
					itemId: 'cpg',
					items: [{
						title: 'Configuration',
						iconCls: 'x-icon-configuration',
						tabTip: 'Configuration tabtip',
						style: 'padding: 10px;',
						html: 'Lorem'
					}, {
						title: 'Email Templates',
						iconCls: 'x-icon-templates',
						tabTip: 'Templates tabtip',
						style: 'padding: 10px;',
						border: false,
						items: {
							xtype: 'form',
							// since we are not using the default 'panel' xtype, we must specify it
							id: 'form-panel',
							labelWidth: 75,
							title: 'Form Layout',
							bodyStyle: 'padding:15px',
							labelPad: 20,
							defaults: {
								width: 230,
								labelSeparator: '',
								msgTarget: 'side'
							},
							defaultType: 'textfield',
							items: [{
								fieldLabel: 'First Name',
								name: 'first',
								allowBlank: false
							}, {
								fieldLabel: 'Last Name',
								name: 'last'
							}, {
								fieldLabel: 'Company',
								name: 'company'
							}, {
								fieldLabel: 'Email',
								name: 'email',
								vtype: 'email'
							}],
							buttons: [{
								text: 'Save'
							}, {
								text: 'Cancel'
							}]
						}
					}]
				}, {
					expanded: false,
					items: {
						title: 'Single item in third',
						bodyPadding: 10,
						html: '<h1>The third tab group only has a single entry.<br>This is to test the tab being tagged with both "first" and "last" classes to ensure rounded corners are applied top and bottom</h1>',
						border: false
					}
				}]
			}]
		});

		win.show();

		expect(win.el).toBeDefined();
		expect(win.el.dom).toBeDefined();

		win.close();
		win.destroy();
	});
});
