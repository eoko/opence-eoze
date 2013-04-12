var x = {
	other: true

	/**
	 * Prepares this module to be used with an {@link #editModule external editing module}.
	 *
	 * This method is called during the initialization phase of the module, and removes functionalities
	 * that are specific to record editing. This allow modules that do not handle editing to provide
	 * partial configuration.
	 *
	 * @private
	 */
	,initEditModule: function() {
		if (this.editModule || this.extra.editModule) {
			Ext.apply(this, {
				buildFormsConfig: Ext.emptyFn
			});
		}
	}

	/**
	 * Get the instance of the module to be used for editing. If editing is not done by another module,
	 * then this method will return `false`. In the contrary, the method will return a promise that
	 * will resolve with the edit module's own {@link #getEditWindow} promise.
	 *
	 * This option can be set in configuration with {@link #editModule}.
	 *
	 * @params {Eoze.GridModule.EditRecordOptions} operation
	 * @return {Deft.Promise|false}
	 * @protected
	 */
	,getEditModule: function(operation) {
		var editModule = this.editModule || this.extra.editModule;

		// Using another edit module
		if (editModule) {
			if (Ext.isString(editModule)) {
				var deferred = Ext4.create('Deft.Deferred');
				Oce.getModule(editModule, function(module) {
					module.getEditWindow(operation).then({
						scope: deferred
						,update: deferred.update
						,success: deferred.resolve
						,failure: deferred.reject
					});
				});
				return deferred.getPromise();
			} else {
				return editModule.getEditWindow(operation);
			}
		}

		// Not using another edit module
		else {
			return false;
		}
	}

	,getEditWindow: function(rowId, cb, opts) { // 08/12/11 21:03 added opts

		var args = arguments,
			editModule = this.getEditModule(function(module) {
				module.getEditWindow.apply(module, args);
			}, this);

		if (editModule !== true) {
			return;
		}

		if (rowId !== null && rowId in this.editWindows) {
			if (cb) {
				cb(this.editWindows[rowId]);
			} else {
				return this.editWindows[rowId];
			}
		}

		// New window
		else {
			this.createEditWindow(operation).then({
				scope: this
				,success: function(win) {

					// Operation
					operation.setWindow(win);

					// Instance lookup
					editWindows[recordId] = win;

					win.on({
						close: function() {
							editWindows[recordId].destroy();
							delete editWindows[recordId];

							// Notify operation
							operation.notifyClosed();
						}
					});

					// Events
					this.fireEvent('aftercreatewindow', this, win, 'edit', recordId, operation);
					this.afterCreateWindow(win, 'edit', recordId, operation);
					this.afterCreateEditWindow(win, recordId, operation);

					// Promise
					deferred.resolve(win);
				}
			});
		}

		return deferred.promise;
	}
}
