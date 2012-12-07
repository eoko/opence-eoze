/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
eo.Testing.addUnitTest('GridModule.AlertWindow', Ext.extend(Object, {
	
	constructor: function() {

		var base = new Ext.Window({
			width: 500
			,height: 500
			,title: 'Base Window'
		});

		var win = new Oce.Modules.GridModule.AlertWindow({
			title: 'hop'
			,modalTo: base
			,okHandler: function() {
				alert('ok!');
			}
			,message: 'Salut'
		});

		win.show();
		base.show();
	}
	
	,currentStep: 0
	
	,next: function() {
		this.steps[this.currentStep++].call(this, arguments);
	}
	
	,steps: [
		function() {
			alert(1);	
		}
		,function() {
			alert(2);
		}
	]
}));