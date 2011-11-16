/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 16 nov. 2011
 */
Ext.onReady(function() {

var ns = 'eo.form.contact',
	NS = Ext.ns(ns);
	
Ext.ns(ns + '.config');

NS.texts = Ext.apply({
	x: 'x'
	,addA: '{phv:}Ajouter un{f}e{/f} {:item:lc}'
	,address: '{nf:}Adresse{p/}'
	,city: 'Ville{p/}'
	,email: 'E-mail{p/}'
	,home: 'Domicile{p/}'
	,homeFax: 'Télécopie{p/} domicile{p/}'
	,invalidEmail: 'Email invalide'
	,main: 'Principal{f}e{/f}{p/}'
	,mobile: 'Mobile{p/}'
	,number: 'Numéro'
	,'number, street': 'Numéro, rue'
	,office: 'Bureau{p}x{/p}'
	,officeFax: 'Télécopie{p/} bureau{p}x{/p}'
	,other: 'Autre{p/}'
	,organisation: '{nf:}Organisation'
	,pager: '{nm:}Pager{p/}'
	,personnal: '{adj:}Personnel{f}le{/f}{p/}'
	,phone: 'Téléphone{p/}'
	,phoneNumber: '{gn:m}Numéro{p/} de téléphone'
	,professionnal: '{adj:}Professionnel{f}le{/f}{p/}'
	,proMobile: 'Mobile{p/} pro{p/}'
	,remove: '{v:}Supprimer'
	,setDefault: '{:type} principal{f}e{/f}'
	,society: 'Société'
	,street: 'Rue{p/}'
	,title: 'Titre'
	,unlisted: '{gn:f}Liste{p/} rouge{p/}'
	,zip: 'Code{p}s{/p} posta{s}l{/s}{p}aux{/p}'
}, NS.config.texts);

NS.config = {

	phone: {
		types: {
			MOBILE:     'mobile',
			MOBILE_PRO: 'proMobile',
			DOMICILE:   'home',
			OFFICE:     'office',
			HOME_FAX:   'homeFax',
			OFFICE_FAX: 'officeFax',
			PAGER:      'pager',
			OTHER:      'other'
		}
		,defaultType: 'MOBILE'
		
		,textKeyItem: 'phone'
		,textKeyNaturalItem: 'phoneNumber'
	}
	
	,email: {
		types: {
			PERSONNAL:    'personnal',
			PROFESSIONAL: 'professionnal',
			OTHER:        'other'
		}
		,defaultType: 'PERSONNAL'
		
		,textKeyItem: 'email'
	}
	
	,address: {
		types: {
			HOME:   'home {g:address}',
//			HOME:   'personnal {g:address}',
			OFFICE: 'office {g:address}',
			OTHER:  'other {g:address}'
		}
		,defaultType: 'HOME'
		
		,textKeyItem: 'address'
	}
	
	,organisation: {
		types: {
			MAIN: 'main {g:organisation}',
			OTHER: 'other {g:organisation}'
		}
		,defaultType: 'MAIN'
		
		,textKeyItem: 'organisation'
	}

};

eo.deps.reg('config', ns);

}); // onReady