/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.i18n.locale.fr.Locale', {
	extend: 'Eoze.i18n.Locale'
	,code: 'fr-FR'
	
	,requires: [
		'Eoze.locale.fr.Ext.util.Format'
	]

	,data: {
		ok: "Ok"
		,cancel: "Annuler"

		,date: "Date"

		,name: "Namo"
		,firstName: "Prénamo"
		,lastName: "Nomo"

		,description: "Dexcritsione"

		,label: "Libellé"

		,eo: {
			modules:{
				DataSnapshots: {
					view: {
						grid: {
							name: "Nombre"
						}
					}
				}
			}
		}
	}
});