/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 févr. 2012
 */
Ext.ns('eo.form');

/**
 * @todo Buggy: doesn't redo container's layout...
 *
 * @xtype growingtextarea
 */
eo.form.GrowingTextArea = Ext.extend(Ext.form.TextArea, {

	grow: true
	,height: 29
	,growMin: 29

	,enableKeyEvents: true

	,initComponent: function() {
		eo.form.GrowingTextArea.superclass.initComponent.call(this);
		this.on('autosize', this.onAutoSize, this);
	}
			
	,setValue: function() {
		this.typing = false;
		eo.form.GrowingTextArea.superclass.setValue.apply(this, arguments);
		delete this.typing;
	}
	
	// private
	,onAutoSize: function(field, h, typing) {
		var last = field.getHeight(),
			// mainCt is used in ContactFields
			ct = this.mainCt || this.ownerCt,
			cte = ct.el,
			el = this.el,
			fel = field.el;

		if (!typing) {
			field.setHeight(h);
			ct.setHeight(h);
			this.setHeight(h);
			this.doParentLayout();
			return;
		}

		if (last < h) {
			ct.setHeight(h);
			this.doLayout();
		}
		cte.scale(cte.getWidth(), h);
		el.scale(el.getWidth, h);
		fel.scale(fel.getWidth(), h, {
			scope: this
			,callback: function() {
				ct.setHeight(h);
				this.setHeight(h);
				this.doParentLayout();
			}
		});
	}
	
	// private
	,doParentLayout: function() {
		var p = this.findParentBy(function(p) {return !p.ownerCt});
		if (p) {
			p.doLayout();
		}
	}
});

Ext.reg('growingtextarea', 'eo.form.GrowingTextArea');