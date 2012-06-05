Ext.apply(Ext.form.VTypes, {

    password : function(val, field) {

		var findByFieldName = function(name) {
			return (
				field.findParentByType('oce.form')
				|| field.findParentByType('form')
			).findBy(
				function(o){
					return o instanceof Ext.form.TextField
						&& o.name && o.name === name;
				}
			)[0]
		}

        if (
			(field.initialPassFieldName && (
				// find by name
				pwd = findByFieldName(field.initialPassFieldName)
			)) || (field.initialPassFieldId && (
				// find by id
				pwd = Ext.getCmp(field.initialPassFieldId)
			)) || (field.initialPassField && (
				// find by id (faster op, so first)
				(pwd = Ext.getCmp(field.initialPassField))
				// ... or by name
				|| (pwd = findByFieldName(field.initialPassField))
			))
		) {
            return (val == pwd.getValue());
		}

		// Haven't been able to find twin field
        return true;
    },

    passwordText : /*lang(*/'Les mots de passes sont différents'/*)*/ // i18n

	,year: function(v) {
		return /^[0-9]{4}$/.test(v);
	}
	
	,yearText: /*lang(*/'Doit être une année valide (e.g. 1964)'/*)*/ //i18n
	
	,numInt: function(v) {
		return /^[0-9]*$/.test(v);
	}

	,numIntText: /*lang(*/"Doit être un nombre entier"/*)*/ // i18n

	,numFloat: function(v) {
		return /^(?:\d+(?:[,.]\d*)|\d*(?:[,.]\d+))$/.test(v);
	}

	,numFloatText: /*lang(*/"Doit être un nombre"/*)*/ // i18n
	,numText: /*lang(*/"Doit être un nombre"/*)*/ // i18n

	,num: function(v) {
		return Oce.form.VTypes.numFloat(v);
	}

});