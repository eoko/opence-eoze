/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
if (window.Ext4) {
	Ext4.application({
		
		requires: [
			'Eoze.lib.Eoze',
			'Eoze.modules.DataSnapshots.view.Main'
		]
		
		,launch: function() {
			Deft.Injector.configure({
				locale: 'Eoze.locale.fr.Locale'
			});
		}
	});	
}
