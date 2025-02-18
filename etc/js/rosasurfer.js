'use strict';


/**
 * Polyfills
 */
if (!Array.from) { Array.from = (function() {
    var toStr = Object.prototype.toString;
    var isCallable = function(fn) {
        return typeof(fn)==='function' || toStr.call(fn)==='[object Function]';
    };
    var toInteger = function(value) {
        var number = Number(value);
        if (isNaN(number))                   return 0;
        if (number===0 || !isFinite(number)) return number;
        return (number > 0 ? 1 : -1) * Math.floor(Math.abs(number));
    };
    var maxSafeInteger = Math.pow(2, 53) - 1;
    var toLength = function(value) {
        var len = toInteger(value);
        return Math.min(Math.max(len, 0), maxSafeInteger);
    };

    // The length property of the from method is 1.
    return function from(arrayLike/*, mapFn, thisArg*/) {
        // 1) Let C be the this value.
        var C = this;
        // 2) Let items be ToObject(arrayLike).
        var items = Object(arrayLike);
        // 3) ReturnIfAbrupt(items).
        if (arrayLike == null) throw new TypeError('Array.from requires an array-like object - not null or undefined');
        // 4) If mapfn is undefined, then let mapping be false.
        var mapFn = arguments.length > 1 ? arguments[1] : void undefined;
        var T;
        if (typeof(mapFn) !== 'undefined') {
            // 5a) If isCallable(mapfn) is false, throw a TypeError exception.
            if (!isCallable(mapFn)) throw new TypeError('Array.from: when provided, the second argument must be a function');
            // 5b) If thisArg was supplied, let T be thisArg; else let T be undefined.
            if (arguments.length > 2) T = arguments[2];
        }
        // 6) Let len be toLength(lenValue).
        var len = toLength(items.length);
        // 7) If isCallable(C) is true, then
        // 7a) Let A be the result of calling the [[Construct]] internal method of C with an argument list containing the single item len.
        // 7b) Else, Let A be ArrayCreate(len).
        var A = isCallable(C) ? Object(new C(len)) : new Array(len);
        // 8) Let k be 0.
        var k = 0, kValue;
        // 9) Repeat, while k < len (also steps a - h)
        while (k < len) {
            kValue = items[k];
            if (mapFn) A[k] = typeof(T)==='undefined' ? mapFn(kValue, k) : mapFn.call(T, kValue, k);
            else       A[k] = kValue;
            k++;
        }
        // 10) Let putStatus be Put(A, "length", len, true).
        A.length = len;
        // 11) Return A.
        return A;
    };
  }());
}
if (!Array.isArray)                   Array.isArray                   = function isArray   (/*mixed*/ arg) { return Object.prototype.toString.call(arg) === '[object Array]'; };
if (!Array.prototype.forEach        ) Array.prototype.forEach         = function forEach   (/*function*/ func, scope) { for (let i=0, len=this.length; i < len; ++i) func.call(scope, this[i], i, this); }
                                                                      
if (!Date.prototype.addDays         ) Date.prototype.addDays          = function addDays   (/*int*/ days)    { this.setTime(this.getTime() + (days*24*60*60*1000)); return this; }
if (!Date.prototype.addHours        ) Date.prototype.addHours         = function addHours  (/*int*/ hours)   { this.setTime(this.getTime() + (  hours*60*60*1000)); return this; }
if (!Date.prototype.addMinutes      ) Date.prototype.addMinutes       = function addMinutes(/*int*/ minutes) { this.setTime(this.getTime() + (   minutes*60*1000)); return this; }
if (!Date.prototype.addSeconds      ) Date.prototype.addSeconds       = function addSeconds(/*int*/ seconds) { this.setTime(this.getTime() + (      seconds*1000)); return this; }
if (!Date.prototype.toLocalISOString) Date.prototype.toLocalISOString = function toLocalISOString() { 
  let utcDate = new Date(Date.UTC(this.getFullYear(), this.getMonth(), this.getDate(), this.getHours(), this.getMinutes(), this.getSeconds(), this.getMilliseconds()));
  let isoDate = utcDate.toISOString().slice(0, -1);
  let offset = this.getTimezoneOffset();
  let sign = offset > 0 ? '-' : '+';
  let absOffset = Math.abs(offset);
  let hours = String(Math.floor(absOffset / 60)).padStart(2, '0');
  let minutes = String(absOffset % 60).padStart(2, '0');
  return isoDate + sign + hours + minutes;
}

if (!String.prototype.capitalize     ) String.prototype.capitalize      = function capitalize     ()                                    { return this.charAt(0).toUpperCase() + this.slice(1); }
if (!String.prototype.capitalizeWords) String.prototype.capitalizeWords = function capitalizeWords()                                    { return this.replace(/\w\S*/g, function(word) { return word.capitalize(); }); }
if (!String.prototype.decodeEntities ) String.prototype.decodeEntities  = function decodeEntities ()                                    { if (!String.prototype.decodeEntities.textarea) /*static*/ String.prototype.decodeEntities.textarea = document.createElement('textarea'); String.prototype.decodeEntities.textarea.innerHTML = this; return String.prototype.decodeEntities.textarea.value; }
if (!String.prototype.startsWith     ) String.prototype.startsWith      = function startsWith     (/*string*/ prefix, /*int*/ pos)      { return this.substr(!pos || pos < 0 ? 0 : +pos, prefix.length) === prefix; }
if (!String.prototype.endsWith       ) String.prototype.endsWith        = function endsWith       (/*string*/ suffix, /*int*/ this_len) { if (this_len===undefined || this_len > this.length) this_len = this.length; return this.substring(this_len - suffix.length, this_len) === suffix; }
if (!String.prototype.includes       ) String.prototype.includes        = function includes       (/*string*/ string, /*int*/ start)    { if (typeof(start) !== 'number') start = 0; if (start + string.length > this.length) return false; return this.indexOf(string, start) !== -1; }
if (!String.prototype.contains       ) String.prototype.contains        = String.prototype.includes;
if (!String.prototype.trim           ) String.prototype.trim            = function trim           ()                                    { return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, ''); }
if (!String.prototype.repeat         ) String.prototype.repeat          = function repeat         (/*int*/ count) {
    let str = ''+ this;
    count = +count;
    if (count != count) count = 0;
    if (count < 0)         throw new RangeError('repeat count must be non-negative');
    if (count == Infinity) throw new RangeError('repeat count must be less than infinity');
    count = Math.floor(count);
    if (!str.length || !count) return '';
    if (str.length * count >= 1 << 28) throw new RangeError('repeat count must not overflow maximum string size');
    let maxCount = str.length * count;
    count = Math.floor(Math.log(count) / Math.log(2));
    while (count--) str += str;
    str += str.substring(0, maxCount - str.length);
    return str;
}

// fix broken Internet Explorer substr()
if ('ab'.substr(-1) != 'b') {
    String.prototype.substr = function substr(start, length) {
        let from = start;
        if (from < 0) from += this.length;
        if (from < 0) from = 0;
        let to = length===undefined ? this.length : from+length;
        if (from > to) to = from;
        return this.substring(from, to);
    }
}

// Add Number.prototype.toFixed10() for decimal adjustment to be used instead of the inaccurate Number.prototype.toFixed().
(function() {
    /**
     * Decimal adjustment of a number.
     *
     * @param  string type  - type of adjustment
     * @param  number value - number
     * @param  int    exp   - exponent (the decimal logarithm of the adjustment base)
     *
     * @return number - adjusted value
     *
     * @see    https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round#Example:_Decimal_rounding
     */
    function decimalAdjust(type, value, exp) {
        // if the exp is undefined or zero...
        if (exp===undefined || +exp===0) {
            return Math[type](value);
        }
        value = +value;
        exp = +exp;
        // if the value is not a number or the exp is not an integer...
        if (isNaN(value) || typeof(exp)!='number' || exp%1===0) {
            return NaN;
        }
        // shift
        value = value.toString().split('e');
        value = Math[type](+(value[0] +'e'+ (value[1] ? (+value[1]-exp) : -exp)));
        // shift back
        value = value.toString().split('e');
        return +(value[0] +'e'+ (value[1] ? (+value[1]+exp) : exp));
    }

    // decimal round, floor and ceil
    if (!Math.round10) Math.round10 = function round10(value, exp) { return decimalAdjust('round', value, exp); };
    if (!Math.floor10) Math.floor10 = function floor10(value, exp) { return decimalAdjust('floor', value, exp); };
    if (!Math.ceil10)  Math.ceil10  = function ceil10 (value, exp) { return decimalAdjust('ceil',  value, exp); };

    Number.prototype.toFixed10 = function toFixed10(precision) {
        return Math.round10(this, -precision).toFixed(precision);
    }
})();


/**
 * Object with helper functions.
 */
var rosasurfer = {

    /**
     * Get an object holding all query parameters. Array parameters are supported for the first dimension.
     *
     * @param  string url [optional] - URL to get query parameters from (default: the current page location)
     *
     * @return object - {key1: value1, key2: value2, ..., keyN: valueN}
     */
    getQueryParameters: function(url) {
        var pos, query;
        if (url===undefined) query = location.search;
        else                 query = ((pos=url.indexOf('?'))==-1) ? '' : url.substr(pos);

        var result={}, pairs=query.slice(1).replace(/\+/g, ' ').split('&'), reArray=/^([^[]+)\[(.*)\]$/, matches, lengths={};

        pairs.forEach(function(pair) {
            var name, value, values=pair.split('=');                       // Unlike PHP the JavaScript function split(str, limit)
                                                                           // discards additional occurrences.
            if (values.length > 1) {
                name  = decodeURIComponent(values.shift());
                value = decodeURIComponent(values.join('='));

                if (name.contains('[') && (matches = name.match(reArray))) {
                    var array = matches[1];
                    var key = matches[2];
                    if (typeof(result[array]) != 'object') {
                      result[array] = {};
                    }
                    if (!key.length)        Array.prototype.push.call(result[array], value);
                    else if (key=='length') lengths[array] = value;        // backup length parameter to not confuse Array.push()
                    else                    result[array][key] = value;
                }
                else {
                  result[name] = value;
                } 
            }
        });

        Object.keys(result).forEach(function(key) {
            if (typeof(result[key]) == 'object') {
               delete result[key].length;                                  // delete length property defined by Array.push()
               if (lengths[key] !== undefined) {
                 result[key].length = lengths[key];                        // restore a backed-up length parameter
               }
            }
        });
        return result;
    },


    /**
     * Get a single query parameter value.
     *
     * @param  string name           - parameter name
     * @param  string url [optional] - URL to get a query parameter from (default: the current page location)
     *
     * @return string - value or undefined if the parameter doesn't exist in the query string
     */
    getQueryParameter: function(name, url) {
        if (name===undefined) return alert('rosasurfer.getQueryParameter()\n\nUndefined parameter "name"');
        return this.getQueryParameters(url)[name];
    },


    /**
     * Whether a parameter exists in the query string.
     *
     * @param  string name           - parameter name
     * @param  string url [optional] - URL to check for the query parameter (default: the current page location)
     *
     * @return bool
     */
    isQueryParameter: function(name, url) {
        if (name===undefined) return alert('rosasurfer.isQueryParameter()\n\nUndefined parameter "name"');
        return this.getQueryParameters(url)[name] !== undefined;
    },


    /**
     * Load a url via GET and pass the response to the specified callback function.
     *
     * @param string   url      - url to load
     * @param function callback - callback function
     */
    getUrl: function(url, callback) {                          // request.readyState = returns the status of the XMLHttpRequest
        var request = new XMLHttpRequest();                    //  0: request not initialized
        request.url = url;                                     //  1: server connection established
        request.onreadystatechange = function() {              //  2: request received
            if (request.readyState == 4) {                     //  3: processing request
                callback(request);                             //  4: request finished and response is ready
            }                                                  //
        };                                                     // request.status = returns the HTTP status-code
        request.open('GET', url , true);                       //  200: "OK"
        request.send(null);                                    //  404: "Not Found" etc.
    },


    /**
     * Load a url via POST and pass the response to the specified callback function.
     *
     * @param string   url      - url to load
     * @param string   data     - content to send in the request's body (i.e. POST parameter)
     * @param object   headers  - additional request header
     * @param function callback - callback function
     */
    postUrl: function(url, data, headers, callback) {          // request.readyState = returns the status of the XMLHttpRequest
        var request = new XMLHttpRequest();                    //  0: request not initialized
        request.url = url;                                     //  1: server connection established
        request.onreadystatechange = function() {              //  2: request received
            if (request.readyState == 4) {                     //  3: processing request
                callback(request);                             //  4: request finished and response is ready
            }                                                  //
        };                                                     // request.status = returns the HTTP status-code
        request.open('POST', url , true);                      //  200: "OK"
        for (var name in headers) {                            //  404: "Not Found" etc.
            request.setRequestHeader(name, headers[name]);
        }
        request.send(data);
    },


    /**
     * Return a nicer representation of the specified argument's type.
     *
     * @param  mixed arg
     *
     * @return string
     */
    getType: function(arg) {
        var type = typeof(arg);
        if (type == 'object') {
            if      (arg === null)    type = 'null';
            else if (arg.constructor) type = arg.constructor.name || arg.constructor.toString();
            else                      type = ''+ arg;

            if (type.startsWith('[object ')) {                      // [object HTMLAnchorElement]
                type = type.slice(8, -1);
            }
            else if (type.startsWith('function')) {                 // function HTMLAnchorElement() { [native code] }
                var name = type.slice(8, type.indexOf('(')).trim(); // function( param1, param2... ) { <custom code> }
                type = name.length ? name : 'function';
            }
        }
        return type;
    },


    /**
     * Get the full selector path of a DOM element.
     *
     * @param  Node   element
     * @param  object options - options defining whether the returned string should contain element ids and/or classes
     *
     * @return string
     */
    getFullSelector: function(element, options = {id:false, class:false}) {
      let path = [];

      do {
        let selector = element.tagName, classes;
        
        if (options?.id && element.id) {
            selector += '#'+ element.id;
        }
        else if (element.parentElement) {
          let siblings = Array.from(element.parentElement.children).filter(el => el?.tagName === element.tagName);
          if (siblings.length > 1) {
            selector += ':nth('+ (Array.from(element.parentElement.children).indexOf(element) + 1) +')';
          }
        }
        if (options?.class && (classes = Array.from(element.classList).join('.'))) {
          selector += '.'+ classes;
        }
        path.unshift(selector);
      } while (element = element.parentElement);
      
      return path.join(' > ');
    },


    /**
     * Whether a variable is defined.
     *
     * @param  mixed arg
     *
     * @return bool
     */
    isDefined: function(arg) {
        return (typeof(arg) != 'undefined');
    },

    
    /**
     * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message Digest Algorithm, as defined in RFC 1321.
     * Based on the original version of (c) Paul Johnston
     *
     * @param  string input
     *
     * @return string
     */
    md5: function(input) {
        var hc = '0123456789abcdef';
        function rh(n)                   { var j, s=''; for (j=0; j<=3; j++) s += hc.charAt((n>>(j*8+4)) & 0x0F) + hc.charAt((n>>(j*8)) & 0x0F); return s; }
        function ad(x, y)                { var l=(x & 0xFFFF) + (y & 0xFFFF); var m=(x>>16) + (y>>16) + (l>>16); return (m<<16) | (l & 0xFFFF); }
        function rl(n, c)                { return (n<<c) | (n>>>(32-c)); }
        function cm(q, a, b,    x, s, t) { return ad(rl(ad(ad(a, q), ad(x, t)), s), b); }
        function ff(a, b, c, d, x, s, t) { return cm((b&c) | ((~b) & d), a, b, x, s, t); }
        function gg(a, b, c, d, x, s, t) { return cm((b&d) | (c & (~d)), a, b, x, s, t); }
        function hh(a, b, c, d, x, s, t) { return cm(b^c^d, a, b, x, s, t); }
        function ii(a, b, c, d, x, s, t) { return cm(c^(b|(~d)), a, b, x, s, t); }
        function sb(x) {
            var i, nblk=((x.length+8)>>6) + 1, blks=new Array(nblk*16); for (i=0; i<nblk*16; i++) blks[i]=0;
            for (i=0; i<x.length; i++) blks[i>>2] |= x.charCodeAt(i) << ((i%4)*8);
            blks[i>>2] |= 0x80<<((i%4)*8); blks[nblk*16-2] = x.length*8; return blks;
        }
        var i, x=sb(input), a=1732584193, b=-271733879, c=-1732584194, d=271733878, olda, oldb, oldc, oldd;
        for (i=0; i<x.length; i+=16) {
            olda = a; oldb = b; oldc = c; oldd = d;
            a = ff(a,b,c,d,x[i+ 0], 7, -680876936); d = ff(d,a,b,c,x[i+ 1],12, -389564586); c = ff(c,d,a,b,x[i+ 2],17,  606105819);
            b = ff(b,c,d,a,x[i+ 3],22,-1044525330); a = ff(a,b,c,d,x[i+ 4], 7, -176418897); d = ff(d,a,b,c,x[i+ 5],12, 1200080426);
            c = ff(c,d,a,b,x[i+ 6],17,-1473231341); b = ff(b,c,d,a,x[i+ 7],22,  -45705983); a = ff(a,b,c,d,x[i+ 8], 7, 1770035416);
            d = ff(d,a,b,c,x[i+ 9],12,-1958414417); c = ff(c,d,a,b,x[i+10],17,     -42063); b = ff(b,c,d,a,x[i+11],22,-1990404162);
            a = ff(a,b,c,d,x[i+12], 7, 1804603682); d = ff(d,a,b,c,x[i+13],12,  -40341101); c = ff(c,d,a,b,x[i+14],17,-1502002290);
            b = ff(b,c,d,a,x[i+15],22, 1236535329); a = gg(a,b,c,d,x[i+ 1], 5, -165796510); d = gg(d,a,b,c,x[i+ 6], 9,-1069501632);
            c = gg(c,d,a,b,x[i+11],14,  643717713); b = gg(b,c,d,a,x[i+ 0],20, -373897302); a = gg(a,b,c,d,x[i+ 5], 5, -701558691);
            d = gg(d,a,b,c,x[i+10], 9,   38016083); c = gg(c,d,a,b,x[i+15],14, -660478335); b = gg(b,c,d,a,x[i+ 4],20, -405537848);
            a = gg(a,b,c,d,x[i+ 9], 5,  568446438); d = gg(d,a,b,c,x[i+14], 9,-1019803690); c = gg(c,d,a,b,x[i+ 3],14, -187363961);
            b = gg(b,c,d,a,x[i+ 8],20, 1163531501); a = gg(a,b,c,d,x[i+13], 5,-1444681467); d = gg(d,a,b,c,x[i+ 2], 9,  -51403784);
            c = gg(c,d,a,b,x[i+ 7],14, 1735328473); b = gg(b,c,d,a,x[i+12],20,-1926607734); a = hh(a,b,c,d,x[i+ 5], 4,    -378558);
            d = hh(d,a,b,c,x[i+ 8],11,-2022574463); c = hh(c,d,a,b,x[i+11],16, 1839030562); b = hh(b,c,d,a,x[i+14],23,  -35309556);
            a = hh(a,b,c,d,x[i+ 1], 4,-1530992060); d = hh(d,a,b,c,x[i+ 4],11, 1272893353); c = hh(c,d,a,b,x[i+ 7],16, -155497632);
            b = hh(b,c,d,a,x[i+10],23,-1094730640); a = hh(a,b,c,d,x[i+13], 4,  681279174); d = hh(d,a,b,c,x[i+ 0],11, -358537222);
            c = hh(c,d,a,b,x[i+ 3],16, -722521979); b = hh(b,c,d,a,x[i+ 6],23,   76029189); a = hh(a,b,c,d,x[i+ 9], 4, -640364487);
            d = hh(d,a,b,c,x[i+12],11, -421815835); c = hh(c,d,a,b,x[i+15],16,  530742520); b = hh(b,c,d,a,x[i+ 2],23, -995338651);
            a = ii(a,b,c,d,x[i+ 0], 6, -198630844); d = ii(d,a,b,c,x[i+ 7],10, 1126891415); c = ii(c,d,a,b,x[i+14],15,-1416354905);
            b = ii(b,c,d,a,x[i+ 5],21,  -57434055); a = ii(a,b,c,d,x[i+12], 6, 1700485571); d = ii(d,a,b,c,x[i+ 3],10,-1894986606);
            c = ii(c,d,a,b,x[i+10],15,   -1051523); b = ii(b,c,d,a,x[i+ 1],21,-2054922799); a = ii(a,b,c,d,x[i+ 8], 6, 1873313359);
            d = ii(d,a,b,c,x[i+15],10,  -30611744); c = ii(c,d,a,b,x[i+ 6],15,-1560198380); b = ii(b,c,d,a,x[i+13],21, 1309151649);
            a = ii(a,b,c,d,x[i+ 4], 6, -145523070); d = ii(d,a,b,c,x[i+11],10,-1120210379); c = ii(c,d,a,b,x[i+ 2],15,  718787259);
            b = ii(b,c,d,a,x[i+ 9],21, -343485551); 
            a = ad(a, olda); b = ad(b, oldb); c = ad(c, oldc); d = ad(d, oldd);
        }
        return rh(a) + rh(b) + rh(c) + rh(d);
    },
    
    
    /**
     * Show all accessible properties of the passed argument.
     *
     * @param  mixed arg
     * @param  bool  sort [optional] - whether to sort the displayed properties (default: yes)
     */
    showProperties: function(arg, sort) {
        if (arg === undefined)                            return alert('rosasurfer.showProperties()\n\nPassed parameter: undefined');
        if (arg === null)                                 return alert('rosasurfer.showProperties()\n\nPassed parameter: null');
        if (this.getType(arg).startsWith('XrayWrapper ')) return this.showProperties(arg.wrappedJSObject, sort);

        var property='', properties=[], type=this.getType(arg);
        sort = (sort===undefined) ? (type!='Array') : Boolean(sort);

        for (var i in arg) {
            try {
                property = ''+ arg[i];
                if ((i=='innerHTML' || i=='textContent') && (property=property.replace(/\n/g, ' ')) && property.length > 100) {
                    property = property.substr(0, 100) +'...';                                      // limit long HTML contents
                }
                else if (property.startsWith('function ') && property.contains('[native code]')) {  // remove line breaks from native function bodies
                    property =  property.replace(/\n/g, ' ');
                }
                property = type +'.'+ i +' = '+ property;
            }
            catch (ex) {
                property = type +'.'+ i +' = exception while reading property ('+ ex.name +': '+ ex.message +')';
            }
            properties[properties.length] = property.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        if (properties.length) {
            if (sort) properties = properties.sort();
            this.log(properties.join('\n'));
        }
        else {
            alert('rosasurfer.showProperties()\n\n'+ type +' has no known properties.');
        }
    },


    /**
     * Log a message.
     *
     * @param  mixed  msg
     * @param  string target - Whether to log to the top (default) or the bottom of the page.
     *                         The method will remember the last used 'target' parameter.
     */
    log: function(msg, target/*='top'*/) {
        var div = this.log.container;
        if (!div) {
            var bodies = document.getElementsByTagName('body');
            if (!bodies || !bodies.length) {
                return alert('rosasurfer.log()\n\nFailed attaching the logger output DIV to the page (BODY tag not found).\nWait for document.onLoad() or use console.log() if you want to log from the page header?');
            }
            div = this.log.container = document.createElement('div');
            div.setAttribute('id', 'rosasurfer.log.output');
            div.style.position        = 'absolute';
            div.style.top             = '6px';
            div.style.left            = '6px';
            div.style.zIndex          = '4294967295';
            div.style.padding         = '6px';
            div.style.textAlign       = 'left';
            div.style.font            = 'normal normal 12px/1.1em arial,helvetica,sans-serif';
            div.style.color           = 'black';
            div.style.backgroundColor = 'lightgray';
            bodies[0].appendChild(div);
        }
        if      (target=='top'   ) div.style.position = 'absolute';
        else if (target=='bottom') div.style.position = 'relative';

        if (msg === null)                  msg = '(null)';
        else if (typeof(msg)=='undefined') msg = '(undefined)';
        else                               msg = msg.toString().replace(/ {2,}/g, function(match) { return '&nbsp;'.repeat(match.length); })
                                                               .replace(/\n/g, '<br>');
        div.innerHTML += msg +'<br>\n';
    },


    /**
     * Clear the log output in the current page.
     */
    clearLog: function() {
        if (this.log.container) {
          this.log.container.innerHTML = '';
        }
    },


    /**
     * Log a message to the status bar.
     *
     * @param  mixed msg
     */
    logStatus: function(msg) {
        if (this.getType(msg) == 'Event') this.logEvent(msg);
        else                              self.status = msg;
    },


    /**
     * Log event infos to the status bar.
     *
     * @param  Event ev
     */
    logEvent: function(ev) {
        this.logStatus(ev.type +' event,  window: ['+ (ev.pageX - pageXOffset) +','+ (ev.pageY - pageYOffset) +']  page: ['+ ev.pageX +','+ ev.pageY +']');
    }
};
var rs = rs || rosasurfer;
