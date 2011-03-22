Oce.deps.wait('Oce.GridModule', function() {

	//Oce.ContentKit = Ext.extend(Ext.Observable, {
	//
	//	component: null
	//
	//	,winConfig: {
	//
	//	}
	//
	//	,tabConfig: {
	//
	//	}
	//
	//	,toolbar: null
	//
	//});

	Oce.GridModule.ContentKit = Ext.extend(Ext.util.Observable, {

		component: null

		,constructor: function(config) {
			
			this.addEvents({
				formpanelcreate: true
			});
			
			Oce.GridModule.ContentKit.superclass.constructor.call(this, config);
			Ext.apply(this, config);
		}

		,createWindow: function() {
			var win = new Oce.FormWindow(Ext.apply({
				pkName: this.pkName
				,kit: this
				,formPanel: this.content
			}, this.winConfig));

			this.fireEvent("formpanelcreate", win.formPanel);

			return win;
		}

		,getWin: function() {
			if (this.component !== null) {
				if (this.component instanceof Ext.Window) return this.component;
				else throw new Error("Content already rendered in panel")
			} else {
				return this.component = this.createWindow();
			}
		}

	});

});
