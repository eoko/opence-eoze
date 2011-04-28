Ext.ns('Oce.ext.Renderer', 'Oce.form')

Oce.ext.Renderer.genre = function(val){
	if(val == 0){
		return '<span class="ico ico_male">&nbsp;</span>';
	}else {
		return '<span class="ico ico_female">&nbsp;</span>';
	}
}

Oce.ext.Renderer.date = Ext.util.Format.dateRenderer('l d F Y');

Oce.ext.Renderer.actif = function(val){
	if (val == 0) {
		return '<span class="ico ico_cross">&nbsp;</span>';
	} else if (val == 1) {
		return '<span class="ico ico_tick">&nbsp;</span>';
	} else {
		return '';
//		return '<span class="ico ico_8ball">&nbsp;</span>';
	}
}

Oce.ext.Renderer.yesNo = function(val){
	if (val == 0) {
		return "Non";
	} else if (val == 1) {
		return "Oui";
	} else {
		return '&mdash;';
//		return '<span class="ico ico_8ball">&nbsp;</span>';
	}
}

Oce.ext.Renderer.yesNoStrict = function(val){
	if (val == 1) {
		return "Oui";
	} else {
		return "Non";
	}
}

Oce.ext.Renderer.rouge = function(val){
	if(val == 0){
		return '<span class="ico ico_bullet_green">&nbsp;</span>';
	}else {
		return '<span class="ico ico_bullet_red">&nbsp;</span>';
	}
}

Oce.ext.Renderer.integer = function(val) {
	if (val === null || val === undefined) return "";
	return '<div class="grid-renderer-integer" style="font-family:monospace; text-align:right;">' 
		+ Oce.php.number_format(val, 0, ',', ' ')
		+ "</div>";
}

//Oce.ext.Renderer.float2 = function(val) {
//	if (val === null || val === undefined) return "";
//	return '<div class="grid-renderer-integer" style="font-family:monospace; text-align:right;">'
//		+ Oce.php.number_format(val, 2, ',', ' ')
//		+ "</div>";
//}

Oce.ext.Renderer.floatP = function(p, dp, sm) {
	return function(val) {
		if (val === null || val === undefined) return "";
		return '<div class="grid-renderer-float" style="font-family:monospace; text-align:right;">'
			+ Oce.php.number_format(val, p, dp, sm)
			+ "</div>"
	};
}

Oce.ext.Renderer.float_fr_2 = Oce.ext.Renderer.floatP(2, ',', ' ');

Oce.defaultField = function() {

	var fields = {
		 id: {name: 'id', header: "ID", width: 40, form: {stick:'top'},
			 hidden:true, add: false, readOnly: true}
		,usr_mod: {name: 'usr_mod', width: 200, header: "Modifié par", form: {stick:'top'},
			hidden: true, add: false, readOnly: true}
		,date_add: {name:'date_add', header:"Ajouté le", form: {stick:'top'},
			hidden: true, add:false, readOnly: true}
		,date_mod: {name:'date_mod', header:"Modifié le", form: {stick:'top'},
			hidden: true, add:false, readOnly: true}
		,actif: {name: 'actif', width: 42, header: "Actif", renderer : Oce.ext.Renderer.actif,
			type: 'checkbox'}
		,email: {name:'email', header:'Email'}
	}

	return function(name) {
		return Oce.clone(fields[name]);
	}
}()

Ext.ns('Oce.Format');

Oce.Format.date = function(v, format) {
	if (!v) {return '';}
	if (!Ext.isDate(v)) {
		// Split timestamp into [ Y, M, D, h, m, s ]
		var t = v.split(/[- :]/);

		// Apply each element to the Date function
		if (t.length === 6) {
			v = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
		} else {
			v = new Date(Date.parse(v));
		}
	}
	return v.dateFormat(format || "m/d/Y");
}

Oce.Format.dateRenderer = function(format) {
	return function(v) {
		return Oce.Format.date(v, format);
	}
}

Oce.form.SubmitDisplayField = Ext.extend(Ext.form.DisplayField, {
	onRender: function(ct) {
		Oce.form.SubmitDisplayField.superclass.onRender.apply(this, arguments);
		if (this.el) {
			var hiddenField = new Ext.form.Hidden({
				'name': this.el.dom.getAttribute('name')
			})
			this.ownerCt.add(hiddenField);
			this.mon(this, 'change', function(e, value) {
				hiddenField.setValue(value)
			}, this)
			this.mon(this, 'afterrender', function() {
				hiddenField.setValue(this.value)
			}, this)
		}
	}
})

Ext.reg('oce.submitdisplayfield', Oce.form.SubmitDisplayField)
Ext.reg('submitdisplayfield', Oce.form.SubmitDisplayField)