/*
 * Panel Framework 0.0.1
 */

(function() {
	'use strict';

	// reference to the global object
	var root = this;

	// top-level namespace - export in the case of a CommonJS module
	var PFX;
	if (typeof exports !== 'undefined' && typeof require !== 'undefined') {
		PFX = exports;
	} else {
		PFX = root.PFX = {};
	}

	// DOM library - works like in Backbone.js
	var $ = root.jQuery || root.Zepto || root.ender;
	PFX.setDomLibrary = function(lib) {
		$ = lib;
	};

	// Classes
	var merge = function(obj) {
		for (var i=1,len=arguments.length; i<len; i++) {
			var source = arguments[i];
			for (var prop in source) {
				obj[prop] = source[prop];
			}
		}
		return obj;
	};
	var ctor = function() {};
	var inherits = function(parent, protoProps, staticProps) {
		var child;
		if (protoProps && protoProps.hasOwnProperty('constructor')) {
			child = protoProps.constructor;
		} else {
			child = function() { parent.apply(this, arguments); };
		}

		merge(child, parent);

		ctor.prototype = parent.prototype;
		child.prototype = new ctor();

		if (protoProps) merge(child.prototype, protoProps);
		if (staticProps) merge(child, staticProps);

		child.prototype.constructor = child;

		child.__super__ = parent.prototype;

		return child;
	};
	var extend = function(protoProps, classProps) {
		var child = inherits(this, protoProps, classProps);
		child.extend = this.extend;
		return child;
	};
	PFX.Class = function(){};
	PFX.Class.extend = extend;

	// Events!
	var returnFalse = function() { return false; };
	PFX.EventedClass = PFX.Class.extend({
		initializeEvents: function() {
			this.on(this.events);
		},
		on: function(events, target, data, handler, context) {
			if (typeof target !== 'string' && target !== null) {
				// target defaults to null
				context = handler;
				handler = data;
				data = target;
				target = null;
			}
			if (typeof data !== 'object' && data !== null) {
				// data defaults to null
				context = handler;
				handler = data;
				data = null;
			}
			if (typeof handler === 'string') {
				handler = this[handler];
				context || (context = this);
			}
			if (handler === false) {
				handler = returnFalse;
			}
			if (typeof events === 'object') {
				if (Array.isArray(events)) {
					for (var i=0, len=events.length; i<len; i++) {
						this.on(events[i], target, data, handler, context);
					}
				} else {
					for (var type in events) {
						this.on(type, target, data, events[type], context);
					}
				}
				return this;
			}
			events = events.trim();
			var spaceIndex = events.indexOf(' ');
			if (spaceIndex > 0) {
				// we use the Backbone.js way of interpreting whitespace, not the jQuery way
				// pass events as an array, e.g. events.split(' ') to use the jQuery way
				target = events.substr(spaceIndex+1).trim();
				events = events.substr(0, spaceIndex);
			}

			// actually add the event handler
			this.eventHandlers || (this.eventHandlers = {});
			this.eventHandlers[events] || (this.eventHandlers[events] = []);

			this.eventHandlers[events].push({
				data: data,
				target: target,
				handler: handler,
				context: context
			});

			if (target.substr(0,1) === '@') {
				var spaceIndex = target.indexOf(' ');
				var subTarget = target.substr(spaceIndex+1).trim();
				target = target.substr(1, spaceIndex-1);
				if (this[target] && this[target].on) {
					if (context) handler = handler.bind(context);
					this[target].on(events, subTarget, data, handler);
				}
			} else if (this.$el) {
				if (context) handler = handler.bind(context);
				this.$el.on(events, target, data, handler);
			}

			return this;
		},
		trigger: function(type, target) {
			if (!this.eventHandlers || !this.eventHandlers[type]) return true;
			var e = this.ensureEvent(type, this);
			// iterate event handlers last to first
			for (var i=this.eventHandlers[type].length-1; i>=0; i++) {
				var handler = this.eventHandlers[type][i];

				if (handler.target !== null) continue;

				e.data = handler.data;
				var retVal = handler.handler.call(handler.context || e.currentTarget, e);
				if (retVal === false) {
					e.preventDefault();
					e.stopPropagation();
				}
				if (e.propagationStopped) break;
			}
			return !e.defaultPrevented;
		},
		ensureEvent: function(event, target) {
			if (typeof event === 'string') {
				return new PFX.Event(event, {target: target})
			}
			if (event instanceof PFX.Event) {
				return event;
			}
			// no harm in wrapping it in a PFX event
			event.target || (event.target = target);
			return new PFX.Event(event);
		}
	});

	// A PFX event tries to imitate a jQuery event as much as possible
	PFX.Event = PFX.Class.extend({
		constructor: function(type, initial) {
			if (typeof type === 'string') {
				initial || (initial = {});
				initial.type = type;
			} else {
				type || (type = {});
				if (initial) merge(type, initial);
				initial = type;
			}
			merge(this, initial);
			this.currentTarget || (this.currentTarget = this.target);
			this.timeStamp = Date.now();
		},
		type: 'event',
		target: null,
		currentTarget: null,
		defaultPrevented: false,
		isDefaultPrevented: function() {
			return this.defaultPrevented;
		},
		preventDefault: function() {
			this.defaultPrevented = true;
		},
		propagationStopped: false,
		isPropagationStopped: function() {
			return this.propagationStopped;
		},
		stopPropagation: function() {
			this.propagationStopped = true;
		}
	});

	// The router!
	var namedParam = /:\w+/g;
	var splatParam = /\*\w+/g;
	var escapeRegExp  = /[-[\]{}()+?.,\\^$|#\s]/g;
	PFX.Router = PFX.Class.extend({
		ensureRegExp: function(route) {
			if (typeof route === 'string') {
				route = route.replace(escapeRegExp, '\\$&')
					.replace(namedParam, '([^\/]+)')
					.replace(splatParam, '(.*?)');
				return new RegExp('^' + route + '$');
			}
			return route;
		}
	});

}).call(this);



// some ES5 polyfills as relevant

// ES5 15.4.3.2
// http://es5.github.com/#x15.4.3.2
// https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/isArray
if (!Array.isArray) {
	var _toString = call.bind(Object.prototype.toString);
    Array.isArray = function isArray(obj) {
        return _toString(obj) == "[object Array]";
    };
}

// ES5 15.9.4.4
// http://es5.github.com/#x15.9.4.4
if (!Date.now) {
    Date.now = function now() {
        return new Date().getTime();
    };
}

// ES-5 15.3.4.5
// http://es5.github.com/#x15.3.4.5
if (!Function.prototype.bind) {
    Function.prototype.bind = function bind(that) {
        var target = this;
        if (typeof target != "function") {
            throw new TypeError("Function.prototype.bind called on incompatible " + target);
        }
        var args = slice.call(arguments, 1); // for normal call
        var bound = function () {
            if (this instanceof bound) {
                var F = function(){};
                F.prototype = target.prototype;
                var self = new F;
                var result = target.apply(
                    self,
                    args.concat(slice.call(arguments))
                );
                if (Object(result) === result) {
                    return result;
                }
                return self;
            } else {
                return target.apply(
                    that,
                    args.concat(slice.call(arguments))
                );
            }
        };
        return bound;
    };
}

// ES5 15.5.4.20
// http://es5.github.com/#x15.5.4.20
if (!String.prototype.trim) {
    String.prototype.trim = function trim() {
        return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
    };
}
