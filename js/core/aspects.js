//Ext.namespace('aspects');
//
//Oce.Aspects = function() {
//
//	var registeredFunctionnalities = {};
//
//	return {
//		get: function(name, preferedProvider) {
////			if (name registeredFunctionnalities)
//		}
//	}
//}()
//
//Oce.Aspects = function() {
//
//	const PRIORITY		= 1;
//	const STABILITY		= 2;
//	const PROVIDER		= 3;
//	const UNICITY		= 4;
//
//	var preferredOrdering = [ PROVIDER, PRIORITY, STABILITY, UNICITY ];
//
//	var registeredConcerns = [];
//	var registeredFunctionnalities = [];
//
//	return {
//
//		Priority: {
//			DEFAULT:		0,
//			APPLICATION:	1,
//			EXTENSION:		2
//		},
//
//		Stability: {
//			STABLE:			0,
//			RC:				1,
//			BETA:			2,
//			DEV:			3
//		},
//
//		getConcern: function(name, ordering) {
//
//		}
//	}
//}()
//
//Oce.Functionality = function() {
//	var name;
//	var provider;
//}
//
//Oce.Concern = function() {
//	var requiredFunctionnalities = [];
//	var registeredProviders = []
//}
//
//Oce.FunctionalityProvider = function() {
//
//	// --- Private ---
//	var name = 'UNDEFINED_FONCTIONNALITY_PROVIDER_NAME';
//	var functionalities = [];
//
//	// --- Protected (privileged) ---
//	this.init = function(config) {
//		throw 'Functionnality must define protected (privileged) method "init"';
////		this.name = config.name;
////		this.provider = config.provider;
//	}
//
//	// --- Constructor ---
//	this.init();
//
//	// --- Public ---
//	return {
//
//		getName: function() {return name;}
//		,
//		getProvider: function() {return provider;}
//	}
//}