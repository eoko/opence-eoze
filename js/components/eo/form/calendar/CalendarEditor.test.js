/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 déc. 2011
 */
eo.Testing.addUnitTest('CalendarEditor', function() {

Ext.QuickTips.init();

var month = Ext.create({
	
	xtype: 'eo.montheditor'

	,year: 2011
	,month: 4

	,zones: [{
		name: 'A'
		,daysOn: [[23,30]]
	},{
		name: 'B'
		,daysOn: [[16,30]]
		,hidden: true
	},{
		name: 'C'
		,daysOn: [[9,25]]
	},'-',{
		name: 'H'
		,title: 'H'
	},{
		name: 'M'
		,title: 'Moyenne'
	},{
		name: 'VV'
	},{
		name: 'SS'
	}]
});

var palette = new eo.form.calendar.Palette({
	values: [{
		text: 'Basse'
		,cellCls: 'B'
		,value: 1
	}, {
		text: 'Moyenne'
		,cellCls: 'M'
		,value: 2
	}, {
		text: 'Haute'
		,cellCls: 'H'
		,value: 3
	}]
});

var editor = new eo.form.calendar.ZonesEditor({

//	from: {
//		year: 2011
//		,month: 4
//	}
	months: [
		{year: 2010, month: 12, editable: false},
		{year: 2011, month: 1},
		{year: 2011, month: 2},
		{year: 2011, month: 3},
		{year: 2011, month: 4},
	]
	
	,palette: palette
	
	,zones: [{
		name: 'A'
		,editable: false
	},{
		name: 'B'
		,editable: false
		,hidden: true
	},{
		name: 'C'
		,editable: false
	},'-',{
		name: 'M'
		,editable: true
	},{
		name: 'H'
		,editable: true
	},'-',{
		name: 'V-V'
		,editable: true
	},{
		name: 'S-S'
		,editable: true
	}]

	,value: {
		A: [
			['2011-07-02', '2011-09-04'],
			['2011-10-22', '2011-11-02'],
			['2011-12-17', '2012-01-02']
		]
	}

});

var jsonPanel = new eo.JsonPanel({
	decode: false
	,flex: 1
});

var win = new Ext.Window({
	
	width: 1064
	,height: 700
	
	,layout: {type: 'vbox', align: 'stretch'}
	
	,items: [{
		xtype: 'container'
		,layout: 'form'
		,padding: 5
		,autoScroll: true
		,items: [editor]
		,flex: 1
	}, jsonPanel]
	
	,bodyStyle: 'background-color: white;'
	
	,tbar: [{
		text: 'Zone A'
		,enableToggle: true
		,pressed: true
		,handler: function(b) {
			editor.zones.A.setVisible(b.pressed);
		}
	},{
		text: 'Zone B'
		,enableToggle: true
		,pressed: false
		,handler: function(b) {
			editor.zones.B.setVisible(b.pressed);
		}
	},{
		text: 'Zone C'
		,enableToggle: true
		,pressed: true
		,handler: function(b) {
			editor.zones.C.setVisible(b.pressed);
		}
	},{
		text: 'Set 2012'
		,handler: function() {
			editor.setFrom(2012, 1, 6);
		}
	},'-',palette]
});

win.show();

editor.on('change', function() {
	jsonPanel.setValue(editor.getValue());
	win.setTitle(editor.isDirty() ? 'Dirty' : 'Clean');
});

});