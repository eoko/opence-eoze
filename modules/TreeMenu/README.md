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

    this.addModuleAction('myActionName', function() {
      // implement action here...
    });
