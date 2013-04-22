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
(function(Ext) {

	/**
	 * This override adds support for a {@link #dirtychanged} event in models. It also adds the
	 * inexplicably missing {@link #isDirty} method.
	 *
	 * @since 2013-04-22 10:11
	 */
	Ext.define('Eoze.Ext.data.model.DirtyEvents', function() {

		function intercept() {
			return function() {
				var wasDirty = this.dirty,
					result = this.callParent(arguments),
					isDirty = this.dirty;

				if (wasDirty !== isDirty) {
					this.fireEvent(this.EVENT_DIRTY_CHANGED, this, isDirty);
				}

				return result;
			}
		}

		return {
			override: 'Ext.data.Model'

			/**
			 * This event is fired when the state of the {@link #dirty} property of the record
			 * changes. The event is fired even if an {@link #beginEdit edit operation} is
			 * current.
			 *
			 * @event dirtychanged
			 * @param {Ext.data.Model} model
			 * @param {Boolean} dirty
			 */
			,EVENT_DIRTY_CHANGED: 'dirtychanged'

			/**
			 * @inheritdoc
			 */
			,constructor: function() {
				this.addEvents(this.EVENT_DIRTY_CHANGED);
				this.callParent(arguments);
			}

			/**
			 * Returns `true` if the record is dirty, else returns `false`.
			 *
			 * @return {Boolean}
			 */
			,isDirty: function() {
				return this.dirty;
			}

			,set: intercept()
			,cancelEdit: intercept()
			,endEdit: intercept()
			,setDirty: intercept()
			,reject: intercept()
			,commit: intercept()

			/**
			 * Changes the dirty state for the specified field. This method will update the dirty
			 * state and the modified fields of the record accordingly.
			 *
			 * @param {String} fieldName
			 * @param {Boolean} dirty
			 */
			,setFieldDirty: function(fieldName, dirty) {

				function run(fieldName, dirty) {

					var modified = this.modified;

					// The field was not dirty and has been modified.
					if (dirty) {
						// Update dirty state:
						this.dirty = true;
						// Update modified fields:
						modified[fieldName] = this.data[fieldName];
					}
					// The field was dirty and has been modified back to its original value.
					else {

						// Update dirty state:
						this.dirty = false;

						if (modified[fieldName]) {
							// Update modified fields:
							delete modified[fieldName];

							// If there remains some modified fields, then the record is still
							// dirty.
							for (var key in modified) {
								if (modified.hasOwnProperty(key)) {
									this.dirty = true;
									break;
								}
							}
						}
					}
				}

				return function() {
					var wasDirty = this.dirty,
						result = run.apply(this, arguments),
						isDirty = this.dirty;

					if (wasDirty !== isDirty) {
						this.fireEvent(this.EVENT_DIRTY_CHANGED, this, isDirty);
					}

					return result;
				};
			}()
		};

	// 2013-04-22 This is intentional (or the function is not getting called, despite it should)
	}());

})(window.Ext4 || Ext);
