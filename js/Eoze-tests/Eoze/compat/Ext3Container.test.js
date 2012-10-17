/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 17 oct. 2012
 */
eo.Testing.addUnitTest('Ext3Container', function() {
	Ext4.create('Ext4.Window', {
		autoShow: true
		,title: "Ext4 Window"
		,width: 300
		,height: 200
		,layout: 'fit'
		,items: [Ext4.create('Eoze.compat.Ext3Container', {
			xtype: 'ext3container'
			,child: {
				xtype: 'panel'
				,title: "I'm Ext3 !"
				,html: 'hop'
			}
		})]
	});
});