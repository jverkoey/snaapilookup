/**
 * Define the snaapi namespace.
 */

var Snap = {};

/**
 * Prototype-based Object extensions.
 */
Object.extend = function(destination, source) {
  for (var property in source)
    destination[property] = source[property];
  return destination;
};

function $A(iterable) {
  if (!iterable) return [];
  if (iterable.toArray) return iterable.toArray();
  var length = iterable.length || 0, results = new Array(length);
  while (length--) results[length] = iterable[length];
  return results;
}

Object.extend(Function.prototype, {
  isUndefined: function(object) {
    return typeof object == "undefined";
  },

  bind: function() {
    if (arguments.length < 2 && Object.isUndefined(arguments[0])) return this;
    var __method = this, args = $A(arguments), object = args.shift();
    return function() {
      return __method.apply(object, args.concat($A(arguments)));
    }
  }
});

Object.extend(String, {
  interpret: function(value) {
    return value == null ? '' : String(value);
  }
});

Object.extend(String.prototype, {
  gsub: function(pattern, replacement) {
    var result = '', source = this, match;

    while (source.length > 0) {
      if (match = source.match(pattern)) {
        result += source.slice(0, match.index);
        result += String.interpret(replacement(match));
        source  = source.slice(match.index + match[0].length);
      } else {
        result += source, source = '';
      }
    }
    return result;
  },

  gindexOf: function(needle) {
    var offsets = [];
    var offset = this.indexOf(needle);
    while( offset >= 0 ) {
      offsets.push(offset);
      offset = this.indexOf(needle, offset+1);
    }
    return offsets;
  }
});
