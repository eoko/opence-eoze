// Create user extensions namespace (Ext.ux.form)
Ext.namespace('Ext.ux.form');
 
/**
  * Ext.ux.form.IconCombo Extension Class
  *
  * @author  Jozef Sakalos
  * @version 1.0
  *
  * @class Ext.ux.form.IconCombo
  * @extends Ext.form.ComboBox
  * @constructor
  * @param {Object} config Configuration options
  */
Ext.ux.form.IconCombo = function(config) {
	
	if (!config) config = {};
 
    // call parent constructor
    Ext.ux.form.IconCombo.superclass.constructor.call(this, config);
 
    this.tpl = config.tpl ||
          '<tpl for="."><div class="x-combo-list-item x-icon-combo-item {' 
        + this.iconClsField 
        + '}">{' 
        + this.displayField 
        + '}</div></tpl>'
    ;
 
    this.on({
        render:{scope:this, fn:function() {
            var wrap = this.el.up('div.x-form-field-wrap');
            this.wrap.applyStyles({position:'relative'});
            this.el.addClass('x-icon-combo-input');
            this.flag = Ext.DomHelper.append(wrap, {
                tag: 'div', style:'position:absolute'
            });
        }}
    });
} // end of Ext.ux.form.IconCombo constructor
 
// extend
Ext.extend(Ext.ux.form.IconCombo, Ext.form.ComboBox, {
 
    setIconCls: function() {
        var rec = this.store.query(this.valueField, this.getValue()).itemAt(0);
        if(rec) {
            this.flag.className = 'x-icon-combo-icon ' + rec.get(this.iconClsField);
        }
    },
 
    setValue: function(value) {
        Ext.ux.form.IconCombo.superclass.setValue.call(this, value);
        this.setIconCls();
    }
 
}); // end of extend

Ext.reg("iconcombo", Ext.ux.form.IconCombo);
 
// end of file
