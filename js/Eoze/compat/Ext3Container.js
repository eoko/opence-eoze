/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 17 oct. 2012
 */
Ext4.define('Eoze.compat.Ext3Container', {
	extend: 'Ext.container.Container'

	,alias: ['widget.ext3container']

	,afterRender: function() {

		this.on('resize', function() {
			var c = this.child;
			if (c) {
				c.setSize(this.getSize());
			}
		}, this);

		if (this.child) {
			this.child = this.createComponent();
			this.child.render(this.el);
		}

		this.callParent(arguments);
	}

	,setChild: function(component) {
		this.child = component;
		if (this.rendered) {
			component.setSize(this.getSize());
			component.render(this.el);
		}
	}

	,createComponent: function() {
		var child = this.child;
		if (child) {
			if (child.render) {
				return child;
			} else {
				return Ext.widget(child);
			}
		}
	}
});