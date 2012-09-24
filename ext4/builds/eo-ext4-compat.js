/**
 * @author http://www.sencha.com/forum/showthread.php?189716-Sandbox-Problems-with-border-layout&p=859036#post859036
 * @since 24 sept. 2012
 * 
 * Fixes CSS sandbox (in Ext4.1)
 */
if (Ext4 && Ext4.isSandboxed){//Check sandbox
    Ext4.onReady(function(){
        if (Ext4.isBorderBox){
            Ext4.get(document.getElementsByTagName('body')[0].parentNode).removeCls(Ext4.baseCSSPrefix +"border-box")
            Ext4.override(Ext4.container.AbstractContainer, {
                beforeRender: function(){
                    this.addCls(Ext4.baseCSSPrefix +"border-box");
                    return this.callOverridden(arguments);
                }
            });
        }
    });
}

/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 sept. 2012
 */
if (window.Ext4) {
	Ext.Msg = Ext.MessageBox = Ext4.MessageBox;
}
