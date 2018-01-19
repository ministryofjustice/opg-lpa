/*!

 handlebars v1.1.2

Copyright (C) 2011 by Yehuda Katz

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

@license
*/
var Handlebars = (function() {
// handlebars/safe-string.js
var __module4__ = (function() {
  "use strict";
  var __exports__;
  // Build out our basic SafeString type
  function SafeString(string) {
    this.string = string;
  }

  SafeString.prototype.toString = function() {
    return "" + this.string;
  };

  __exports__ = SafeString;
  return __exports__;
})();

// handlebars/utils.js
var __module3__ = (function(__dependency1__) {
  "use strict";
  var __exports__ = {};
  var SafeString = __dependency1__;

  var escape = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#x27;",
    "`": "&#x60;"
  };

  var badChars = /[&<>"'`]/g;
  var possible = /[&<>"'`]/;

  function escapeChar(chr) {
    return escape[chr] || "&amp;";
  }

  function extend(obj, value) {
    for(var key in value) {
      if(value.hasOwnProperty(key)) {
        obj[key] = value[key];
      }
    }
  }

  __exports__.extend = extend;var toString = Object.prototype.toString;
  __exports__.toString = toString;
  // Sourced from lodash
  // https://github.com/bestiejs/lodash/blob/master/LICENSE.txt
  var isFunction = function(value) {
    return typeof value === 'function';
  };
  // fallback for older versions of Chrome and Safari
  if (isFunction(/x/)) {
    isFunction = function(value) {
      return typeof value === 'function' && toString.call(value) === '[object Function]';
    };
  }
  var isFunction;
  __exports__.isFunction = isFunction;
  var isArray = Array.isArray || function(value) {
    return (value && typeof value === 'object') ? toString.call(value) === '[object Array]' : false;
  };
  __exports__.isArray = isArray;

  function escapeExpression(string) {
    // don't escape SafeStrings, since they're already safe
    if (string instanceof SafeString) {
      return string.toString();
    } else if (!string && string !== 0) {
      return "";
    }

    // Force a string conversion as this will be done by the append regardless and
    // the regex test will do this transparently behind the scenes, causing issues if
    // an object's to string has escaped characters in it.
    string = "" + string;

    if(!possible.test(string)) { return string; }
    return string.replace(badChars, escapeChar);
  }

  __exports__.escapeExpression = escapeExpression;function isEmpty(value) {
    if (!value && value !== 0) {
      return true;
    } else if (isArray(value) && value.length === 0) {
      return true;
    } else {
      return false;
    }
  }

  __exports__.isEmpty = isEmpty;
  return __exports__;
})(__module4__);

// handlebars/exception.js
var __module5__ = (function() {
  "use strict";
  var __exports__;

  var errorProps = ['description', 'fileName', 'lineNumber', 'message', 'name', 'number', 'stack'];

  function Exception(/* message */) {
    var tmp = Error.prototype.constructor.apply(this, arguments);

    // Unfortunately errors are not enumerable in Chrome (at least), so `for prop in tmp` doesn't work.
    for (var idx = 0; idx < errorProps.length; idx++) {
      this[errorProps[idx]] = tmp[errorProps[idx]];
    }
  }

  Exception.prototype = new Error();

  __exports__ = Exception;
  return __exports__;
})();

// handlebars/base.js
var __module2__ = (function(__dependency1__, __dependency2__) {
  "use strict";
  var __exports__ = {};
  /*globals Exception, Utils */
  var Utils = __dependency1__;
  var Exception = __dependency2__;

  var VERSION = "1.1.2";
  __exports__.VERSION = VERSION;var COMPILER_REVISION = 4;
  __exports__.COMPILER_REVISION = COMPILER_REVISION;
  var REVISION_CHANGES = {
    1: '<= 1.0.rc.2', // 1.0.rc.2 is actually rev2 but doesn't report it
    2: '== 1.0.0-rc.3',
    3: '== 1.0.0-rc.4',
    4: '>= 1.0.0'
  };
  __exports__.REVISION_CHANGES = REVISION_CHANGES;
  var isArray = Utils.isArray,
      isFunction = Utils.isFunction,
      toString = Utils.toString,
      objectType = '[object Object]';

  function HandlebarsEnvironment(helpers, partials) {
    this.helpers = helpers || {};
    this.partials = partials || {};

    registerDefaultHelpers(this);
  }

  __exports__.HandlebarsEnvironment = HandlebarsEnvironment;HandlebarsEnvironment.prototype = {
    constructor: HandlebarsEnvironment,

    logger: logger,
    log: log,

    registerHelper: function(name, fn, inverse) {
      if (toString.call(name) === objectType) {
        if (inverse || fn) { throw new Exception('Arg not supported with multiple helpers'); }
        Utils.extend(this.helpers, name);
      } else {
        if (inverse) { fn.not = inverse; }
        this.helpers[name] = fn;
      }
    },

    registerPartial: function(name, str) {
      if (toString.call(name) === objectType) {
        Utils.extend(this.partials,  name);
      } else {
        this.partials[name] = str;
      }
    }
  };

  function registerDefaultHelpers(instance) {
    instance.registerHelper('helperMissing', function(arg) {
      if(arguments.length === 2) {
        return undefined;
      } else {
        throw new Error("Missing helper: '" + arg + "'");
      }
    });

    instance.registerHelper('blockHelperMissing', function(context, options) {
      var inverse = options.inverse || function() {}, fn = options.fn;

      if (isFunction(context)) { context = context.call(this); }

      if(context === true) {
        return fn(this);
      } else if(context === false || context == null) {
        return inverse(this);
      } else if (isArray(context)) {
        if(context.length > 0) {
          return instance.helpers.each(context, options);
        } else {
          return inverse(this);
        }
      } else {
        return fn(context);
      }
    });

    instance.registerHelper('each', function(context, options) {
      var fn = options.fn, inverse = options.inverse;
      var i = 0, ret = "", data;

      if (isFunction(context)) { context = context.call(this); }

      if (options.data) {
        data = createFrame(options.data);
      }

      if(context && typeof context === 'object') {
        if (isArray(context)) {
          for(var j = context.length; i<j; i++) {
            if (data) {
              data.index = i;
              data.first = (i === 0)
              data.last  = (i === (context.length-1));
            }
            ret = ret + fn(context[i], { data: data });
          }
        } else {
          for(var key in context) {
            if(context.hasOwnProperty(key)) {
              if(data) { data.key = key; }
              ret = ret + fn(context[key], {data: data});
              i++;
            }
          }
        }
      }

      if(i === 0){
        ret = inverse(this);
      }

      return ret;
    });

    instance.registerHelper('if', function(conditional, options) {
      if (isFunction(conditional)) { conditional = conditional.call(this); }

      // Default behavior is to render the positive path if the value is truthy and not empty.
      // The `includeZero` option may be set to treat the condtional as purely not empty based on the
      // behavior of isEmpty. Effectively this determines if 0 is handled by the positive path or negative.
      if ((!options.hash.includeZero && !conditional) || Utils.isEmpty(conditional)) {
        return options.inverse(this);
      } else {
        return options.fn(this);
      }
    });

    instance.registerHelper('unless', function(conditional, options) {
      return instance.helpers['if'].call(this, conditional, {fn: options.inverse, inverse: options.fn, hash: options.hash});
    });

    instance.registerHelper('with', function(context, options) {
      if (isFunction(context)) { context = context.call(this); }

      if (!Utils.isEmpty(context)) return options.fn(context);
    });

    instance.registerHelper('log', function(context, options) {
      var level = options.data && options.data.level != null ? parseInt(options.data.level, 10) : 1;
      instance.log(level, context);
    });
  }

  var logger = {
    methodMap: { 0: 'debug', 1: 'info', 2: 'warn', 3: 'error' },

    // State enum
    DEBUG: 0,
    INFO: 1,
    WARN: 2,
    ERROR: 3,
    level: 3,

    // can be overridden in the host environment
    log: function(level, obj) {
      if (logger.level <= level) {
        var method = logger.methodMap[level];
        if (typeof console !== 'undefined' && console[method]) {
          console[method].call(console, obj);
        }
      }
    }
  };
  __exports__.logger = logger;
  function log(level, obj) { logger.log(level, obj); }

  __exports__.log = log;var createFrame = function(object) {
    var obj = {};
    Utils.extend(obj, object);
    return obj;
  };
  __exports__.createFrame = createFrame;
  return __exports__;
})(__module3__, __module5__);

// handlebars/runtime.js
var __module6__ = (function(__dependency1__, __dependency2__, __dependency3__) {
  "use strict";
  var __exports__ = {};
  /*global Utils */
  var Utils = __dependency1__;
  var Exception = __dependency2__;
  var COMPILER_REVISION = __dependency3__.COMPILER_REVISION;
  var REVISION_CHANGES = __dependency3__.REVISION_CHANGES;

  function checkRevision(compilerInfo) {
    var compilerRevision = compilerInfo && compilerInfo[0] || 1,
        currentRevision = COMPILER_REVISION;

    if (compilerRevision !== currentRevision) {
      if (compilerRevision < currentRevision) {
        var runtimeVersions = REVISION_CHANGES[currentRevision],
            compilerVersions = REVISION_CHANGES[compilerRevision];
        throw new Error("Template was precompiled with an older version of Handlebars than the current runtime. "+
              "Please update your precompiler to a newer version ("+runtimeVersions+") or downgrade your runtime to an older version ("+compilerVersions+").");
      } else {
        // Use the embedded version info since the runtime doesn't know about this revision yet
        throw new Error("Template was precompiled with a newer version of Handlebars than the current runtime. "+
              "Please update your runtime to a newer version ("+compilerInfo[1]+").");
      }
    }
  }

  // TODO: Remove this line and break up compilePartial

  function template(templateSpec, env) {
    if (!env) {
      throw new Error("No environment passed to template");
    }

    var invokePartialWrapper;
    if (env.compile) {
      invokePartialWrapper = function(partial, name, context, helpers, partials, data) {
        // TODO : Check this for all inputs and the options handling (partial flag, etc). This feels
        // like there should be a common exec path
        var result = invokePartial.apply(this, arguments);
        if (result) { return result; }

        var options = { helpers: helpers, partials: partials, data: data };
        partials[name] = env.compile(partial, { data: data !== undefined }, env);
        return partials[name](context, options);
      };
    } else {
      invokePartialWrapper = function(partial, name /* , context, helpers, partials, data */) {
        var result = invokePartial.apply(this, arguments);
        if (result) { return result; }
        throw new Exception("The partial " + name + " could not be compiled when running in runtime-only mode");
      };
    }

    // Just add water
    var container = {
      escapeExpression: Utils.escapeExpression,
      invokePartial: invokePartialWrapper,
      programs: [],
      program: function(i, fn, data) {
        var programWrapper = this.programs[i];
        if(data) {
          programWrapper = program(i, fn, data);
        } else if (!programWrapper) {
          programWrapper = this.programs[i] = program(i, fn);
        }
        return programWrapper;
      },
      merge: function(param, common) {
        var ret = param || common;

        if (param && common && (param !== common)) {
          ret = {};
          Utils.extend(ret, common);
          Utils.extend(ret, param);
        }
        return ret;
      },
      programWithDepth: programWithDepth,
      noop: noop,
      compilerInfo: null
    };

    return function(context, options) {
      options = options || {};
      var namespace = options.partial ? options : env,
          helpers,
          partials;

      if (!options.partial) {
        helpers = options.helpers;
        partials = options.partials;
      }
      var result = templateSpec.call(
            container,
            namespace, context,
            helpers,
            partials,
            options.data);

      if (!options.partial) {
        checkRevision(container.compilerInfo);
      }

      return result;
    };
  }

  __exports__.template = template;function programWithDepth(i, fn, data /*, $depth */) {
    var args = Array.prototype.slice.call(arguments, 3);

    var prog = function(context, options) {
      options = options || {};

      return fn.apply(this, [context, options.data || data].concat(args));
    };
    prog.program = i;
    prog.depth = args.length;
    return prog;
  }

  __exports__.programWithDepth = programWithDepth;function program(i, fn, data) {
    var prog = function(context, options) {
      options = options || {};

      return fn(context, options.data || data);
    };
    prog.program = i;
    prog.depth = 0;
    return prog;
  }

  __exports__.program = program;function invokePartial(partial, name, context, helpers, partials, data) {
    var options = { partial: true, helpers: helpers, partials: partials, data: data };

    if(partial === undefined) {
      throw new Exception("The partial " + name + " could not be found");
    } else if(partial instanceof Function) {
      return partial(context, options);
    }
  }

  __exports__.invokePartial = invokePartial;function noop() { return ""; }

  __exports__.noop = noop;
  return __exports__;
})(__module3__, __module5__, __module2__);

// handlebars.runtime.js
var __module1__ = (function(__dependency1__, __dependency2__, __dependency3__, __dependency4__, __dependency5__) {
  "use strict";
  var __exports__;
  var base = __dependency1__;

  // Each of these augment the Handlebars object. No need to setup here.
  // (This is done to easily share code between commonjs and browse envs)
  var SafeString = __dependency2__;
  var Exception = __dependency3__;
  var Utils = __dependency4__;
  var runtime = __dependency5__;

  // For compatibility and usage outside of module systems, make the Handlebars object a namespace
  var create = function() {
    var hb = new base.HandlebarsEnvironment();

    Utils.extend(hb, base);
    hb.SafeString = SafeString;
    hb.Exception = Exception;
    hb.Utils = Utils;

    hb.VM = runtime;
    hb.template = function(spec) {
      return runtime.template(spec, hb);
    };

    return hb;
  };

  var Handlebars = create();
  Handlebars.create = create;

  __exports__ = Handlebars;
  return __exports__;
})(__module2__, __module4__, __module5__, __module3__, __module6__);

// handlebars/compiler/ast.js
var __module7__ = (function(__dependency1__) {
  "use strict";
  var __exports__ = {};
  var Exception = __dependency1__;

  function ProgramNode(statements, inverseStrip, inverse) {
    this.type = "program";
    this.statements = statements;
    this.strip = {};

    if(inverse) {
      this.inverse = new ProgramNode(inverse, inverseStrip);
      this.strip.right = inverseStrip.left;
    } else if (inverseStrip) {
      this.strip.left = inverseStrip.right;
    }
  }

  __exports__.ProgramNode = ProgramNode;function MustacheNode(rawParams, hash, open, strip) {
    this.type = "mustache";
    this.hash = hash;
    this.strip = strip;

    var escapeFlag = open[3] || open[2];
    this.escaped = escapeFlag !== '{' && escapeFlag !== '&';

    var id = this.id = rawParams[0];
    var params = this.params = rawParams.slice(1);

    // a mustache is an eligible helper if:
    // * its id is simple (a single part, not `this` or `..`)
    var eligibleHelper = this.eligibleHelper = id.isSimple;

    // a mustache is definitely a helper if:
    // * it is an eligible helper, and
    // * it has at least one parameter or hash segment
    this.isHelper = eligibleHelper && (params.length || hash);

    // if a mustache is an eligible helper but not a definite
    // helper, it is ambiguous, and will be resolved in a later
    // pass or at runtime.
  }

  __exports__.MustacheNode = MustacheNode;function PartialNode(partialName, context, strip) {
    this.type         = "partial";
    this.partialName  = partialName;
    this.context      = context;
    this.strip = strip;
  }

  __exports__.PartialNode = PartialNode;function BlockNode(mustache, program, inverse, close) {
    if(mustache.id.original !== close.path.original) {
      throw new Exception(mustache.id.original + " doesn't match " + close.path.original);
    }

    this.type = "block";
    this.mustache = mustache;
    this.program  = program;
    this.inverse  = inverse;

    this.strip = {
      left: mustache.strip.left,
      right: close.strip.right
    };

    (program || inverse).strip.left = mustache.strip.right;
    (inverse || program).strip.right = close.strip.left;

    if (inverse && !program) {
      this.isInverse = true;
    }
  }

  __exports__.BlockNode = BlockNode;function ContentNode(string) {
    this.type = "content";
    this.string = string;
  }

  __exports__.ContentNode = ContentNode;function HashNode(pairs) {
    this.type = "hash";
    this.pairs = pairs;
  }

  __exports__.HashNode = HashNode;function IdNode(parts) {
    this.type = "ID";

    var original = "",
        dig = [],
        depth = 0;

    for(var i=0,l=parts.length; i<l; i++) {
      var part = parts[i].part;
      original += (parts[i].separator || '') + part;

      if (part === ".." || part === "." || part === "this") {
        if (dig.length > 0) { throw new Exception("Invalid path: " + original); }
        else if (part === "..") { depth++; }
        else { this.isScoped = true; }
      }
      else { dig.push(part); }
    }

    this.original = original;
    this.parts    = dig;
    this.string   = dig.join('.');
    this.depth    = depth;

    // an ID is simple if it only has one part, and that part is not
    // `..` or `this`.
    this.isSimple = parts.length === 1 && !this.isScoped && depth === 0;

    this.stringModeValue = this.string;
  }

  __exports__.IdNode = IdNode;function PartialNameNode(name) {
    this.type = "PARTIAL_NAME";
    this.name = name.original;
  }

  __exports__.PartialNameNode = PartialNameNode;function DataNode(id) {
    this.type = "DATA";
    this.id = id;
  }

  __exports__.DataNode = DataNode;function StringNode(string) {
    this.type = "STRING";
    this.original =
      this.string =
      this.stringModeValue = string;
  }

  __exports__.StringNode = StringNode;function IntegerNode(integer) {
    this.type = "INTEGER";
    this.original =
      this.integer = integer;
    this.stringModeValue = Number(integer);
  }

  __exports__.IntegerNode = IntegerNode;function BooleanNode(bool) {
    this.type = "BOOLEAN";
    this.bool = bool;
    this.stringModeValue = bool === "true";
  }

  __exports__.BooleanNode = BooleanNode;function CommentNode(comment) {
    this.type = "comment";
    this.comment = comment;
  }

  __exports__.CommentNode = CommentNode;
  return __exports__;
})(__module5__);

// handlebars/compiler/parser.js
var __module9__ = (function() {
  "use strict";
  var __exports__;
  /* Jison generated parser */
  var handlebars = (function(){
  var parser = {trace: function trace() { },
  yy: {},
  symbols_: {"error":2,"root":3,"statements":4,"EOF":5,"program":6,"simpleInverse":7,"statement":8,"openInverse":9,"closeBlock":10,"openBlock":11,"mustache":12,"partial":13,"CONTENT":14,"COMMENT":15,"OPEN_BLOCK":16,"inMustache":17,"CLOSE":18,"OPEN_INVERSE":19,"OPEN_ENDBLOCK":20,"path":21,"OPEN":22,"OPEN_UNESCAPED":23,"CLOSE_UNESCAPED":24,"OPEN_PARTIAL":25,"partialName":26,"partial_option0":27,"inMustache_repetition0":28,"inMustache_option0":29,"dataName":30,"param":31,"STRING":32,"INTEGER":33,"BOOLEAN":34,"hash":35,"hash_repetition_plus0":36,"hashSegment":37,"ID":38,"EQUALS":39,"DATA":40,"pathSegments":41,"SEP":42,"$accept":0,"$end":1},
  terminals_: {2:"error",5:"EOF",14:"CONTENT",15:"COMMENT",16:"OPEN_BLOCK",18:"CLOSE",19:"OPEN_INVERSE",20:"OPEN_ENDBLOCK",22:"OPEN",23:"OPEN_UNESCAPED",24:"CLOSE_UNESCAPED",25:"OPEN_PARTIAL",32:"STRING",33:"INTEGER",34:"BOOLEAN",38:"ID",39:"EQUALS",40:"DATA",42:"SEP"},
  productions_: [0,[3,2],[3,1],[6,2],[6,3],[6,2],[6,1],[6,1],[6,0],[4,1],[4,2],[8,3],[8,3],[8,1],[8,1],[8,1],[8,1],[11,3],[9,3],[10,3],[12,3],[12,3],[13,4],[7,2],[17,3],[17,1],[31,1],[31,1],[31,1],[31,1],[31,1],[35,1],[37,3],[26,1],[26,1],[26,1],[30,2],[21,1],[41,3],[41,1],[27,0],[27,1],[28,0],[28,2],[29,0],[29,1],[36,1],[36,2]],
  performAction: function anonymous(yytext,yyleng,yylineno,yy,yystate,$$,_$) {

  var $0 = $$.length - 1;
  switch (yystate) {
  case 1: return new yy.ProgramNode($$[$0-1]);
  break;
  case 2: return new yy.ProgramNode([]);
  break;
  case 3:this.$ = new yy.ProgramNode([], $$[$0-1], $$[$0]);
  break;
  case 4:this.$ = new yy.ProgramNode($$[$0-2], $$[$0-1], $$[$0]);
  break;
  case 5:this.$ = new yy.ProgramNode($$[$0-1], $$[$0], []);
  break;
  case 6:this.$ = new yy.ProgramNode($$[$0]);
  break;
  case 7:this.$ = new yy.ProgramNode([]);
  break;
  case 8:this.$ = new yy.ProgramNode([]);
  break;
  case 9:this.$ = [$$[$0]];
  break;
  case 10: $$[$0-1].push($$[$0]); this.$ = $$[$0-1];
  break;
  case 11:this.$ = new yy.BlockNode($$[$0-2], $$[$0-1].inverse, $$[$0-1], $$[$0]);
  break;
  case 12:this.$ = new yy.BlockNode($$[$0-2], $$[$0-1], $$[$0-1].inverse, $$[$0]);
  break;
  case 13:this.$ = $$[$0];
  break;
  case 14:this.$ = $$[$0];
  break;
  case 15:this.$ = new yy.ContentNode($$[$0]);
  break;
  case 16:this.$ = new yy.CommentNode($$[$0]);
  break;
  case 17:this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1], $$[$0-2], stripFlags($$[$0-2], $$[$0]));
  break;
  case 18:this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1], $$[$0-2], stripFlags($$[$0-2], $$[$0]));
  break;
  case 19:this.$ = {path: $$[$0-1], strip: stripFlags($$[$0-2], $$[$0])};
  break;
  case 20:this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1], $$[$0-2], stripFlags($$[$0-2], $$[$0]));
  break;
  case 21:this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1], $$[$0-2], stripFlags($$[$0-2], $$[$0]));
  break;
  case 22:this.$ = new yy.PartialNode($$[$0-2], $$[$0-1], stripFlags($$[$0-3], $$[$0]));
  break;
  case 23:this.$ = stripFlags($$[$0-1], $$[$0]);
  break;
  case 24:this.$ = [[$$[$0-2]].concat($$[$0-1]), $$[$0]];
  break;
  case 25:this.$ = [[$$[$0]], null];
  break;
  case 26:this.$ = $$[$0];
  break;
  case 27:this.$ = new yy.StringNode($$[$0]);
  break;
  case 28:this.$ = new yy.IntegerNode($$[$0]);
  break;
  case 29:this.$ = new yy.BooleanNode($$[$0]);
  break;
  case 30:this.$ = $$[$0];
  break;
  case 31:this.$ = new yy.HashNode($$[$0]);
  break;
  case 32:this.$ = [$$[$0-2], $$[$0]];
  break;
  case 33:this.$ = new yy.PartialNameNode($$[$0]);
  break;
  case 34:this.$ = new yy.PartialNameNode(new yy.StringNode($$[$0]));
  break;
  case 35:this.$ = new yy.PartialNameNode(new yy.IntegerNode($$[$0]));
  break;
  case 36:this.$ = new yy.DataNode($$[$0]);
  break;
  case 37:this.$ = new yy.IdNode($$[$0]);
  break;
  case 38: $$[$0-2].push({part: $$[$0], separator: $$[$0-1]}); this.$ = $$[$0-2];
  break;
  case 39:this.$ = [{part: $$[$0]}];
  break;
  case 42:this.$ = [];
  break;
  case 43:$$[$0-1].push($$[$0]);
  break;
  case 46:this.$ = [$$[$0]];
  break;
  case 47:$$[$0-1].push($$[$0]);
  break;
  }
  },
  table: [{3:1,4:2,5:[1,3],8:4,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,11],22:[1,13],23:[1,14],25:[1,15]},{1:[3]},{5:[1,16],8:17,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,11],22:[1,13],23:[1,14],25:[1,15]},{1:[2,2]},{5:[2,9],14:[2,9],15:[2,9],16:[2,9],19:[2,9],20:[2,9],22:[2,9],23:[2,9],25:[2,9]},{4:20,6:18,7:19,8:4,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,21],20:[2,8],22:[1,13],23:[1,14],25:[1,15]},{4:20,6:22,7:19,8:4,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,21],20:[2,8],22:[1,13],23:[1,14],25:[1,15]},{5:[2,13],14:[2,13],15:[2,13],16:[2,13],19:[2,13],20:[2,13],22:[2,13],23:[2,13],25:[2,13]},{5:[2,14],14:[2,14],15:[2,14],16:[2,14],19:[2,14],20:[2,14],22:[2,14],23:[2,14],25:[2,14]},{5:[2,15],14:[2,15],15:[2,15],16:[2,15],19:[2,15],20:[2,15],22:[2,15],23:[2,15],25:[2,15]},{5:[2,16],14:[2,16],15:[2,16],16:[2,16],19:[2,16],20:[2,16],22:[2,16],23:[2,16],25:[2,16]},{17:23,21:24,30:25,38:[1,28],40:[1,27],41:26},{17:29,21:24,30:25,38:[1,28],40:[1,27],41:26},{17:30,21:24,30:25,38:[1,28],40:[1,27],41:26},{17:31,21:24,30:25,38:[1,28],40:[1,27],41:26},{21:33,26:32,32:[1,34],33:[1,35],38:[1,28],41:26},{1:[2,1]},{5:[2,10],14:[2,10],15:[2,10],16:[2,10],19:[2,10],20:[2,10],22:[2,10],23:[2,10],25:[2,10]},{10:36,20:[1,37]},{4:38,8:4,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,11],20:[2,7],22:[1,13],23:[1,14],25:[1,15]},{7:39,8:17,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,21],20:[2,6],22:[1,13],23:[1,14],25:[1,15]},{17:23,18:[1,40],21:24,30:25,38:[1,28],40:[1,27],41:26},{10:41,20:[1,37]},{18:[1,42]},{18:[2,42],24:[2,42],28:43,32:[2,42],33:[2,42],34:[2,42],38:[2,42],40:[2,42]},{18:[2,25],24:[2,25]},{18:[2,37],24:[2,37],32:[2,37],33:[2,37],34:[2,37],38:[2,37],40:[2,37],42:[1,44]},{21:45,38:[1,28],41:26},{18:[2,39],24:[2,39],32:[2,39],33:[2,39],34:[2,39],38:[2,39],40:[2,39],42:[2,39]},{18:[1,46]},{18:[1,47]},{24:[1,48]},{18:[2,40],21:50,27:49,38:[1,28],41:26},{18:[2,33],38:[2,33]},{18:[2,34],38:[2,34]},{18:[2,35],38:[2,35]},{5:[2,11],14:[2,11],15:[2,11],16:[2,11],19:[2,11],20:[2,11],22:[2,11],23:[2,11],25:[2,11]},{21:51,38:[1,28],41:26},{8:17,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,11],20:[2,3],22:[1,13],23:[1,14],25:[1,15]},{4:52,8:4,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,11],20:[2,5],22:[1,13],23:[1,14],25:[1,15]},{14:[2,23],15:[2,23],16:[2,23],19:[2,23],20:[2,23],22:[2,23],23:[2,23],25:[2,23]},{5:[2,12],14:[2,12],15:[2,12],16:[2,12],19:[2,12],20:[2,12],22:[2,12],23:[2,12],25:[2,12]},{14:[2,18],15:[2,18],16:[2,18],19:[2,18],20:[2,18],22:[2,18],23:[2,18],25:[2,18]},{18:[2,44],21:56,24:[2,44],29:53,30:60,31:54,32:[1,57],33:[1,58],34:[1,59],35:55,36:61,37:62,38:[1,63],40:[1,27],41:26},{38:[1,64]},{18:[2,36],24:[2,36],32:[2,36],33:[2,36],34:[2,36],38:[2,36],40:[2,36]},{14:[2,17],15:[2,17],16:[2,17],19:[2,17],20:[2,17],22:[2,17],23:[2,17],25:[2,17]},{5:[2,20],14:[2,20],15:[2,20],16:[2,20],19:[2,20],20:[2,20],22:[2,20],23:[2,20],25:[2,20]},{5:[2,21],14:[2,21],15:[2,21],16:[2,21],19:[2,21],20:[2,21],22:[2,21],23:[2,21],25:[2,21]},{18:[1,65]},{18:[2,41]},{18:[1,66]},{8:17,9:5,11:6,12:7,13:8,14:[1,9],15:[1,10],16:[1,12],19:[1,11],20:[2,4],22:[1,13],23:[1,14],25:[1,15]},{18:[2,24],24:[2,24]},{18:[2,43],24:[2,43],32:[2,43],33:[2,43],34:[2,43],38:[2,43],40:[2,43]},{18:[2,45],24:[2,45]},{18:[2,26],24:[2,26],32:[2,26],33:[2,26],34:[2,26],38:[2,26],40:[2,26]},{18:[2,27],24:[2,27],32:[2,27],33:[2,27],34:[2,27],38:[2,27],40:[2,27]},{18:[2,28],24:[2,28],32:[2,28],33:[2,28],34:[2,28],38:[2,28],40:[2,28]},{18:[2,29],24:[2,29],32:[2,29],33:[2,29],34:[2,29],38:[2,29],40:[2,29]},{18:[2,30],24:[2,30],32:[2,30],33:[2,30],34:[2,30],38:[2,30],40:[2,30]},{18:[2,31],24:[2,31],37:67,38:[1,68]},{18:[2,46],24:[2,46],38:[2,46]},{18:[2,39],24:[2,39],32:[2,39],33:[2,39],34:[2,39],38:[2,39],39:[1,69],40:[2,39],42:[2,39]},{18:[2,38],24:[2,38],32:[2,38],33:[2,38],34:[2,38],38:[2,38],40:[2,38],42:[2,38]},{5:[2,22],14:[2,22],15:[2,22],16:[2,22],19:[2,22],20:[2,22],22:[2,22],23:[2,22],25:[2,22]},{5:[2,19],14:[2,19],15:[2,19],16:[2,19],19:[2,19],20:[2,19],22:[2,19],23:[2,19],25:[2,19]},{18:[2,47],24:[2,47],38:[2,47]},{39:[1,69]},{21:56,30:60,31:70,32:[1,57],33:[1,58],34:[1,59],38:[1,28],40:[1,27],41:26},{18:[2,32],24:[2,32],38:[2,32]}],
  defaultActions: {3:[2,2],16:[2,1],50:[2,41]},
  parseError: function parseError(str, hash) {
      throw new Error(str);
  },
  parse: function parse(input) {
      var self = this, stack = [0], vstack = [null], lstack = [], table = this.table, yytext = "", yylineno = 0, yyleng = 0, recovering = 0, TERROR = 2, EOF = 1;
      this.lexer.setInput(input);
      this.lexer.yy = this.yy;
      this.yy.lexer = this.lexer;
      this.yy.parser = this;
      if (typeof this.lexer.yylloc == "undefined")
          this.lexer.yylloc = {};
      var yyloc = this.lexer.yylloc;
      lstack.push(yyloc);
      var ranges = this.lexer.options && this.lexer.options.ranges;
      if (typeof this.yy.parseError === "function")
          this.parseError = this.yy.parseError;
      function popStack(n) {
          stack.length = stack.length - 2 * n;
          vstack.length = vstack.length - n;
          lstack.length = lstack.length - n;
      }
      function lex() {
          var token;
          token = self.lexer.lex() || 1;
          if (typeof token !== "number") {
              token = self.symbols_[token] || token;
          }
          return token;
      }
      var symbol, preErrorSymbol, state, action, a, r, yyval = {}, p, len, newState, expected;
      while (true) {
          state = stack[stack.length - 1];
          if (this.defaultActions[state]) {
              action = this.defaultActions[state];
          } else {
              if (symbol === null || typeof symbol == "undefined") {
                  symbol = lex();
              }
              action = table[state] && table[state][symbol];
          }
          if (typeof action === "undefined" || !action.length || !action[0]) {
              var errStr = "";
              if (!recovering) {
                  expected = [];
                  for (p in table[state])
                      if (this.terminals_[p] && p > 2) {
                          expected.push("'" + this.terminals_[p] + "'");
                      }
                  if (this.lexer.showPosition) {
                      errStr = "Parse error on line " + (yylineno + 1) + ":\n" + this.lexer.showPosition() + "\nExpecting " + expected.join(", ") + ", got '" + (this.terminals_[symbol] || symbol) + "'";
                  } else {
                      errStr = "Parse error on line " + (yylineno + 1) + ": Unexpected " + (symbol == 1?"end of input":"'" + (this.terminals_[symbol] || symbol) + "'");
                  }
                  this.parseError(errStr, {text: this.lexer.match, token: this.terminals_[symbol] || symbol, line: this.lexer.yylineno, loc: yyloc, expected: expected});
              }
          }
          if (action[0] instanceof Array && action.length > 1) {
              throw new Error("Parse Error: multiple actions possible at state: " + state + ", token: " + symbol);
          }
          switch (action[0]) {
          case 1:
              stack.push(symbol);
              vstack.push(this.lexer.yytext);
              lstack.push(this.lexer.yylloc);
              stack.push(action[1]);
              symbol = null;
              if (!preErrorSymbol) {
                  yyleng = this.lexer.yyleng;
                  yytext = this.lexer.yytext;
                  yylineno = this.lexer.yylineno;
                  yyloc = this.lexer.yylloc;
                  if (recovering > 0)
                      recovering--;
              } else {
                  symbol = preErrorSymbol;
                  preErrorSymbol = null;
              }
              break;
          case 2:
              len = this.productions_[action[1]][1];
              yyval.$ = vstack[vstack.length - len];
              yyval._$ = {first_line: lstack[lstack.length - (len || 1)].first_line, last_line: lstack[lstack.length - 1].last_line, first_column: lstack[lstack.length - (len || 1)].first_column, last_column: lstack[lstack.length - 1].last_column};
              if (ranges) {
                  yyval._$.range = [lstack[lstack.length - (len || 1)].range[0], lstack[lstack.length - 1].range[1]];
              }
              r = this.performAction.call(yyval, yytext, yyleng, yylineno, this.yy, action[1], vstack, lstack);
              if (typeof r !== "undefined") {
                  return r;
              }
              if (len) {
                  stack = stack.slice(0, -1 * len * 2);
                  vstack = vstack.slice(0, -1 * len);
                  lstack = lstack.slice(0, -1 * len);
              }
              stack.push(this.productions_[action[1]][0]);
              vstack.push(yyval.$);
              lstack.push(yyval._$);
              newState = table[stack[stack.length - 2]][stack[stack.length - 1]];
              stack.push(newState);
              break;
          case 3:
              return true;
          }
      }
      return true;
  }
  };


  function stripFlags(open, close) {
    return {
      left: open[2] === '~',
      right: close[0] === '~' || close[1] === '~'
    };
  }

  /* Jison generated lexer */
  var lexer = (function(){
  var lexer = ({EOF:1,
  parseError:function parseError(str, hash) {
          if (this.yy.parser) {
              this.yy.parser.parseError(str, hash);
          } else {
              throw new Error(str);
          }
      },
  setInput:function (input) {
          this._input = input;
          this._more = this._less = this.done = false;
          this.yylineno = this.yyleng = 0;
          this.yytext = this.matched = this.match = '';
          this.conditionStack = ['INITIAL'];
          this.yylloc = {first_line:1,first_column:0,last_line:1,last_column:0};
          if (this.options.ranges) this.yylloc.range = [0,0];
          this.offset = 0;
          return this;
      },
  input:function () {
          var ch = this._input[0];
          this.yytext += ch;
          this.yyleng++;
          this.offset++;
          this.match += ch;
          this.matched += ch;
          var lines = ch.match(/(?:\r\n?|\n).*/g);
          if (lines) {
              this.yylineno++;
              this.yylloc.last_line++;
          } else {
              this.yylloc.last_column++;
          }
          if (this.options.ranges) this.yylloc.range[1]++;

          this._input = this._input.slice(1);
          return ch;
      },
  unput:function (ch) {
          var len = ch.length;
          var lines = ch.split(/(?:\r\n?|\n)/g);

          this._input = ch + this._input;
          this.yytext = this.yytext.substr(0, this.yytext.length-len-1);
          //this.yyleng -= len;
          this.offset -= len;
          var oldLines = this.match.split(/(?:\r\n?|\n)/g);
          this.match = this.match.substr(0, this.match.length-1);
          this.matched = this.matched.substr(0, this.matched.length-1);

          if (lines.length-1) this.yylineno -= lines.length-1;
          var r = this.yylloc.range;

          this.yylloc = {first_line: this.yylloc.first_line,
            last_line: this.yylineno+1,
            first_column: this.yylloc.first_column,
            last_column: lines ?
                (lines.length === oldLines.length ? this.yylloc.first_column : 0) + oldLines[oldLines.length - lines.length].length - lines[0].length:
                this.yylloc.first_column - len
            };

          if (this.options.ranges) {
              this.yylloc.range = [r[0], r[0] + this.yyleng - len];
          }
          return this;
      },
  more:function () {
          this._more = true;
          return this;
      },
  less:function (n) {
          this.unput(this.match.slice(n));
      },
  pastInput:function () {
          var past = this.matched.substr(0, this.matched.length - this.match.length);
          return (past.length > 20 ? '...':'') + past.substr(-20).replace(/\n/g, "");
      },
  upcomingInput:function () {
          var next = this.match;
          if (next.length < 20) {
              next += this._input.substr(0, 20-next.length);
          }
          return (next.substr(0,20)+(next.length > 20 ? '...':'')).replace(/\n/g, "");
      },
  showPosition:function () {
          var pre = this.pastInput();
          var c = new Array(pre.length + 1).join("-");
          return pre + this.upcomingInput() + "\n" + c+"^";
      },
  next:function () {
          if (this.done) {
              return this.EOF;
          }
          if (!this._input) this.done = true;

          var token,
              match,
              tempMatch,
              index,
              col,
              lines;
          if (!this._more) {
              this.yytext = '';
              this.match = '';
          }
          var rules = this._currentRules();
          for (var i=0;i < rules.length; i++) {
              tempMatch = this._input.match(this.rules[rules[i]]);
              if (tempMatch && (!match || tempMatch[0].length > match[0].length)) {
                  match = tempMatch;
                  index = i;
                  if (!this.options.flex) break;
              }
          }
          if (match) {
              lines = match[0].match(/(?:\r\n?|\n).*/g);
              if (lines) this.yylineno += lines.length;
              this.yylloc = {first_line: this.yylloc.last_line,
                             last_line: this.yylineno+1,
                             first_column: this.yylloc.last_column,
                             last_column: lines ? lines[lines.length-1].length-lines[lines.length-1].match(/\r?\n?/)[0].length : this.yylloc.last_column + match[0].length};
              this.yytext += match[0];
              this.match += match[0];
              this.matches = match;
              this.yyleng = this.yytext.length;
              if (this.options.ranges) {
                  this.yylloc.range = [this.offset, this.offset += this.yyleng];
              }
              this._more = false;
              this._input = this._input.slice(match[0].length);
              this.matched += match[0];
              token = this.performAction.call(this, this.yy, this, rules[index],this.conditionStack[this.conditionStack.length-1]);
              if (this.done && this._input) this.done = false;
              if (token) return token;
              else return;
          }
          if (this._input === "") {
              return this.EOF;
          } else {
              return this.parseError('Lexical error on line '+(this.yylineno+1)+'. Unrecognized text.\n'+this.showPosition(),
                      {text: "", token: null, line: this.yylineno});
          }
      },
  lex:function lex() {
          var r = this.next();
          if (typeof r !== 'undefined') {
              return r;
          } else {
              return this.lex();
          }
      },
  begin:function begin(condition) {
          this.conditionStack.push(condition);
      },
  popState:function popState() {
          return this.conditionStack.pop();
      },
  _currentRules:function _currentRules() {
          return this.conditions[this.conditionStack[this.conditionStack.length-1]].rules;
      },
  topState:function () {
          return this.conditionStack[this.conditionStack.length-2];
      },
  pushState:function begin(condition) {
          this.begin(condition);
      }});
  lexer.options = {};
  lexer.performAction = function anonymous(yy,yy_,$avoiding_name_collisions,YY_START) {


  function strip(start, end) {
    return yy_.yytext = yy_.yytext.substr(start, yy_.yyleng-end);
  }


  var YYSTATE=YY_START
  switch($avoiding_name_collisions) {
  case 0:
                                     if(yy_.yytext.slice(-2) === "\\\\") {
                                       strip(0,1);
                                       this.begin("mu");
                                     } else if(yy_.yytext.slice(-1) === "\\") {
                                       strip(0,1);
                                       this.begin("emu");
                                     } else {
                                       this.begin("mu");
                                     }
                                     if(yy_.yytext) return 14;

  break;
  case 1:return 14;
  break;
  case 2:
                                     if(yy_.yytext.slice(-1) !== "\\") this.popState();
                                     if(yy_.yytext.slice(-1) === "\\") strip(0,1);
                                     return 14;

  break;
  case 3:strip(0,4); this.popState(); return 15;
  break;
  case 4:return 25;
  break;
  case 5:return 16;
  break;
  case 6:return 20;
  break;
  case 7:return 19;
  break;
  case 8:return 19;
  break;
  case 9:return 23;
  break;
  case 10:return 22;
  break;
  case 11:this.popState(); this.begin('com');
  break;
  case 12:strip(3,5); this.popState(); return 15;
  break;
  case 13:return 22;
  break;
  case 14:return 39;
  break;
  case 15:return 38;
  break;
  case 16:return 38;
  break;
  case 17:return 42;
  break;
  case 18:/*ignore whitespace*/
  break;
  case 19:this.popState(); return 24;
  break;
  case 20:this.popState(); return 18;
  break;
  case 21:yy_.yytext = strip(1,2).replace(/\\"/g,'"'); return 32;
  break;
  case 22:yy_.yytext = strip(1,2).replace(/\\'/g,"'"); return 32;
  break;
  case 23:return 40;
  break;
  case 24:return 34;
  break;
  case 25:return 34;
  break;
  case 26:return 33;
  break;
  case 27:return 38;
  break;
  case 28:yy_.yytext = strip(1,2); return 38;
  break;
  case 29:return 'INVALID';
  break;
  case 30:return 5;
  break;
  }
  };
  lexer.rules = [/^(?:[^\x00]*?(?=(\{\{)))/,/^(?:[^\x00]+)/,/^(?:[^\x00]{2,}?(?=(\{\{|$)))/,/^(?:[\s\S]*?--\}\})/,/^(?:\{\{(~)?>)/,/^(?:\{\{(~)?#)/,/^(?:\{\{(~)?\/)/,/^(?:\{\{(~)?\^)/,/^(?:\{\{(~)?\s*else\b)/,/^(?:\{\{(~)?\{)/,/^(?:\{\{(~)?&)/,/^(?:\{\{!--)/,/^(?:\{\{![\s\S]*?\}\})/,/^(?:\{\{(~)?)/,/^(?:=)/,/^(?:\.\.)/,/^(?:\.(?=([=~}\s\/.])))/,/^(?:[\/.])/,/^(?:\s+)/,/^(?:\}(~)?\}\})/,/^(?:(~)?\}\})/,/^(?:"(\\["]|[^"])*")/,/^(?:'(\\[']|[^'])*')/,/^(?:@)/,/^(?:true(?=([~}\s])))/,/^(?:false(?=([~}\s])))/,/^(?:-?[0-9]+(?=([~}\s])))/,/^(?:([^\s!"#%-,\.\/;->@\[-\^`\{-~]+(?=([=~}\s\/.]))))/,/^(?:\[[^\]]*\])/,/^(?:.)/,/^(?:$)/];
  lexer.conditions = {"mu":{"rules":[4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30],"inclusive":false},"emu":{"rules":[2],"inclusive":false},"com":{"rules":[3],"inclusive":false},"INITIAL":{"rules":[0,1,30],"inclusive":true}};
  return lexer;})()
  parser.lexer = lexer;
  function Parser () { this.yy = {}; }Parser.prototype = parser;parser.Parser = Parser;
  return new Parser;
  })();__exports__ = handlebars;
  return __exports__;
})();

// handlebars/compiler/base.js
var __module8__ = (function(__dependency1__, __dependency2__) {
  "use strict";
  var __exports__ = {};
  var parser = __dependency1__;
  var AST = __dependency2__;

  __exports__.parser = parser;

  function parse(input) {
    // Just return if an already-compile AST was passed in.
    if(input.constructor === AST.ProgramNode) { return input; }

    parser.yy = AST;
    return parser.parse(input);
  }

  __exports__.parse = parse;
  return __exports__;
})(__module9__, __module7__);

// handlebars/compiler/javascript-compiler.js
var __module11__ = (function(__dependency1__) {
  "use strict";
  var __exports__;
  var COMPILER_REVISION = __dependency1__.COMPILER_REVISION;
  var REVISION_CHANGES = __dependency1__.REVISION_CHANGES;
  var log = __dependency1__.log;

  function Literal(value) {
    this.value = value;
  }

  function JavaScriptCompiler() {}

  JavaScriptCompiler.prototype = {
    // PUBLIC API: You can override these methods in a subclass to provide
    // alternative compiled forms for name lookup and buffering semantics
    nameLookup: function(parent, name /* , type*/) {
      var wrap,
          ret;
      if (parent.indexOf('depth') === 0) {
        wrap = true;
      }

      if (/^[0-9]+$/.test(name)) {
        ret = parent + "[" + name + "]";
      } else if (JavaScriptCompiler.isValidJavaScriptVariableName(name)) {
        ret = parent + "." + name;
      }
      else {
        ret = parent + "['" + name + "']";
      }

      if (wrap) {
        return '(' + parent + ' && ' + ret + ')';
      } else {
        return ret;
      }
    },

    appendToBuffer: function(string) {
      if (this.environment.isSimple) {
        return "return " + string + ";";
      } else {
        return {
          appendToBuffer: true,
          content: string,
          toString: function() { return "buffer += " + string + ";"; }
        };
      }
    },

    initializeBuffer: function() {
      return this.quotedString("");
    },

    namespace: "Handlebars",
    // END PUBLIC API

    compile: function(environment, options, context, asObject) {
      this.environment = environment;
      this.options = options || {};

      log('debug', this.environment.disassemble() + "\n\n");

      this.name = this.environment.name;
      this.isChild = !!context;
      this.context = context || {
        programs: [],
        environments: [],
        aliases: { }
      };

      this.preamble();

      this.stackSlot = 0;
      this.stackVars = [];
      this.registers = { list: [] };
      this.compileStack = [];
      this.inlineStack = [];

      this.compileChildren(environment, options);

      var opcodes = environment.opcodes, opcode;

      this.i = 0;

      for(var l=opcodes.length; this.i<l; this.i++) {
        opcode = opcodes[this.i];

        if(opcode.opcode === 'DECLARE') {
          this[opcode.name] = opcode.value;
        } else {
          this[opcode.opcode].apply(this, opcode.args);
        }

        // Reset the stripNext flag if it was not set by this operation.
        if (opcode.opcode !== this.stripNext) {
          this.stripNext = false;
        }
      }

      // Flush any trailing content that might be pending.
      this.pushSource('');

      return this.createFunctionContext(asObject);
    },

    preamble: function() {
      var out = [];

      if (!this.isChild) {
        var namespace = this.namespace;

        var copies = "helpers = this.merge(helpers, " + namespace + ".helpers);";
        if (this.environment.usePartial) { copies = copies + " partials = this.merge(partials, " + namespace + ".partials);"; }
        if (this.options.data) { copies = copies + " data = data || {};"; }
        out.push(copies);
      } else {
        out.push('');
      }

      if (!this.environment.isSimple) {
        out.push(", buffer = " + this.initializeBuffer());
      } else {
        out.push("");
      }

      // track the last context pushed into place to allow skipping the
      // getContext opcode when it would be a noop
      this.lastContext = 0;
      this.source = out;
    },

    createFunctionContext: function(asObject) {
      var locals = this.stackVars.concat(this.registers.list);

      if(locals.length > 0) {
        this.source[1] = this.source[1] + ", " + locals.join(", ");
      }

      // Generate minimizer alias mappings
      if (!this.isChild) {
        for (var alias in this.context.aliases) {
          if (this.context.aliases.hasOwnProperty(alias)) {
            this.source[1] = this.source[1] + ', ' + alias + '=' + this.context.aliases[alias];
          }
        }
      }

      if (this.source[1]) {
        this.source[1] = "var " + this.source[1].substring(2) + ";";
      }

      // Merge children
      if (!this.isChild) {
        this.source[1] += '\n' + this.context.programs.join('\n') + '\n';
      }

      if (!this.environment.isSimple) {
        this.pushSource("return buffer;");
      }

      var params = this.isChild ? ["depth0", "data"] : ["Handlebars", "depth0", "helpers", "partials", "data"];

      for(var i=0, l=this.environment.depths.list.length; i<l; i++) {
        params.push("depth" + this.environment.depths.list[i]);
      }

      // Perform a second pass over the output to merge content when possible
      var source = this.mergeSource();

      if (!this.isChild) {
        var revision = COMPILER_REVISION,
            versions = REVISION_CHANGES[revision];
        source = "this.compilerInfo = ["+revision+",'"+versions+"'];\n"+source;
      }

      if (asObject) {
        params.push(source);

        return Function.apply(this, params);
      } else {
        var functionSource = 'function ' + (this.name || '') + '(' + params.join(',') + ') {\n  ' + source + '}';
        log('debug', functionSource + "\n\n");
        return functionSource;
      }
    },
    mergeSource: function() {
      // WARN: We are not handling the case where buffer is still populated as the source should
      // not have buffer append operations as their final action.
      var source = '',
          buffer;
      for (var i = 0, len = this.source.length; i < len; i++) {
        var line = this.source[i];
        if (line.appendToBuffer) {
          if (buffer) {
            buffer = buffer + '\n    + ' + line.content;
          } else {
            buffer = line.content;
          }
        } else {
          if (buffer) {
            source += 'buffer += ' + buffer + ';\n  ';
            buffer = undefined;
          }
          source += line + '\n  ';
        }
      }
      return source;
    },

    // [blockValue]
    //
    // On stack, before: hash, inverse, program, value
    // On stack, after: return value of blockHelperMissing
    //
    // The purpose of this opcode is to take a block of the form
    // `{{#foo}}...{{/foo}}`, resolve the value of `foo`, and
    // replace it on the stack with the result of properly
    // invoking blockHelperMissing.
    blockValue: function() {
      this.context.aliases.blockHelperMissing = 'helpers.blockHelperMissing';

      var params = ["depth0"];
      this.setupParams(0, params);

      this.replaceStack(function(current) {
        params.splice(1, 0, current);
        return "blockHelperMissing.call(" + params.join(", ") + ")";
      });
    },

    // [ambiguousBlockValue]
    //
    // On stack, before: hash, inverse, program, value
    // Compiler value, before: lastHelper=value of last found helper, if any
    // On stack, after, if no lastHelper: same as [blockValue]
    // On stack, after, if lastHelper: value
    ambiguousBlockValue: function() {
      this.context.aliases.blockHelperMissing = 'helpers.blockHelperMissing';

      var params = ["depth0"];
      this.setupParams(0, params);

      var current = this.topStack();
      params.splice(1, 0, current);

      // Use the options value generated from the invocation
      params[params.length-1] = 'options';

      this.pushSource("if (!" + this.lastHelper + ") { " + current + " = blockHelperMissing.call(" + params.join(", ") + "); }");
    },

    // [appendContent]
    //
    // On stack, before: ...
    // On stack, after: ...
    //
    // Appends the string value of `content` to the current buffer
    appendContent: function(content) {
      if (this.pendingContent) {
        content = this.pendingContent + content;
      }
      if (this.stripNext) {
        content = content.replace(/^\s+/, '');
      }

      this.pendingContent = content;
    },

    // [strip]
    //
    // On stack, before: ...
    // On stack, after: ...
    //
    // Removes any trailing whitespace from the prior content node and flags
    // the next operation for stripping if it is a content node.
    strip: function() {
      if (this.pendingContent) {
        this.pendingContent = this.pendingContent.replace(/\s+$/, '');
      }
      this.stripNext = 'strip';
    },

    // [append]
    //
    // On stack, before: value, ...
    // On stack, after: ...
    //
    // Coerces `value` to a String and appends it to the current buffer.
    //
    // If `value` is truthy, or 0, it is coerced into a string and appended
    // Otherwise, the empty string is appended
    append: function() {
      // Force anything that is inlined onto the stack so we don't have duplication
      // when we examine local
      this.flushInline();
      var local = this.popStack();
      this.pushSource("if(" + local + " || " + local + " === 0) { " + this.appendToBuffer(local) + " }");
      if (this.environment.isSimple) {
        this.pushSource("else { " + this.appendToBuffer("''") + " }");
      }
    },

    // [appendEscaped]
    //
    // On stack, before: value, ...
    // On stack, after: ...
    //
    // Escape `value` and append it to the buffer
    appendEscaped: function() {
      this.context.aliases.escapeExpression = 'this.escapeExpression';

      this.pushSource(this.appendToBuffer("escapeExpression(" + this.popStack() + ")"));
    },

    // [getContext]
    //
    // On stack, before: ...
    // On stack, after: ...
    // Compiler value, after: lastContext=depth
    //
    // Set the value of the `lastContext` compiler value to the depth
    getContext: function(depth) {
      if(this.lastContext !== depth) {
        this.lastContext = depth;
      }
    },

    // [lookupOnContext]
    //
    // On stack, before: ...
    // On stack, after: currentContext[name], ...
    //
    // Looks up the value of `name` on the current context and pushes
    // it onto the stack.
    lookupOnContext: function(name) {
      this.push(this.nameLookup('depth' + this.lastContext, name, 'context'));
    },

    // [pushContext]
    //
    // On stack, before: ...
    // On stack, after: currentContext, ...
    //
    // Pushes the value of the current context onto the stack.
    pushContext: function() {
      this.pushStackLiteral('depth' + this.lastContext);
    },

    // [resolvePossibleLambda]
    //
    // On stack, before: value, ...
    // On stack, after: resolved value, ...
    //
    // If the `value` is a lambda, replace it on the stack by
    // the return value of the lambda
    resolvePossibleLambda: function() {
      this.context.aliases.functionType = '"function"';

      this.replaceStack(function(current) {
        return "typeof " + current + " === functionType ? " + current + ".apply(depth0) : " + current;
      });
    },

    // [lookup]
    //
    // On stack, before: value, ...
    // On stack, after: value[name], ...
    //
    // Replace the value on the stack with the result of looking
    // up `name` on `value`
    lookup: function(name) {
      this.replaceStack(function(current) {
        return current + " == null || " + current + " === false ? " + current + " : " + this.nameLookup(current, name, 'context');
      });
    },

    // [lookupData]
    //
    // On stack, before: ...
    // On stack, after: data, ...
    //
    // Push the data lookup operator
    lookupData: function() {
      this.push('data');
    },

    // [pushStringParam]
    //
    // On stack, before: ...
    // On stack, after: string, currentContext, ...
    //
    // This opcode is designed for use in string mode, which
    // provides the string value of a parameter along with its
    // depth rather than resolving it immediately.
    pushStringParam: function(string, type) {
      this.pushStackLiteral('depth' + this.lastContext);

      this.pushString(type);

      if (typeof string === 'string') {
        this.pushString(string);
      } else {
        this.pushStackLiteral(string);
      }
    },

    emptyHash: function() {
      this.pushStackLiteral('{}');

      if (this.options.stringParams) {
        this.register('hashTypes', '{}');
        this.register('hashContexts', '{}');
      }
    },
    pushHash: function() {
      this.hash = {values: [], types: [], contexts: []};
    },
    popHash: function() {
      var hash = this.hash;
      this.hash = undefined;

      if (this.options.stringParams) {
        this.register('hashContexts', '{' + hash.contexts.join(',') + '}');
        this.register('hashTypes', '{' + hash.types.join(',') + '}');
      }
      this.push('{\n    ' + hash.values.join(',\n    ') + '\n  }');
    },

    // [pushString]
    //
    // On stack, before: ...
    // On stack, after: quotedString(string), ...
    //
    // Push a quoted version of `string` onto the stack
    pushString: function(string) {
      this.pushStackLiteral(this.quotedString(string));
    },

    // [push]
    //
    // On stack, before: ...
    // On stack, after: expr, ...
    //
    // Push an expression onto the stack
    push: function(expr) {
      this.inlineStack.push(expr);
      return expr;
    },

    // [pushLiteral]
    //
    // On stack, before: ...
    // On stack, after: value, ...
    //
    // Pushes a value onto the stack. This operation prevents
    // the compiler from creating a temporary variable to hold
    // it.
    pushLiteral: function(value) {
      this.pushStackLiteral(value);
    },

    // [pushProgram]
    //
    // On stack, before: ...
    // On stack, after: program(guid), ...
    //
    // Push a program expression onto the stack. This takes
    // a compile-time guid and converts it into a runtime-accessible
    // expression.
    pushProgram: function(guid) {
      if (guid != null) {
        this.pushStackLiteral(this.programExpression(guid));
      } else {
        this.pushStackLiteral(null);
      }
    },

    // [invokeHelper]
    //
    // On stack, before: hash, inverse, program, params..., ...
    // On stack, after: result of helper invocation
    //
    // Pops off the helper's parameters, invokes the helper,
    // and pushes the helper's return value onto the stack.
    //
    // If the helper is not found, `helperMissing` is called.
    invokeHelper: function(paramSize, name) {
      this.context.aliases.helperMissing = 'helpers.helperMissing';

      var helper = this.lastHelper = this.setupHelper(paramSize, name, true);
      var nonHelper = this.nameLookup('depth' + this.lastContext, name, 'context');

      this.push(helper.name + ' || ' + nonHelper);
      this.replaceStack(function(name) {
        return name + ' ? ' + name + '.call(' +
            helper.callParams + ") " + ": helperMissing.call(" +
            helper.helperMissingParams + ")";
      });
    },

    // [invokeKnownHelper]
    //
    // On stack, before: hash, inverse, program, params..., ...
    // On stack, after: result of helper invocation
    //
    // This operation is used when the helper is known to exist,
    // so a `helperMissing` fallback is not required.
    invokeKnownHelper: function(paramSize, name) {
      var helper = this.setupHelper(paramSize, name);
      this.push(helper.name + ".call(" + helper.callParams + ")");
    },

    // [invokeAmbiguous]
    //
    // On stack, before: hash, inverse, program, params..., ...
    // On stack, after: result of disambiguation
    //
    // This operation is used when an expression like `{{foo}}`
    // is provided, but we don't know at compile-time whether it
    // is a helper or a path.
    //
    // This operation emits more code than the other options,
    // and can be avoided by passing the `knownHelpers` and
    // `knownHelpersOnly` flags at compile-time.
    invokeAmbiguous: function(name, helperCall) {
      this.context.aliases.functionType = '"function"';

      this.pushStackLiteral('{}');    // Hash value
      var helper = this.setupHelper(0, name, helperCall);

      var helperName = this.lastHelper = this.nameLookup('helpers', name, 'helper');

      var nonHelper = this.nameLookup('depth' + this.lastContext, name, 'context');
      var nextStack = this.nextStack();

      this.pushSource('if (' + nextStack + ' = ' + helperName + ') { ' + nextStack + ' = ' + nextStack + '.call(' + helper.callParams + '); }');
      this.pushSource('else { ' + nextStack + ' = ' + nonHelper + '; ' + nextStack + ' = typeof ' + nextStack + ' === functionType ? ' + nextStack + '.call(' + helper.callParams + ') : ' + nextStack + '; }');
    },

    // [invokePartial]
    //
    // On stack, before: context, ...
    // On stack after: result of partial invocation
    //
    // This operation pops off a context, invokes a partial with that context,
    // and pushes the result of the invocation back.
    invokePartial: function(name) {
      var params = [this.nameLookup('partials', name, 'partial'), "'" + name + "'", this.popStack(), "helpers", "partials"];

      if (this.options.data) {
        params.push("data");
      }

      this.context.aliases.self = "this";
      this.push("self.invokePartial(" + params.join(", ") + ")");
    },

    // [assignToHash]
    //
    // On stack, before: value, hash, ...
    // On stack, after: hash, ...
    //
    // Pops a value and hash off the stack, assigns `hash[key] = value`
    // and pushes the hash back onto the stack.
    assignToHash: function(key) {
      var value = this.popStack(),
          context,
          type;

      if (this.options.stringParams) {
        type = this.popStack();
        context = this.popStack();
      }

      var hash = this.hash;
      if (context) {
        hash.contexts.push("'" + key + "': " + context);
      }
      if (type) {
        hash.types.push("'" + key + "': " + type);
      }
      hash.values.push("'" + key + "': (" + value + ")");
    },

    // HELPERS

    compiler: JavaScriptCompiler,

    compileChildren: function(environment, options) {
      var children = environment.children, child, compiler;

      for(var i=0, l=children.length; i<l; i++) {
        child = children[i];
        compiler = new this.compiler();

        var index = this.matchExistingProgram(child);

        if (index == null) {
          this.context.programs.push('');     // Placeholder to prevent name conflicts for nested children
          index = this.context.programs.length;
          child.index = index;
          child.name = 'program' + index;
          this.context.programs[index] = compiler.compile(child, options, this.context);
          this.context.environments[index] = child;
        } else {
          child.index = index;
          child.name = 'program' + index;
        }
      }
    },
    matchExistingProgram: function(child) {
      for (var i = 0, len = this.context.environments.length; i < len; i++) {
        var environment = this.context.environments[i];
        if (environment && environment.equals(child)) {
          return i;
        }
      }
    },

    programExpression: function(guid) {
      this.context.aliases.self = "this";

      if(guid == null) {
        return "self.noop";
      }

      var child = this.environment.children[guid],
          depths = child.depths.list, depth;

      var programParams = [child.index, child.name, "data"];

      for(var i=0, l = depths.length; i<l; i++) {
        depth = depths[i];

        if(depth === 1) { programParams.push("depth0"); }
        else { programParams.push("depth" + (depth - 1)); }
      }

      return (depths.length === 0 ? "self.program(" : "self.programWithDepth(") + programParams.join(", ") + ")";
    },

    register: function(name, val) {
      this.useRegister(name);
      this.pushSource(name + " = " + val + ";");
    },

    useRegister: function(name) {
      if(!this.registers[name]) {
        this.registers[name] = true;
        this.registers.list.push(name);
      }
    },

    pushStackLiteral: function(item) {
      return this.push(new Literal(item));
    },

    pushSource: function(source) {
      if (this.pendingContent) {
        this.source.push(this.appendToBuffer(this.quotedString(this.pendingContent)));
        this.pendingContent = undefined;
      }

      if (source) {
        this.source.push(source);
      }
    },

    pushStack: function(item) {
      this.flushInline();

      var stack = this.incrStack();
      if (item) {
        this.pushSource(stack + " = " + item + ";");
      }
      this.compileStack.push(stack);
      return stack;
    },

    replaceStack: function(callback) {
      var prefix = '',
          inline = this.isInline(),
          stack;

      // If we are currently inline then we want to merge the inline statement into the
      // replacement statement via ','
      if (inline) {
        var top = this.popStack(true);

        if (top instanceof Literal) {
          // Literals do not need to be inlined
          stack = top.value;
        } else {
          // Get or create the current stack name for use by the inline
          var name = this.stackSlot ? this.topStackName() : this.incrStack();

          prefix = '(' + this.push(name) + ' = ' + top + '),';
          stack = this.topStack();
        }
      } else {
        stack = this.topStack();
      }

      var item = callback.call(this, stack);

      if (inline) {
        if (this.inlineStack.length || this.compileStack.length) {
          this.popStack();
        }
        this.push('(' + prefix + item + ')');
      } else {
        // Prevent modification of the context depth variable. Through replaceStack
        if (!/^stack/.test(stack)) {
          stack = this.nextStack();
        }

        this.pushSource(stack + " = (" + prefix + item + ");");
      }
      return stack;
    },

    nextStack: function() {
      return this.pushStack();
    },

    incrStack: function() {
      this.stackSlot++;
      if(this.stackSlot > this.stackVars.length) { this.stackVars.push("stack" + this.stackSlot); }
      return this.topStackName();
    },
    topStackName: function() {
      return "stack" + this.stackSlot;
    },
    flushInline: function() {
      var inlineStack = this.inlineStack;
      if (inlineStack.length) {
        this.inlineStack = [];
        for (var i = 0, len = inlineStack.length; i < len; i++) {
          var entry = inlineStack[i];
          if (entry instanceof Literal) {
            this.compileStack.push(entry);
          } else {
            this.pushStack(entry);
          }
        }
      }
    },
    isInline: function() {
      return this.inlineStack.length;
    },

    popStack: function(wrapped) {
      var inline = this.isInline(),
          item = (inline ? this.inlineStack : this.compileStack).pop();

      if (!wrapped && (item instanceof Literal)) {
        return item.value;
      } else {
        if (!inline) {
          this.stackSlot--;
        }
        return item;
      }
    },

    topStack: function(wrapped) {
      var stack = (this.isInline() ? this.inlineStack : this.compileStack),
          item = stack[stack.length - 1];

      if (!wrapped && (item instanceof Literal)) {
        return item.value;
      } else {
        return item;
      }
    },

    quotedString: function(str) {
      return '"' + str
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r')
        .replace(/\u2028/g, '\\u2028')   // Per Ecma-262 7.3 + 7.8.4
        .replace(/\u2029/g, '\\u2029') + '"';
    },

    setupHelper: function(paramSize, name, missingParams) {
      var params = [];
      this.setupParams(paramSize, params, missingParams);
      var foundHelper = this.nameLookup('helpers', name, 'helper');

      return {
        params: params,
        name: foundHelper,
        callParams: ["depth0"].concat(params).join(", "),
        helperMissingParams: missingParams && ["depth0", this.quotedString(name)].concat(params).join(", ")
      };
    },

    // the params and contexts arguments are passed in arrays
    // to fill in
    setupParams: function(paramSize, params, useRegister) {
      var options = [], contexts = [], types = [], param, inverse, program;

      options.push("hash:" + this.popStack());

      inverse = this.popStack();
      program = this.popStack();

      // Avoid setting fn and inverse if neither are set. This allows
      // helpers to do a check for `if (options.fn)`
      if (program || inverse) {
        if (!program) {
          this.context.aliases.self = "this";
          program = "self.noop";
        }

        if (!inverse) {
         this.context.aliases.self = "this";
          inverse = "self.noop";
        }

        options.push("inverse:" + inverse);
        options.push("fn:" + program);
      }

      for(var i=0; i<paramSize; i++) {
        param = this.popStack();
        params.push(param);

        if(this.options.stringParams) {
          types.push(this.popStack());
          contexts.push(this.popStack());
        }
      }

      if (this.options.stringParams) {
        options.push("contexts:[" + contexts.join(",") + "]");
        options.push("types:[" + types.join(",") + "]");
        options.push("hashContexts:hashContexts");
        options.push("hashTypes:hashTypes");
      }

      if(this.options.data) {
        options.push("data:data");
      }

      options = "{" + options.join(",") + "}";
      if (useRegister) {
        this.register('options', options);
        params.push('options');
      } else {
        params.push(options);
      }
      return params.join(", ");
    }
  };

  var reservedWords = (
    "break else new var" +
    " case finally return void" +
    " catch for switch while" +
    " continue function this with" +
    " default if throw" +
    " delete in try" +
    " do instanceof typeof" +
    " abstract enum int short" +
    " boolean export interface static" +
    " byte extends long super" +
    " char final native synchronized" +
    " class float package throws" +
    " const goto private transient" +
    " debugger implements protected volatile" +
    " double import public let yield"
  ).split(" ");

  var compilerWords = JavaScriptCompiler.RESERVED_WORDS = {};

  for(var i=0, l=reservedWords.length; i<l; i++) {
    compilerWords[reservedWords[i]] = true;
  }

  JavaScriptCompiler.isValidJavaScriptVariableName = function(name) {
    if(!JavaScriptCompiler.RESERVED_WORDS[name] && /^[a-zA-Z_$][0-9a-zA-Z_$]+$/.test(name)) {
      return true;
    }
    return false;
  };

  __exports__ = JavaScriptCompiler;
  return __exports__;
})(__module2__);

// handlebars/compiler/compiler.js
var __module10__ = (function(__dependency1__, __dependency2__, __dependency3__, __dependency4__) {
  "use strict";
  var __exports__ = {};
  var Exception = __dependency1__;
  var parse = __dependency2__.parse;
  var JavaScriptCompiler = __dependency3__;
  var AST = __dependency4__;

  function Compiler() {}

  __exports__.Compiler = Compiler;// the foundHelper register will disambiguate helper lookup from finding a
  // function in a context. This is necessary for mustache compatibility, which
  // requires that context functions in blocks are evaluated by blockHelperMissing,
  // and then proceed as if the resulting value was provided to blockHelperMissing.

  Compiler.prototype = {
    compiler: Compiler,

    disassemble: function() {
      var opcodes = this.opcodes, opcode, out = [], params, param;

      for (var i=0, l=opcodes.length; i<l; i++) {
        opcode = opcodes[i];

        if (opcode.opcode === 'DECLARE') {
          out.push("DECLARE " + opcode.name + "=" + opcode.value);
        } else {
          params = [];
          for (var j=0; j<opcode.args.length; j++) {
            param = opcode.args[j];
            if (typeof param === "string") {
              param = "\"" + param.replace("\n", "\\n") + "\"";
            }
            params.push(param);
          }
          out.push(opcode.opcode + " " + params.join(" "));
        }
      }

      return out.join("\n");
    },

    equals: function(other) {
      var len = this.opcodes.length;
      if (other.opcodes.length !== len) {
        return false;
      }

      for (var i = 0; i < len; i++) {
        var opcode = this.opcodes[i],
            otherOpcode = other.opcodes[i];
        if (opcode.opcode !== otherOpcode.opcode || opcode.args.length !== otherOpcode.args.length) {
          return false;
        }
        for (var j = 0; j < opcode.args.length; j++) {
          if (opcode.args[j] !== otherOpcode.args[j]) {
            return false;
          }
        }
      }

      len = this.children.length;
      if (other.children.length !== len) {
        return false;
      }
      for (i = 0; i < len; i++) {
        if (!this.children[i].equals(other.children[i])) {
          return false;
        }
      }

      return true;
    },

    guid: 0,

    compile: function(program, options) {
      this.opcodes = [];
      this.children = [];
      this.depths = {list: []};
      this.options = options;

      // These changes will propagate to the other compiler components
      var knownHelpers = this.options.knownHelpers;
      this.options.knownHelpers = {
        'helperMissing': true,
        'blockHelperMissing': true,
        'each': true,
        'if': true,
        'unless': true,
        'with': true,
        'log': true
      };
      if (knownHelpers) {
        for (var name in knownHelpers) {
          this.options.knownHelpers[name] = knownHelpers[name];
        }
      }

      return this.accept(program);
    },

    accept: function(node) {
      var strip = node.strip || {},
          ret;
      if (strip.left) {
        this.opcode('strip');
      }

      ret = this[node.type](node);

      if (strip.right) {
        this.opcode('strip');
      }

      return ret;
    },

    program: function(program) {
      var statements = program.statements;

      for(var i=0, l=statements.length; i<l; i++) {
        this.accept(statements[i]);
      }
      this.isSimple = l === 1;

      this.depths.list = this.depths.list.sort(function(a, b) {
        return a - b;
      });

      return this;
    },

    compileProgram: function(program) {
      var result = new this.compiler().compile(program, this.options);
      var guid = this.guid++, depth;

      this.usePartial = this.usePartial || result.usePartial;

      this.children[guid] = result;

      for(var i=0, l=result.depths.list.length; i<l; i++) {
        depth = result.depths.list[i];

        if(depth < 2) { continue; }
        else { this.addDepth(depth - 1); }
      }

      return guid;
    },

    block: function(block) {
      var mustache = block.mustache,
          program = block.program,
          inverse = block.inverse;

      if (program) {
        program = this.compileProgram(program);
      }

      if (inverse) {
        inverse = this.compileProgram(inverse);
      }

      var type = this.classifyMustache(mustache);

      if (type === "helper") {
        this.helperMustache(mustache, program, inverse);
      } else if (type === "simple") {
        this.simpleMustache(mustache);

        // now that the simple mustache is resolved, we need to
        // evaluate it by executing `blockHelperMissing`
        this.opcode('pushProgram', program);
        this.opcode('pushProgram', inverse);
        this.opcode('emptyHash');
        this.opcode('blockValue');
      } else {
        this.ambiguousMustache(mustache, program, inverse);

        // now that the simple mustache is resolved, we need to
        // evaluate it by executing `blockHelperMissing`
        this.opcode('pushProgram', program);
        this.opcode('pushProgram', inverse);
        this.opcode('emptyHash');
        this.opcode('ambiguousBlockValue');
      }

      this.opcode('append');
    },

    hash: function(hash) {
      var pairs = hash.pairs, pair, val;

      this.opcode('pushHash');

      for(var i=0, l=pairs.length; i<l; i++) {
        pair = pairs[i];
        val  = pair[1];

        if (this.options.stringParams) {
          if(val.depth) {
            this.addDepth(val.depth);
          }
          this.opcode('getContext', val.depth || 0);
          this.opcode('pushStringParam', val.stringModeValue, val.type);
        } else {
          this.accept(val);
        }

        this.opcode('assignToHash', pair[0]);
      }
      this.opcode('popHash');
    },

    partial: function(partial) {
      var partialName = partial.partialName;
      this.usePartial = true;

      if(partial.context) {
        this.ID(partial.context);
      } else {
        this.opcode('push', 'depth0');
      }

      this.opcode('invokePartial', partialName.name);
      this.opcode('append');
    },

    content: function(content) {
      this.opcode('appendContent', content.string);
    },

    mustache: function(mustache) {
      var options = this.options;
      var type = this.classifyMustache(mustache);

      if (type === "simple") {
        this.simpleMustache(mustache);
      } else if (type === "helper") {
        this.helperMustache(mustache);
      } else {
        this.ambiguousMustache(mustache);
      }

      if(mustache.escaped && !options.noEscape) {
        this.opcode('appendEscaped');
      } else {
        this.opcode('append');
      }
    },

    ambiguousMustache: function(mustache, program, inverse) {
      var id = mustache.id,
          name = id.parts[0],
          isBlock = program != null || inverse != null;

      this.opcode('getContext', id.depth);

      this.opcode('pushProgram', program);
      this.opcode('pushProgram', inverse);

      this.opcode('invokeAmbiguous', name, isBlock);
    },

    simpleMustache: function(mustache) {
      var id = mustache.id;

      if (id.type === 'DATA') {
        this.DATA(id);
      } else if (id.parts.length) {
        this.ID(id);
      } else {
        // Simplified ID for `this`
        this.addDepth(id.depth);
        this.opcode('getContext', id.depth);
        this.opcode('pushContext');
      }

      this.opcode('resolvePossibleLambda');
    },

    helperMustache: function(mustache, program, inverse) {
      var params = this.setupFullMustacheParams(mustache, program, inverse),
          name = mustache.id.parts[0];

      if (this.options.knownHelpers[name]) {
        this.opcode('invokeKnownHelper', params.length, name);
      } else if (this.options.knownHelpersOnly) {
        throw new Error("You specified knownHelpersOnly, but used the unknown helper " + name);
      } else {
        this.opcode('invokeHelper', params.length, name);
      }
    },

    ID: function(id) {
      this.addDepth(id.depth);
      this.opcode('getContext', id.depth);

      var name = id.parts[0];
      if (!name) {
        this.opcode('pushContext');
      } else {
        this.opcode('lookupOnContext', id.parts[0]);
      }

      for(var i=1, l=id.parts.length; i<l; i++) {
        this.opcode('lookup', id.parts[i]);
      }
    },

    DATA: function(data) {
      this.options.data = true;
      if (data.id.isScoped || data.id.depth) {
        throw new Exception('Scoped data references are not supported: ' + data.original);
      }

      this.opcode('lookupData');
      var parts = data.id.parts;
      for(var i=0, l=parts.length; i<l; i++) {
        this.opcode('lookup', parts[i]);
      }
    },

    STRING: function(string) {
      this.opcode('pushString', string.string);
    },

    INTEGER: function(integer) {
      this.opcode('pushLiteral', integer.integer);
    },

    BOOLEAN: function(bool) {
      this.opcode('pushLiteral', bool.bool);
    },

    comment: function() {},

    // HELPERS
    opcode: function(name) {
      this.opcodes.push({ opcode: name, args: [].slice.call(arguments, 1) });
    },

    declare: function(name, value) {
      this.opcodes.push({ opcode: 'DECLARE', name: name, value: value });
    },

    addDepth: function(depth) {
      if(isNaN(depth)) { throw new Error("EWOT"); }
      if(depth === 0) { return; }

      if(!this.depths[depth]) {
        this.depths[depth] = true;
        this.depths.list.push(depth);
      }
    },

    classifyMustache: function(mustache) {
      var isHelper   = mustache.isHelper;
      var isEligible = mustache.eligibleHelper;
      var options    = this.options;

      // if ambiguous, we can possibly resolve the ambiguity now
      if (isEligible && !isHelper) {
        var name = mustache.id.parts[0];

        if (options.knownHelpers[name]) {
          isHelper = true;
        } else if (options.knownHelpersOnly) {
          isEligible = false;
        }
      }

      if (isHelper) { return "helper"; }
      else if (isEligible) { return "ambiguous"; }
      else { return "simple"; }
    },

    pushParams: function(params) {
      var i = params.length, param;

      while(i--) {
        param = params[i];

        if(this.options.stringParams) {
          if(param.depth) {
            this.addDepth(param.depth);
          }

          this.opcode('getContext', param.depth || 0);
          this.opcode('pushStringParam', param.stringModeValue, param.type);
        } else {
          this[param.type](param);
        }
      }
    },

    setupMustacheParams: function(mustache) {
      var params = mustache.params;
      this.pushParams(params);

      if(mustache.hash) {
        this.hash(mustache.hash);
      } else {
        this.opcode('emptyHash');
      }

      return params;
    },

    // this will replace setupMustacheParams when we're done
    setupFullMustacheParams: function(mustache, program, inverse) {
      var params = mustache.params;
      this.pushParams(params);

      this.opcode('pushProgram', program);
      this.opcode('pushProgram', inverse);

      if(mustache.hash) {
        this.hash(mustache.hash);
      } else {
        this.opcode('emptyHash');
      }

      return params;
    }
  };

  function precompile(input, options) {
    if (input == null || (typeof input !== 'string' && input.constructor !== AST.ProgramNode)) {
      throw new Exception("You must pass a string or Handlebars AST to Handlebars.precompile. You passed " + input);
    }

    options = options || {};
    if (!('data' in options)) {
      options.data = true;
    }

    var ast = parse(input);
    var environment = new Compiler().compile(ast, options);
    return new JavaScriptCompiler().compile(environment, options);
  }

  __exports__.precompile = precompile;function compile(input, options, env) {
    if (input == null || (typeof input !== 'string' && input.constructor !== AST.ProgramNode)) {
      throw new Exception("You must pass a string or Handlebars AST to Handlebars.compile. You passed " + input);
    }

    options = options || {};

    if (!('data' in options)) {
      options.data = true;
    }

    var compiled;

    function compileInput() {
      var ast = parse(input);
      var environment = new Compiler().compile(ast, options);
      var templateSpec = new JavaScriptCompiler().compile(environment, options, undefined, true);
      return env.template(templateSpec);
    }

    // Template is only compiled on first use and cached after that point.
    return function(context, options) {
      if (!compiled) {
        compiled = compileInput();
      }
      return compiled.call(this, context, options);
    };
  }

  __exports__.compile = compile;
  return __exports__;
})(__module5__, __module8__, __module11__, __module7__);

// handlebars.js
var __module0__ = (function(__dependency1__, __dependency2__, __dependency3__, __dependency4__, __dependency5__) {
  "use strict";
  var __exports__;
  var Handlebars = __dependency1__;

  // Compiler imports
  var AST = __dependency2__;
  var Parser = __dependency3__.parser;
  var parse = __dependency3__.parse;
  var Compiler = __dependency4__.Compiler;
  var compile = __dependency4__.compile;
  var precompile = __dependency4__.precompile;
  var JavaScriptCompiler = __dependency5__;

  var _create = Handlebars.create;
  var create = function() {
    var hb = _create();

    hb.compile = function(input, options) {
      return compile(input, options, hb);
    };
    hb.precompile = precompile;

    hb.AST = AST;
    hb.Compiler = Compiler;
    hb.JavaScriptCompiler = JavaScriptCompiler;
    hb.Parser = Parser;
    hb.parse = parse;

    return hb;
  };

  Handlebars = create();
  Handlebars.create = create;

  __exports__ = Handlebars;
  return __exports__;
})(__module1__, __module7__, __module8__, __module10__, __module11__);

  return __module0__;
})();
;
/**
 * @license
 * Lo-Dash 2.4.1 (Custom Build) lodash.com/license | Underscore.js 1.5.2 underscorejs.org/LICENSE
 * Build: `lodash modern -o ./dist/lodash.js`
 */
;(function(){function n(n,t,e){e=(e||0)-1;for(var r=n?n.length:0;++e<r;)if(n[e]===t)return e;return-1}function t(t,e){var r=typeof e;if(t=t.l,"boolean"==r||null==e)return t[e]?0:-1;"number"!=r&&"string"!=r&&(r="object");var u="number"==r?e:m+e;return t=(t=t[r])&&t[u],"object"==r?t&&-1<n(t,e)?0:-1:t?0:-1}function e(n){var t=this.l,e=typeof n;if("boolean"==e||null==n)t[n]=true;else{"number"!=e&&"string"!=e&&(e="object");var r="number"==e?n:m+n,t=t[e]||(t[e]={});"object"==e?(t[r]||(t[r]=[])).push(n):t[r]=true
}}function r(n){return n.charCodeAt(0)}function u(n,t){for(var e=n.m,r=t.m,u=-1,o=e.length;++u<o;){var i=e[u],a=r[u];if(i!==a){if(i>a||typeof i=="undefined")return 1;if(i<a||typeof a=="undefined")return-1}}return n.n-t.n}function o(n){var t=-1,r=n.length,u=n[0],o=n[r/2|0],i=n[r-1];if(u&&typeof u=="object"&&o&&typeof o=="object"&&i&&typeof i=="object")return false;for(u=f(),u["false"]=u["null"]=u["true"]=u.undefined=false,o=f(),o.k=n,o.l=u,o.push=e;++t<r;)o.push(n[t]);return o}function i(n){return"\\"+U[n]
}function a(){return h.pop()||[]}function f(){return g.pop()||{k:null,l:null,m:null,"false":false,n:0,"null":false,number:null,object:null,push:null,string:null,"true":false,undefined:false,o:null}}function l(n){n.length=0,h.length<_&&h.push(n)}function c(n){var t=n.l;t&&c(t),n.k=n.l=n.m=n.object=n.number=n.string=n.o=null,g.length<_&&g.push(n)}function p(n,t,e){t||(t=0),typeof e=="undefined"&&(e=n?n.length:0);var r=-1;e=e-t||0;for(var u=Array(0>e?0:e);++r<e;)u[r]=n[t+r];return u}function s(e){function h(n,t,e){if(!n||!V[typeof n])return n;
t=t&&typeof e=="undefined"?t:tt(t,e,3);for(var r=-1,u=V[typeof n]&&Fe(n),o=u?u.length:0;++r<o&&(e=u[r],false!==t(n[e],e,n)););return n}function g(n,t,e){var r;if(!n||!V[typeof n])return n;t=t&&typeof e=="undefined"?t:tt(t,e,3);for(r in n)if(false===t(n[r],r,n))break;return n}function _(n,t,e){var r,u=n,o=u;if(!u)return o;for(var i=arguments,a=0,f=typeof e=="number"?2:i.length;++a<f;)if((u=i[a])&&V[typeof u])for(var l=-1,c=V[typeof u]&&Fe(u),p=c?c.length:0;++l<p;)r=c[l],"undefined"==typeof o[r]&&(o[r]=u[r]);
return o}function U(n,t,e){var r,u=n,o=u;if(!u)return o;var i=arguments,a=0,f=typeof e=="number"?2:i.length;if(3<f&&"function"==typeof i[f-2])var l=tt(i[--f-1],i[f--],2);else 2<f&&"function"==typeof i[f-1]&&(l=i[--f]);for(;++a<f;)if((u=i[a])&&V[typeof u])for(var c=-1,p=V[typeof u]&&Fe(u),s=p?p.length:0;++c<s;)r=p[c],o[r]=l?l(o[r],u[r]):u[r];return o}function H(n){var t,e=[];if(!n||!V[typeof n])return e;for(t in n)me.call(n,t)&&e.push(t);return e}function J(n){return n&&typeof n=="object"&&!Te(n)&&me.call(n,"__wrapped__")?n:new Q(n)
}function Q(n,t){this.__chain__=!!t,this.__wrapped__=n}function X(n){function t(){if(r){var n=p(r);be.apply(n,arguments)}if(this instanceof t){var o=nt(e.prototype),n=e.apply(o,n||arguments);return wt(n)?n:o}return e.apply(u,n||arguments)}var e=n[0],r=n[2],u=n[4];return $e(t,n),t}function Z(n,t,e,r,u){if(e){var o=e(n);if(typeof o!="undefined")return o}if(!wt(n))return n;var i=ce.call(n);if(!K[i])return n;var f=Ae[i];switch(i){case T:case F:return new f(+n);case W:case P:return new f(n);case z:return o=f(n.source,C.exec(n)),o.lastIndex=n.lastIndex,o
}if(i=Te(n),t){var c=!r;r||(r=a()),u||(u=a());for(var s=r.length;s--;)if(r[s]==n)return u[s];o=i?f(n.length):{}}else o=i?p(n):U({},n);return i&&(me.call(n,"index")&&(o.index=n.index),me.call(n,"input")&&(o.input=n.input)),t?(r.push(n),u.push(o),(i?St:h)(n,function(n,i){o[i]=Z(n,t,e,r,u)}),c&&(l(r),l(u)),o):o}function nt(n){return wt(n)?ke(n):{}}function tt(n,t,e){if(typeof n!="function")return Ut;if(typeof t=="undefined"||!("prototype"in n))return n;var r=n.__bindData__;if(typeof r=="undefined"&&(De.funcNames&&(r=!n.name),r=r||!De.funcDecomp,!r)){var u=ge.call(n);
De.funcNames||(r=!O.test(u)),r||(r=E.test(u),$e(n,r))}if(false===r||true!==r&&1&r[1])return n;switch(e){case 1:return function(e){return n.call(t,e)};case 2:return function(e,r){return n.call(t,e,r)};case 3:return function(e,r,u){return n.call(t,e,r,u)};case 4:return function(e,r,u,o){return n.call(t,e,r,u,o)}}return Mt(n,t)}function et(n){function t(){var n=f?i:this;if(u){var h=p(u);be.apply(h,arguments)}return(o||c)&&(h||(h=p(arguments)),o&&be.apply(h,o),c&&h.length<a)?(r|=16,et([e,s?r:-4&r,h,null,i,a])):(h||(h=arguments),l&&(e=n[v]),this instanceof t?(n=nt(e.prototype),h=e.apply(n,h),wt(h)?h:n):e.apply(n,h))
}var e=n[0],r=n[1],u=n[2],o=n[3],i=n[4],a=n[5],f=1&r,l=2&r,c=4&r,s=8&r,v=e;return $e(t,n),t}function rt(e,r){var u=-1,i=st(),a=e?e.length:0,f=a>=b&&i===n,l=[];if(f){var p=o(r);p?(i=t,r=p):f=false}for(;++u<a;)p=e[u],0>i(r,p)&&l.push(p);return f&&c(r),l}function ut(n,t,e,r){r=(r||0)-1;for(var u=n?n.length:0,o=[];++r<u;){var i=n[r];if(i&&typeof i=="object"&&typeof i.length=="number"&&(Te(i)||yt(i))){t||(i=ut(i,t,e));var a=-1,f=i.length,l=o.length;for(o.length+=f;++a<f;)o[l++]=i[a]}else e||o.push(i)}return o
}function ot(n,t,e,r,u,o){if(e){var i=e(n,t);if(typeof i!="undefined")return!!i}if(n===t)return 0!==n||1/n==1/t;if(n===n&&!(n&&V[typeof n]||t&&V[typeof t]))return false;if(null==n||null==t)return n===t;var f=ce.call(n),c=ce.call(t);if(f==D&&(f=q),c==D&&(c=q),f!=c)return false;switch(f){case T:case F:return+n==+t;case W:return n!=+n?t!=+t:0==n?1/n==1/t:n==+t;case z:case P:return n==oe(t)}if(c=f==$,!c){var p=me.call(n,"__wrapped__"),s=me.call(t,"__wrapped__");if(p||s)return ot(p?n.__wrapped__:n,s?t.__wrapped__:t,e,r,u,o);
if(f!=q)return false;if(f=n.constructor,p=t.constructor,f!=p&&!(dt(f)&&f instanceof f&&dt(p)&&p instanceof p)&&"constructor"in n&&"constructor"in t)return false}for(f=!u,u||(u=a()),o||(o=a()),p=u.length;p--;)if(u[p]==n)return o[p]==t;var v=0,i=true;if(u.push(n),o.push(t),c){if(p=n.length,v=t.length,(i=v==p)||r)for(;v--;)if(c=p,s=t[v],r)for(;c--&&!(i=ot(n[c],s,e,r,u,o)););else if(!(i=ot(n[v],s,e,r,u,o)))break}else g(t,function(t,a,f){return me.call(f,a)?(v++,i=me.call(n,a)&&ot(n[a],t,e,r,u,o)):void 0}),i&&!r&&g(n,function(n,t,e){return me.call(e,t)?i=-1<--v:void 0
});return u.pop(),o.pop(),f&&(l(u),l(o)),i}function it(n,t,e,r,u){(Te(t)?St:h)(t,function(t,o){var i,a,f=t,l=n[o];if(t&&((a=Te(t))||Pe(t))){for(f=r.length;f--;)if(i=r[f]==t){l=u[f];break}if(!i){var c;e&&(f=e(l,t),c=typeof f!="undefined")&&(l=f),c||(l=a?Te(l)?l:[]:Pe(l)?l:{}),r.push(t),u.push(l),c||it(l,t,e,r,u)}}else e&&(f=e(l,t),typeof f=="undefined"&&(f=t)),typeof f!="undefined"&&(l=f);n[o]=l})}function at(n,t){return n+he(Re()*(t-n+1))}function ft(e,r,u){var i=-1,f=st(),p=e?e.length:0,s=[],v=!r&&p>=b&&f===n,h=u||v?a():s;
for(v&&(h=o(h),f=t);++i<p;){var g=e[i],y=u?u(g,i,e):g;(r?!i||h[h.length-1]!==y:0>f(h,y))&&((u||v)&&h.push(y),s.push(g))}return v?(l(h.k),c(h)):u&&l(h),s}function lt(n){return function(t,e,r){var u={};e=J.createCallback(e,r,3),r=-1;var o=t?t.length:0;if(typeof o=="number")for(;++r<o;){var i=t[r];n(u,i,e(i,r,t),t)}else h(t,function(t,r,o){n(u,t,e(t,r,o),o)});return u}}function ct(n,t,e,r,u,o){var i=1&t,a=4&t,f=16&t,l=32&t;if(!(2&t||dt(n)))throw new ie;f&&!e.length&&(t&=-17,f=e=false),l&&!r.length&&(t&=-33,l=r=false);
var c=n&&n.__bindData__;return c&&true!==c?(c=p(c),c[2]&&(c[2]=p(c[2])),c[3]&&(c[3]=p(c[3])),!i||1&c[1]||(c[4]=u),!i&&1&c[1]&&(t|=8),!a||4&c[1]||(c[5]=o),f&&be.apply(c[2]||(c[2]=[]),e),l&&we.apply(c[3]||(c[3]=[]),r),c[1]|=t,ct.apply(null,c)):(1==t||17===t?X:et)([n,t,e,r,u,o])}function pt(n){return Be[n]}function st(){var t=(t=J.indexOf)===Wt?n:t;return t}function vt(n){return typeof n=="function"&&pe.test(n)}function ht(n){var t,e;return n&&ce.call(n)==q&&(t=n.constructor,!dt(t)||t instanceof t)?(g(n,function(n,t){e=t
}),typeof e=="undefined"||me.call(n,e)):false}function gt(n){return We[n]}function yt(n){return n&&typeof n=="object"&&typeof n.length=="number"&&ce.call(n)==D||false}function mt(n,t,e){var r=Fe(n),u=r.length;for(t=tt(t,e,3);u--&&(e=r[u],false!==t(n[e],e,n)););return n}function bt(n){var t=[];return g(n,function(n,e){dt(n)&&t.push(e)}),t.sort()}function _t(n){for(var t=-1,e=Fe(n),r=e.length,u={};++t<r;){var o=e[t];u[n[o]]=o}return u}function dt(n){return typeof n=="function"}function wt(n){return!(!n||!V[typeof n])
}function jt(n){return typeof n=="number"||n&&typeof n=="object"&&ce.call(n)==W||false}function kt(n){return typeof n=="string"||n&&typeof n=="object"&&ce.call(n)==P||false}function xt(n){for(var t=-1,e=Fe(n),r=e.length,u=Xt(r);++t<r;)u[t]=n[e[t]];return u}function Ct(n,t,e){var r=-1,u=st(),o=n?n.length:0,i=false;return e=(0>e?Ie(0,o+e):e)||0,Te(n)?i=-1<u(n,t,e):typeof o=="number"?i=-1<(kt(n)?n.indexOf(t,e):u(n,t,e)):h(n,function(n){return++r<e?void 0:!(i=n===t)}),i}function Ot(n,t,e){var r=true;t=J.createCallback(t,e,3),e=-1;
var u=n?n.length:0;if(typeof u=="number")for(;++e<u&&(r=!!t(n[e],e,n)););else h(n,function(n,e,u){return r=!!t(n,e,u)});return r}function Nt(n,t,e){var r=[];t=J.createCallback(t,e,3),e=-1;var u=n?n.length:0;if(typeof u=="number")for(;++e<u;){var o=n[e];t(o,e,n)&&r.push(o)}else h(n,function(n,e,u){t(n,e,u)&&r.push(n)});return r}function It(n,t,e){t=J.createCallback(t,e,3),e=-1;var r=n?n.length:0;if(typeof r!="number"){var u;return h(n,function(n,e,r){return t(n,e,r)?(u=n,false):void 0}),u}for(;++e<r;){var o=n[e];
if(t(o,e,n))return o}}function St(n,t,e){var r=-1,u=n?n.length:0;if(t=t&&typeof e=="undefined"?t:tt(t,e,3),typeof u=="number")for(;++r<u&&false!==t(n[r],r,n););else h(n,t);return n}function Et(n,t,e){var r=n?n.length:0;if(t=t&&typeof e=="undefined"?t:tt(t,e,3),typeof r=="number")for(;r--&&false!==t(n[r],r,n););else{var u=Fe(n),r=u.length;h(n,function(n,e,o){return e=u?u[--r]:--r,t(o[e],e,o)})}return n}function Rt(n,t,e){var r=-1,u=n?n.length:0;if(t=J.createCallback(t,e,3),typeof u=="number")for(var o=Xt(u);++r<u;)o[r]=t(n[r],r,n);
else o=[],h(n,function(n,e,u){o[++r]=t(n,e,u)});return o}function At(n,t,e){var u=-1/0,o=u;if(typeof t!="function"&&e&&e[t]===n&&(t=null),null==t&&Te(n)){e=-1;for(var i=n.length;++e<i;){var a=n[e];a>o&&(o=a)}}else t=null==t&&kt(n)?r:J.createCallback(t,e,3),St(n,function(n,e,r){e=t(n,e,r),e>u&&(u=e,o=n)});return o}function Dt(n,t,e,r){if(!n)return e;var u=3>arguments.length;t=J.createCallback(t,r,4);var o=-1,i=n.length;if(typeof i=="number")for(u&&(e=n[++o]);++o<i;)e=t(e,n[o],o,n);else h(n,function(n,r,o){e=u?(u=false,n):t(e,n,r,o)
});return e}function $t(n,t,e,r){var u=3>arguments.length;return t=J.createCallback(t,r,4),Et(n,function(n,r,o){e=u?(u=false,n):t(e,n,r,o)}),e}function Tt(n){var t=-1,e=n?n.length:0,r=Xt(typeof e=="number"?e:0);return St(n,function(n){var e=at(0,++t);r[t]=r[e],r[e]=n}),r}function Ft(n,t,e){var r;t=J.createCallback(t,e,3),e=-1;var u=n?n.length:0;if(typeof u=="number")for(;++e<u&&!(r=t(n[e],e,n)););else h(n,function(n,e,u){return!(r=t(n,e,u))});return!!r}function Bt(n,t,e){var r=0,u=n?n.length:0;if(typeof t!="number"&&null!=t){var o=-1;
for(t=J.createCallback(t,e,3);++o<u&&t(n[o],o,n);)r++}else if(r=t,null==r||e)return n?n[0]:v;return p(n,0,Se(Ie(0,r),u))}function Wt(t,e,r){if(typeof r=="number"){var u=t?t.length:0;r=0>r?Ie(0,u+r):r||0}else if(r)return r=zt(t,e),t[r]===e?r:-1;return n(t,e,r)}function qt(n,t,e){if(typeof t!="number"&&null!=t){var r=0,u=-1,o=n?n.length:0;for(t=J.createCallback(t,e,3);++u<o&&t(n[u],u,n);)r++}else r=null==t||e?1:Ie(0,t);return p(n,r)}function zt(n,t,e,r){var u=0,o=n?n.length:u;for(e=e?J.createCallback(e,r,1):Ut,t=e(t);u<o;)r=u+o>>>1,e(n[r])<t?u=r+1:o=r;
return u}function Pt(n,t,e,r){return typeof t!="boolean"&&null!=t&&(r=e,e=typeof t!="function"&&r&&r[t]===n?null:t,t=false),null!=e&&(e=J.createCallback(e,r,3)),ft(n,t,e)}function Kt(){for(var n=1<arguments.length?arguments:arguments[0],t=-1,e=n?At(Ve(n,"length")):0,r=Xt(0>e?0:e);++t<e;)r[t]=Ve(n,t);return r}function Lt(n,t){var e=-1,r=n?n.length:0,u={};for(t||!r||Te(n[0])||(t=[]);++e<r;){var o=n[e];t?u[o]=t[e]:o&&(u[o[0]]=o[1])}return u}function Mt(n,t){return 2<arguments.length?ct(n,17,p(arguments,2),null,t):ct(n,1,null,null,t)
}function Vt(n,t,e){function r(){c&&ve(c),i=c=p=v,(g||h!==t)&&(s=Ue(),a=n.apply(l,o),c||i||(o=l=null))}function u(){var e=t-(Ue()-f);0<e?c=_e(u,e):(i&&ve(i),e=p,i=c=p=v,e&&(s=Ue(),a=n.apply(l,o),c||i||(o=l=null)))}var o,i,a,f,l,c,p,s=0,h=false,g=true;if(!dt(n))throw new ie;if(t=Ie(0,t)||0,true===e)var y=true,g=false;else wt(e)&&(y=e.leading,h="maxWait"in e&&(Ie(t,e.maxWait)||0),g="trailing"in e?e.trailing:g);return function(){if(o=arguments,f=Ue(),l=this,p=g&&(c||!y),false===h)var e=y&&!c;else{i||y||(s=f);var v=h-(f-s),m=0>=v;
m?(i&&(i=ve(i)),s=f,a=n.apply(l,o)):i||(i=_e(r,v))}return m&&c?c=ve(c):c||t===h||(c=_e(u,t)),e&&(m=true,a=n.apply(l,o)),!m||c||i||(o=l=null),a}}function Ut(n){return n}function Gt(n,t,e){var r=true,u=t&&bt(t);t&&(e||u.length)||(null==e&&(e=t),o=Q,t=n,n=J,u=bt(t)),false===e?r=false:wt(e)&&"chain"in e&&(r=e.chain);var o=n,i=dt(o);St(u,function(e){var u=n[e]=t[e];i&&(o.prototype[e]=function(){var t=this.__chain__,e=this.__wrapped__,i=[e];if(be.apply(i,arguments),i=u.apply(n,i),r||t){if(e===i&&wt(i))return this;
i=new o(i),i.__chain__=t}return i})})}function Ht(){}function Jt(n){return function(t){return t[n]}}function Qt(){return this.__wrapped__}e=e?Y.defaults(G.Object(),e,Y.pick(G,A)):G;var Xt=e.Array,Yt=e.Boolean,Zt=e.Date,ne=e.Function,te=e.Math,ee=e.Number,re=e.Object,ue=e.RegExp,oe=e.String,ie=e.TypeError,ae=[],fe=re.prototype,le=e._,ce=fe.toString,pe=ue("^"+oe(ce).replace(/[.*+?^${}()|[\]\\]/g,"\\$&").replace(/toString| for [^\]]+/g,".*?")+"$"),se=te.ceil,ve=e.clearTimeout,he=te.floor,ge=ne.prototype.toString,ye=vt(ye=re.getPrototypeOf)&&ye,me=fe.hasOwnProperty,be=ae.push,_e=e.setTimeout,de=ae.splice,we=ae.unshift,je=function(){try{var n={},t=vt(t=re.defineProperty)&&t,e=t(n,n,n)&&t
}catch(r){}return e}(),ke=vt(ke=re.create)&&ke,xe=vt(xe=Xt.isArray)&&xe,Ce=e.isFinite,Oe=e.isNaN,Ne=vt(Ne=re.keys)&&Ne,Ie=te.max,Se=te.min,Ee=e.parseInt,Re=te.random,Ae={};Ae[$]=Xt,Ae[T]=Yt,Ae[F]=Zt,Ae[B]=ne,Ae[q]=re,Ae[W]=ee,Ae[z]=ue,Ae[P]=oe,Q.prototype=J.prototype;var De=J.support={};De.funcDecomp=!vt(e.a)&&E.test(s),De.funcNames=typeof ne.name=="string",J.templateSettings={escape:/<%-([\s\S]+?)%>/g,evaluate:/<%([\s\S]+?)%>/g,interpolate:N,variable:"",imports:{_:J}},ke||(nt=function(){function n(){}return function(t){if(wt(t)){n.prototype=t;
var r=new n;n.prototype=null}return r||e.Object()}}());var $e=je?function(n,t){M.value=t,je(n,"__bindData__",M)}:Ht,Te=xe||function(n){return n&&typeof n=="object"&&typeof n.length=="number"&&ce.call(n)==$||false},Fe=Ne?function(n){return wt(n)?Ne(n):[]}:H,Be={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"},We=_t(Be),qe=ue("("+Fe(We).join("|")+")","g"),ze=ue("["+Fe(Be).join("")+"]","g"),Pe=ye?function(n){if(!n||ce.call(n)!=q)return false;var t=n.valueOf,e=vt(t)&&(e=ye(t))&&ye(e);return e?n==e||ye(n)==e:ht(n)
}:ht,Ke=lt(function(n,t,e){me.call(n,e)?n[e]++:n[e]=1}),Le=lt(function(n,t,e){(me.call(n,e)?n[e]:n[e]=[]).push(t)}),Me=lt(function(n,t,e){n[e]=t}),Ve=Rt,Ue=vt(Ue=Zt.now)&&Ue||function(){return(new Zt).getTime()},Ge=8==Ee(d+"08")?Ee:function(n,t){return Ee(kt(n)?n.replace(I,""):n,t||0)};return J.after=function(n,t){if(!dt(t))throw new ie;return function(){return 1>--n?t.apply(this,arguments):void 0}},J.assign=U,J.at=function(n){for(var t=arguments,e=-1,r=ut(t,true,false,1),t=t[2]&&t[2][t[1]]===n?1:r.length,u=Xt(t);++e<t;)u[e]=n[r[e]];
return u},J.bind=Mt,J.bindAll=function(n){for(var t=1<arguments.length?ut(arguments,true,false,1):bt(n),e=-1,r=t.length;++e<r;){var u=t[e];n[u]=ct(n[u],1,null,null,n)}return n},J.bindKey=function(n,t){return 2<arguments.length?ct(t,19,p(arguments,2),null,n):ct(t,3,null,null,n)},J.chain=function(n){return n=new Q(n),n.__chain__=true,n},J.compact=function(n){for(var t=-1,e=n?n.length:0,r=[];++t<e;){var u=n[t];u&&r.push(u)}return r},J.compose=function(){for(var n=arguments,t=n.length;t--;)if(!dt(n[t]))throw new ie;
return function(){for(var t=arguments,e=n.length;e--;)t=[n[e].apply(this,t)];return t[0]}},J.constant=function(n){return function(){return n}},J.countBy=Ke,J.create=function(n,t){var e=nt(n);return t?U(e,t):e},J.createCallback=function(n,t,e){var r=typeof n;if(null==n||"function"==r)return tt(n,t,e);if("object"!=r)return Jt(n);var u=Fe(n),o=u[0],i=n[o];return 1!=u.length||i!==i||wt(i)?function(t){for(var e=u.length,r=false;e--&&(r=ot(t[u[e]],n[u[e]],null,true)););return r}:function(n){return n=n[o],i===n&&(0!==i||1/i==1/n)
}},J.curry=function(n,t){return t=typeof t=="number"?t:+t||n.length,ct(n,4,null,null,null,t)},J.debounce=Vt,J.defaults=_,J.defer=function(n){if(!dt(n))throw new ie;var t=p(arguments,1);return _e(function(){n.apply(v,t)},1)},J.delay=function(n,t){if(!dt(n))throw new ie;var e=p(arguments,2);return _e(function(){n.apply(v,e)},t)},J.difference=function(n){return rt(n,ut(arguments,true,true,1))},J.filter=Nt,J.flatten=function(n,t,e,r){return typeof t!="boolean"&&null!=t&&(r=e,e=typeof t!="function"&&r&&r[t]===n?null:t,t=false),null!=e&&(n=Rt(n,e,r)),ut(n,t)
},J.forEach=St,J.forEachRight=Et,J.forIn=g,J.forInRight=function(n,t,e){var r=[];g(n,function(n,t){r.push(t,n)});var u=r.length;for(t=tt(t,e,3);u--&&false!==t(r[u--],r[u],n););return n},J.forOwn=h,J.forOwnRight=mt,J.functions=bt,J.groupBy=Le,J.indexBy=Me,J.initial=function(n,t,e){var r=0,u=n?n.length:0;if(typeof t!="number"&&null!=t){var o=u;for(t=J.createCallback(t,e,3);o--&&t(n[o],o,n);)r++}else r=null==t||e?1:t||r;return p(n,0,Se(Ie(0,u-r),u))},J.intersection=function(){for(var e=[],r=-1,u=arguments.length,i=a(),f=st(),p=f===n,s=a();++r<u;){var v=arguments[r];
(Te(v)||yt(v))&&(e.push(v),i.push(p&&v.length>=b&&o(r?e[r]:s)))}var p=e[0],h=-1,g=p?p.length:0,y=[];n:for(;++h<g;){var m=i[0],v=p[h];if(0>(m?t(m,v):f(s,v))){for(r=u,(m||s).push(v);--r;)if(m=i[r],0>(m?t(m,v):f(e[r],v)))continue n;y.push(v)}}for(;u--;)(m=i[u])&&c(m);return l(i),l(s),y},J.invert=_t,J.invoke=function(n,t){var e=p(arguments,2),r=-1,u=typeof t=="function",o=n?n.length:0,i=Xt(typeof o=="number"?o:0);return St(n,function(n){i[++r]=(u?t:n[t]).apply(n,e)}),i},J.keys=Fe,J.map=Rt,J.mapValues=function(n,t,e){var r={};
return t=J.createCallback(t,e,3),h(n,function(n,e,u){r[e]=t(n,e,u)}),r},J.max=At,J.memoize=function(n,t){function e(){var r=e.cache,u=t?t.apply(this,arguments):m+arguments[0];return me.call(r,u)?r[u]:r[u]=n.apply(this,arguments)}if(!dt(n))throw new ie;return e.cache={},e},J.merge=function(n){var t=arguments,e=2;if(!wt(n))return n;if("number"!=typeof t[2]&&(e=t.length),3<e&&"function"==typeof t[e-2])var r=tt(t[--e-1],t[e--],2);else 2<e&&"function"==typeof t[e-1]&&(r=t[--e]);for(var t=p(arguments,1,e),u=-1,o=a(),i=a();++u<e;)it(n,t[u],r,o,i);
return l(o),l(i),n},J.min=function(n,t,e){var u=1/0,o=u;if(typeof t!="function"&&e&&e[t]===n&&(t=null),null==t&&Te(n)){e=-1;for(var i=n.length;++e<i;){var a=n[e];a<o&&(o=a)}}else t=null==t&&kt(n)?r:J.createCallback(t,e,3),St(n,function(n,e,r){e=t(n,e,r),e<u&&(u=e,o=n)});return o},J.omit=function(n,t,e){var r={};if(typeof t!="function"){var u=[];g(n,function(n,t){u.push(t)});for(var u=rt(u,ut(arguments,true,false,1)),o=-1,i=u.length;++o<i;){var a=u[o];r[a]=n[a]}}else t=J.createCallback(t,e,3),g(n,function(n,e,u){t(n,e,u)||(r[e]=n)
});return r},J.once=function(n){var t,e;if(!dt(n))throw new ie;return function(){return t?e:(t=true,e=n.apply(this,arguments),n=null,e)}},J.pairs=function(n){for(var t=-1,e=Fe(n),r=e.length,u=Xt(r);++t<r;){var o=e[t];u[t]=[o,n[o]]}return u},J.partial=function(n){return ct(n,16,p(arguments,1))},J.partialRight=function(n){return ct(n,32,null,p(arguments,1))},J.pick=function(n,t,e){var r={};if(typeof t!="function")for(var u=-1,o=ut(arguments,true,false,1),i=wt(n)?o.length:0;++u<i;){var a=o[u];a in n&&(r[a]=n[a])
}else t=J.createCallback(t,e,3),g(n,function(n,e,u){t(n,e,u)&&(r[e]=n)});return r},J.pluck=Ve,J.property=Jt,J.pull=function(n){for(var t=arguments,e=0,r=t.length,u=n?n.length:0;++e<r;)for(var o=-1,i=t[e];++o<u;)n[o]===i&&(de.call(n,o--,1),u--);return n},J.range=function(n,t,e){n=+n||0,e=typeof e=="number"?e:+e||1,null==t&&(t=n,n=0);var r=-1;t=Ie(0,se((t-n)/(e||1)));for(var u=Xt(t);++r<t;)u[r]=n,n+=e;return u},J.reject=function(n,t,e){return t=J.createCallback(t,e,3),Nt(n,function(n,e,r){return!t(n,e,r)
})},J.remove=function(n,t,e){var r=-1,u=n?n.length:0,o=[];for(t=J.createCallback(t,e,3);++r<u;)e=n[r],t(e,r,n)&&(o.push(e),de.call(n,r--,1),u--);return o},J.rest=qt,J.shuffle=Tt,J.sortBy=function(n,t,e){var r=-1,o=Te(t),i=n?n.length:0,p=Xt(typeof i=="number"?i:0);for(o||(t=J.createCallback(t,e,3)),St(n,function(n,e,u){var i=p[++r]=f();o?i.m=Rt(t,function(t){return n[t]}):(i.m=a())[0]=t(n,e,u),i.n=r,i.o=n}),i=p.length,p.sort(u);i--;)n=p[i],p[i]=n.o,o||l(n.m),c(n);return p},J.tap=function(n,t){return t(n),n
},J.throttle=function(n,t,e){var r=true,u=true;if(!dt(n))throw new ie;return false===e?r=false:wt(e)&&(r="leading"in e?e.leading:r,u="trailing"in e?e.trailing:u),L.leading=r,L.maxWait=t,L.trailing=u,Vt(n,t,L)},J.times=function(n,t,e){n=-1<(n=+n)?n:0;var r=-1,u=Xt(n);for(t=tt(t,e,1);++r<n;)u[r]=t(r);return u},J.toArray=function(n){return n&&typeof n.length=="number"?p(n):xt(n)},J.transform=function(n,t,e,r){var u=Te(n);if(null==e)if(u)e=[];else{var o=n&&n.constructor;e=nt(o&&o.prototype)}return t&&(t=J.createCallback(t,r,4),(u?St:h)(n,function(n,r,u){return t(e,n,r,u)
})),e},J.union=function(){return ft(ut(arguments,true,true))},J.uniq=Pt,J.values=xt,J.where=Nt,J.without=function(n){return rt(n,p(arguments,1))},J.wrap=function(n,t){return ct(t,16,[n])},J.xor=function(){for(var n=-1,t=arguments.length;++n<t;){var e=arguments[n];if(Te(e)||yt(e))var r=r?ft(rt(r,e).concat(rt(e,r))):e}return r||[]},J.zip=Kt,J.zipObject=Lt,J.collect=Rt,J.drop=qt,J.each=St,J.eachRight=Et,J.extend=U,J.methods=bt,J.object=Lt,J.select=Nt,J.tail=qt,J.unique=Pt,J.unzip=Kt,Gt(J),J.clone=function(n,t,e,r){return typeof t!="boolean"&&null!=t&&(r=e,e=t,t=false),Z(n,t,typeof e=="function"&&tt(e,r,1))
},J.cloneDeep=function(n,t,e){return Z(n,true,typeof t=="function"&&tt(t,e,1))},J.contains=Ct,J.escape=function(n){return null==n?"":oe(n).replace(ze,pt)},J.every=Ot,J.find=It,J.findIndex=function(n,t,e){var r=-1,u=n?n.length:0;for(t=J.createCallback(t,e,3);++r<u;)if(t(n[r],r,n))return r;return-1},J.findKey=function(n,t,e){var r;return t=J.createCallback(t,e,3),h(n,function(n,e,u){return t(n,e,u)?(r=e,false):void 0}),r},J.findLast=function(n,t,e){var r;return t=J.createCallback(t,e,3),Et(n,function(n,e,u){return t(n,e,u)?(r=n,false):void 0
}),r},J.findLastIndex=function(n,t,e){var r=n?n.length:0;for(t=J.createCallback(t,e,3);r--;)if(t(n[r],r,n))return r;return-1},J.findLastKey=function(n,t,e){var r;return t=J.createCallback(t,e,3),mt(n,function(n,e,u){return t(n,e,u)?(r=e,false):void 0}),r},J.has=function(n,t){return n?me.call(n,t):false},J.identity=Ut,J.indexOf=Wt,J.isArguments=yt,J.isArray=Te,J.isBoolean=function(n){return true===n||false===n||n&&typeof n=="object"&&ce.call(n)==T||false},J.isDate=function(n){return n&&typeof n=="object"&&ce.call(n)==F||false
},J.isElement=function(n){return n&&1===n.nodeType||false},J.isEmpty=function(n){var t=true;if(!n)return t;var e=ce.call(n),r=n.length;return e==$||e==P||e==D||e==q&&typeof r=="number"&&dt(n.splice)?!r:(h(n,function(){return t=false}),t)},J.isEqual=function(n,t,e,r){return ot(n,t,typeof e=="function"&&tt(e,r,2))},J.isFinite=function(n){return Ce(n)&&!Oe(parseFloat(n))},J.isFunction=dt,J.isNaN=function(n){return jt(n)&&n!=+n},J.isNull=function(n){return null===n},J.isNumber=jt,J.isObject=wt,J.isPlainObject=Pe,J.isRegExp=function(n){return n&&typeof n=="object"&&ce.call(n)==z||false
},J.isString=kt,J.isUndefined=function(n){return typeof n=="undefined"},J.lastIndexOf=function(n,t,e){var r=n?n.length:0;for(typeof e=="number"&&(r=(0>e?Ie(0,r+e):Se(e,r-1))+1);r--;)if(n[r]===t)return r;return-1},J.mixin=Gt,J.noConflict=function(){return e._=le,this},J.noop=Ht,J.now=Ue,J.parseInt=Ge,J.random=function(n,t,e){var r=null==n,u=null==t;return null==e&&(typeof n=="boolean"&&u?(e=n,n=1):u||typeof t!="boolean"||(e=t,u=true)),r&&u&&(t=1),n=+n||0,u?(t=n,n=0):t=+t||0,e||n%1||t%1?(e=Re(),Se(n+e*(t-n+parseFloat("1e-"+((e+"").length-1))),t)):at(n,t)
},J.reduce=Dt,J.reduceRight=$t,J.result=function(n,t){if(n){var e=n[t];return dt(e)?n[t]():e}},J.runInContext=s,J.size=function(n){var t=n?n.length:0;return typeof t=="number"?t:Fe(n).length},J.some=Ft,J.sortedIndex=zt,J.template=function(n,t,e){var r=J.templateSettings;n=oe(n||""),e=_({},e,r);var u,o=_({},e.imports,r.imports),r=Fe(o),o=xt(o),a=0,f=e.interpolate||S,l="__p+='",f=ue((e.escape||S).source+"|"+f.source+"|"+(f===N?x:S).source+"|"+(e.evaluate||S).source+"|$","g");n.replace(f,function(t,e,r,o,f,c){return r||(r=o),l+=n.slice(a,c).replace(R,i),e&&(l+="'+__e("+e+")+'"),f&&(u=true,l+="';"+f+";\n__p+='"),r&&(l+="'+((__t=("+r+"))==null?'':__t)+'"),a=c+t.length,t
}),l+="';",f=e=e.variable,f||(e="obj",l="with("+e+"){"+l+"}"),l=(u?l.replace(w,""):l).replace(j,"$1").replace(k,"$1;"),l="function("+e+"){"+(f?"":e+"||("+e+"={});")+"var __t,__p='',__e=_.escape"+(u?",__j=Array.prototype.join;function print(){__p+=__j.call(arguments,'')}":";")+l+"return __p}";try{var c=ne(r,"return "+l).apply(v,o)}catch(p){throw p.source=l,p}return t?c(t):(c.source=l,c)},J.unescape=function(n){return null==n?"":oe(n).replace(qe,gt)},J.uniqueId=function(n){var t=++y;return oe(null==n?"":n)+t
},J.all=Ot,J.any=Ft,J.detect=It,J.findWhere=It,J.foldl=Dt,J.foldr=$t,J.include=Ct,J.inject=Dt,Gt(function(){var n={};return h(J,function(t,e){J.prototype[e]||(n[e]=t)}),n}(),false),J.first=Bt,J.last=function(n,t,e){var r=0,u=n?n.length:0;if(typeof t!="number"&&null!=t){var o=u;for(t=J.createCallback(t,e,3);o--&&t(n[o],o,n);)r++}else if(r=t,null==r||e)return n?n[u-1]:v;return p(n,Ie(0,u-r))},J.sample=function(n,t,e){return n&&typeof n.length!="number"&&(n=xt(n)),null==t||e?n?n[at(0,n.length-1)]:v:(n=Tt(n),n.length=Se(Ie(0,t),n.length),n)
},J.take=Bt,J.head=Bt,h(J,function(n,t){var e="sample"!==t;J.prototype[t]||(J.prototype[t]=function(t,r){var u=this.__chain__,o=n(this.__wrapped__,t,r);return u||null!=t&&(!r||e&&typeof t=="function")?new Q(o,u):o})}),J.VERSION="2.4.1",J.prototype.chain=function(){return this.__chain__=true,this},J.prototype.toString=function(){return oe(this.__wrapped__)},J.prototype.value=Qt,J.prototype.valueOf=Qt,St(["join","pop","shift"],function(n){var t=ae[n];J.prototype[n]=function(){var n=this.__chain__,e=t.apply(this.__wrapped__,arguments);
return n?new Q(e,n):e}}),St(["push","reverse","sort","unshift"],function(n){var t=ae[n];J.prototype[n]=function(){return t.apply(this.__wrapped__,arguments),this}}),St(["concat","slice","splice"],function(n){var t=ae[n];J.prototype[n]=function(){return new Q(t.apply(this.__wrapped__,arguments),this.__chain__)}}),J}var v,h=[],g=[],y=0,m=+new Date+"",b=75,_=40,d=" \t\x0B\f\xa0\ufeff\n\r\u2028\u2029\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000",w=/\b__p\+='';/g,j=/\b(__p\+=)''\+/g,k=/(__e\(.*?\)|\b__t\))\+'';/g,x=/\$\{([^\\}]*(?:\\.[^\\}]*)*)\}/g,C=/\w*$/,O=/^\s*function[ \n\r\t]+\w/,N=/<%=([\s\S]+?)%>/g,I=RegExp("^["+d+"]*0+(?=.$)"),S=/($^)/,E=/\bthis\b/,R=/['\n\r\t\u2028\u2029\\]/g,A="Array Boolean Date Function Math Number Object RegExp String _ attachEvent clearTimeout isFinite isNaN parseInt setTimeout".split(" "),D="[object Arguments]",$="[object Array]",T="[object Boolean]",F="[object Date]",B="[object Function]",W="[object Number]",q="[object Object]",z="[object RegExp]",P="[object String]",K={};
K[B]=false,K[D]=K[$]=K[T]=K[F]=K[W]=K[q]=K[z]=K[P]=true;var L={leading:false,maxWait:0,trailing:false},M={configurable:false,enumerable:false,value:null,writable:false},V={"boolean":false,"function":true,object:true,number:false,string:false,undefined:false},U={"\\":"\\","'":"'","\n":"n","\r":"r","\t":"t","\u2028":"u2028","\u2029":"u2029"},G=V[typeof window]&&window||this,H=V[typeof exports]&&exports&&!exports.nodeType&&exports,J=V[typeof module]&&module&&!module.nodeType&&module,Q=J&&J.exports===H&&H,X=V[typeof global]&&global;!X||X.global!==X&&X.window!==X||(G=X);
var Y=s();typeof define=="function"&&typeof define.amd=="object"&&define.amd?(G._=Y, define(function(){return Y})):H&&J?Q?(J.exports=Y)._=Y:H._=Y:G._=Y}).call(this);;
// Stageprompt 2.0.1
//
// See: https://github.com/alphagov/stageprompt
//
// Stageprompt allows user journeys to be described and instrumented
// using data attributes.
//
// Setup (run this on document ready):
//
//   GOVUK.performance.stageprompt.setupForGoogleAnalytics();
//
// Usage:
//
//   Sending events on page load:
//
//     <div id="wrapper" class="service" data-journey="pay-register-birth-abroad:start">
//         [...]
//     </div>
//
//   Sending events on click:
//
//     <a class="help-button" href="#" data-journey-click="stage:help:info">See more info...</a>

var GOVUK = GOVUK || {};

GOVUK.performance = GOVUK.performance || {};

GOVUK.performance.stageprompt = (function () {

  var setup, setupForGoogleAnalytics, splitAction;

  splitAction = function (action) {
    var parts = action.split(':');
    if (parts.length <= 3) return parts;
    return [parts.shift(), parts.shift(), parts.join(':')];
  };

  setup = function (analyticsCallback) {
    var journeyStage = $('[data-journey]').attr('data-journey'),
        journeyHelpers = $('[data-journey-click]');

    if (journeyStage) {
      analyticsCallback.apply(null, splitAction(journeyStage));
    }

    journeyHelpers.on('click', function (event) {
      analyticsCallback.apply(null, splitAction($(this).data('journey-click')));
    });
  };

  setupForGoogleAnalytics = function () {
    setup(GOVUK.performance.sendGoogleAnalyticsEvent);
  };

  return {
    setup: setup,
    setupForGoogleAnalytics: setupForGoogleAnalytics
  };
}());

GOVUK.performance.sendGoogleAnalyticsEvent = function (category, event, label) {
  if (window.ga && typeof(window.ga) === 'function') {
    ga('send', 'event', category, event, label);
  } else {
    _gaq.push(['_trackEvent', category, event, label, undefined, true]);
  }
};
;
;(function (global) {
  'use strict'

  var $ = global.jQuery
  var GOVUK = global.GOVUK || {}

  var SelectionButtons = function (elmsOrSelector, opts) {
    this.selectedClass = 'selected'
    this.focusedClass = 'focused'
    this.radioClass = 'selection-button-radio'
    this.checkboxClass = 'selection-button-checkbox'
    if (opts !== undefined) {
      $.each(opts, function (optionName, optionObj) {
        this[optionName] = optionObj
      }.bind(this))
    }
    if (typeof elmsOrSelector === 'string') {
      this.selector = elmsOrSelector
      this.setInitialState($(this.selector))
    } else if (elmsOrSelector !== undefined) {
      this.$elms = elmsOrSelector
      this.setInitialState(this.$elms)
    }
    this.addEvents()
  }
  SelectionButtons.prototype.addEvents = function () {
    if (typeof this.$elms !== 'undefined') {
      this.addElementLevelEvents()
    } else {
      this.addDocumentLevelEvents()
    }
  }
  SelectionButtons.prototype.setInitialState = function ($elms) {
    $elms.each(function (idx, elm) {
      var $elm = $(elm)

      var labelClass = $elm.attr('type') === 'radio' ? this.radioClass : this.checkboxClass
      $elm.parent('label').addClass(labelClass)
      if ($elm.is(':checked')) {
        this.markSelected($elm)
      }
    }.bind(this))
  }
  SelectionButtons.prototype.markFocused = function ($elm, state) {
    if (state === 'focused') {
      $elm.parent('label').addClass(this.focusedClass)
    } else {
      $elm.parent('label').removeClass(this.focusedClass)
    }
  }
  SelectionButtons.prototype.markSelected = function ($elm) {
    var radioName

    if ($elm.attr('type') === 'radio') {
      radioName = $elm.attr('name')
      $($elm[0].form).find('input[name="' + radioName + '"]')
        .parent('label')
        .removeClass(this.selectedClass)
      $elm.parent('label').addClass(this.selectedClass)
    } else { // checkbox
      if ($elm.is(':checked')) {
        $elm.parent('label').addClass(this.selectedClass)
      } else {
        $elm.parent('label').removeClass(this.selectedClass)
      }
    }
  }
  SelectionButtons.prototype.addElementLevelEvents = function () {
    this.clickHandler = this.getClickHandler()
    this.focusHandler = this.getFocusHandler({ 'level': 'element' })

    this.$elms
      .on('click', this.clickHandler)
      .on('focus blur', this.focusHandler)
  }
  SelectionButtons.prototype.addDocumentLevelEvents = function () {
    this.clickHandler = this.getClickHandler()
    this.focusHandler = this.getFocusHandler({ 'level': 'document' })

    $(document)
      .on('click', this.selector, this.clickHandler)
      .on('focus blur', this.selector, this.focusHandler)
  }
  SelectionButtons.prototype.getClickHandler = function () {
    return function (e) {
      this.markSelected($(e.target))
    }.bind(this)
  }
  SelectionButtons.prototype.getFocusHandler = function (opts) {
    var focusEvent = (opts.level === 'document') ? 'focusin' : 'focus'

    return function (e) {
      var state = (e.type === focusEvent) ? 'focused' : 'blurred'

      this.markFocused($(e.target), state)
    }.bind(this)
  }
  SelectionButtons.prototype.destroy = function () {
    if (typeof this.selector !== 'undefined') {
      $(document)
        .off('click', this.selector, this.clickHandler)
        .off('focus blur', this.selector, this.focusHandler)
    } else {
      this.$elms
        .off('click', this.clickHandler)
        .off('focus blur', this.focusHandler)
    }
  }

  GOVUK.SelectionButtons = SelectionButtons
  global.GOVUK = GOVUK
})(window)
;
;(function (global) {
  'use strict'

  var $ = global.jQuery
  var GOVUK = global.GOVUK || {}

  function ShowHideContent () {
    var self = this

    // Radio and Checkbox selectors
    var selectors = {
      namespace: 'ShowHideContent',
      radio: '[data-target] > input[type="radio"]',
      checkbox: '[data-target] > input[type="checkbox"]'
    }

    // Escape name attribute for use in DOM selector
    function escapeElementName (str) {
      var result = str.replace('[', '\\[').replace(']', '\\]')
      return result
    }

    // Adds ARIA attributes to control + associated content
    function initToggledContent () {
      var $control = $(this)
      var $content = getToggledContent($control)

      // Set aria-controls and defaults
      if ($content.length) {
        $control.attr('aria-controls', $content.attr('id'))
        $control.attr('aria-expanded', 'false')
        $content.attr('aria-hidden', 'true')
      }
    }

    // Return toggled content for control
    function getToggledContent ($control) {
      var id = $control.attr('aria-controls')

      // ARIA attributes aren't set before init
      if (!id) {
        id = $control.closest('[data-target]').data('target')
      }

      // Find show/hide content by id
      return $('#' + id)
    }

    // Show toggled content for control
    function showToggledContent ($control, $content) {
      // Show content
      if ($content.hasClass('js-hidden')) {
        $content.removeClass('js-hidden')
        $content.attr('aria-hidden', 'false')

        // If the controlling input, update aria-expanded
        if ($control.attr('aria-controls')) {
          $control.attr('aria-expanded', 'true')
        }
      }
    }

    // Hide toggled content for control
    function hideToggledContent ($control, $content) {
      $content = $content || getToggledContent($control)

      // Hide content
      if (!$content.hasClass('js-hidden')) {
        $content.addClass('js-hidden')
        $content.attr('aria-hidden', 'true')

        // If the controlling input, update aria-expanded
        if ($control.attr('aria-controls')) {
          $control.attr('aria-expanded', 'false')
        }
      }
    }

    // Handle radio show/hide
    function handleRadioContent ($control, $content) {
      // All radios in this group which control content
      var selector = selectors.radio + '[name=' + escapeElementName($control.attr('name')) + '][aria-controls]'
      var $form = $control.closest('form')
      var $radios = $form.length ? $form.find(selector) : $(selector)

      // Hide content for radios in group
      $radios.each(function () {
        hideToggledContent($(this))
      })

      // Select content for this control
      if ($control.is('[aria-controls]')) {
        showToggledContent($control, $content)
      }
    }

    // Handle checkbox show/hide
    function handleCheckboxContent ($control, $content) {
      // Show checkbox content
      if ($control.is(':checked')) {
        showToggledContent($control, $content)
      } else { // Hide checkbox content
        hideToggledContent($control, $content)
      }
    }

    // Set up event handlers etc
    function init ($container, elementSelector, eventSelectors, handler) {
      $container = $container || $(document.body)

      // Handle control clicks
      function deferred () {
        var $control = $(this)
        handler($control, getToggledContent($control))
      }

      // Prepare ARIA attributes
      var $controls = $(elementSelector)
      $controls.each(initToggledContent)

      // Handle events
      $.each(eventSelectors, function (idx, eventSelector) {
        $container.on('click.' + selectors.namespace, eventSelector, deferred)
      })

      // Any already :checked on init?
      if ($controls.is(':checked')) {
        $controls.filter(':checked').each(deferred)
      }
    }

    // Get event selectors for all radio groups
    function getEventSelectorsForRadioGroups () {
      var radioGroups = []

      // Build an array of radio group selectors
      return $(selectors.radio).map(function () {
        var groupName = $(this).attr('name')

        if ($.inArray(groupName, radioGroups) === -1) {
          radioGroups.push(groupName)
          return 'input[type="radio"][name="' + $(this).attr('name') + '"]'
        }
        return null
      })
    }

    // Set up radio show/hide content for container
    self.showHideRadioToggledContent = function ($container) {
      init($container, selectors.radio, getEventSelectorsForRadioGroups(), handleRadioContent)
    }

    // Set up checkbox show/hide content for container
    self.showHideCheckboxToggledContent = function ($container) {
      init($container, selectors.checkbox, [selectors.checkbox], handleCheckboxContent)
    }

    // Remove event handlers
    self.destroy = function ($container) {
      $container = $container || $(document.body)
      $container.off('.' + selectors.namespace)
    }
  }

  ShowHideContent.prototype.init = function ($container) {
    this.showHideRadioToggledContent($container)
    this.showHideCheckboxToggledContent($container)
  }

  GOVUK.ShowHideContent = ShowHideContent
  global.GOVUK = GOVUK
})(window)
;
;(function (global) {
  'use strict'

  var $ = global.jQuery
  var GOVUK = global.GOVUK || {}

  var GOVUKTracker = function (gifUrl) {
    this.gifUrl = gifUrl
    this.dimensions = []
    if (global.ga) {
      global.ga(function (tracker) {
        this.gaClientId = tracker.get('clientId')
      }.bind(this))
    }
  }

  GOVUKTracker.load = function () {}

  GOVUKTracker.prototype.trackPageview = function (path, title, options) {
    var pageviewObject

    if (typeof path === 'string') {
      pageviewObject = { page: path }
    }

    if (typeof title === 'string') {
      pageviewObject = pageviewObject || {}
      pageviewObject.title = title
    }

    // Set an options object for the pageview (e.g. transport, sessionControl)
    // https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#transport
    if (typeof options === 'object') {
      pageviewObject = $.extend(pageviewObject || {}, options)
    }

    if (!$.isEmptyObject(pageviewObject)) {
      this.sendToTracker('pageview', pageviewObject)
    } else {
      this.sendToTracker('pageview')
    }
  }

  // https://developers.google.com/analytics/devguides/collection/analyticsjs/events
  GOVUKTracker.prototype.trackEvent = function (category, action, options) {
    options = options || {}
    var evt = {
      eventCategory: category,
      eventAction: action
    }

    if (options.label) {
      evt.eventLabel = options.label
      delete options.label
    }

    if (options.value) {
      evt.eventValue = options.value.toString()
      delete options.value
    }

    if (typeof options === 'object') {
      $.extend(evt, options)
    }

    this.sendToTracker('event', evt)
  }

  GOVUKTracker.prototype.trackSocial = function (network, action, target) {
    this.sendToTracker('social', {
      'socialNetwork': network,
      'socialAction': action,
      'socialTarget': target
    })
  }

  GOVUKTracker.prototype.addLinkedTrackerDomain = function () { /* noop */ }

  GOVUKTracker.prototype.setDimension = function (index, value) {
    this.dimensions['dimension' + index] = value
  }

  GOVUKTracker.prototype.payloadParams = function (type, payload) {
    var data = $.extend({},
      payload,
      this.dimensions,
      {
        eventType: type,
        referrer: global.document.referrer,
        gaClientId: this.gaClientId,
        windowWidth: global.innerWidth,
        windowHeight: global.innerHeight,
        screenWidth: global.screen.width,
        screenHeight: global.screen.height,
        colorDepth: global.screen.colorDepth
      }
    )

    if (global.performance) {
      data.navigationType = global.performance.navigation.type.toString()
      data.redirectCount = global.performance.navigation.redirectCount.toString()

      for (var k in global.performance.timing) {
        var v = global.performance.timing[k]
        if (typeof v === 'string' || typeof v === 'number') {
          data['timing_' + k] = v.toString()
        }
      }
    }

    return data
  }

  GOVUKTracker.prototype.sendData = function (params) {
    var url = this.gifUrl + '?' + $.param(params)
    $.get(url)
  }

  GOVUKTracker.prototype.sendToTracker = function (type, payload) {
    $(global.document).ready(function () {
      this.sendData(this.payloadParams(type, payload))
    }.bind(this))
  }

  GOVUK.GOVUKTracker = GOVUKTracker

  global.GOVUK = GOVUK
})(window)
;
;(function (global) {
  'use strict'

  var $ = global.jQuery
  var GOVUK = global.GOVUK || {}

  var GoogleAnalyticsUniversalTracker = function (trackingId, fieldsObject) {
    function configureProfile () {
      // https://developers.google.com/analytics/devguides/collection/analyticsjs/command-queue-reference#create
      sendToGa('create', trackingId, fieldsObject)
    }

    function anonymizeIp () {
      // https://developers.google.com/analytics/devguides/collection/analyticsjs/advanced#anonymizeip
      sendToGa('set', 'anonymizeIp', true)
    }

    // Support legacy cookieDomain param
    if (typeof fieldsObject === 'string') {
      fieldsObject = { cookieDomain: fieldsObject }
    }

    configureProfile()
    anonymizeIp()
  }

  GoogleAnalyticsUniversalTracker.load = function () {
    /* eslint-disable */
    (function (i, s, o, g, r, a, m) { i['GoogleAnalyticsObject'] = r; i[r] = i[r] || function () {
      (i[r].q = i[r].q || []).push(arguments) }, i[r].l = 1 * new Date(); a = s.createElement(o),
                             m = s.getElementsByTagName(o)[0]; a.async = 1; a.src = g; m.parentNode.insertBefore(a, m)
    })(global, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga')
    /* eslint-enable */
  }

  // https://developers.google.com/analytics/devguides/collection/analyticsjs/pages
  GoogleAnalyticsUniversalTracker.prototype.trackPageview = function (path, title, options) {
    var pageviewObject

    if (typeof path === 'string') {
      pageviewObject = { page: path }
    }

    if (typeof title === 'string') {
      pageviewObject = pageviewObject || {}
      pageviewObject.title = title
    }

    // Set an options object for the pageview (e.g. transport, sessionControl)
    // https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#transport
    if (typeof options === 'object') {
      pageviewObject = $.extend(pageviewObject || {}, options)
    }

    if (!$.isEmptyObject(pageviewObject)) {
      sendToGa('send', 'pageview', pageviewObject)
    } else {
      sendToGa('send', 'pageview')
    }
  }

  // https://developers.google.com/analytics/devguides/collection/analyticsjs/events
  GoogleAnalyticsUniversalTracker.prototype.trackEvent = function (category, action, options) {
    options = options || {}
    var value
    var evt = {
      hitType: 'event',
      eventCategory: category,
      eventAction: action
    }

    // Label is optional
    if (typeof options.label === 'string') {
      evt.eventLabel = options.label
      delete options.label
    }

    // Value is optional, but when used must be an
    // integer, otherwise the event will be invalid
    // and not logged
    if (options.value || options.value === 0) {
      value = parseInt(options.value, 10)
      if (typeof value === 'number' && !isNaN(value)) {
        options.eventValue = value
      }
      delete options.value
    }

    // Prevents an event from affecting bounce rate
    // https://developers.google.com/analytics/devguides/collection/analyticsjs/events#implementation
    if (options.nonInteraction) {
      options.nonInteraction = 1
    }

    if (typeof options === 'object') {
      $.extend(evt, options)
    }

    sendToGa('send', evt)
  }

  /*
    https://developers.google.com/analytics/devguides/collection/analyticsjs/social-interactions
    network – The network on which the action occurs (e.g. Facebook, Twitter)
    action – The type of action that happens (e.g. Like, Send, Tweet)
    target – Specifies the target of a social interaction.
             This value is typically a URL but can be any text.
  */
  GoogleAnalyticsUniversalTracker.prototype.trackSocial = function (network, action, target) {
    sendToGa('send', {
      'hitType': 'social',
      'socialNetwork': network,
      'socialAction': action,
      'socialTarget': target
    })
  }

  /*
   https://developers.google.com/analytics/devguides/collection/analyticsjs/cross-domain
   trackerId - the UA account code to track the domain against
   name      - name for the tracker
   domain    - the domain to track
  */
  GoogleAnalyticsUniversalTracker.prototype.addLinkedTrackerDomain = function (trackerId, name, domain) {
    sendToGa('create',
             trackerId,
             'auto',
             {'name': name})
    // Load the plugin.
    sendToGa('require', 'linker')
    sendToGa(name + '.require', 'linker')

    // Define which domains to autoLink.
    sendToGa('linker:autoLink', [domain])
    sendToGa(name + '.linker:autoLink', [domain])

    sendToGa(name + '.set', 'anonymizeIp', true)
    sendToGa(name + '.send', 'pageview')
  }

  // https://developers.google.com/analytics/devguides/collection/analyticsjs/custom-dims-mets
  GoogleAnalyticsUniversalTracker.prototype.setDimension = function (index, value) {
    sendToGa('set', 'dimension' + index, String(value))
  }

  function sendToGa () {
    if (typeof global.ga === 'function') {
      global.ga.apply(global, arguments)
    }
  }

  GOVUK.GoogleAnalyticsUniversalTracker = GoogleAnalyticsUniversalTracker

  global.GOVUK = GOVUK
})(window)
;
;(function (global) {
  'use strict'

  var GOVUK = global.GOVUK || {}

  // For usage and initialisation see:
  // https://github.com/alphagov/govuk_frontend_toolkit/blob/master/docs/analytics.md#create-an-analytics-tracker

  var Analytics = function (config) {
    this.trackers = []
    if (typeof config.universalId !== 'undefined') {
      var universalId = config.universalId
      delete config.universalId
      this.trackers.push(new GOVUK.GoogleAnalyticsUniversalTracker(universalId, config))
    }
    if (typeof config.govukTrackerGifUrl !== 'undefined') {
      var govukTrackerGifUrl = config.govukTrackerGifUrl
      delete config.govukTrackerGifUrl
      this.trackers.push(new GOVUK.GOVUKTracker(govukTrackerGifUrl))
    }
  }

  Analytics.prototype.sendToTrackers = function (method, args) {
    for (var i = 0, l = this.trackers.length; i < l; i++) {
      var tracker = this.trackers[i]
      var fn = tracker[method]

      if (typeof fn === 'function') {
        fn.apply(tracker, args)
      }
    }
  }

  Analytics.load = function () {
    GOVUK.GoogleAnalyticsUniversalTracker.load()
    GOVUK.GOVUKTracker.load()
  }

  Analytics.prototype.trackPageview = function (path, title, options) {
    this.sendToTrackers('trackPageview', arguments)
  }

  /*
    https://developers.google.com/analytics/devguides/collection/analyticsjs/events
    options.label – Useful for categorizing events (eg nav buttons)
    options.value – Values must be non-negative. Useful to pass counts
    options.nonInteraction – Prevent event from impacting bounce rate
  */
  Analytics.prototype.trackEvent = function (category, action, options) {
    this.sendToTrackers('trackEvent', arguments)
  }

  Analytics.prototype.trackShare = function (network) {
    this.sendToTrackers('trackSocial', [network, 'share', global.location.pathname])
  }

  /*
    The custom dimension index must be configured within the
    Universal Analytics profile
   */
  Analytics.prototype.setDimension = function (index, value) {
    this.sendToTrackers('setDimension', arguments)
  }

  /*
   Add a beacon to track a page in another GA account on another domain.
   */
  Analytics.prototype.addLinkedTrackerDomain = function (trackerId, name, domain) {
    this.sendToTrackers('addLinkedTrackerDomain', arguments)
  }

  GOVUK.Analytics = Analytics

  global.GOVUK = GOVUK
})(window)
;
;GOVUK.analyticsSetup = function(global) {
  "use strict";

  var $ = global.jQuery
  var GOVUK = global.GOVUK || {}
  var gaConfig = global.gaConfig || {}

  // Load Google Analytics libraries
  GOVUK.Analytics.load();

  // Use document.domain in dev, preview and staging so that tracking works
  // Otherwise explicitly set the domain as lastingpowerofattorney.service.justice.gov.uk.
  var cookieDomain = (document.domain === 'lastingpowerofattorney.service.justice.gov.uk') ? '.lastingpowerofattorney.service.justice.gov.uk' : document.domain;

  // Configure profiles and make interface public
  // for custom dimensions, virtual pageviews and events
  GOVUK.analytics = new GOVUK.Analytics({
    universalId: gaConfig.universalId  || '',
    cookieDomain: cookieDomain,
    allowLinker: true,
    allowAnchor: true
  });

  // Track initial pageview
  if (typeof GOVUK.pageviewOptions !== 'undefined') {
    GOVUK.analytics.trackPageview(null, null, GOVUK.pageviewOptions);
  }
  else {
    GOVUK.analytics.trackPageview();
  }

};

GOVUK.analyticsSetup(window);
/**
 * JQuery Postcode Lookup plugin for OPG-LPA project
 * Relies on /postcode/lookup route
 *
 * @copyright The Engine Group
 * @author Chris Moreton <chris.moreton@netsensia.com>
 */

(function ($) {
  'use strict';
  $.fn.hasScrollBar = function () {
    return this.get(0).scrollHeight > this.innerHeight();
  };
})(jQuery);;
/**
 * jQuery Ajax Spinner for OPG LPA project
 *
 * @copyright MOJ Digital Services Division
 * @author Mat Harden <mat.harden@digital.justice.gov.uk>
 */

(function ($) {
  'use strict';

  // Create the defaults once
  var pluginName = 'spinner',
      defaults = { disabledClass: 'disabled', placement: 'after' };

  // The actual plugin constructor
  function Plugin(element, options) {
    // Merge options with defaults
    this.options = $.extend({}, defaults, options);

    // Set master element
    this.element = element;
    this.$el = $(element);
    this.$spinElement = this.options.element ? $(this.options.element) : this.$el;

    this._defaults = defaults;
    this._name = pluginName;

    this.init();
  }

  Plugin.prototype = {

    init: function () {
      if (this.disabled()) {
        return;
      }
      if (this.options.placement === 'after') {
        this.$spinElement.after($('<img src="/assets/v2/images/ajax-loader.gif" alt="Loading spinner" class="spinner" />'));
      } else if (this.options.placement === 'before') {
        this.$spinElement.before($('<img src="/assets/v2/images/ajax-loader.gif" alt="Loading spinner" class="spinner" />'));
      }
      this.disable();
    },

    disable: function () {
      // Apply disabled class to trigger element
      this.$el.addClass(this.options.disabledClass);

      // If it's a form control disable it
      if (this.isFormControl()) {
        this.$el.prop('disabled', true);
      }

      if (this.isLink()) {
        var href = this.$el.attr('href');
        this.$el.data('href', href).removeAttr('href');
      }
    },

    enable: function () {
      this.$el.removeClass(this.options.disabledClass);

      if (this.isFormControl()) {
        this.$el.prop('disabled', false);
      }

      if (this.isLink()) {
        var href = this.$el.data('href');
        this.$el.attr('href', href);
      }
    },

    off: function () {
      this.enable();
      this.$spinElement.siblings('img.spinner').remove();
    },

    disabled: function () {
      return this.$el.hasClass(this.options.disabledClass);
    },

    isFormControl: function () {
      if (this.element.tagName === 'SELECT' || this.element.tagName === 'BUTTON' || this.element.tagName === 'INPUT') {
        return true;
      }
    },

    isLink: function () {
      return this.element.tagName === 'A';
    }
  };

  // Plugin wrapper
  $.fn[pluginName] = function (options) {
    return this.each(function () {
      var data = $.data(this),
          plugin = 'plugin_' + pluginName;

      if (data[plugin]) {
        if (options === 'off') {
          data[plugin].off();
        } else {
          data[plugin].init();
        }
      } else {
        $.data(this, plugin, new Plugin(this, options));
      }
    });
  };

})(jQuery);;
(function () {
  'use strict';

  var moj = {

    Modules: {},

    Helpers: {},

    Events: $({}),

    init: function () {
      for (var x in moj.Modules) {
        if (typeof moj.Modules[x].init === 'function') {
          moj.Modules[x].init();
        }
      }
      // trigger initial render event
      moj.Events.trigger('render');
    },

    // safe logging
    log: function (msg) {
      if (window && window.console) {
        window.console.log(msg);
      }
    },
    dir: function (obj) {
      if (window && window.console) {
        window.console.dir(obj);
      }
    }

  };

  window.moj = moj;
}());

(function () {
  'use strict';

  // Invite interested developers to join us
  moj.Modules.devs = {
    init: function () {
      var m = '      ___          ___       ___\n     /__/\\        /  /\\     /  /\\\n    |  |::\\      /  /::\\   /  /:/\n    |  |:|:\\    /  /:/\\:\\ /__/::\\\n  __|__|:|\\:\\  /  /:/  \\:\\\\__\\/\\:\\\n /__/::::| \\:\\/__/:/ \\__\\:\\  \\  \\:\\\n \\  \\:\\~~\\__\\/\\  \\:\\ /  /:/   \\__\\:\\\n  \\  \\:\\       \\  \\:\\  /:/    /  /:/\n   \\  \\:\\       \\  \\:\\/:/    /__/:/\n    \\  \\:\\       \\  \\::/     \\__\\/\n     \\__\\/        \\__\\/',
      txt = '\n\nLike what you see? Want to make a difference?' +
            '\n\nFind out how we\'re making the Ministry Of Justice Digital by Default.' +
            '\nhttp://blogs.justice.gov.uk/digital/.' +
            '\n\nGet in touch to see what positions are available and see what projects you could be working on.' +
            '\nhttps://twitter.com/Justice_Digital';
      moj.log(m + txt);
    }
  };
}());;
(function () {
  'use strict';

  // test for html5 storage
  moj.Helpers.hasHtml5Storage = function () {
    try {
      return 'sessionStorage' in window && window.sessionStorage !== null;
    } catch (e) {
      return false;
    }
  };

  // helper to check if popup is currently open
  moj.Helpers.isPopupOpen = function () {
    if (moj.Modules.Popup !== undefined) {
      return moj.Modules.Popup.isOpen();
    } else {
      return false;
    }
  };

  // check if children are all empty
  moj.Helpers.hasCleanFields = function (wrap) {
    var clean = true;
    $('input:not([type="submit"]), select:not([name*="country"]), textarea', wrap).each(function () {
      if ($(this).val() !== '') {
        clean = false;
      }
    });
    return clean;
  };

  // helper to return the scroll position of an element
  moj.Helpers.scrollTo = function (e) {
    var $target = e.target !== undefined ? $($(e.target).attr('href')) : $(e),
        $scrollEl = moj.Helpers.isPopupOpen() ? $('#mask') : $('html, body'),
        topPos = moj.Helpers.scrollPos($target);

    $scrollEl
      .animate({
        scrollTop: topPos
      },0)
      .promise()
      .done(function () {
        $target.closest('.group').find('input, select, textarea').first().focus();
      });
  };

  // helper to return the scroll position of an element
  moj.Helpers.scrollPos = function (target) {
    /*jshint laxbreak: true */
    return moj.Helpers.isPopupOpen()
              ? target.offset().top - $('#popup').offset().top
              : target.offset().top;
  };

  moj.Helpers.isMobileWidth = function () {
    var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    if (w > 640) {
      return false;
    } else {
      return true;
    }
  };
})();;
this["lpa"] = this["lpa"] || {};
this["lpa"]["templates"] = this["lpa"]["templates"] || {};

this["lpa"]["templates"]["alert.withinForm"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<div class=\"form-group ";
  if (helper = helpers.elementJSref) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementJSref); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">\n	<div class=\"alert panel text\" role=\"alert\" tabindex=\"-1\">\n		<i class=\"icon icon-";
  if (helper = helpers.alertType) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.alertType); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" role=\"presentation\"></i>\n		<div class=\"alert-message\">\n			";
  if (helper = helpers.alertMessage) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.alertMessage); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n		</div>\n	</div>\n</div>";
  return buffer;
  });

this["lpa"]["templates"]["dialog.confirmRepeatApplication"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<div class=\"dialog-container\">\n\n    <div class=\"dialog-title-block\">";
  if (helper = helpers.dialogTitle) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.dialogTitle); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</div>\n\n    <div class=\"dialog-message-block\"><p>";
  if (helper = helpers.dialogMessage) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.dialogMessage); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</p></div>\n\n    <div class=\"dialog__button-bar\">\n        <a class=\"button dialog__button--accept ";
  if (helper = helpers.acceptClass) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.acceptClass); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">";
  if (helper = helpers.acceptButtonText) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.acceptButtonText); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</a>\n        <a class=\"button-secondary dialog__button--cancel  ";
  if (helper = helpers.cancelClass) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.cancelClass); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">";
  if (helper = helpers.cancelButtonText) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.cancelButtonText); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</a>\n    </div>\n\n</div>\n";
  return buffer;
  });

this["lpa"]["templates"]["errors.formElement"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<div class=\"form-element-errors\">\n    <div class=\"group validation\">\n        <span class=\"validation-message\">";
  if (helper = helpers.validationMessage) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.validationMessage); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</span>\n    </div>\n</div>";
  return buffer;
  });

this["lpa"]["templates"]["errors.formMessage"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<span class=\"error-message text\">";
  if (helper = helpers.errorMessage) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.errorMessage); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</span>";
  return buffer;
  });

this["lpa"]["templates"]["errors.formSummary"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"validation-summary group\" role=\"alert\" aria-labelledby=\"error-heading\" tabindex=\"-1\">\n    <h1 id=\"error-heading\">There was a problem submitting the form</h1>\n\n    <p>Because of the following problems:</p>\n    <ol>\n    </ol>\n</div>\n";
  });

this["lpa"]["templates"]["input.checkbox"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, helper, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<fieldset class=\"";
  if (helper = helpers.elementJSref) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementJSref); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">\n    <div class=\"input-checkbox group\">\n        <label for=\"";
  if (helper = helpers.elementName) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementName); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">\n            <input type=\"checkbox\" name=\"";
  if (helper = helpers.elementName) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementName); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" id=\"";
  if (helper = helpers.elementName) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementName); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" class=\"confirmation-validation\" value=\"1\"\n                   required=\"required\">\n            ";
  if (helper = helpers.elementLabel) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.elementLabel); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n        </label>\n    </div>\n</fieldset>\n";
  return buffer;
  });

this["lpa"]["templates"]["popup.close"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<p class=\"close\">\n  <a href=\"#\" class=\"js-popup-close button-close\" title=\"Click or press escape to close this window\">Close</a>\n</p>";
  });

this["lpa"]["templates"]["popup.container"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div id=\"popup\" role=\"dialog\" class=\"popup\"></div>";
  });

this["lpa"]["templates"]["popup.content"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div id=\"popup-content\"></div>";
  });

this["lpa"]["templates"]["popup.mask"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div id=\"mask\" class=\"popover-mask\"></div>";
  });

this["lpa"]["templates"]["postcodeLookup.address-change"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"form-group\">\n	<ul class=\"address-type-toggle\">\n		<li><a href=\"#\" class=\"js-PostcodeLookup__change\" title=\"Search for UK Postcode\">Search for UK postcode</a></li>\n	</ul>\n</div>\n";
  });

this["lpa"]["templates"]["postcodeLookup.address-toggle"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"form-group\">\n	<ul class=\"address-type-toggle\">\n		<li><a href=\"#\" class=\"js-PostcodeLookup__toggle-address\">Enter address manually</a></li>\n	</ul>\n</div>\n";
  });

this["lpa"]["templates"]["postcodeLookup.search-field"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"form-group js-PostcodeLookup__search\">\n  <label class=\"form-label\" for=\"postcode-lookup\">Postcode lookup</label>\n  <input autocomplete=\"off\" type=\"text\" id=\"postcode-lookup\" class=\"postcode-input form-control js-PostcodeLookup__query\">\n  <a href=\"#\" id=\"find_uk_address\" class=\"postcode-button button js-PostcodeLookup__search-btn\" role=\"button\">Find UK address</a>\n</div>\n";
  });

this["lpa"]["templates"]["postcodeLookup.search-result"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression, self=this;

function program1(depth0,data) {
  
  var buffer = "", stack1, helper;
  buffer += "\n      <option value=\"";
  if (helper = helpers.id) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.id); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-line1=\"";
  if (helper = helpers.line1) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.line1); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-line2=\"";
  if (helper = helpers.line2) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.line2); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-line3=\"";
  if (helper = helpers.line3) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.line3); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\" data-postcode=\"";
  if (helper = helpers.postcode) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.postcode); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "\">";
  if (helper = helpers.description) { stack1 = helper.call(depth0, {hash:{},data:data}); }
  else { helper = (depth0 && depth0.description); stack1 = typeof helper === functionType ? helper.call(depth0, {hash:{},data:data}) : helper; }
  buffer += escapeExpression(stack1)
    + "</option>\n    ";
  return buffer;
  }

  buffer += "<div class=\"form-group\">\n  <label class=\"form-label\" for=\"address-search-result\">Address</label>\n  <select class=\"form-control js-PostcodeLookup__search-results\" id=\"address-search-result\">\n    <option value=\"\">Please select an address...</option>\n    ";
  stack1 = helpers.each.call(depth0, (depth0 && depth0.results), {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n  </select>\n</div>\n";
  return buffer;
  });

this["lpa"]["templates"]["shared.loading-popup"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"loading\">\n	<img src=\"/assets/v2/images/ajax-loader.gif\" class=\"spinner\">\n	<p>Loading</p>\n</div>\n";
  });;
// Fees module for LPA
// Dependencies: moj, jQuery

(function() {
    // Applies to /login /signup and /user/change-password
    // on change password page there are two show / hide links
    moj.Modules.PasswordHide = {

        init: function () {
            this.hookupShowPasswordToggles();
        },

        hookupShowPasswordToggles: function() {
            var link = $('.js-showHidePassword');
            var skipConfirmPassword = $('#js-skipConfirmPassword');
            var pwdConfirmParent = $('#password_confirm').parent();

            //  The show/hide password links are themselves hidden by default so they're not available for non-JS - show them now
            link.removeClass('hidden');

            //  By default ensure that the confirm password hidden validation skip value is set to false and show the link
            skipConfirmPassword.val(0);

            link.click(function() {
                var pwd = $('#' + $(this).attr('data-for'));
                var alsoHideConfirm = $(this).attr('data-alsoHideConfirm');

                //  Determine if we are showing or hiding the password confirm input
                var isShowing = (pwd.attr('type') === "password");

                if (isShowing) {
                    if (alsoHideConfirm) {
                        pwdConfirmParent.addClass('hidden');
                        skipConfirmPassword.val(1);
                    }
                } else {
                    if (alsoHideConfirm) {
                        pwdConfirmParent.removeClass('hidden');
                        skipConfirmPassword.val(0);
                    }
                }

                //  Change the input values as required
                pwd.attr('type', (isShowing ? 'text' : 'password'));
                $(this).html(isShowing ? 'Hide password' : 'Show password');

                return false;
            });
        }
    };

})();
;
// Popup module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  // Define the class
  var Popup = function (options) {
    this.settings = $.extend({}, this.defaults, options);
    this._cacheEls();
  };

  Popup.prototype = {
    defaults: {
      source: $('#content'),
      placement: 'body',
      popupId: 'popup',
      ident: null,
      maskTemplate: lpa.templates['popup.mask'](),
      containerTemplate: lpa.templates['popup.container'](),
      contentTemplate: lpa.templates['popup.content'](),
      closeTemplate: lpa.templates['popup.close'](),
      beforeOpen: null,
      onOpen: null,
      onClose: null
    },

    init: function () {},

    _cacheEls: function () {
      this.$win = $(window);
      this.$html = $('html');
      this.$body = $('body');
      this.$mask = $(this.settings.maskTemplate);
      this.$popup = $(this.settings.containerTemplate);
      this.$content = $(this.settings.contentTemplate);
      this.$close = $(this.settings.closeTemplate);
    },

    _bindEvents: function () {
      var self = this;

      $('body').on('keydown.moj.Modules.Popup', function (e) {
        if (e.which === 27) {
          self.close();
        }
      });
      this.$popup.on('click.moj.Modules.Popup', '.js-popup-close, .js-cancel', function (e) {
        e.preventDefault();
        self.close();
      });
    },

    _unbindEvents: function () {
      $('body').off('keydown.moj.Modules.Popup');
      this.$popup.off('click.moj.Modules.Popup');
    },

    open: function (html, opts) {
      var self = this;
      // combine opts with settings for local settings
      opts = $.extend({}, this.settings, opts);

      // disable body scroll
      $('html').addClass('noscroll');
      // hide main contents from print layout
      $('body > *').addClass('print-hidden');

      // Join it all together
      this.$popup.data('settings', opts)
        .removeClass()
        .addClass('popup ' + opts.ident)
        .append(this.$close)
        .append(this.$content.html(html))
        .appendTo(this.$mask);

      // bind event handlers
      this._bindEvents();

      // Place the mask in the DOM
      // If a placement has been provided, the popup is appended to that element,
      // otherwise the popup is appended to the body element.
      $(opts.placement)[opts.placement === 'body' ? 'append' : 'after'](this.$mask);

      // callback func
      if (opts.beforeOpen && typeof(opts.beforeOpen) === 'function') {
        opts.beforeOpen();
      }

      // Fade in the mask
      this.$mask.fadeTo(200, 1);

      // Center and fase in the popup
      this.$popup.delay(100).fadeIn(200, function () {
        self.$popup.find('h2').attr('tabindex', -1);
        self.$popup.find('.close a').focus(); // for accessibility

        // set tabs
        self.loopTabKeys(self.$popup);

        // callback func
        if (opts.onOpen && typeof(opts.onOpen) === 'function') {
          opts.onOpen();
        }
      });
    },

    close: function () {
      // make sure there is a popup to close
      if (this.isOpen()) {
        var self = this,
            opts = $('#popup').data('settings'),
            scrollPosition = $(window).scrollTop();

        self.$popup.fadeOut(400, function () {
          self.$mask.fadeOut(200, function () {
            // focus on previous element
            if (typeof opts.source !== 'undefined' && opts.source) {
              opts.source.focus();
            }
            // clear out any hash locations
            window.location.hash = '';
            if (history.pushState) {
              history.pushState('', document.title, window.location.pathname);
            }

            // callback func
            if (opts.onClose && typeof(opts.onClose) === 'function') {
              opts.onClose();
            }

            // Remove the popup from the DOM
            $(this).remove();

            // re-enable body scroll
            $(window).scrollTop(scrollPosition);
            $('html').removeClass('noscroll');
            // unhide main contents from print layout
            $('body > *').removeClass('print-hidden');

            // unbind event handlers
            self._unbindEvents();
          });
        });
      }
    },

    loopTabKeys: function (wrap) {
      var tabbable = 'a, area, button, input, object, select, textarea, [tabindex]',
          first = wrap.find(tabbable).filter(':first'),
          last = wrap.find(tabbable).filter(':last');

      first.add(last).keydown(function (e) {

        var code = (e.keyCode ? e.keyCode : e.which),
            shift = e.shiftKey,
            self = $(this)[0],
            down = (self === last[0] && !shift),
            up = (self === first[0] && shift),
            focusOn = down ? first : (up ? last : null);

        if (code === 9 && (down || up)) {
          e.preventDefault();
          focusOn.focus();
        }
      });
    },

    isOpen: function () {
      return $('#' + this.settings.popupId).length > 0;
    }
  };

  // Add module to MOJ namespace
  moj.Modules.Popup = new Popup();
}());;
// Help System module for LPA
// Dependencies: popup, moj, jQuery

(function () {
  'use strict';

  // Define the class
  var HelpSystem = function (options) {
    this.settings = $.extend({}, this.defaults, options);
  };

  HelpSystem.prototype = {
    defaults: {
      guidancePath: 'guide',
      selector: 'a.js-guidance',
      overlayIdent: 'help-system',
      overlaySource: '#content',
      loadingTemplate: lpa.templates['shared.loading-popup'](),
      popupOnClose: function () {
        moj.Modules.HelpSystem.topic = undefined;
      }
    },

    init: function () {
      // only load if not on the static page
      if ($('#help-system').length === 0) {
        this._cacheEls();
        this._bindEvents();

        // open popup if hash is present in url
        var hash = window.location.hash,
          topic;
        if (this._isGuidanceHash(hash)) {
          // on page load parse hash
          topic = hash.substring(hash.lastIndexOf('/') + 1);
          // set topic
          this._selectHelpTopic(topic);
        }
      }
    },

    _cacheEls: function () {
      this.html = undefined;
      this.topic = false;
      this.source = false;
    },

    _bindEvents: function () {
      var self = this;

      // nav click event
      $('body').on('click', this.settings.selector, function () {
        // Be a normal link for mobile and go to the non-js guidance page
        if (moj.Helpers.isMobileWidth()) {
          return true;
        }
        var href = $(this).attr('href'),
          topic = href.substring(href.lastIndexOf('#') + 1);
        // report the click to ga
        ga('send', 'pageview', href);
        // set the current click as the source
        self.source = $(this);
        // select topic
        self._selectHelpTopic(topic);
        return false;
      });

      // listen to hash changes in url
      $(window).on('hashchange.moj.Modules.HelpSystem', function () {
        var hash = window.location.hash,
          topic;

        // if a change has been made, select the topic
        if (self._isGuidanceHash(hash)) {
          topic = hash.substring(hash.lastIndexOf('/') + 1);
          self._selectHelpTopic(topic);
        }
        // if the new hash is empty, clear out the popup
        else if (hash === '') {
          moj.Modules.Popup.close();
        }
      });
    },

    _isGuidanceHash: function (hash) {
      return hash !== '' && hash !== '#/' && hash.indexOf(this.settings.guidancePath) !== -1;
    },

    _selectHelpTopic: function (topic) {
      var self = this;

      // make sure no duplicate calls are fired
      if (topic !== this.topic) {
        // if the overlay is present, set topic immediately
        if ($('#popup.help-system').length > 0) {
          self._setTopic(topic);
          // On small screens, jump to topic
          if (moj.Helpers.isMobileWidth()) {
            $('#' + topic)[0].scrollIntoView(true);
          }
        } else {
          // otherwise, load in the overlay first and set in callback
          this._loadOverlay(topic);
        }
      }
    },

    _setTopic: function (slug) {
      // set topic to global obj
      this.topic = slug;

      // make sure we're not resetting the hash and adding to the history if we don't need to
      if ('#/' + this.settings.guidancePath + '/' + slug !== window.location.hash) {
        window.location.hash = '#/' + this.settings.guidancePath + '/' + slug;
      }

      // Set nav item as active
      $('.help-navigation a[href$="#' + slug + '"]').parent() // use 'ends with' selector so don't have to define url slug
        .addClass('active')
        .siblings('li')
        .removeClass('active');

      // Show associated content
      $('#' + slug)
        .removeClass('hidden')
        .siblings('article')
        .addClass('hidden');

      // Associated back link visibility
      $('.link-back')
        .addClass('hidden');

      $('#' + slug + ' + .link-back')
        .removeClass('hidden');

      // Scroll back to top of help
      $('#mask').scrollTop(0);
    },

    _hasCachedContent: function () {
      // first try to load from html5 storage
      if (moj.Helpers.hasHtml5Storage() && typeof sessionStorage.guidanceHTML !== 'undefined') {
        return sessionStorage.guidanceHTML;
      }
      // then try from this class
      else if (typeof this.html !== 'undefined') {
        return this.html;
      }
      // otherwise, return false
      else {
        return false;
      }
    },

    _loadOverlay: function (topic) {
      var self = this,
        html = this._hasCachedContent();

      // todo - remove this
      html = false;

      // if content has been cached, load it straight in
      if (html !== false) {
        moj.Modules.Popup.open(html, {
          ident: this.settings.overlayIdent,
          source: this.source,
          beforeOpen: function () {
            // set topic
            self._setTopic(topic);
          },
          onClose: this.settings.popupOnClose
        });
      }
      // otherwise, AJAX it in and then switch the content in the popup
      else {
        // load overlay
        moj.Modules.Popup.open(this.settings.loadingTemplate, {
          ident: self.settings.overlayIdent,
          source: this.source,
          beforeOpen: function () {
            $('#popup-content').load('/' + self.settings.guidancePath, function (html) {
              // cache content
              if (moj.Helpers.hasHtml5Storage()) {
                // save to html5 storage
                sessionStorage.guidanceHTML = html;
              } else {
                // save to obj
                self.html = html;
              }
              // set the topic now that all content has loaded
              self._setTopic(topic);
            });
          },
          onClose: this.settings.popupOnClose
        });
      }
    }
  };

  // Add module to MOJ namespace
  moj.Modules.HelpSystem = new HelpSystem();
}());;
// Form Popup module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  // Define the class
  var FormPopup = function (options) {
    this.settings = $.extend({}, this.defaults, options);
  };

  FormPopup.prototype = {
    defaults: {
      selector: '.js-form-popup',
      overlayIdent: 'form-popup',
      overlaySource: '#content'
    },

    init: function () {
      // bind 'this' as this in following methods
      _.bindAll(this, 'btnClick', 'submitForm');
      this.cacheEls();
      this.bindEvents();
    },

    cacheEls: function () {
      this.formContent = [];
      this.originalSource = false;
      this.source = false;
    },

    bindEvents: function () {
      $('body')
        // form open
        .on('click.moj.Modules.FormPopup', this.settings.selector, this.btnClick)
        // submit form
        .on('submit.moj.Modules.FormPopup', '#popup.form-popup form', this.submitForm);
        moj.Events.on('FormPopup.renderSelectionButtons', this.renderSelectionButtons);
        moj.Events.on('FormPopup.checkReusedDetails', this.checkReusedDetails);
    },

    renderSelectionButtons: function() {
        //switch checkboxes on (usually only happens on page load so need to manually do this step when opening lightbox)
        // Use GOV.UK selection-buttons.js to set selected
        // and focused states for block labels
        var $blockLabels = $(".block-label input[type='radio'], .block-label input[type='checkbox']");
        new GOVUK.SelectionButtons($blockLabels); // eslint-disable-line
    },

    btnClick: function (e) {
      var source = $(e.target),
        href = source.attr('href');

      // set original source to be the original link clicked form the body to be able to return to it when the popup is closed
      // fixes when links inside a popup load another form. User should be focused back to original content button when closing
      if ($('#popup').length === 0) {
        this.originalSource = source;
      }
      // always set this source to be the clicked link
      this.source = source;

      // If this link is disabled then stop here
      if (!source.hasClass('disabled')) {
        // set loading spinner
        source.spinner();
        // show form
        this.loadContent(href);
      }

      return false;
    },

    loadContent: function (url) {
      var self = this;

      $.get(url, function (html) {
        if (html.toLowerCase().indexOf('sign in') !== -1) {
          // if no longer signed in, redirect
          window.location.reload();
        } else {
          // render form and check the reused details content
          self.renderForm(html);

          if (url.indexOf('reuse-details') !== -1) {
            self.checkReusedDetails();
          }
        }
      });
    },

    checkReusedDetails: function () {
      // Align to top after loading in the content to avoid having the form starting half
      // way down (a scenario that happens when you've scrolled far down on Reuse details page)
      moj.Helpers.scrollTo('#popup-content');
      // If the user is reusing details then trigger some actions manually to give warning messages a chance to display
      $('#dob-date-day').trigger('change');
      $('input[name="name-first"]').trigger('change');
    },

    renderForm: function (html) {
      this.source.spinner('off');

      moj.Modules.Popup.open(html, {
        ident: this.settings.overlayIdent,
        source: this.originalSource,
        beforeOpen: function () {
          // trigger title replacement event
          moj.Events.trigger('TitleSwitch.render', {wrap: '#popup'});
          // trigger postcode lookup event
          moj.Events.trigger('PostcodeLookup.render', {wrap: '#popup'});
          // trigger person form events
          moj.Events.trigger('PersonForm.render', {wrap: '#popup'});
          // trigger polyfill form events
          moj.Events.trigger('Polyfill.fill', {wrap: '#popup'});
        }
      });

      this.renderSelectionButtons();
    },

    submitForm: function (e) {
      var $form = $(e.target),
        url = $form.attr('action'),
        method = 'post';

      $form.find('input[type="submit"]').spinner();

      //  If a method is set on the form use that value instead of the default post
      if ($form.attr('method') !== undefined) {
          method = $form.attr('method');
      }

      $.ajax({
        url: url,
        type: method,
        data: $form.serialize(),
        context: $form,
        success: this.ajaxSuccess,
        error: this.ajaxError
      });

      return false;
    },

    ajaxSuccess: function (response, textStatus, jqXHR) {
      var $form = $(this),
        data;

      if (response.success !== undefined && response.success) {
        // successful, so redirect
        window.location.reload();
      } else if (response.toLowerCase().indexOf('sign in') !== -1) {
        // if no longer signed in, redirect
        window.location.reload();
      } else if (jqXHR.status !== 200) {
        // if not a succesful request, reload page
        window.location.reload();
      } else {
        // if field errors, display them
        if (response.errors !== undefined) {
          data = {errors: []};
          $.each(response.errors, function (name, errors) {
            data.errors.push({label_id: name + '_label', label: $('#' + name + '_label').text(), error: errors[0]});
            moj.Events.trigger('Validation.renderFieldSummary', {form: $form, name: name, errors: errors});
          });
          moj.Events.trigger('Validation.renderSummary', {form: $form, data: data});
          // show error summary
        } else if (response.success === undefined) {
          // repopulate popup
          $('#popup-content').html(response);
          // trigger title replacement event
          moj.Events.trigger('TitleSwitch.render', {wrap: '#popup'});
          // trigger postcode lookup event
          moj.Events.trigger('PostcodeLookup.render', {wrap: '#popup'});
          // trigger validation accessibility method
          moj.Events.trigger('Validation.render', {wrap: '#popup'});
          moj.Events.trigger('FormPopup.renderSelectionButtons');
          //  If the form submitted a reuse details parameter then execute the check details
          if ($form.serialize().indexOf('reuse-details') !== -1) {
            moj.Events.trigger('FormPopup.checkReusedDetails');
          }
        } else {
          window.location.reload();
        }
        // stop spinner
        $form.find('input[type="submit"]').spinner('off');
      }
    },

    ajaxError: function () {
      // an error occured, reload the page
      window.location.reload();
    }
  };

  // Add module to MOJ namespace
  moj.Modules.FormPopup = new FormPopup();
}());
;
// Title Switch module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.TitleSwitch = {
    selector: '[name="name-title"]',

    init: function () {
      // bind 'this' as this in following methods
      _.bindAll(this, 'render', 'selectChanged', 'switchTitle');
      this.bindEvents();
    },

    bindEvents: function () {
      $('body').on('change.moj.Modules.TitleSwitch', '.js-TitleSwitch-select', this.selectChanged);
      // default moj render event
      moj.Events.on('render', this.render);
      // custom render event
      moj.Events.on('TitleSwitch.render', this.render);
    },

    render: function (e, params) {
      var wrap = params !== undefined && params.wrap !== undefined ? params.wrap : 'body';
      $(this.selector, wrap).each(this.switchTitle);
    },

    switchTitle: function (i, el) {
      var $titleInput = $(el),
          $label = $('label[for="' + $titleInput.attr('id') + '"]'),
          value = $titleInput.val(),
          options = $titleInput.data('select-options'),
          _this = this,
          $select;

      //  If the current value isn't an option then exit and just display as text
      if (!$.isArray(options) || !_.contains(options, value)) {
        return;
      }

      // build select box with the options
      $select = $('<select>', {
        'id': $titleInput.attr('id'),
        'name': $titleInput.attr('name'),
        'class': 'js-TitleSwitch-select form-control'
      });

      // add options and select an existing value if possible
      $.each(options, function (idx, text) {
        $select.append($('<option>', { value: text }).text(text));

        if (value === text) {
          $select.val(text);
        }
      });

      //  Replace the text input with the new select input
      $titleInput.replaceWith($select);
    },

    selectChanged: function (e) {
      var $titleInput = $(e.target),
          value = $titleInput.val();

      if (value === 'Other') {
        //  Replace the select input with a text input
        var $text = $('<input>', {
          'id': $titleInput.attr('id'),
          'name': $titleInput.attr('name'),
          'class': 'form-control'
        });

        //  Replace the select input with the new text input
        $titleInput.replaceWith($text);
        $titleInput.val(value);
      }
    }
  };

})();
;
// Postcode lookup module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  // will be populated with either "mojDs" or "postcodeAnywhere" following
  // initial call to F/E postcode endpoint
  var postcodeService = null;

  // Define the class
  var PostcodeLookup = function (el) {
      _.bindAll(this, 'searchClicked', 'toggleClicked', 'changeClicked', 'resultsChanged', 'queryEnter', 'postcodeSuccess', 'postcodeError', 'addressSuccess', 'populateFields');
      this.cacheEls(el);
      this.bindEvents();
      this.init();
  };

  PostcodeLookup.prototype = {
    settings: {
      postcodeSearchUrl: '/address-lookup',
      addressSearchUrl: '/address-lookup',
      // used to populate fields
      // key is the key name sent in response and value is name of app's field
      fieldMappings: {
        line1: 'address-address1',
        line2: 'address-address2',
        line3: 'address-address3',
        postcode: 'postcode'
      }
    },

    cacheEls: function (wrap) {
      this.$wrap = wrap;
      this.$form = this.$wrap.closest('form');
      this.$postalFields = this.$wrap.find('.js-PostcodeLookup__postal-add');

      this.searchTpl = lpa.templates['postcodeLookup.search-field'];
      this.toggleTpl = lpa.templates['postcodeLookup.address-toggle'];
      this.resultTpl = lpa.templates['postcodeLookup.search-result'];
      this.changeTpl = lpa.templates['postcodeLookup.address-change'];
      this.errorMessageTpl = lpa.templates['errors.formMessage'];
    },

    bindEvents: function () {
      this.$wrap.on('click.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__search-btn', this.searchClicked);
      this.$wrap.on('click.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__toggle-address', this.toggleClicked);
      this.$wrap.on('click.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__change', this.changeClicked);
      this.$wrap.on('change.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__search-results', this.resultsChanged);
      this.$wrap.on('keydown.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__query', this.queryEnter);
    },

    init: function () {
        // prepend template to postal fields
        this.$postalFields.before(this.searchTpl() + this.toggleTpl() + this.changeTpl()).addClass('hidden');

        // if all fields are empty and there are no validation messages, hide them
        if (moj.Helpers.hasCleanFields(this.$postalFields) && !$('.error-summary').length) {
            this.$wrap.find('.js-PostcodeLookup__change').closest('div').addClass('hidden');
        } else {
            this.hideSearchForm();
            this.toggleAddress();
        }
    },

    hideSearchForm: function() {
        this.$wrap.find('.js-PostcodeLookup__search').addClass('hidden');
        this.$wrap.find('.js-PostcodeLookup__toggle-address').closest('div').addClass('hidden');
        this.$wrap.find('.js-PostcodeLookup__change').closest('div').removeClass('hidden');
    },

    changeClicked: function(e) {
        this.$wrap.find('.js-PostcodeLookup__change').closest('div').addClass('hidden');
        this.$wrap.find('.js-PostcodeLookup__search').removeClass('hidden');
        if (moj.Helpers.hasCleanFields(this.$postalFields) && !$('.error-summary').length) {
            this.$wrap.find('.js-PostcodeLookup__toggle-address').closest('div').removeClass('hidden');
        }
        this.$wrap.find('.js-PostcodeLookup__query').focus();
        return false;
    },

    searchClicked: function (e) {
      var $el = $(e.target);
      var $searchContainer = this.$wrap.find('.js-PostcodeLookup__search');
      var $postcodeLabel = $('label[for="postcode-lookup"]');

      // store the current query
      this.query = this.$wrap.find('.js-PostcodeLookup__query').val();

      if (!$el.hasClass('disabled')) {
        if (this.query !== '') {
          $el.spinner();
          this.findPostcode(this.query);
          $searchContainer.removeClass('error');
          $postcodeLabel.children('.error-message').remove();
        } else {
          $searchContainer.addClass('error');
          $postcodeLabel.children('.error-message').remove();
          $postcodeLabel
            .append($(this.errorMessageTpl({
              'errorMessage': 'Please enter a postcode'
            })));
        }
      }
      return false;
    },

    toggleClicked: function (e) {
      var $el = $(e.target);
      this.toggleAddress();
      return false;
    },

    resultsChanged: function (e) {
      var $el = $(e.target),
      val = $el.val();

      if (postcodeService === 'mojDs') {
          var $selectedOption = $el.find(':selected');

	      $('[name*="' + this.settings.fieldMappings.line1 + '"]').val($selectedOption.data('line1'));
	      $('[name*="' + this.settings.fieldMappings.line2 + '"]').val($selectedOption.data('line2'));
	      $('[name*="' + this.settings.fieldMappings.line3 + '"]').val($selectedOption.data('line3'));
	      $('[name*="' + this.settings.fieldMappings.postcode + '"]').val($selectedOption.data('postcode')).change();
      } else {
    	  $el.spinner();
          this.findAddress(val);
      }

      this.toggleAddress();
    },

    queryEnter: function (e) {
      var code = (e.keyCode ? e.keyCode : e.which);

      if (code === 13) {
        e.preventDefault();
        this.$wrap.find('.js-PostcodeLookup__search-btn').click();
      }
    },

    findPostcode: function (query) {
      $.ajax({
        url: this.settings.postcodeSearchUrl,
        data: {postcode: query},
        dataType: 'json',
        timeout: 10000,
        cache: true,
        error: this.postcodeError,
        success: this.postcodeSuccess
      });
    },

    postcodeError: function (jqXHR, textStatus, errorThrown) {
      var errorText = 'There was a problem: ';

      this.$wrap.find('.js-PostcodeLookup__search-btn').spinner('off');

      if (textStatus === 'timeout') {
        errorText += 'the service did not respond in the allotted time';
      } else {
        errorText += errorThrown;
      }

      alert(errorText);
    },

    postcodeSuccess: function (response) {
      // not successful
      if (!response.success || response.addresses === null) {
        var $searchContainer = this.$wrap.find('.js-PostcodeLookup__search');
        var $postcodeLabel = $('label[for="postcode-lookup"]');

        if (response.isPostcodeValid) {
          $searchContainer.addClass('error');
          $postcodeLabel.children('.error-message').remove();
          $postcodeLabel
            .append($(this.errorMessageTpl({
              'errorMessage': 'No address found for this postcode. Please try again or enter the address manually.'
            })));
        } else {
          alert('Please enter a valid UK postcode');
        }
      } else {
        // successful

        postcodeService = response.postcodeService;

        if (this.$wrap.find('.js-PostcodeLookup__search-results').length > 0) {
          this.$wrap.find('.js-PostcodeLookup__search-results').parent().replaceWith(this.resultTpl({results: response.addresses}));
        } else {
          this.$wrap.find('.js-PostcodeLookup__search').after(this.resultTpl({results: response.addresses}));
        }
        this.$wrap.find('.js-PostcodeLookup__search-results').focus();
      }
      this.$wrap.find('.js-PostcodeLookup__search-btn').spinner('off');
    },

    findAddress: function (query) {
        $.ajax({
          url: this.settings.addressSearchUrl,
          data: {addressid: parseInt($.trim(query), 10)},
          dataType: 'json',
          timeout: 10000,
          cache: true,
          success: this.addressSuccess
        });
      },

    addressSuccess: function (response) {
      this.populateFields(response);
    },

    populateFields: function (data) {
      _.each(this.settings.fieldMappings, function (value, key) {
        if (value !== null) {
          this.$postalFields.find('[name*="' + value + '"]').val(data[key]).change();
        }
      }, this);
      this.toggleAddress();
      // remove result list
      this.$wrap.find('.js-PostcodeLookup__search-results').spinner('off');
    },

    toggleAddress: function () {
        var $search = this.$wrap.find('.js-PostcodeLookup__query'),
          $pcode = this.$wrap.find('[name*="' + this.settings.fieldMappings.postcode + '"]');
        // popuplate postcode field
        if ($search.val() !== '' && $pcode.val() === '') {
          $pcode.val($search.val()).change();
        }
        this.$postalFields.removeClass('hidden');
        // focus on first address field
        if ($('.js-PostcodeLookup__postal-add').parent().find('#address-search-result').length === 1) {
          this.$postalFields.find('[name*="addr1"]').focus();
        }
    }
  };

  // Add module to LPA namespace
  moj.Modules.PostcodeLookup = {
    init: function () {
      $('.js-PostcodeLookup').each(function () {
        if (!$(this).data('moj.PostcodeLookup')) {
          $(this).data('moj.PostcodeLookup', new PostcodeLookup($(this), $(this).data()));
        }
      });

      moj.Events.on('PostcodeLookup.render', this.render);
    },

    render: function (e, params) {
      $('.js-PostcodeLookup', params.wrap).each(function () {
        if (!$(this).data('moj.PostcodeLookup')) {
          $(this).data('moj.PostcodeLookup', new PostcodeLookup($(this), $(this).data()));
        }
      });
    }
  };
}());
;
// Person Form module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  var self;

  moj.Modules.PersonForm = {
    selector: '.js-PersonForm',

    init: function () {
      self = this;

      _.bindAll(this, 'render', 'formEvents');
      this.cacheEls();
      this.bindEvents();
    },

    cacheEls: function () {
      this.$els = $(this.selector);
    },

    bindEvents: function () {
      // default moj render event
      moj.Events.on('render', this.render);
      // custom render event
      moj.Events.on('TitleSwitch.render', this.render);
    },

    render: function (e, params) {
      var wrap = params !== undefined && params.wrap !== undefined ? params.wrap : 'body';
      $(this.selector, wrap).each(this.formEvents);
    },

    formEvents: function (i, el) {
      if (window && window.console) {
      	window.console.log('count ' + i);
      }

      // Set variables
      var $form = $(el),
        $submitBtn = $('input[type="submit"]', $form),
        $allFields = $('input[required], label.required + input, label.required ~ select', $form),
        $addressFields = $('input[name^="address"]', $form),
        allPopulated,
        countAddr,
        dob,
        getDOB = function () {
          var day,
            month,
            year,
            $dayObj = $('#dob-date-day'),
            $monthObj = $('#dob-date-month'),
            $yearObj = $('#dob-date-year'),
            returnDate;

          if ($dayObj.val() !== '') {
            day = parseInt($dayObj.val(), 10);
            if (isNaN(day) || day < 1 || day > 31) {
              day = undefined;
            }
          }
          if ($monthObj.val() !== '') {
            month = parseInt($monthObj.val(), 10);
            if (isNaN(month) || month <= 0 || month > 12) {
              month = undefined;
            }
            else {
              month = month - 1;
            }
          }
          if ($yearObj.val() !== '') {
            year = parseInt($yearObj.val(), 10);
            if (isNaN(year) || (year <= 0)) {
              year = undefined;
            }
          }

          returnDate = new Date(year, month, day);
          if (!isFinite(returnDate)) {
            returnDate = null;
          }

          return returnDate;

        },
        tplFormElementErrors = lpa.templates['errors.formElement'],
        tplErrorsFormSummary = lpa.templates['errors.formSummary'],
        tplAlert = lpa.templates['alert.withinForm'],
        tplInputCheckbox = lpa.templates['input.checkbox'];

      // Listen for changes to form
      $form
        .on('change.moj.Modules.PersonForm', 'input, select', function (evt) {


          var $target = $(evt.target),
            currentDate = new Date(),
            minAge = new Date(currentDate.getUTCFullYear() - 18, currentDate.getUTCMonth(), currentDate.getUTCDate()),
            maxAge = new Date(currentDate.getUTCFullYear() - 100, currentDate.getUTCMonth(), currentDate.getUTCDate()),
            $dobElement = $('.dob-element'),
            $dobGroup,
            actionGroup = $('.group.action'),
            $firstName = $('input[name="name-first"]', $form),
            $lastName = $('input[name="name-last"]', $form),
            duplicateName = null,
            loop,
            item;

          if (!$target.hasClass('confirmation-validation')) {
            // If the input changed is not a confirmation tick box, then do the form checks...


            // Are we editing the name fields?
            if (($target.attr('name') === 'name-first') || ($target.attr('name') === 'name-last')) {

              // Check for duplicate names
              var actorNames = $form.data('actor-names');

              if ((typeof actorNames !== 'undefined') && actorNames.length) {
                for (loop = 0; loop < actorNames.length; loop++) {
                  item = actorNames[loop];

                  if ($firstName.val().toLocaleLowerCase().trim() === item.firstname.toLocaleLowerCase()) {
                    if ($lastName.val().toLocaleLowerCase().trim() === item.lastname.toLocaleLowerCase()) {
                      duplicateName = item;
                      break;
                    }
                  }
                }
              }

              // Cleanup
              $('.js-duplication-alert').remove();

              // Display alert if duplicate
              if (duplicateName !== null) {
                //  Construct the correct starting phrase for the warning
                var alertStart = 'The ' + duplicateName.type + '\'s name is also ';

                if ($.inArray(duplicateName.type, ['replacement attorney', 'person to notify']) > -1) {
                  alertStart = 'There is already a ' + duplicateName.type + ' called ';
                } else if (duplicateName.type == 'attorney') {
                  alertStart = 'There is already an ' + duplicateName.type + ' called ';
                }

                $('label[for="name-last"]', $form)
                  .parents('.form-group')
                  .after($(tplAlert({
                    'elementJSref': 'js-duplication-alert',
                    'alertType': 'important-small',
                    'alertMessage': '<p>' + alertStart + duplicateName.firstname + ' ' + duplicateName.lastname + '. You can\'t use the same person in multiple roles. By saving this section, you are confirming that these are two different people with the same name.</p>'
                  })));

                // Focus on alert panel for accessibility
                $('.alert.panel').focus();
              }
            }


            // Are we editing the DOB?
            if ($target.parents('.dob-element').length) {

              // Cleanup
              $('.js-age-check').remove();

              dob = getDOB();
              if (dob !== null) {

                // Display alerts if under 18 or over 100 years old
                // Under 18 and earlier than today. A server side validation check is in place for dob greater than today.
                if (dob > minAge && dob < new Date()) {
                  $('.dob-element', $form)
                    .after($(tplAlert({
                      'elementJSref': 'js-age-check',
                      'alertType': 'important-small',
                      'alertMessage': 'This person is under 18. I understand they must be at least 18 <strong class="bold-small">on the date the donor signs the LPA</strong>, otherwise it will be rejected.'
                    })));
                }
                // Over 100
                else if (dob <= maxAge) {
                  $('.dob-element', $form)
                    .after($(tplAlert({
                      'elementJSref': 'js-age-check',
                      'alertType': 'important-small',
                      'alertMessage': 'By saving this section, you confirm that the person is more than 100 years old. If not, please change the date.'
                    })));
                }

                // Focus on alert panel for accessibility
                $('.alert.panel').focus();

              }

            }

          }

          $allFields = $('input[required], label.required + input, label.required ~ select', $form);

          allPopulated = true;

          // Test required fields are populated
          $allFields.each(function () {
            if (allPopulated) {

              var $field = $(this);

              if ($.trim($field.val()) === '') {
                allPopulated = false;
              }
              if ($field.prop('type') === 'checkbox') {
                allPopulated = $field.prop('checked');
              }

            }
          });

          // Count populated address fields
          countAddr = $addressFields.filter(function () {
            return this.value.length !== 0;
          }).length;

          // Test address fields - business logic states 2 address fields as min
          if (countAddr < 2) {
            allPopulated = false;
          }
        }
      )
        // Relationship: other toggle
        .on('change.moj.Modules.PersonForm', '[name="relationshipToDonor"]', function () {
          var other = $('#relationshipToDonorOther').closest('.group');
          if ($(this).val() === 'Other') {
            other.show().find('input').focus();
          } else {
            other.hide();
          }
        });

      // toggle initial change on donor relationship
      $('[name="relationshipToDonor"]', $form).change().closest('form').data('dirty', false);
    }

  };

})();
;
// Validation module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Validation = {
    selector: '.error-summary[role=group]',

    init: function () {
      _.bindAll(this, 'render');
      this.bindEvents();
      this.render(null, {wrap: 'body'});
    },

    bindEvents: function () {
      moj.Events.on('Validation.render', this.render);
    },

    render: function (e, params) {
      var $el = $(this.selector, $(params.wrap));

      // Focus on error summary
      if ($el.length > 0) {
        $el.focus();
      }
    }
  };

})();;
// User Timeout module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.UserTimeout = {
    timeoutDuration: 1000 * 60 * 55, // ms * s * m = 55 minutes
    gracePeriod: 1000 * 60 * 5, // ms * s * m = 5 minutes
    timeout: null,

    init: function () {
      _.bindAll(this, 'warning');

      var startTime = this.time();
      // check to see if user is logged in

      if (false) {

        $.ajax({
          url: '/user/is-logged-in',
          dataType: 'json',
          timeout: 10000,
          cache: false,
          context: this,
          success: function (response) {
            if (response.isLoggedIn) {
              // if logged in, start setTimeout to alert them when timeout is imminent
              this.timeout = setTimeout(this.warning, this.timeoutDuration - (this.time() - startTime));
            }
          }
        });

      }
    },

    warning: function () {
      var html = '',
        self = this;

      // TODO: get this HTML out of the JS into a template
      // TODO: get someone to look at this copy, I made it up. Clive.
      html += '<div class="timeout-body">';
      html += '<h1>Session timeout</h1>';
      html += '<p>You have been inactive for 55 minutes and will be automatically logged out in 5 minutes.</p>';
      html += '<p>Click \'OK\' to stay signed in and continue with your LPA.</p>';
      html += '<ul class="actions">';
      html += '<li><a id="sessionRefresh" class="button" href="#">OK</a></li>';
      html += '<li><a class="button-secondary" href="/user/logout">Sign out</a></li>';
      html += '</ul>';
      html += '</div>';

      moj.Modules.Popup.open(html, {
        ident: 'timeout'
      });

      $('#sessionRefresh').on('click', function (e) {
        e.preventDefault();
        window.clearTimeout(self.timeout);
        self.timeout = null;
        moj.Modules.Popup.close();
        self.refreshSession();
      });

      self.timeout = setTimeout(function () {
        // TODO: possibly redirect to a different 'timed out' page rather than standard logout?
        window.location = '/login/timeout';
      }, self.gracePeriod);
    },

    refreshSession: function () {
      var startTime = this.time();

      if (false) {

        $.ajax({
          url: '/user/is-logged-in',
          dataType: 'json',
          timeout: 10000,
          cache: false,
          context: this,
          success: function (response) {
            if (response.isLoggedIn) {
              // if logged in, start another timeout
              this.timeout = setTimeout(this.warning, this.timeoutDuration - (this.time() - startTime));
            } else {
              // redirect tp login if no longer logged in
              window.location = '/login/timeout';
            }
          },
          error: function () {
            // if any errors occur, attempt logout
            window.location = '/login/timeout';
          }
        });

      }
    },

    time: function () {
      return new Date().getTime();
    }
  };

})();;
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.StickyNav = {

    init: function () {
      _.bindAll(this, 'stickIt');
      this.stickIt();
    },

    stickIt: function () {
      if ($('nav.progress-indicator').length) {
        var em = 16, //assume 1em is 16px.
          contentWidth = Math.floor($('#content').width() - em),
          sticky = $('nav.progress-indicator'),
          stickyTop = sticky.offset().top;

        sticky.css({
          width: contentWidth
        });

        $(window).resize(function () {
          contentWidth = Math.floor($('#content').width() - em);
          sticky.css({
            width: contentWidth
          });
        });

        $(window).scroll(function () { // scroll event
          var windowTop = $(window).scrollTop();

          if (stickyTop < windowTop) {
            sticky.addClass('sticky').removeClass('static');
          } else {
            sticky.addClass('static').removeClass('sticky');
          }

        });
      }
    }
  };
})();
;
// Repeat Application code module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.RepeatApplication = {
    selector: '#lpa-type',

    init: function () {
      _.bindAll(this, 'render');
      this.cacheEls();
      this.bindEvents();
      this.render(null, {wrap: 'body'});
    },

    cacheEls: function () {
      this.$selector = $(this.selector);
    },

    bindEvents: function () {
      moj.Events.on('RepeatApplication.render', this.render);
    },
    displayCaseNumber: function (duration) {
      if ($('#is-repeat-application:checked').length) {
          $('.js-case-number').removeClass('js-hidden');
      }
      else {
          $('.js-case-number').addClass('js-hidden');
      }

    },
    render: function () {
      this.displayCaseNumber(0);
      this.initialiseEvents();

    },
    onRepeatApplicationFormChangeHandler: function () {
      this.displayCaseNumber(500);
    },
    onRepeatApplicationFormClickHandler: function (evt) {
      var tplDialogConfirm = lpa.templates['dialog.confirmRepeatApplication'],
        html,
        formToSubmit,
        formSubmitted = false;

      if ($('#is-repeat-application:checked').length) {

        formToSubmit = evt.target.form;
        evt.preventDefault();
        evt.stopImmediatePropagation();

        html = tplDialogConfirm({
          'dialogTitle': 'Confirm',
          'dialogMessage': 'I confirm that the Office of the Public Guardian has told me that I can apply to make a repeat application for £41 within 3 months.',
          'acceptButtonText': 'Confirm and continue',
          'cancelButtonText': 'Cancel',
          'acceptClass': 'js-dialog-accept',
          'cancelClass': 'js-dialog-cancel'
        });
        moj.Modules.Popup.open(html, {
          ident: 'dialog'
        });

        $('.dialog').on('click', 'a', function (evt) {
          var $target = $(evt.target);

          if (!formSubmitted) {

            if ($target.hasClass('js-dialog-accept')) {
              $target.addClass('disabled');
              formToSubmit.submit();
              formSubmitted = true;
            }
            else {
              moj.Modules.Popup.close();
            }

          }

        });

      }
    },
    initialiseEvents: function () {
      var self = this;

      $('form#form-repeat-application').on('change', 'input[type="radio"]', function (evt) {
        self.onRepeatApplicationFormChangeHandler(evt);
      });

      $('form#form-repeat-application').on('click', 'input[type="submit"]', function (evt) {
        self.onRepeatApplicationFormClickHandler(evt);
      });

    }
  };

})();
;
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Dashboard = {

    init: function () {
      this.changeMobileActions();
      this.searchFocus();
      this.ie8NthChildFix();
    },
    changeMobileActions: function(){
      // In list view on mobile, disable DETAILS and show all actions
      if (moj.Helpers.isMobileWidth()) {
        $('tr .lpa-actions').each(function(){
          $('.lpa-manage details', this).before($('.lpa-manage details ul', this));
          $('.lpa-manage details').addClass('hidden');
        });
      }
    },
    searchFocus:function(){
      // Add .focus class if there's text already there (on load)
      if ($('.js-search-focus').val() !== '') {
        $('.js-search-focus').addClass('focus');
      }
      // Add .focus class when input gets focus
      $('.js-search-focus').on('focus', function(){
        if (!$(this).hasClass('focus')){
          $(this).addClass('focus');
        }
      });
      // Remove .focus class if input is blurred
      $('.js-search-focus').on('blur', function(){
        if ($(this).val() === '') {
          $(this).removeClass('focus');
        }
      });
    },
    ie8NthChildFix: function(){
      if ($('html').hasClass('lte-ie8')) {
        $('.lpa-cards tr:nth-child(2n-1)').addClass('odd');
      }
    }
  };
  
})();
;
// UI Behaviour module for LPA
// Dependencies: moj, jQuery

(function () {
    'use strict';

    moj.Modules.UIBehaviour = {

        init: function () {
            this.sectionScrollTo();
        },

        sectionScrollTo: function(){
            var sectionToScrollTo = $('#current');

            if (sectionToScrollTo.offset() !== undefined ) {
                setTimeout(function() {
                    window.scrollTo(0, sectionToScrollTo.offset().top);
                }, 1);
            }
        }
    };
})();
;
// Dependencies: moj, jQuery

(function () {
    'use strict';

    moj.Modules.Applicant = {

        init: function () {
            this.selectionBehaviour();
        },

        selectionBehaviour: function() {
            // Only do the following if .js-attorney-list exists
            if ($('.js-attorney-list')[0]) {

                // Toggle all checkboxes under Attorneys
                $('[name="whoIsRegistering"]').change(function() {
                    if ($(this).val() === 'donor') {
                        //  Uncheck the attorney checkboxes and re-render the input styles
                        $('.js-attorney-list input:checkbox').prop('checked', false);

                        moj.Modules.Applicant.renderRegisteredByInputs();
                    }
                });

                //  If an attorney checkbox is checked then ensure that the correct radio button is selected
                $('.js-attorney-list input').change(function() {
                    //  If an attorney is selected directly then trigger the radio button select
                    if ($('.js-attorney-list input').is(':checked')) {
                        $('input[name="whoIsRegistering"][value!="donor"]').prop('checked', true);
                    }

                    moj.Modules.Applicant.renderRegisteredByInputs();
                });

            }
        },

        renderRegisteredByInputs: function () {
            //  Render the radio buttons and checkboxes as required
            $('[name="whoIsRegistering"]').parent().removeClass('selected');
            $('[name="whoIsRegistering"]:checked').parent().addClass('selected');

            $('.js-attorney-list input').parent().removeClass('selected');
            $('.js-attorney-list input:checked').parent().addClass('selected');
        }

    };
})();;
// Fees module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Fees = {

    init: function () {
      this.labelSubtextDisplay();
    },

    labelSubtextDisplay: function(){
      $('input[name=reductionOptions]').change(function(){
        $('.revised-fee').addClass('hidden');
        if ($('#reducedFeeReceivesBenefits').is(':checked')) {
          $('#revised-fee-0').removeClass('hidden');
        } else if ($('#reducedFeeUniversalCredit').is(':checked')) {
          $('#revised-fee-uc').removeClass('hidden');
        }
      });
    }
  };
})();;
// Who Are You module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.WhoAreYou = {

    init: function () {
      this.selectionBehaviour();
    },

    selectionBehaviour: function(){
      $('#WhoAreYou input:radio:checked').parents('.set').find('.subquestion:first').show();

      $('#WhoAreYou input:radio').change(function (){
        // Hides all sub-questions...
        $('#WhoAreYou .subquestion').hide();
        // Displays the first sub-questions for the selected radio (if any)...
        $(this).parents('.set').find('.subquestion:first').show();
        // Uncheck all other sub-questions (if any)...
        $('#WhoAreYou .subquestion:hidden input:radio').prop('checked', false);

      });
    }
  };
})();;
// <details> polyfill
// http://caniuse.com/#feat=details

// FF Support for HTML5's <details> and <summary>
// https://bugzilla.mozilla.org/show_bug.cgi?id=591737

// http://www.sitepoint.com/fixing-the-details-element/

//A fork of https://github.com/alphagov/govuk_elements/commits/master/assets/javascripts/govuk/details.polyfill.js

;(function () {
    'use strict'

    var NATIVE_DETAILS = typeof document.createElement('details').open === 'boolean'

    // Add event construct for modern browsers or IE
    // which fires the callback with a pre-converted target reference
    function addEvent (node, type, callback) {
        if (node.addEventListener) {
            node.addEventListener(type, function (e) {
                callback(e, e.target)
            }, false)
        } else if (node.attachEvent) {
            node.attachEvent('on' + type, function (e) {
                callback(e, e.srcElement)
            })
        }
    }

    // Handle cross-modal click events
    function addClickEvent (node, callback) {
        // Prevent space(32) from scrolling the page
        addEvent(node, 'keypress', function (e, target) {
            if (target.nodeName === 'SUMMARY') {
                if (e.keyCode === 32) {
                    if (e.preventDefault) {
                        e.preventDefault()
                    } else {
                        e.returnValue = false
                    }
                }
            }
        })
        // When the key comes up - check if it is enter(13) or space(32)
        addEvent(node, 'keyup', function (e, target) {
            if (e.keyCode === 13 || e.keyCode === 32) { callback(e, target) }
        })
        addEvent(node, 'mouseup', function (e, target) {
            callback(e, target)
        })
    }

    // Get the nearest ancestor element of a node that matches a given tag name
    function getAncestor (node, match) {
        do {
            if (!node || node.nodeName.toLowerCase() === match) {
                break
            }
            node = node.parentNode
        } while (node)

        return node
    }

    // Define a statechange function that updates aria-expanded and style.display
    // Also update the arrow position
    function statechange (summary) {
        var expanded = summary.__details.__summary.getAttribute('aria-expanded') === 'true'
        var hidden = summary.__details.__content.getAttribute('aria-hidden') === 'true'

        summary.__details.__summary.setAttribute('aria-expanded', (expanded ? 'false' : 'true'))
        summary.__details.__content.setAttribute('aria-hidden', (hidden ? 'false' : 'true'))

        if (!NATIVE_DETAILS) {
            summary.__details.__content.style.display = (expanded ? 'none' : '')

            var hasOpenAttr = summary.__details.getAttribute('open') !== null
            if (!hasOpenAttr) {
                summary.__details.setAttribute('open', 'open')
            } else {
                summary.__details.removeAttribute('open')
            }
        }

        if (summary.__twisty) {
            summary.__twisty.firstChild.nodeValue = (expanded ? '\u25ba' : '\u25bc')
            summary.__twisty.setAttribute('class', (expanded ? 'arrow arrow-closed' : 'arrow arrow-open'))
        }

        return true
    }

    // Bind a click event to handle summary elements
    addClickEvent(document, function (e, summary) {
        if (!(summary = getAncestor(summary, 'summary'))) {
            return true
        }
        return statechange(summary)
    })

    // Initialisation function
    function addDetailsPolyfill (list) {
        // Get the collection of details elements, but if that's empty
        // then we don't need to bother with the rest of the scripting
        if ((list = document.getElementsByTagName('details')).length === 0) {
            return
        }

        // else iterate through them to apply their initial state
        var n = list.length
        var i = 0
        for (i; i < n; i++) {
            var details = list[i]

            //Ensure each details element is only processed once
            if(details.__filled) {
                continue;
            }
            details.__filled = true;

            // Save shortcuts to the inner summary and content elements
            details.__summary = details.getElementsByTagName('summary').item(0)
            details.__content = details.getElementsByTagName('div').item(0)

            // If the content doesn't have an ID, assign it one now
            // which we'll need for the summary's aria-controls assignment
            if (!details.__content.id) {
                details.__content.id = 'details-content-' + i
            }

            // Add ARIA role="group" to details
            details.setAttribute('role', 'group')

            // Add role=button to summary
            details.__summary.setAttribute('role', 'button')

            // Add aria-controls
            details.__summary.setAttribute('aria-controls', details.__content.id)

            // Set tabIndex so the summary is keyboard accessible for non-native elements
            // http://www.saliences.com/browserBugs/tabIndex.html
            if (!NATIVE_DETAILS) {
                details.__summary.tabIndex = 0
            }

            // Detect initial open state
            var openAttr = details.getAttribute('open') !== null
            if (openAttr === true) {
                details.__summary.setAttribute('aria-expanded', 'true')
                details.__content.setAttribute('aria-hidden', 'false')
            } else {
                details.__summary.setAttribute('aria-expanded', 'false')
                details.__content.setAttribute('aria-hidden', 'true')
                if (!NATIVE_DETAILS) {
                    details.__content.style.display = 'none'
                }
            }

            // Create a circular reference from the summary back to its
            // parent details element, for convenience in the click handler
            details.__summary.__details = details

            // If this is not a native implementation, create an arrow
            // inside the summary
            if (!NATIVE_DETAILS) {
                var twisty = document.createElement('i')

                if (openAttr === true) {
                    twisty.className = 'arrow arrow-open'
                    twisty.appendChild(document.createTextNode('\u25bc'))
                } else {
                    twisty.className = 'arrow arrow-closed'
                    twisty.appendChild(document.createTextNode('\u25ba'))
                }

                details.__summary.__twisty = details.__summary.insertBefore(twisty, details.__summary.firstChild)
                details.__summary.__twisty.setAttribute('aria-hidden', 'true')
            }
        }
    }

    moj.Modules.DetailsPolyfill = {
        init: function () {
            moj.Events.on('Polyfill.fill', this.fill);
            this.fill();
        },

        fill: function () {
            addDetailsPolyfill();
        }
    };
})()
;
// Disable link after use module for LPA
// Dependencies: moj, jQuery

(function () {
    'use strict';

    // Define the class
    var SingleUse = function (options) {
        this.settings = $.extend({}, this.defaults, options);
    };

    SingleUse.prototype = {
        defaults: {
            selector: '.js-single-use'
        },

        init: function () {
            // bind 'this' as this in following methods
            _.bindAll(this, 'btnClick');
            this.bindEvents();
        },

        bindEvents: function () {
            $('body')
                // link click
                .on('click.moj.Modules.SingleUse', this.settings.selector, this.btnClick)
                // submit form
                .on('submit.moj.Modules.SingleUse', this.settings.selector, this.submitForm);
        },

        btnClick: function (e) {
            if (e.target.tagName === 'A') {
                var source = $(e.target);

                // Disable link
                source.attr('disabled', 'disabled');
            }
        },

        submitForm: function (e) {
            if (e.target.tagName === 'FORM') {
                var $form = $(e.target);

                // Disable submit button
                $form.find('input[type="submit"]').attr('disabled', 'disabled');
            }
        }
    };

    // Add module to MOJ namespace
    moj.Modules.SingleUse = new SingleUse();
}());
;
// ====================================================================================
// INITITALISE ALL MOJ MODULES
;$(moj.init);


// ====================================================================================
// INITITALISE ALL GOVUK MODULES

// Initiating the SelectionButtons GOVUK module
var $blockLabels = $(".block-label input[type='radio'], .block-label input[type='checkbox']");
new GOVUK.SelectionButtons($blockLabels);


// Where .block-label uses the data-target attribute
// to toggle hidden content
var showHideContent = new GOVUK.ShowHideContent();
showHideContent.init();


// ====================================================================================
// VENDOR CONFIGURATION

// Remove the no-js class
$('body').removeClass('no-js');
;
// SHAME.JS
// This is a temporary dumping ground which should NEVER go into production

// $(document).ready(function () {
//   'use strict';

// });


//# sourceMappingURL=application.js.map