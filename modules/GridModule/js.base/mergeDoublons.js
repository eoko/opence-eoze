Oce.deps.wait('Oce.GridModule', function() {

	Ext.apply(Oce.GridModule.prototype, {

		mergeDoublonsWin: null

		,initActions: Ext.Function.createSequence(Oce.GridModule.prototype.initActions, function() {

			var xx = this.extra.mergeDoublons;

			if (xx) {
				this.actions.mergeDoublons = {
					xtype: 'oce.rbbutton'
					,text: xx.rbButtonText
					,handler: this.mergeDoublons.createDelegate(this)
					,iconCls: xx.iconClass
				}
			}
		})

		,initPlugins: Ext.Function.createSequence(Oce.GridModule.prototype.initPlugins, function() {
			if (this.extra.mergeDoublons) {
				Ext.applyIf(this.extra.mergeDoublons, {
					itemName: 'enregistrement'
					,rbButtonText: 'Fusionner'
					,iconClass: 'b_ico_coherence'
				});
			}
		})

		,mergeDoublons: function() {
			// itemName => agences

			var itemName = this.extra.mergeDoublons.itemName,
				itemNamePlural = this.extra.mergeDoublons.itemNamePlural
				;

			var ucItemName = itemName.substr(0,1).toUpperCase() + itemName.substr(1,itemName.length);
			if (!itemNamePlural) itemNamePlural = itemName + 's';

			if (this.mergeDoublonsWin) {
				this.mergeDoublonsWin.show();
				return;
			}

			var me = this,
				win;

			var combo1 = new Oce.form.ForeignComboBox({
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
						url: 'api'
						,params: {
							controller: me.controller
							,action: 'merge_doublons'
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
						,failure: Ext.Function.createSequence(Oce.Ajax.handleFormError, function() {
							started = false;
						})
					})
				}
			}

			win = this.mergeDoublonsWin = new Oce.FormWindow({
				title: 'Fusionner des ' + itemNamePlural // i18n
				,formPanel: {
					xtype: 'oce.form'
					,items: [combo1, combo2]
				}
				,buttons: [
					{text: 'Exécuter', handler: commitHandler} // i18n
					,{text: 'Annuler', handler: function() {win.close()}} // i18n
				]
				,listeners: {
					destroy: function() {
						me.mergeDoublonsWin = null;
					}
				}
			});

			win.show();
		}
	});

	Oce.deps.reg('Oce.GridModule.mergeDoublons');

}); // <- deps
