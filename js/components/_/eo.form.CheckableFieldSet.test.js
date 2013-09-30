/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 juil. 2012
 */
eo.Testing.addUnitTest('CheckableFieldSet', function() {
   
   var cfs = new eo.form.CheckableFieldSet({
       title: 'Hey !'
       ,readOnly: true
       ,items: [{
           xtype: 'textfield'
           ,fieldLabel: 'Test field'
       }]
   });
   
   var win = new Ext.Window({
       width: 640
       ,height: 480
       ,layout: 'form'
       ,items: [cfs]
   });
   
   win.show();
   
//   cfs.setReadOnly(true);
   
});