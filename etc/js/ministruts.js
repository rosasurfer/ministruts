/**
 * Extend objects.
 */
if (!String.prototype.capitalize     ) String.prototype.capitalize      = function()                  { return this.charAt(0).toUpperCase() + this.slice(1); }
if (!String.prototype.capitalizeWords) String.prototype.capitalizeWords = function()                  { return this.replace(/\w\S*/g, function(word) { return word.capitalize(); }); }
if (!String.prototype.contains       ) String.prototype.contains        = function(/*string*/ string) { var pos = this.indexOf(string); return (pos != -1); }
if (!String.prototype.decodeEntities ) String.prototype.decodeEntities  = function()                  { return this.replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&Auml;/g, 'Ä').replace(/&Ouml;/g, 'Ö').replace(/&Uuml;/g, 'Ü').replace(/&auml;/g, 'ä').replace(/&ouml;/g, 'ö').replace(/&uuml;/g, 'ü').replace(/&szlig;/g, 'ß'); }
if (!String.prototype.startsWith     ) String.prototype.startsWith      = function(/*string*/ prefix) { return (this.indexOf(prefix) === 0); }
if (!String.prototype.endsWith       ) String.prototype.endsWith        = function(/*string*/ suffix) { var pos = this.lastIndexOf(suffix); return (pos!=-1 && this.length==pos+suffix.length); }
if (!String.prototype.trim           ) String.prototype.trim            = function()                  { return this.replace(/(^\s+)|(\s+$)/g, ''); }
if ('ab'.substr(-1) != 'b') {                                        / broken Internet Explorer
   String.prototype.substr = function(start, length) {
      var from = start;
         if (from < 0) from += this.length;
         if (from < 0) from = 0;
      var to = typeof(length)=='undefined' ? this.length : from+length;
         if (from > to) to = from;
      return this.substring(from, to);
   }
   /*
   String.prototype.substr = function(substr) {
      return function(start, length) {
         if (start < 0)
            start = this.length + start;
         return substr.call(this, start, length);
      }
   }(String.prototype.substr);
   */
}                                                                    // Internet Explorer 8 support
if (!Array.prototype.forEach  ) Array.prototype.forEach   = function(/*function*/fn, scope) { for (var i=0, len=this.length; i < len; ++i) fn.call(scope, this[i], i, this); }

if (!Date.prototype.addDays   ) Date.prototype.addDays    = function(days)    { this.setTime(this.getTime() + (days*24*60*60*1000)); return this; }
if (!Date.prototype.addHours  ) Date.prototype.addHours   = function(hours)   { this.setTime(this.getTime() + (  hours*60*60*1000)); return this; }
if (!Date.prototype.addMinutes) Date.prototype.addMinutes = function(minutes) { this.setTime(this.getTime() + (   minutes*60*1000)); return this; }
if (!Date.prototype.addSeconds) Date.prototype.addSeconds = function(seconds) { this.setTime(this.getTime() + (      seconds*1000)); return this; }


/**
 * Get an array with all query parameters.
 *
 * @param  url   - static URL to get query parameters from (if not given, the current page's url is used)
 *
 * @return Array - [key1=>value1, key2=>value2, ..., keyN=>valueN]
 */
function getQueryParameters(/*string*/url) {
   var pos, search;
   if (typeof(url) == 'undefined') search = location.search;
   else                            search = ((pos=url.indexOf('?'))==-1) ? '' : url.substr(pos);

   var result={}, values, pairs=search.slice(1).split('&');
   pairs.forEach(function(/*string*/pair) {
      values = pair.split('=');                                      // unlike PHP the JavaScript function split(str, limit) discards additional occurrences
      if (values.length > 1)
         result[values.shift()] = values.join('=');
   });
   return result;
}


/**
 * Get a single query parameter value.
 *
 * @param  name   - parameter name
 * @param  url    - static URL to get a query parameter from (if not given, the current page's url is used)
 *
 * @return string - value or null if the parameter doesn't exist in the query string
 */
function getQueryParameter(/*string*/name, /*string*/url) {
   if (typeof(name) == 'undefined') return alert('getQueryParameter()\n\nUndefined parameter: name');

   return getQueryParameters(url)[name];
}


/**
 * Whether or not a parameter exists in the query string.
 *
 * @param  name - parameter name
 * @param  url  - static URL to check for the query parameter (if not given, the current page's url is used)
 *
 * @return bool
 */
function isQueryParameter(/*string*/name, /*string*/url) {
   if (typeof(name) == 'undefined') return alert('isQueryParameter()\n\nUndefined parameter: name');

   return typeof(getQueryParameters(url)[name]) != 'undefined';
}


/**
 * Show all accessible properties of the given argument.
 */
function showProperties(/*mixed*/arg) {
   if (typeof(arg) == 'undefined') return alert('showProperties()\n\nUndefined parameter: arg');

   var properties=[], property='';

   for (var i in arg) {
      try {
         property = arg.toString() +'.'+ i +' = '+ arg[i];
      }
      catch (ex) {
         break;
         property = arg.toString() +'.'+ i +' = Exception while reading property (name: '+ ex.name +', message: '+ ex.message +')';
      }
      properties[properties.length] = property.replace(/</g, '&lt;').replace(/>/g, '&gt;');
   }

   if (properties.length) {
      if (true || navigator.userAgent.endsWith('/4.0')) {
         log(properties.sort().join('<br>\n'));                      // workaround for flawed GreaseMonkey in Firefox 4.0
      }
      else {
         var popup = open('', 'show_properties', 'resizable,scrollbars,width=700,height=800');
         if (!popup) return alert('showProperties()\n\nCannot open popup for '+ location +'\nPlease disable popup blocker.');

         var div = popup.document.createElement('div');
         div.style.fontSize   = '13px';
         div.style.fontFamily = 'arial,helvetica,sans-serif';
         div.innerHTML        = properties.sort().join('<br>\n');

         popup.document.getElementsByTagName('body')[0].appendChild(div);
         popup.focus();
      }
   }
   else {
      var type = typeof(arg);
      if (type == 'function') type = '';
      else                    type = type.charAt(0).toUpperCase() + type.slice(1);
      alert('showProperties()\n\n'+ (type +' '+ arg +' has no known properties.').trim());
   }
}


/**
 * Log a message to the bottom of the current page or to a log window.
 */
var Logger = {
   div:      null,
   popup:    null,
   popupDiv: null,

   log:     function(/*string*/msg, /*mixed*/target) { Logger.writeln(msg          , target); },
   writeln: function(/*string*/msg, /*mixed*/target) { Logger.write  (msg +'<br>\n', target); },

   write: function(/*string*/msg, /*mixed*/target) {
      if (!target) {
         if (!Logger.div) {
            Logger.div = document.createElement('div');
            Logger.div.setAttribute('id', 'logger');
            Logger.div.style.zIndex          = ''+ (0xFFFFFFFFFFFF+1);
            Logger.div.style.position        = 'absolute';
            Logger.div.style.left            = '10px';
            Logger.div.style.top             = '10px';
            Logger.div.style.padding         = '10px';
            Logger.div.style.textAlign       = 'left';
            Logger.div.style.fontSize        = '13px';
            Logger.div.style.fontFamily      = 'arial,helvetica,sans-serif';
            Logger.div.style.color           = 'black';
            Logger.div.style.backgroundColor = 'lightgray';

            var bodies = document.getElementsByTagName('body');
            if (!bodies || !bodies.length)
               return alert('Logger.write()\n\nError: you can only log from inside the <body> tag !');
            bodies[0].appendChild(Logger.div);
         }
         target = Logger.div;
      }

      if (target === true) {
         if (navigator.userAgent.endsWith('/4.0'))
            return Logger.write(msg);                                // workaround for flawed GreaseMonkey in Firefox 4.0

         if (!Logger.popupDiv) {
            Logger.popup = open('', 'logWindow', 'resizable,scrollbars,width=600,height=400');
            if (!Logger.popup)
               return alert('Logger.write()\n\nCannot open popup for '+ location +'\nPlease disable your popup blocker.');

            Logger.popupDiv = Logger.popup.document.createElement('div');
            Logger.popupDiv.style.fontSize   = '13px';
            Logger.popupDiv.style.fontFamily = 'arial,helvetica,sans-serif';
            Logger.popup.document.getElementsByTagName('body')[0].appendChild(Logger.popupDiv);
         }
         else if (Logger.popup.closed) {
            Logger.popup = Logger.popupDiv = null;
            return Logger.write(msg, target);
         }
         target = Logger.popupDiv;
      }
      target.innerHTML += msg;
   },

   clear: function() {
      if (Logger.div)
         Logger.div.innerHTML = '';
      if (Logger.popup && !Logger.popup.closed && Logger.popupDiv)
         Logger.popupDiv.innerHTML = '';
   }
}
log = Logger.log;


/**
 * Log a message to the status bar.
 */
function logStatus(/*mixed*/msg) {
   if (typeof(msg)=='object' && msg=='[object Event]')
      logEvent(msg);
   else
      self.status = msg;
}


/**
 * Log Event infos to the status bar.
 */
function logEvent(/*Event*/ev) {
   logStatus(ev.type +' event,  window: ['+ (ev.pageX - pageXOffset) +','+ (ev.pageY - pageYOffset) +']  page: ['+ ev.pageX +','+ ev.pageY +']');
}


/**
 * Load a url via GET and pass the response to the specified callback function.
 *
 * @param string   url      - url to load
 * @param function callback - callback function
 */
function loadUrl(/*string*/ url, /*function*/ callback) {                                       // request.readyState = returns the status of the XMLHttpRequest
   var request = new XMLHttpRequest();                                                          //  0: request not initialized
   request.url = url;                                                                           //  1: server connection established
   request.onreadystatechange = function() {                                                    //  2: request received
      if (request.readyState == 4) {                                                            //  3: processing request
         callback(request);                                                                     //  4: request finished and response is ready
      }                                                                                         //
   };                                                                                           // request.status = returns the HTTP status-code
   request.open('GET', url , true);                                                             //  200: "OK"
   request.send(null);                                                                          //  404: "Not Found" etc.
}


/**
 * Load a url via POST and pass the response to the specified callback function.
 *
 * @param string   url      - url to load
 * @param string   data     - content to send in the request's body (i.e. POST parameter)
 * @param object   headers  - additional request header
 * @param function callback - callback function
 */
function postUrl(/*string*/ url, /*string*/ data, /*object*/ headers, /*function*/ callback) {  // request.readyState = returns the status of the XMLHttpRequest
   var request = new XMLHttpRequest();                                                          //  0: request not initialized
   request.url = url;                                                                           //  1: server connection established
   request.onreadystatechange = function() {                                                    //  2: request received
      if (request.readyState == 4) {                                                            //  3: processing request
         callback(request);                                                                     //  4: request finished and response is ready
      }                                                                                         //
   };                                                                                           // request.status = returns the HTTP status-code
   request.open('POST', url , true);                                                            //  200: "OK"
   for (var name in headers) {                                                                  //  404: "Not Found" etc.
      request.setRequestHeader(name, headers[name]);
   }
   request.send(data);
}
