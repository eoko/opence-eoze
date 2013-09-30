/**
 * Copyright (C) 2012 Eoko
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
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 *
 * @since 2012-11-28 17:35
 */
Ext4.define('Eoze.Ext.data.association.HasOne', {
	override: 'Ext.data.association.HasOne'

	,constructor: function() {
		this.callParent(arguments);

		var me             = this,
			ownerProto     = me.ownerModel.prototype,
			associatedName = me.associatedName,
			getterName     = me.getterName || this.makeGetterName(associatedName),
			setterName     = me.setterName || this.makeSetterName(associatedName);

		Ext.applyIf(me, {
			name        : associatedName,
			foreignKey  : associatedName.toLowerCase() + "_id",
			instanceName: associatedName + 'HasOneInstance',
			associationKey: associatedName.toLowerCase()
		});

		ownerProto[getterName] = me.createGetter();
		ownerProto[setterName] = me.createSetter();
	}

	,makeGetterName: function(associatedName) {
		var matches = /\.([^.]+)$/.exec(associatedName);
		if (matches) {
			associatedName = matches[1];
		}
		return 'get' + associatedName;
	}

	,makeSetterName: function(associatedName) {
		var matches = /\.([^.]+)$/.exec(associatedName);
		if (matches) {
			associatedName = matches[1];
		}
		return 'set' + associatedName;
	}
});
