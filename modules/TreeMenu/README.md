TreeMenu module
===============


Exposing a menu action from a module
------------------------------------

1. Declare the action in module (yml) config in `menu.actions`. Minimal config would be:

    # MyModule.yml

    extra.menu:
      actions:
        myAction: {label: 'myLabel', command: '@%module%#myActionName'}


2. Hook the handler with `GridModule#addModuleAction`.

    /**
     * In method `initActions` of MyModule instanceof of `Oce.GridModule#initActions`.
     */

    this.addModuleAction('myActionName', function(callback, scope, args) {

        // implement action here...

        // Important! The callback must be executed after the action is launched,
        // so that the menu knows to stop representing the action as 'loading'.
        Ext.callback(callback, scope);
    });
