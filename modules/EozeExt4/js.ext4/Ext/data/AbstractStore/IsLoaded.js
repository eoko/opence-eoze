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
 * This override adds the method {@link #isLoaded} to stores.
 *
 * @since 2013-05-30 11:26
 */
Ext4.define('Eoze.Ext.data.AbstractStore.IsLoaded', {
	override: 'Ext.data.AbstractStore'

	/**
	 * This flag is set to true after the first successful loading of the store.
	 *
	 * @property {Boolean}
	 * @private
	 */
	,hasBeenLoaded: false

	,constructor: function() {
		this.callParent(arguments);

		this.on({
			scope: this
			,single: true
			,load: function(me, records, success) {
				if (success) {
					this.hasBeenLoaded = true;
				}
			}
		});
	}

	/**
	 * Returns true if the store has successfully completed at least one loading operation.
	 *
	 * @return {Boolean}
	 */
	,isLoaded: function() {
		return this.hasBeenLoaded;
	}
//	,setProxy: function() {
//		var proxy = this.callParent(arguments);
//
//		if (proxy.attachStore) {
//			proxy.attachStore(this);
//		}
//
//		return proxy;
//	}
});
