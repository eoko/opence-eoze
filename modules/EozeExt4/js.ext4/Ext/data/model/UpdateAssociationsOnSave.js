(function(Ext) {
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
 * Overrides the {@link #copyFrom} method to also update association stores from the
 * source models. The data are loaded in the existing store, so the existing store
 * will **not be replaced**.
 *
 * @since 2013-07-25 05:49
 */
Ext.define('Eoze.Ext.data.model.UpdateAssociationsOnSave', {
	override: 'Ext.data.Model'

	,requires: [
		// Must be loaded before, since it will replace copyFrom
		'Eoze.data.type.HasOne'
	]

	/**
	 * This method will also load records from association stores into existing associated
	 * stores. The existing store will **not be replaced** by the one of the source model,
	 * except if the associated store has not yet been created in the target model.
	 *
	 * @param {Ext.data.Model} sourceModel
	 */
	,copyFrom: function(sourceModel) {
		this.callParent(arguments);

		this.associations.each(function(association) {
			var storeName = association.storeName;
			if (storeName) {
				var currentStore = this[storeName],
					newStore = sourceModel[storeName];
				if (newStore) {
					if (currentStore) {
						currentStore.loadData(newStore.getRange());
					} else {
						this[storeName] = newStore;
					}
				}
			}
		}, this);
	}

});
})(window.Ext4 || Ext.getVersion && Ext);
