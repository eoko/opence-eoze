/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 8 nov. 2011
 */

MODULE.override({
	
	createTabConfig: function() {
		var viewClass = Ext.ns(this.config.mainViewClass);
		return new viewClass(Ext.apply(this.config, {
			module: this
			,title: this.getTitle()
			,iconCls: this.getIconCls()
			,closable: true
		}));
	}
	
});