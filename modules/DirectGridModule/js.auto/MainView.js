/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 8 nov. 2011
 */
Oce.deps.wait("eo.DirectModule.MainView", function() {
	
Ext.ns('eo.DirectGridModule');

var NS = eo.DirectGridModule,
	sp = eo.DirectModule.MainView,
	spp = sp.prototype;

eo.DirectGridModule.MainView = Ext.extend(sp, {
	
	initComponent: function() {
		debugger
		spp.initComponent.call(this);
	}
});
}); // deps
