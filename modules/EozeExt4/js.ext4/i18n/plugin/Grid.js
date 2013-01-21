/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.i18n.plugin.Grid', {
	
	extend: 'Ext4.AbstractPlugin'
	
	,inject: ['locale']
	
	,alias: ['plugin.i18n.Grid']
	
	,init: function(g) {
		this.className = Ext4.getClassName(g);
		Ext.each(g.query('gridcolumn'), this.localizeGridColumn, this);
	}
	
	/**
	 * @private
	 */
	,localizeGridColumn: function(column) {
		var text = column.text,
			key = column.dataIndex;
		if (!text || text === '&#160;' || text === '&nbsp;' || text instanceof Eoze.i18n.Entry) {
			text = this.locale.translate(key, {
				tags: ['model']
			});
			if (text)  {
				column.setText(text);
			}
		}
	}
	
});
