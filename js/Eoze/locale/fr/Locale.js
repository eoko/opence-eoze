/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.locale.fr.Locale', {
	extend: 'Eoze.i18n.Locale'
	
	,requires: [
		'Eoze.locale.fr.Ext.util.Format'
	]
	
	,code: 'fr-FR'
	
	,isPlural: function(count) {
		return count <= 1;
	}

	,dictionary: {
		date: {
			one: "Date"
			,other: "Dates"
		}
		,cancel: "Annuler"
		,'delete': 'Supprimer'
		,description: "Description"
		,firstName: "Prénom"
		,id: 'Identifiant'
		,label: "Libellé"
		,lastName: "Nom"
		,name: "Nom"
		,ok: "Ok"
		,save: 'Enregistrer'
	}
});