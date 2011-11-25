Ext.ns('Oce.GridModule')

Oce.deps.wait('Oce.form.ForeignComboBox', function() {

//	Oce.YearCombo = Ext.extend(Oce.form.ForeignComboBox, {
//
//		label: 'Année'
//
//		,constructor: function(config) {
//			
//			this.addEvents({
//				'yearchanged': true
//			});
//
//			Ext.applyIf(config, {
//				fieldLabel: this.label
//				,controller: 'years'
//				,clearable: false
//	//			,listeners: {
//	//				select: function(combo, record, index) {
//	//					this.onValueChange(record.data.id);
//	//				}.createDelegate(this)
//	//			}
//
//				,forceSelection: true
//			});
//
//			this.on('select', function(combo, record, index) {
//				this.onValueChange(record.data.id);
//			}.createDelegate(this))
//
//			this.addEvents('firstload');
//
//			this.firstLoadListener = function() {
//				this.store.un('load', this.firstLoadListener);
//				this.firstLoadListener = true;
//				this.fireEvent('firstload');
//			}.createDelegate(this);
//
//			Oce.YearCombo.superclass.constructor.call(this, config);
//
//			this.whenStoreLoaded(this.firstLoadListener);
//
//			this.setValue(config.year);
//		}
//
//		,waitFirstLoad: function(callback) {
//			if (this.firstLoadListener === true) {
//				callback();
//			} else {
//				this.on('firstload', callback);
//			}
//		}
//
//		,onValueChange: function(year) {
//			this.fireEvent('yearchanged', year);
//		}
//
//		,setValue: function(v) {
//			if (this.store != null) {
//				Oce.YearCombo.superclass.setValue.call(this, v);
//			} else {
//				this.value = v;
//			}
//		}
//	})

	Oce.YearCombo = Ext.extend(Ext.form.DateField, {
		onValueChange: function(year) {}
		,waitFirstLoad: function(callback) {
			callback();
		}
	});

	Ext.reg('oce.yearcombo', Oce.YearCombo);

	Oce.GridModuleYearCombo = Ext.extend(Oce.YearCombo, {

//		constructor: function(config) {
//
//			Oce.GridModuleYearCombo.superclass.constructor.call(this, config);
//
//			if (false === 'module' in config) throw new 'Missing required config param: module';
//
//			this.module = config.module;
//
//			var superOnRead = this.module.store.proxy.onRead;
//
//			this.module.store.proxy.onRead = function(action, trans, result, res) {
//
//				superOnRead.apply(this.module.store.proxy, arguments);
//
//				var o = Ext.util.JSON.decode(result.responseText);
//				this.setValue(o.year);
//			}.createDelegate(this)
//
//			this.on('yearchanged', function(year) {
//				this.module.store.setBaseParam('year', year);
//				this.module.store.reload();
//			}.createDelegate(this))
//		}

	});

	Ext.reg('oce.gm.yearcombo', Oce.GridModuleYearCombo);

	Oce.GlobalYearManager = Ext.extend(Oce.YearCombo, {

		constructor: function(config) {
			Oce.GlobalYearManager.superclass.constructor.call(this, config);
		}
	})

	Oce.deps.reg('GridModuleYearCombo');
	Oce.deps.reg('Oce.GridModule.YearCombo');
})