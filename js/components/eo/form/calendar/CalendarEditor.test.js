/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 déc. 2011
 */
eo.Testing.addUnitTest('CalendarEditor', function() {

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
	}, {
		text: 'Moyenne'
		,cellCls: 'M'
	}, {
		text: 'Haute'
		,cellCls: 'H'
	}]
});

var editor = new eo.form.calendar.ZonesEditor({

	from: {
		year: 2011
		,month: 4
	}
	
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

var win = new Ext.Window({
	
	width: 1020
	,height: 500
	
	,layout: 'form'
	
	,padding: 5
	,autoScroll: true
	
	,items: [editor]
	
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

});