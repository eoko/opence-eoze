/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext4.define('Eoze.modules.DataSnapshots.model.DataSnapshot', {
	
	extend: 'Ext4.data.Model'
	
	,fields: [
		{name: 'id', type: 'int'}
		,{name: 'name', type: 'string'}
		,{name: 'description', type: 'string'}
		,{name: 'date', type: 'date'}
		,{name: 'version', type: 'string'}
		,{name: 'revision', type: 'string'}
	]
	
	,validations: [
		{type: 'presence', name: 'id'}
		,{type: 'presence', name: 'name'}
	]
});