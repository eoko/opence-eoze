Oce.deps.wait('Oce.GridModule', function() {

	Ext.apply(Oce.GridModule.prototype, {

		mergeMembersWindow: null

		,initActions: Oce.GridModule.prototype.initActions.createSequence(function() {

			var xx = this.extra.mergeMembers;

			if (xx) {
				this.actions.mergeMembers = {
					xtype: 'oce.rbbutton'
					,text: xx.rbButtonText
					,handler: this.mergeMembers.createDelegate(this)
					,iconCls: xx.iconClass
				};
			}
		})

		,initPlugins: Oce.GridModule.prototype.initPlugins.createSequence(function() {
			if (this.extra.mergeMembers) {
				Ext.applyIf(this.extra.mergeMembers, {
					itemName: 'enregistrement'
					,rbButtonText: 'Fusionner'
					,iconClass: 'b_ico_coherence'
				});
			}
		})

		,mergeMembers: function() {
			// itemName => agences

			var itemName = this.extra.mergeMembers.itemName,
				itemNamePlural = this.extra.mergeMembers.itemNamePlural
				;

			var ucItemName = itemName.substr(0,1).toUpperCase() + itemName.substr(1,itemName.length);
			if (!itemNamePlural) itemNamePlural = itemName + 's';

			if (this.mergeMembersWindow) {
				this.mergeMembersWindow.show();
				return;
			}

			var me = this,
				win;

			var yearCombo = new Oce.YearCombo({
				year: Oce.mx.application.YearManager.value
				,allowBlank: false
				,name: 'year'
			})

				,combo1 = new Oce.form.ForeignComboBox({
				controller: this.controller
				,column: 'src'
				,fieldLabel: ucItemName + ' à incorporer' // i18n
				,allowBlank: false
			}), combo2 = new Oce.form.ForeignComboBox({
				controller: this.controller
				,column: 'dest'
				,fieldLabel: 'Destination' // i18n
				,allowBlank: false
			});

			var started = false;
			var commitHandler = function() {
				if (started) return;
				started = true;
				var form = win.formPanel.form;
				if (form.isValid) {
					form.submit({
						url: 'index.php'
						,params: {
							controller: me.controller
							,action: 'merge_members'
						}
						,waitTitle : 'Exécution' // i18n
						,waitMsg : 'Traitement en cours' // i18n
						,success: function(form, action) {
							win.close();
							var msg = Oce.pickFirst(action.result, ['message','messages','msg']);
							Ext.MessageBox.alert(
								'Opération terminée',
								msg || 'La migration a été réalisée avec succès.' // i18n
							);
							me.reload();
						}
						,failure: Oce.Ajax.handleFormError.createSequence(function() {
							started = false;
						})
					})
				}
			}

			win = this.mergeMembersWindow = new Oce.FormWindow({
				title: 'Fusionner des ' + itemNamePlural // i18n
				,formPanel: {
					xtype: 'oce.form'
					,items: [yearCombo, combo1, combo2]
				}
				,buttons: [
					{text: 'Exécuter', handler: commitHandler} // i18n
					,{text: 'Annuler', handler: function() {win.close()}} // i18n
				]
				,listeners: {
					destroy: function() {
						me.mergeMembersWindow = null;
					}
				}
			});

			win.show();
		}
	});

	Oce.deps.reg('Oce.GridModule.mergeMembers');

}); // <- deps
