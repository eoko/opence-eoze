/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 mai 2012
 */
Ext.ns('eo.form');

/**
 * @inheritdoc
 */
eo.form.ComboBox = Ext.extend(Ext.form.ComboBox, {
	
	
    initEvents: function() {
		eo.form.ComboBox.superclass.initEvents.apply(this, arguments);
		
		var isClosingKey = false;
		
		this.on('specialkey', function(me, e) {
			if (e.getKey() === e.ENTER) {
				if (isClosingKey) {
					isClosingKey = false;
				} else {
					/**
					* @event enterkey
					* Fires when the enterkey is pressed and is not interpreted as a navigation key
					* (that is, to select an item in the list).
					* @param {eo.form.ComboBox} this
					* @param {Ext.EventObject} e The event object.
					*/
					me.fireEvent('enterkey', me, e);
				}
			}
		});
		
		this.on('expand', function() {
			isClosingKey = false;
		});
		
		this.keyNav.enter = function(e) {
			// If the 
			var wasExpanded = this.isExpanded();
			this.onViewClick(true);
			if (wasExpanded && !this.isExpanded()) {
				isClosingKey = true;
			}
		}
	}
});

Ext.reg('combo', eo.form.ComboBox);
Oce.deps.reg('eo.form.ComboBox');