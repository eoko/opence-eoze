/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 16 août 2012
 */

(function() {
	
var clear = Ext.form.MessageTargets.qtip.clear;

// Ensures that the target element still exists before clearing.
// The target element may indeed have already been destroyed, if the clear method
// is called in a delayed task (which is the case in the code of Ext itself).
Ext.form.MessageTargets.qtip.clear = function(field) {
	if (field.el) {
		clear.apply(this, arguments);
	}
};
	
})();
