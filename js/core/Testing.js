/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 nov. 2011
 */
Ext.ns('eo.Testing');

eo.Testing = {
	
	unitTests: {}
	
	,isUnitTestEnv: function() {
		return /[?&]unit-tests(?:[&=]|$)/.test(window.location.href);
	}
	
	,addUnitTest: function(name, fn) {
		this.unitTests[name] = {
			fn: fn
		};
	}
	
	,currentTest: undefined
	
	,startUnitTest: function(name) {
		var test = this.unitTests[name];
		if (!test) {
			throw new Error('No test registered for key: ' + name);
		}
		this.currentTest = new test.fn;
	}
	
	,nextStep: function() {
		this.currentTest.next();
	}
}

if (!eo.Testing.isUnitTestEnv()) {
	Ext.apply(eo.Testing, {
		addUnitTest: Ext.emptyFn
		,startUnitTest: Ext.emptyFn
	})
} else {
	Ext.onReady(function() {
		var security = new Oce.Security;
		if (security.isIdentified()) {
			
			var currentTest;
			
			Ext.iterate(eo.Testing.unitTests, function(name, test) {
				var a = Ext.getBody().createChild({
					tag: 'div'
		//			,href: 'javascript:void(0)'
					,html: name
					,id: 'eose-openlink-' + name
					,style: 'border: 1px solid; padding: 5px; margin: 5px;'
				});

				a.on('click', function() {
//					currentTest = new test.fn;
					eo.Testing.startUnitTest(name);
				}, {single: true});
			});
			
			var next = Ext.getBody().createChild({
				tag: 'div'
				,style: 'float: right; border: 1px solid red; padding: 5px; margin: 10px'
				,html: 'Next test'
				,id: 'eoze-unit-test-next-test'
			});
			
			next.on('click', eo.Testing.nextStep, eo.Testing);
			
		} else {
			security.addListener('login', function() {
				window.location = window.location;
			});
			security.requestLogin(false);
		}
	});
}

eo.isUnitTestEnv = eo.Testing.isUnitTestEnv;

Ext.onReady(function() {
	var matches = /[?\\]unit-tests=([^&]*)(?:[&\\=]|$)/.exec(window.location.href);
	if (matches) {
		eo.Testing.startUnitTest(matches[1]);
	}
});

Oce.deps.reg('eo.Testing');