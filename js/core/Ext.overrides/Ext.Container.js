/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 6 sept. 2012
 */

/**
 * @class Ext.Container
 * @cfg {Boolean} [forbidHiddenLayout=false]
 * 
 * True to prevent the container from doing layout while hidden. This 
 * option was originally added to permit fixing a bug with a toolbar
 * layout being distroyed while rendered hidden.
 * 
 * It should be noted that Ext may layout in a DelayedTask, in order
 * to let a parent container finish its own layout. Under some
 * conditions, the layout's target container may have been hidden
 * before the delay of the task is passed. And rendering a toolbar
 * for which HTML elements are not visible can be catastrophic. Here,
 * that's why.
 * 
 * @see {Ext.layout.ContainerLayout#runDelayedLayout}
 */

/**
 * @class Ext.layout.ContainerLayout
 */
Ext.override(Ext.layout.ContainerLayout, {
	// <rx>
	/**
	 * @private
	 * Implements {@link Ext.Container#forbidHiddenLayout} option.
	 */
    runDelayedLayout: function() {
		var forbidHiddenLayout = false,
			o = this.container;
		while (o) {
			if (o.forbidHiddenLayout) {
				forbidHiddenLayout = true;
			}
			if (!o.isVisible() && forbidHiddenLayout) {
				return false;
			}
			o = o.ownerCt;
		}
		this.runLayout.apply(this, arguments);
	}
	// </rx>
	,onResize: function(){
        var ct = this.container,
            b;
        if(ct.collapsed){
            return;
        }
        if(b = ct.bufferResize && ct.shouldBufferLayout()){
            if(!this.resizeTask){
                // <rx>
				this.resizeTask = new Ext.util.DelayedTask(this.runDelayedLayout, this);
				// this.resizeTask = new Ext.util.DelayedTask(this.runLayout, this);
                // </rx>
				this.resizeBuffer = Ext.isNumber(b) ? b : 50;
            }
            ct.layoutPending = true;
            this.resizeTask.delay(this.resizeBuffer);
        }else{
            this.runLayout();
        }
    }
});
