var x = {
	local: true

	/**
	 * @version 2011-12-08 21:03 Added opts
	 * @version 2013-03-19 14:26 Removed opts
	 * @version 2013-03-19 16:20 Removed callback
	 *
	 * @params {Eoze.GridModule.EditRecordOptions} operation
	 * @return {Deft.Promise} Promise of a {@link Ext.Window}.
	 *
	 * @protected
	 */
	,getEditWindow: function(operation) {

		var editModulePromise = this.getEditModule(operation);

		if (editModulePromise) {
			return editModulePromise;
		}

		var deferred = Ext4.create('Deft.Deferred'),
			recordId = operation.getRecordId(),
			editWindows = this.editWindows;

		// Test for already existing window
		var existingWindow = recordId && editWindows[recordId];

		// Already opened window
		if (existingWindow) {
			operation.setWindow(existingWindow, true);
			deferred.resolve(existingWindow);
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
