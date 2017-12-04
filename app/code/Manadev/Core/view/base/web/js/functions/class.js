define(function() {
    return function (name, parent, methods) {
        var constructor = null;

        if (!(parent instanceof Function)) {
            methods = parent;
            parent = null;
        }

        eval(
            "constructor = function " + name.replace(/\/|::/g, '_') + "() { \n" +
            "    for (var property in this) {\n" +
            "        if (property.startsWith('__get_')) {\n" +
            "            Object.defineProperty(this, property.substr('__get_'.length), { \n" +
            "                get: this[property],\n" +
            "                enumerable: true,\n" +
            "                configurable: true\n" +
            "            });\n" +
            "        }\n" +
            "    }\n" +
            "    if (this.__constructor) this.__constructor.apply(this, arguments); \n" +
            "};");


        if (parent) {
            constructor.prototype = Object.create(parent.prototype);
            constructor.prototype.constructor = constructor;
        }

        for (var method in methods) {
            if (!methods.hasOwnProperty(method)) {
                continue;
            }

            constructor.prototype[method] = methods[method];
            
            /* IE 11 issue solution start*/
            if (!String.prototype.startsWith) {
                String.prototype.startsWith = function(searchString, position){
                  return this.substr(position || 0, searchString.length) === searchString;
              };
            }
              /* IE 11 issue solution end*/
            
            if (method.startsWith('__lazy_')) {
                var property = method.substr('__lazy_'.length);
                var getter;
                eval("getter = function() {\n" +
                 "    delete this." + property + ";\n" +
                 "    return this." + property + " = this." + method + "();\n" +
                 "};");
                constructor.prototype['__get_' + method.substr('__lazy_'.length)] = getter;
            }
        }

        return constructor;
    };
});