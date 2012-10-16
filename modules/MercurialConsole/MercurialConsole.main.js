var o = {
	
	doConstruct: function() {
	}
	
	,moduleActions: ["open"]
	
	,open: function(destination) {
		if (!this.win) this.win = this.createWindow();
		this.win.show();
		this.afterOpen();
	}
	
	,createWindow: function() {
		return new eo.Window({
			title: "Mercurial"
			,width: 200
			,height: 150
			,bodyStyle: "padding: 10px"
			,layout: {
				type: "vbox"
				,align: "stretch"
			}
			,items: [
				this.createOutput({
					flex: 1
					,background: "transparent"
				})
//				,{xtype: "box", height: 10}
//				,this.createInput()
			]
			,listeners: {
				scope: this
				,close: function() {
					delete this.win;
				}
			}
		});
	}
	
	// private
	,createOutput: function(config) {
		
		var out = this.outPanel = new Ext.Container(config),
			me = this;
		
		Oce.Ajax.request({
			params: {
				controller: this.config.controller
				,action: "id"
			}
			,onSuccess: function(o) {
				if (o.output) {
					me.out(o.output);
				}
			}
		});
		
		return out;
	}
	
	// out
	,out: function(out) {
		this.outPanel.update(out);
	}
	
	,createInput: function() {
		return this.input = new Ext.form.TextField();
	}

};
	
MODULE.override(o);
