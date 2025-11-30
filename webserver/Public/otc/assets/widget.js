/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "http://192.168.1.101:8010/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 11);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var globName = X4CryptoCharts;
var x4plugin = window[globName];
/* harmony default export */ __webpack_exports__["a"] = ({
  components: function (modules) {
    var components = {};

    for (var name in modules) {
      components[name] = modules[name].default;
    }

    return components;
  },
  variables: function (methods) {
    var _loop = function (name) {
      var wrappedMethod = methods[name];

      methods[name] = function () {
        var args = [].slice.call(arguments);
        args.unshift({
          state: this.$store.state,
          getters: this.$store.getters,
          commit: this.$store.commit,
          dispatch: this.$store.dispatch
        });
        return wrappedMethod.apply(this, args);
      };
    };

    for (var name in methods) {
      _loop(name);
    }

    return methods;
  }
});

/***/ }),
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return normalizeComponent; });
/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

function normalizeComponent (
  scriptExports,
  render,
  staticRenderFns,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier, /* server only */
  shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
    ? scriptExports.options
    : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
      ? function () { injectStyles.call(this, this.$root.$options.shadowRoot) }
      : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functioal component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing
        ? [].concat(existing, hook)
        : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}


/***/ }),
/* 2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Style/Style.vue?vue&type=template&id=69a6a5ad&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('Style',{attrs:{"type":"text/css"},domProps:{"innerHTML":_vm._s(_vm.output)}})}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Style/Style.vue?vue&type=template&id=69a6a5ad&lang=pug&

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Style/Style.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
/* harmony default export */ var Stylevue_type_script_lang_js_ = ({
  props: ['scale', 'colors'],
  data: function () {
    return {
      text: this.$slots.default ? this.$slots.default[0].text : ''
    };
  },
  updated: function () {
    if (this.$slots.default && this.text !== this.$slots.default[0].text) {
      this.text = this.$slots.default[0].text;
    }
  },
  computed: {
    ascale: function () {
      return parseFloat(this.scale || this.$vnode.context.scale) || 1;
    },
    acolors: function () {
      return this.colors || this.$root.colors || this.$store.state.colors;
    },
    output: function () {
      var _this = this;

      var result = '';
      var text = this.text;
      text = this.applyScale(text);
      text = this.applyColor(text);

      var _this$buildBlocks = this.buildBlocks(text),
          blocks = _this$buildBlocks.blocks,
          extraBlocks = _this$buildBlocks.extraBlocks;

      blocks = this.buildMultiBlocks(blocks);
      extraBlocks.forEach(function (extraBlock) {
        extraBlock.blocks = _this.buildMultiBlocks(extraBlock.blocks);
      });
      result += this.buildOutput(blocks);
      result += this.buildExtraOutput(extraBlocks);
      return result;
    }
  },
  methods: {
    buildBlocks: function (text) {
      var blocks = [];
      var curSelector = [];
      var curProps = [];
      var extraBlocks = [];
      var curExtraBlock = {
        key: false,
        name: false,
        blocks: []
      };

      var pushBlock = function () {
        if (curProps.length === 0) {
          return;
        }

        var container = curExtraBlock.name ? curExtraBlock.blocks : blocks;
        container.push({
          selector: curSelector,
          props: curProps
        });
        curProps = [];
      };

      text.split('\n').forEach(function (line) {
        var trimmed = line.trim();
        var splitted = trimmed.split(': ');

        if (trimmed.length === 0) {
          return;
        }

        if (trimmed.match(/\{$/)) {
          pushBlock();

          if (trimmed.match(/^@/)) {
            curExtraBlock.key = trimmed.match(/^(@[^\s]+)\s/)[1];
          }

          curExtraBlock.name = trimmed.match(/^(.+)\{$/)[1].trim();
          return;
        }

        if (trimmed === '}') {
          pushBlock();

          if (curExtraBlock.blocks.length > 0) {
            extraBlocks.push(curExtraBlock);
          }

          curExtraBlock = {
            key: false,
            name: false,
            blocks: []
          };
          return;
        }

        if (splitted.length === 1) {
          pushBlock();
          var index = Math.ceil(line.match(/^\s*/)[0].length / 2) - (curExtraBlock.name ? 1 : 0);
          curSelector = curSelector.slice(0, index + 1);
          curSelector[index] = trimmed;
        } else {
          curProps.push({
            name: splitted[0].trim(),
            value: splitted.slice(1).join(':').trim()
          });
        }
      });
      pushBlock();
      return {
        blocks: blocks,
        extraBlocks: extraBlocks
      };
    },
    applyScale: function (text) {
      var _this2 = this;

      return text.replace(/\$scale\(([^)]+)\)/g, function (match, value) {
        if (_this2.ascale !== 1) {
          value = value.replace(/[0-9.]+/g, function (match) {
            return _this2.ascale * parseFloat(match);
          });
        }

        return value;
      });
    },
    applyColor: function (text) {
      var _this3 = this;

      return text.replace(/\$color\(([^)]+)\)/g, function (match, value) {
        var colorString = value.split(',')[0].trim();
        var opacityString = (value.split(',')[1] || '').trim();
        value = _this3.acolors[colorString];

        if (opacityString) {
          var opacity = parseFloat(opacityString);

          if (_this3.acolors.dark && opacity <= .16 && opacityString.substr(opacityString.length - 1, 1) !== '!') {
            opacity *= 2;
          }

          if (opacity < 1) {
            opacity = opacity.toString().replace('0.', '.');
          }

          value = value.replace(/,\s*[0-9.]+\s*\)$/g, ',' + opacity + ')');
        }

        return value;
      });
    },
    buildMultiBlocks: function (blocks) {
      var multiBlocks = [];
      blocks.forEach(function (block) {
        var selectors = [[]];
        block.selector.forEach(function (subselectors, index) {
          var tmpselectors = [];
          subselectors = subselectors.split(',');

          var _loop = function (i) {
            selectors.forEach(function (selector) {
              var tmpselector = selector.slice();
              tmpselector[index] = subselectors[i].trim();
              tmpselectors.push(tmpselector);
            });
          };

          for (var i = 0; i < subselectors.length; i++) {
            _loop(i);
          }

          selectors = tmpselectors;
        });
        selectors.forEach(function (selector) {
          multiBlocks.push(Object.assign({}, block, {
            selector: selector
          }));
        });
      });
      return multiBlocks;
    },
    buildOutput: function (blocks, noUnique) {
      var _this4 = this;

      var output = '';
      var restrictedUnique = ['@font-face'];
      blocks.forEach(function (block) {
        noUnique = noUnique || restrictedUnique.indexOf(block.selector[0]) !== -1;

        if (!noUnique) {
          var parts = block.selector[0].split(' ');
          parts[0] += '[data-x4wp="' + (_this4.$vnode.context.x4wp || _this4.$parent.x4wp) + '"]';
          block.selector[0] = parts.join(' ');
        }

        block.selector = block.selector.join(' ').replace(/ &/g, '');
        block.selector = block.selector.replace(/ > /g, '>');
        var props = block.props.map(function (prop) {
          return prop.name + ':' + prop.value;
        });
        output += block.selector + '{' + props.join(';') + '}';
      });
      return output;
    },
    buildExtraOutput: function (extraBlocks) {
      var _this5 = this;

      var output = '';
      var restrictedUnique = ['@block', '@keyframes'];
      extraBlocks.forEach(function (extraBlock) {
        var noUnique = restrictedUnique.indexOf(extraBlock.key) !== -1;

        var extraOutput = _this5.buildOutput(extraBlock.blocks, noUnique);

        output += extraBlock.key !== '@block' ? extraBlock.name + '{' + extraOutput + '}' : extraOutput;
      });
      return output;
    }
  }
});
// CONCATENATED MODULE: ./common/components/ui/Style/Style.vue?vue&type=script&lang=js&
 /* harmony default export */ var Style_Stylevue_type_script_lang_js_ = (Stylevue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Style/Style.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  Style_Stylevue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "Style.vue"
/* harmony default export */ var Style = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 3 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/DIV/DIV.vue?vue&type=template&id=5844510a&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c(_vm.atag,{tag:"div",attrs:{"data-x4wp":_vm.x4wp}},[_vm._t("default")],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/DIV/DIV.vue?vue&type=template&id=5844510a&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/DIV/DIV.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var DIVvue_type_script_lang_js_ = ({
  props: ['tag', 'level'],
  created: function () {
    var parent = this.$parent;
    this.x4wp = !parent.x4wp ? Math.random().toString(36).substr(2, 8) : parent.x4wp;

    if (this.level) {
      var current = this;

      for (var i = 0; i < this.level; i++) {
        current.$parent.x4wp = this.x4wp;
        current = this.$parent;
      }
    } else {
      while (!parent.x4wp && parent !== this.$root) {
        parent.x4wp = this.x4wp;
        parent = parent.$parent;
      }
    }
  },
  computed: map["a" /* default */].variables({
    atag: function () {
      return this.tag || 'div';
    }
  })
});
// CONCATENATED MODULE: ./common/components/ui/DIV/DIV.vue?vue&type=script&lang=js&
 /* harmony default export */ var DIV_DIVvue_type_script_lang_js_ = (DIVvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/DIV/DIV.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  DIV_DIVvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "DIV.vue"
/* harmony default export */ var DIV = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 4 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony default export */ __webpack_exports__["a"] = (function (value) {
  var wp = window.wp;
  return wp && wp.i18n ? wp.i18n.__(value, 'x4-crypto-charts') : value;
});

/***/ }),
/* 5 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony default export */ __webpack_exports__["a"] = (function (condition, callback, options) {
  var iteration = function () {
    if (condition()) {
      if (!endless) {
        clearInterval(interval);
      }

      callback();
    }
  };

  var timeout = options && options.timeout ? options.timeout : 100;
  var endless = options && options.endless ? options.endless : false;
  var interval = setInterval(iteration, timeout);
  iteration();
});

/***/ }),
/* 6 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "buildStore", function() { return buildStore; });
/* harmony import */ var _common_bootstrap_polyfills__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _common_bootstrap_polyfills__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_common_bootstrap_polyfills__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _common_bootstrap_wait__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5);


var x4wp = window.x4wp;
var x4plugin = window['X4CryptoCharts'];
var x4builder = x4wp.builders['X4CryptoCharts'];
x4wp.wait = x4wp.wait || _common_bootstrap_wait__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"];
x4plugin.defaults = {
  "lineChart": {
    "type": "lineChart",
    "theme": "material",
    "subtheme": "filled",
    "layout": "full-featured",
    "sublayout": "1",
    "margins": {
      "fixed": false,
      "left": 0,
      "right": 0,
      "top": 48,
      "bottom": 48,
      "width": 1200
    },
    "colors": {
      "dark": false,
      "primary": "rgba(0,0,0,.87)",
      "inverted": "rgba(255,255,255,1)",
      "accent": "rgba(0,188,212,1)",
      "coin1": "rgba(0,188,212,1)",
      "coin2": "rgba(0,188,212,1)",
      "coin3": "rgba(0,188,212,1)",
      "coin4": "rgba(0,188,212,1)",
      "crosshair": "rgba(244,67,54,1)"
    },
    "controls": {
      "lineChart": {
        "visible": true,
        "height": 400,
        "format": {
          "template": "[symbol][value] [short]",
          "factor": "",
          "separator": ",",
          "precision": "auto"
        },
        "line": {
          "fill": true,
          "colorize": true,
          "thickness": 2,
          "smoothness": 4
        },
        "legend": {
          "visible": false,
          "template": "[coin]/[fiat]"
        },
        "scales": {
          "visible": true,
          "horizontal": true,
          "vertical": true
        },
        "tooltip": {
          "visible": true,
          "date": true
        },
        "crosshair": {
          "visible": true,
          "horizontal": true,
          "vertical": true,
          "dotted": false
        },
        "watermark": {
          "visible": true,
          "template": "[coin1]/[fiat]"
        },
        "loader": {
          "visible": true,
          "colorize": true
        }
      },
      "fiatSelect": {
        "visible": true,
        "icon": "monetization_on",
        "label": "Fiat currency",
        "items": "getters.controls/fiatSelect",
        "itemValue": "id",
        "itemTemplate": "[short]",
        "top": ["united-states-dollar", "euro", "japanese-yen", "british-pound-sterling", "bitcoin", "ethereum"]
      },
      "periodSelect": {
        "visible": true,
        "items": "state.selections.period",
        "itemValue": "value",
        "itemTitle": "title"
      },
      "loader": {
        "visible": true,
        "colorize": true,
        "size": 200
      }
    },
    "values": {
      "coin1": "bitcoin",
      "coin2": null,
      "coin3": null,
      "coin4": null,
      "fiat": "united-states-dollar",
      "period": 31536000000
    },
    "changes": {
      "material": {
        "filled": {},
        "outlined": {},
        "standard": {}
      }
    },
    "selections": {
      "period": [{
        "value": 63072000000,
        "title": "2 years"
      }, {
        "value": 31536000000,
        "title": "365 days"
      }, {
        "value": 15552000000,
        "title": "180 days"
      }, {
        "value": 7776000000,
        "title": "90 days"
      }, {
        "value": 2592000000,
        "title": "30 days"
      }, {
        "value": 604800000,
        "title": "7 days"
      }, {
        "value": 86400000,
        "title": "24 hours"
      }]
    }
  }
};
x4plugin.multiValues = {
  "lineChart": {}
};
x4plugin.hiddenValues = {
  "lineChart": {}
};
Object(_common_bootstrap_wait__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(function () {
  return !!window.Promise && !!window.Vue && !!window.Vuex;
}, function () {
  var push = x4plugin.push;

  x4plugin.push = function () {
    push.apply(this, arguments);
    render.apply(this, arguments);
  };

  x4plugin.forEach(render);
});
function buildStore(_ref) {
  var slug = _ref.slug,
      options = _ref.options;
  var Vue = window.Vue;
  var mixedOptions = calcOptions(options);

  var getStore = __webpack_require__(10).default;

  return getStore(mixedOptions.type, {
    slug: slug,
    options: mixedOptions
  });
}

function render(init) {
  var Vue = window.Vue;

  if (!init) {
    return;
  }

  var selector = init.selector + ':not(.x4-rendered)';
  var element = document.querySelector(selector);

  if (!element) {
    return;
  }

  element.classList.add('x4-rendered');
  var attributes = {};

  for (var i = 0; i < element.attributes.length; i++) {
    attributes[element.attributes[i].name] = element.attributes[i].value;
  }

  var slug = Math.random().toString(36).substr(2, 8);
  var options = calcOptions(init);
  x4plugin.widgets = x4plugin.widgets || {};
  x4plugin.widgets[slug] = {
    init: init,
    options: options
  };

  var getStore = __webpack_require__(10).default;

  var store = x4plugin.widgets[slug].store = getStore(options.type, {
    slug: slug,
    options: options
  });
  store.dispatch('bootstrap');

  var App = __webpack_require__(64).default;

  var app = x4plugin.widgets[slug].app = new Vue({
    store: store,
    el: element,
    render: function (h) {
      return h(App);
    },
    data: function () {
      return {
        attributes: attributes
      };
    }
  });
  return slug;
}

function calcOptions(init) {
  var type = init.type || 'lineChart';
  var defaults = x4plugin.defaults[type];
  var options = calcOptionsRecursive({}, init, defaults, init, defaults);
  options = calcOptionsRecursive(options, defaults, init, init, defaults);
  delete options.changes;
  applyChanges(init, options, defaults);
  return options;
}

function calcOptionsRecursive(options, src1, src2, primary, slave) {
  for (var key in src1) {
    if (Array.isArray(src1[key])) {
      options[key] = primary[key] !== undefined ? primary[key] : slave[key];
      options[key] = JSON.parse(JSON.stringify(options[key]));
    } else if (src1[key] instanceof Object) {
      if (Object.keys(src1[key]).length > 0) {
        options[key] = calcOptionsRecursive(options[key] || {}, src1[key] || {}, src2[key] || {}, primary[key] || {}, slave[key] || {});
      }
    } else {
      options[key] = primary[key] !== undefined ? primary[key] : slave[key];
    }
  }

  return options;
}

function applyChanges(init, options, defaults) {
  if (defaults.changes && defaults.changes[options.theme] && defaults.changes[options.theme][options.subtheme]) {
    var changes = defaults.changes[options.theme][options.subtheme];

    for (var key in changes) {
      changes[key].forEach(function (change) {
        var initValue = init;
        change.path.split('.').forEach(function (name) {
          initValue = initValue !== undefined && initValue[name] !== undefined ? initValue[name] : undefined;
        });

        if (initValue === undefined) {
          var sections = change.path.split('.');
          var option = sections.pop();
          var result = options;
          sections.forEach(function (name) {
            return result = result[name];
          });
          result[option] = change.value;
        }
      });
    }
  }
}

/***/ }),
/* 7 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Loader/Loader.vue?vue&type=template&id=66d5aa9e&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('DIV',{class:_vm.baseClass},[_c('div',{staticClass:"x4-shape x4-shape1"}),_c('div',{staticClass:"x4-shape x4-shape2"}),_c('div',{staticClass:"x4-shape x4-shape3"}),_c('Style',[_vm._v(".x4-ui-loader\n  height: "+_vm._s(_vm.size)+"px\n  margin-left: auto\n  margin-right: auto\n  position: relative\n  width: "+_vm._s(_vm.size)+"px\n\n  .x4-shape\n    border-radius: 50%\n    box-sizing: border-box\n    height: 100%\n    position: absolute\n    width: 100%\n\n  .x4-shape1\n    animation: x4-ui-loader1 1.15s linear infinite\n    border-bottom: 3px solid $color(primary, .24)\n    left: 0\n    top: 0\n\n  .x4-shape2\n    animation: x4-ui-loader2 1.15s linear infinite\n    border-right: 3px solid $color(primary, .24)\n    right: 0\n    top: 0\n\n  .x4-shape3\n    animation: x4-ui-loader3 1.15s linear infinite\n    border-top: 3px solid $color(primary, .24)\n    right: 0\n    bottom: 0\n\n  &.x4-colorize\n    .x4-shape1, .x4-shape2, .x4-shape3\n      border-color: $color(accent)\n\n\n@keyframes x4-ui-loader1 {\n  0%\n    transform: rotateX(35deg) rotateY(-45deg) rotateZ(0deg)\n\n  100%\n    transform: rotateX(35deg) rotateY(-45deg) rotateZ(360deg)\n}\n\n@keyframes x4-ui-loader2 {\n  0%\n    transform: rotateX(50deg) rotateY(10deg) rotateZ(0deg)\n\n  100%\n    transform: rotateX(50deg) rotateY(10deg) rotateZ(360deg)\n}\n\n@keyframes x4-ui-loader3 {\n  0%\n    transform: rotateX(35deg) rotateY(55deg) rotateZ(0deg)\n\n  100%\n    transform: rotateX(35deg) rotateY(55deg) rotateZ(360deg)\n}\n")])],1)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Loader/Loader.vue?vue&type=template&id=66d5aa9e&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Loader/Loader.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var Loadervue_type_script_lang_js_ = ({
  props: ['colorize', 'size'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2)
  }),
  computed: map["a" /* default */].variables({
    baseClass: function (_ref) {
      var state = _ref.state;
      return {
        'x4-ui-loader': true,
        'x4-colorize': !!this.colorize
      };
    }
  })
});
// CONCATENATED MODULE: ./common/components/ui/Loader/Loader.vue?vue&type=script&lang=js&
 /* harmony default export */ var Loader_Loadervue_type_script_lang_js_ = (Loadervue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Loader/Loader.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  Loader_Loadervue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "Loader.vue"
/* harmony default export */ var Loader = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 8 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Icon/Icon.vue?vue&type=template&id=77a7a7c2&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('i',{directives:[{name:"show",rawName:"v-show",value:(_vm.visible),expression:"visible"}],class:_vm.baseClass},[_vm._v(_vm._s(_vm.icon)),_vm._t("default")],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Icon/Icon.vue?vue&type=template&id=77a7a7c2&lang=pug&

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Icon/Icon.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ var Iconvue_type_script_lang_js_ = ({
  props: ['icon'],
  data: function () {
    return {
      visible: document.fonts && document.fonts.check ? document.fonts.check('12px \'Material Icons\'') : true
    };
  },
  mounted: function () {
    var _this = this;

    if (!this.visible) {
      document.fonts.load('12px \'Material Icons\'').then(function (fonts) {
        if (fonts.length > 0) {
          _this.visible = true;
        }
      });
    }
  },
  computed: {
    baseClass: function () {
      var result = {};
      result['x4-ui-icon'] = true;
      result['material-icons'] = true;
      return result;
    }
  }
});
// CONCATENATED MODULE: ./common/components/ui/Icon/Icon.vue?vue&type=script&lang=js&
 /* harmony default export */ var Icon_Iconvue_type_script_lang_js_ = (Iconvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Icon/Icon.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  Icon_Iconvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "Icon.vue"
/* harmony default export */ var Icon = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 9 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/fiatSelect/components/main/FiatSelect.vue?vue&type=template&id=5465403a&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('Input',{staticClass:"x4-fiat-select",attrs:{"type":"select","value":_vm.value,"options":_vm.options},on:{"change":_vm.change}})}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/controls/crypto/fiatSelect/components/main/FiatSelect.vue?vue&type=template&id=5465403a&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/fiatSelect/components/main/FiatSelect.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var FiatSelectvue_type_script_lang_js_ = ({
  components: map["a" /* default */].components({
    Input: __webpack_require__(57)
  }),
  computed: map["a" /* default */].variables({
    value: function (_ref) {
      var state = _ref.state;
      return state.values.fiat;
    },
    options: function (_ref2) {
      var state = _ref2.state;
      return state.controls.fiatSelect;
    }
  }),
  methods: map["a" /* default */].variables({
    change: function (_ref3, _ref4) {
      var commit = _ref3.commit;
      var value = _ref4.value;
      commit('FIAT_CHANGE', {
        fiat: value
      });
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/controls/crypto/fiatSelect/components/main/FiatSelect.vue?vue&type=script&lang=js&
 /* harmony default export */ var main_FiatSelectvue_type_script_lang_js_ = (FiatSelectvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/controls/crypto/fiatSelect/components/main/FiatSelect.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  main_FiatSelectvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "FiatSelect.vue"
/* harmony default export */ var FiatSelect = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 10 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./common/bootstrap/store.js
var mutCache = {};
/* harmony default export */ var bootstrap_store = (function (modules, dataContext, store) {
  var Vuex = window.Vuex;
  var Vue = window.Vue;

  if (!Vuex.used) {
    Vuex.used = true;
    Vue.use(Vuex);
  }

  var context = {
    state: {},
    getters: {},
    actions: {},
    modules: {
      mutations: {
        modules: {}
      }
    }
  };
  modules.forEach(function (module) {
    module = module(dataContext);
    setState(context, module);
    setMutations(context, module);
    setGetters(context, module);
    setActions(context, module);
  });

  if (!store) {
    store = new Vuex.Store(context);
  } else {
    store.hotUpdate(context);
  }

  context._store = store;
  return store;
});

function setState(context, module) {
  if (module.state !== undefined) {
    var subparts = module.name.split('/');
    var substate = context.state;
    var last = subparts.pop();
    subparts.forEach(function (subpart) {
      substate[subpart] = substate[subpart] || {};
      substate = substate[subpart];
    });
    substate[last] = module.state;
  }
}

function setGetters(context, module) {
  if (module.getters) {
    var _loop = function (name) {
      var prefixedName = (module.name ? module.name + '/' + name : name).replace('/_name', '');

      context.getters[prefixedName] = function () {
        return module.getters[name].apply(context._store, arguments);
      };
    };

    for (var name in module.getters) {
      _loop(name);
    }
  }
}

function setMutations(context, module) {
  if (module.mutations) {
    var _loop2 = function (name) {
      var savedMutation = module.mutations[name];
      var prefixedName = module.name + '/' + name;
      var submodules = context.modules.mutations.modules;
      var mutName = mutCache[prefixedName] = mutCache[prefixedName] || Math.random();
      submodules[mutName] = submodules[mutName] || {
        mutations: {}
      };

      submodules[mutName].mutations[name] = function (state, payload) {
        payload = fixPayload(payload);

        if (false) {}

        savedMutation.call(this, this.state, payload || {});
      };
    };

    for (var name in module.mutations) {
      _loop2(name);
    }
  }
}

function setActions(context, module) {
  if (module.actions) {
    var _loop3 = function (name) {
      var savedAction = module.actions[name];
      var prefixedName = (module.name ? module.name + '/' + name : name).replace('/_name', '');

      context.actions[prefixedName] = function (context, payload) {
        payload = fixPayload(payload);

        if (false) {}

        return savedAction.call(this, this, payload || {});
      };
    };

    for (var name in module.actions) {
      _loop3(name);
    }
  }
}

function fixPayload(payload) {
  if (payload) {
    for (var key in payload) {
      if (payload[key] === undefined) {
        delete payload[key];
      }
    }

    if (Object.keys(payload).length === 0) {
      payload = undefined;
    }
  }

  return payload;
}
// CONCATENATED MODULE: ./common/src/assets/main/store/store.js

/* harmony default export */ var store_store = __webpack_exports__["default"] = (function (widgetType, dataContext) {
  var context = getContext()[widgetType];
  var store = bootstrap_store(context.store, dataContext, null);

  if (false) { var modules; }

  return store;
});

function getContext() {
  return {
    lineChart: {
      store: [__webpack_require__(13).default, __webpack_require__(14).default, __webpack_require__(15).default, __webpack_require__(16).default, __webpack_require__(17).default, __webpack_require__(18).default, __webpack_require__(19).default, __webpack_require__(20).default, __webpack_require__(21).default, __webpack_require__(22).default, __webpack_require__(23).default, __webpack_require__(24).default, __webpack_require__(25).default, __webpack_require__(26).default, __webpack_require__(27).default, __webpack_require__(28).default, __webpack_require__(29).default, __webpack_require__(30).default, __webpack_require__(31).default, __webpack_require__(32).default, __webpack_require__(33).default, __webpack_require__(34).default, __webpack_require__(35).default, __webpack_require__(36).default, __webpack_require__(37).default, __webpack_require__(38).default, __webpack_require__(39).default, __webpack_require__(40).default, __webpack_require__(41).default, __webpack_require__(42).default, __webpack_require__(43).default, __webpack_require__(44).default, __webpack_require__(45).default, __webpack_require__(46).default, __webpack_require__(47).default, __webpack_require__(48).default, __webpack_require__(49).default, __webpack_require__(50).default, __webpack_require__(51).default, __webpack_require__(52).default, __webpack_require__(53).default, __webpack_require__(54).default, __webpack_require__(55).default],
      modules: []
    }
  };
}

/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(6);


/***/ }),
/* 12 */
/***/ (function(module, exports) {

Object.assign = function (a) {
  for (var i = 1; i < arguments.length; i++) {
    var b = arguments[i];

    for (var c in b) {
      a[c] = b[c];
    }
  }

  return a;
};

/***/ }),
/* 13 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _common_bootstrap_wait__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(5);

var x4wp = window.x4wp;
var globName = 'X4CryptoCharts';
var x4plugin = window[globName];
var x4builder = x4wp.builders[globName];
var script = document.querySelector('script[data-entry="x4-crypto-charts"]');
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'builder',
    state: {
      enabled: script.dataset.vcEnabled !== undefined,
      opened: false
    },
    mutations: {
      BUILDER_ENABLED_CHANGE: function (state, _ref) {
        var enabled = _ref.enabled;
        state.builder.enabled = enabled;
      },
      BUILDER_OPENED_CHANGE: function (state, _ref2) {
        var opened = _ref2.opened;
        state.builder.opened = opened;
      },
      BUILDER_OPTION_CHANGE: function (state, _ref3) {
        var path = _ref3.path,
            value = _ref3.value;
        arguments[1].log = false;
        var Vue = window.Vue;
        var sections = path.split('.');
        var option = sections.pop();
        sections.forEach(function (name) {
          if (state[name] === undefined) {
            Vue.set(state, name, {});
          }

          state = state[name];
        });

        if (value !== undefined) {
          Vue.set(state, option, value);
        } else {
          Vue.delete(state, option);
        }
      }
    },
    actions: {
      open: function (_ref4, _ref5) {
        var dispatch = _ref4.dispatch;
        var parent = _ref5.parent;
        Object(_common_bootstrap_wait__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(function () {
          return !!x4builder.create;
        }, function () {
          if (x4wp.builders.current) {
            return dispatch('builder/close').then(function () {
              dispatch('builder/open/next', {
                parent: parent
              });
            });
          }

          dispatch('builder/open/next', {
            parent: parent
          });
        });
      },
      'open/next': function (_ref6, _ref7) {
        var state = _ref6.state,
            commit = _ref6.commit;
        var parent = _ref7.parent;
        commit('BUILDER_OPENED_CHANGE', {
          opened: true
        });
        x4builder.create({
          parent: parent,
          slug: state.slug
        });
      },
      close: function () {
        var _x4wp$builders$curren = x4wp.builders.current,
            app = _x4wp$builders$curren.app,
            store = _x4wp$builders$curren.store,
            globName = _x4wp$builders$curren.globName;
        store.commit('BUILDER_OPENED_CHANGE', {
          opened: false
        });
        return new Promise(function (resolve) {
          setTimeout(function () {
            app.$destroy();

            if (app.$el.parentNode) {
              app.$el.parentNode.removeChild(app.$el);
            }

            x4wp.builders.current = null;
            delete x4wp.builders[globName].widgets[store.state.slug];
            resolve();
          }, 150);
        });
      },
      'option/change': function (_ref8, _ref9) {
        var getters = _ref8.getters,
            commit = _ref8.commit,
            dispatch = _ref8.dispatch;
        var path = _ref9.path,
            value = _ref9.value;

        if (typeof path === 'string') {
          if (path.substr(0, 1) === '.') {
            path = path.substr(1);
          }

          commit('BUILDER_OPTION_CHANGE', {
            path: path,
            value: value
          });
        } else {
          var payload = {};
          payload[path.var] = value;

          if (path.mutation) {
            commit(path.mutation, payload);
          }

          if (path.action) {
            dispatch(path.action, payload);
          }
        }
      }
    }
  };
});

/***/ }),
/* 14 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var script = document.querySelector('script[data-entry="x4-crypto-charts"]');
var colors = {
  dark: {
    dark: true,
    primary: 'rgba(255,255,255,1)',
    inverted: 'rgba(0,0,0,1)',
    accent: 'rgba(255,255,255,1)'
  },
  light: {
    dark: false,
    primary: 'rgba(0,0,0,1)',
    inverted: 'rgba(255,255,255,1)',
    accent: 'rgba(0,188,212,1)'
  }
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'builder/colors',
    state: Object.assign({}, script.dataset.vcInvert !== undefined ? colors.light : colors.dark),
    mutations: {
      BUILDER_COLORS_CHANGE: function (state, _ref) {
        var colors = _ref.colors;
        state.builder.colors = colors;
      }
    },
    actions: {
      invert: function (_ref2) {
        var state = _ref2.state,
            commit = _ref2.commit;
        var newColors = Object.assign({}, state.builder.colors.dark ? colors.light : colors.dark);
        commit('BUILDER_COLORS_CHANGE', {
          colors: newColors
        });
      }
    }
  };
});

/***/ }),
/* 15 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'builder/instructions',
    state: {
      visible: false,
      init: null
    },
    mutations: {
      BUILDER_INSTRUCTIONS_SHOW: function (state, _ref) {
        var init = _ref.init;
        state.builder.instructions.init = init;
        state.builder.instructions.visible = true;
      },
      BUILDER_INSTRUCTIONS_HIDE: function (state) {
        state.builder.instructions.visible = false;
      }
    },
    actions: {
      show: function (_ref2, _ref3) {
        var commit = _ref2.commit;
        var init = _ref3.init;
        commit('BUILDER_INSTRUCTIONS_SHOW', {
          init: init
        });
      },
      hide: function (_ref4) {
        var commit = _ref4.commit;
        commit('BUILDER_INSTRUCTIONS_HIDE');
      }
    }
  };
});

/***/ }),
/* 16 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _common_src_assets_main__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);

var globName = 'X4CryptoCharts';
var x4plugin = window[globName];
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'builder',
    getters: {
      'calc/init': function (state, getters) {
        return function () {
          var init = {};
          var defaults = x4plugin.defaults[state.type];

          if (false) {}

          init.type = state.type;
          init.selector = state.selector;
          getters['builder/calc/init/recursive']({
            init: init,
            options: state,
            defaults: defaults,
            keys: Object.keys(defaults)
          });
          delete init.changes;
          return init;
        };
      },
      'calc/init/recursive': function (state, getters) {
        return function (_ref) {
          var init = _ref.init,
              options = _ref.options,
              defaults = _ref.defaults,
              keys = _ref.keys;
          keys = keys || Object.keys(options);
          keys.forEach(function (key) {
            if (Array.isArray(options[key])) {
              if (JSON.stringify(options[key]) !== JSON.stringify(defaults[key] || [])) {
                init[key] = JSON.parse(JSON.stringify(options[key]));
              }
            } else if (options[key] instanceof Object) {
              init[key] = init[key] || {};
              getters['builder/calc/init/recursive']({
                init: init[key],
                options: options[key] || {},
                defaults: defaults[key] || {}
              });

              if (Object.keys(init[key]).length === 0) {
                delete init[key];
              }
            } else {
              if (options[key] !== defaults[key]) {
                init[key] = options[key];
              }
            }
          });
        };
      }
    },
    actions: {
      'reset/initial': function (_ref2) {
        var state = _ref2.state;
        var _x4plugin$widgets$sta = x4plugin.widgets[state.slug],
            app = _x4plugin$widgets$sta.app,
            init = _x4plugin$widgets$sta.init;
        var store = Object(_common_src_assets_main__WEBPACK_IMPORTED_MODULE_0__["buildStore"])({
          slug: state.slug,
          options: init
        });
        store.commit('BUILDER_ENABLED_CHANGE', {
          enabled: true
        });
        store.commit('BUILDER_OPENED_CHANGE', {
          opened: true
        });
        app.$store.dispatch('bootstrap/reset').then(function () {
          app.$store.replaceState(store.state);
          app.$store.dispatch('bootstrap');
        });
      },
      'reset/default': function (_ref3) {
        var state = _ref3.state;
        var _x4plugin$widgets$sta2 = x4plugin.widgets[state.slug],
            app = _x4plugin$widgets$sta2.app,
            init = _x4plugin$widgets$sta2.init;
        var type = init.type || x4plugin.defaults.type;
        var options = Object.assign({}, init, x4plugin.defaults[type]);
        var store = Object(_common_src_assets_main__WEBPACK_IMPORTED_MODULE_0__["buildStore"])({
          slug: state.slug,
          options: options
        });
        store.commit('BUILDER_ENABLED_CHANGE', {
          enabled: true
        });
        store.commit('BUILDER_OPENED_CHANGE', {
          opened: true
        });
        app.$store.dispatch('bootstrap/reset').then(function () {
          app.$store.replaceState(store.state);
          app.$store.dispatch('bootstrap');
        });
      },
      'reset/initial/option': function (_ref4, _ref5) {
        var state = _ref4.state,
            dispatch = _ref4.dispatch;
        var path = _ref5.path;
        var _x4plugin$widgets$sta3 = x4plugin.widgets[state.slug],
            app = _x4plugin$widgets$sta3.app,
            init = _x4plugin$widgets$sta3.init;
        var store = Object(_common_src_assets_main__WEBPACK_IMPORTED_MODULE_0__["buildStore"])({
          slug: state.slug,
          options: init
        });
        var value = store.state;
        var subpath = path instanceof Object ? path.value : path;
        subpath.split('.').forEach(function (name) {
          value = value[name];
        });
        dispatch('builder/option/change', {
          path: path,
          value: value
        });
      },
      'reset/default/option': function (_ref6, _ref7) {
        var state = _ref6.state,
            dispatch = _ref6.dispatch;
        var path = _ref7.path;
        var _x4plugin$widgets$sta4 = x4plugin.widgets[state.slug],
            app = _x4plugin$widgets$sta4.app,
            init = _x4plugin$widgets$sta4.init;
        var type = init.type || x4plugin.defaults.type;
        var options = Object.assign({}, init, x4plugin.defaults[type]);
        var store = Object(_common_src_assets_main__WEBPACK_IMPORTED_MODULE_0__["buildStore"])({
          slug: state.slug,
          options: options
        });
        var value = store.state;
        var subpath = path instanceof Object ? path.value : path;
        subpath.split('.').forEach(function (name) {
          value = value[name];
        });
        dispatch('builder/option/change', {
          path: path,
          value: value
        });
      },
      'options/save': function (_ref8, _ref9) {
        var dispatch = _ref8.dispatch;
        var init = _ref9.init;

        if (false) { var options; }
      }
    }
  };
});

/***/ }),
/* 17 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var script = document.querySelector('script[data-entry="x4-crypto-charts"]');
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'builder/position',
    state: script.dataset.vcRight !== undefined ? 'right' : 'left',
    mutations: {
      BUILDER_POSITION_CHANGE: function (state, _ref) {
        var position = _ref.position;
        state.builder.position = position;
      }
    },
    actions: {
      change: function (_ref2) {
        var state = _ref2.state,
            commit = _ref2.commit;
        var position = state.builder.position === 'left' ? 'right' : 'left';
        commit('BUILDER_POSITION_CHANGE', {
          position: position
        });
      }
    }
  };
});

/***/ }),
/* 18 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var x4wp = window.x4wp;
var globName = 'X4CryptoCharts';
var x4plugin = window[globName];
var x4builder = x4wp.builders[globName];
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'builder/presets',
    getters: {
      themes: function (state) {
        return function (_ref) {
          var changeName = _ref.changeName,
              allowedThemes = _ref.allowedThemes,
              allowedSubthemes = _ref.allowedSubthemes;
          var presets = [];
          x4builder.schema[state.type].themes.forEach(function (theme) {
            if (allowedThemes.indexOf(theme.name) !== -1) {
              var ptheme = Object.assign({}, theme, {
                subthemes: []
              });
              theme.subthemes.forEach(function (subtheme) {
                if (allowedSubthemes[theme.name].indexOf(subtheme.name) !== -1) {
                  var psubtheme = Object.assign({}, subtheme, {
                    changes: {}
                  });
                  var changes = x4plugin.defaults[state.type].changes;

                  if (changes[theme.name] && changes[theme.name][subtheme.name]) {
                    if (changeName === true) {
                      psubtheme.changes = changes[theme.name][subtheme.name];
                    } else if (changes[theme.name][subtheme.name][changeName]) {
                      psubtheme.changes[changeName] = changes[theme.name][subtheme.name][changeName];
                    }
                  }

                  ptheme.subthemes.push(psubtheme);
                }
              });
              presets.push(ptheme);
            }
          });
          return presets;
        };
      },
      colors: function (state) {
        return function (_ref2) {
          var allowedColors = _ref2.allowedColors,
              path = _ref2.path;
          var presets = x4builder.schema[state.type].colors.filter(function (color) {
            return allowedColors.indexOf(color.name) !== -1;
          });

          if (path !== undefined) {
            presets = presets.map(function (color) {
              return Object.assign({
                path: path
              }, color);
            });
          }

          return presets;
        };
      }
    }
  };
});

/***/ }),
/* 19 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'colors',
    state: Object.assign({}, options.colors)
  };
});

/***/ }),
/* 20 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'layout',
    state: options.layout
  };
});

/***/ }),
/* 21 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'margins',
    state: options.margins
  };
});

/***/ }),
/* 22 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'selector',
    state: options.selector
  };
});

/***/ }),
/* 23 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var slug = _ref.slug;
  return {
    name: 'slug',
    state: slug
  };
});

/***/ }),
/* 24 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'sublayout',
    state: options.sublayout
  };
});

/***/ }),
/* 25 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'subtheme',
    state: options.subtheme
  };
});

/***/ }),
/* 26 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'theme',
    state: options.theme
  };
});

/***/ }),
/* 27 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'type',
    state: options.type
  };
});

/***/ }),
/* 28 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/colors',
    getters: {
      rgba2hex: function () {
        return function (_ref) {
          var rgba = _ref.rgba;
          rgba = rgba.match(/^rgba\(\s*([0-9]+),\s*([0-9]+),\s*([0-9]+),\s*([0-9.]+)\s*\)$/);

          if (!rgba) {
            return '#000000';
          }

          var r = ('0' + parseInt(rgba[1], 10).toString(16)).slice(-2);
          var g = ('0' + parseInt(rgba[2], 10).toString(16)).slice(-2);
          var b = ('0' + parseInt(rgba[3], 10).toString(16)).slice(-2);

          if (r[0] === r[1] && g[0] === g[1] && b[0] === b[1]) {
            r = r[0];
            g = g[0];
            b = b[0];
          }

          return '#' + r + g + b;
        };
      },
      rgba2opacity: function () {
        return function (_ref2) {
          var rgba = _ref2.rgba;
          rgba = rgba.match(/^rgba\(\s*([0-9]+),\s*([0-9]+),\s*([0-9]+),\s*([0-9.]+)\s*\)$/i);

          if (!rgba) {
            return 1;
          }

          return parseFloat(rgba[4]).toFixed(2);
        };
      },
      'rgba/opacity': function () {
        return function (_ref3) {
          var rgba = _ref3.rgba,
              opacity = _ref3.opacity;

          if (opacity < 1) {
            opacity = opacity.toString().replace('0.', '.');
          }

          return rgba.replace(/,\s*[0-9.]+\s*\)$/, ',' + opacity + ')');
        };
      },
      hex2rgba: function () {
        return function (_ref4) {
          var hex = _ref4.hex,
              opacity = _ref4.opacity;

          if (!hex || !hex.match(/^#[0-9a-f]+$/i) || hex.length !== 7 && hex.length !== 4) {
            return 'rgba(0,0,0,0)';
          }

          opacity = !isNaN(opacity) ? opacity : 1;
          hex = hex.replace('#', '').split('');

          if (hex.length === 3) {
            hex.splice(0, 0, hex[0]);
            hex.splice(2, 0, hex[2]);
            hex.splice(4, 0, hex[4]);
          }

          var r = parseInt(hex[0] + hex[1], 16);
          var g = parseInt(hex[2] + hex[3], 16);
          var b = parseInt(hex[4] + hex[5], 16);

          if (opacity < 1) {
            opacity = opacity.toString().replace('0.', '.');
          }

          return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity + ')';
        };
      }
    }
  };
});

/***/ }),
/* 29 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(4);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/items',
    getters: {
      'format/valtag': function () {
        return function (value, _ref) {
          var notags = _ref.notags;
          return !notags ? '<div class="x4-val">' + value + '</div>' : value;
        };
      },
      'format/template': function () {
        return function (value, _ref2) {
          var template = _ref2.template,
              patterns = _ref2.patterns,
              notags = _ref2.notags;
          var result = Object(___WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(template) || '';

          if (!notags) {
            result = result.replace(/\s/g, '&nbsp;');
          }

          if (value !== null) {
            result = result.replace('[value]', Object(___WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(value));
          }

          if (patterns) {
            result = result.replace(/\[([^\]]+)\]/g, function (match, options) {
              options = options.split(',');
              var name = options[0];
              var prefix = options[1];
              var suffix = options[2];
              return patterns[name] !== undefined && patterns[name] !== '' ? (prefix || '') + Object(___WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(patterns[name]) + (suffix || '') : '';
            });
          }

          return result;
        };
      },
      'format/number': function () {
        return function (value, _ref3) {
          var factor = _ref3.factor,
              separator = _ref3.separator,
              precision = _ref3.precision,
              notags = _ref3.notags;
          value = value !== undefined ? value : 0;
          precision = precision !== undefined ? precision : 0;
          var abbreviations = ['K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
          var abbreviation = '';

          if (factor) {
            var max = factor !== 'auto' ? abbreviations.indexOf(factor) + 1 : 8;

            for (var i = 0; i < max; i++) {
              var val = value / 1000;

              if (factor === 'auto' && val < 1) {
                break;
              }

              abbreviation = abbreviations[i];
              value = val;
            }
          }

          if (precision === 'auto') {
            if (value >= 100) {
              precision = 0;
            } else if (value >= 10 && value < 100) {
              precision = 1;
            } else if (value >= 0.1 && value < 10) {
              precision = 2;
            } else if (value >= 0.01 && value < 0.1) {
              precision = 3;
            } else if (value >= 0.001 && value < 0.01) {
              precision = 4;
            } else if (value >= 0.0001 && value < 0.001) {
              precision = 5;
            } else if (value >= 0.00001 && value < 0.0001) {
              precision = 6;
            } else if (value >= 0.000001 && value < 0.00001) {
              precision = 7;
            } else if (value < 0.000001) {
              precision = 8;
            }
          }

          value = value.toFixed(precision).toString().split('.');

          if (separator) {
            value[0] = value[0].replace(/\B(?=(\d{3})+(?!\d))/g, separator);
          }

          value = value.join('.') + abbreviation;
          return !notags ? '<div class="x4-number">' + value + '</div>' : value;
        };
      }
    }
  };
});

/***/ }),
/* 30 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _common_bootstrap_wait__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(5);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/items',
    getters: {
      src: function () {
        var _this = this;

        return function (_ref) {
          var src = _ref.src;
          var result = src;

          if (typeof src === 'string') {
            result = _this;
            src.split('.').forEach(function (name) {
              result = result[name];
            });
          }

          return result;
        };
      },
      sortable: function () {
        return function (_ref2) {
          var $el = _ref2.$el,
              options = _ref2.options;
          return new Promise(function (resolve) {
            Object(_common_bootstrap_wait__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(function () {
              return !!window.Sortable;
            }, function () {
              var Sortable = window.Sortable;
              var sortable = Sortable.create($el, Object.assign({}, options, {
                sort: true,
                animation: 150,
                ghostClass: 'x4-sortable-ghost',
                chosenClass: 'x4-sortable-chosen',
                dragClass: 'x4-sortable-drag'
              }));
              resolve({
                sortable: sortable
              });
            });
          });
        };
      },
      get: function () {
        return function (_ref3) {
          var hash = _ref3.hash,
              strategy = _ref3.strategy,
              except = _ref3.except,
              top = _ref3.top,
              filters = _ref3.filters,
              sort = _ref3.sort;
          var rtop = [];
          var list = [];

          if (!strategy || strategy === 'include_all') {
            if (except && except.length > 0) {
              for (var key in hash) {
                if (except.indexOf(key) === -1) {
                  list.push(hash[key]);
                }
              }
            } else {
              list = Object.values(hash);
            }
          } else {
            if (except && except.length > 0) {
              except.forEach(function (key) {
                if (hash[key]) {
                  list.push(hash[key]);
                }
              });
            }
          }

          if (filters) {
            filters.forEach(function (filter) {
              list = list.filter(function (item) {
                return filter(item);
              });
            });
          }

          if (top && top.length > 0) {
            top = top.map(function (key) {
              return hash[key];
            }).filter(function (item) {
              return !!item;
            });
            list = list.filter(function (item) {
              var index = top.indexOf(item);

              if (index !== -1) {
                rtop[index] = item;
                return false;
              }

              return true;
            });
            rtop = rtop.filter(function (item) {
              return !!item;
            });
          }

          if (sort) {
            var field = sort.split(':').shift();
            var type = sort.split(':').pop();
            var less = type === 'asc' ? -1 : 1;
            var more = type === 'asc' ? 1 : -1;
            list.sort(function (item1, item2) {
              return item1[field] < item2[field] ? less : item1[field] === item2[field] ? 0 : more;
            });
          }

          return rtop.concat(list);
        };
      }
    }
  };
});

/***/ }),
/* 31 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(4);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/items/menu',
    getters: {
      hash: function (state, getters) {
        return function (_ref) {
          var items = _ref.items,
              itemValue = _ref.itemValue,
              itemTitle = _ref.itemTitle,
              itemTemplate = _ref.itemTemplate,
              hasNull = _ref.hasNull;
          var result = {};

          if (hasNull) {
            result[null] = '';
          }

          for (var key in items) {
            var value = itemValue !== '_key' && itemValue !== '_value' ? items[key][itemValue] : itemValue === '_value' ? items[key] : key;
            var title = '';

            if (itemTitle) {
              title = Object(___WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(itemTitle !== '_key' && itemTitle !== '_value' ? items[key][itemTitle] : itemTitle === '_value' ? items[key] : key);
            }

            if (itemTemplate) {
              title = getters['helpers/items/format/template'](null, {
                template: itemTemplate,
                patterns: items[key]
              });
            }

            result[value] = title;
          }

          return result;
        };
      },
      options: function () {
        return function (_ref2) {
          var items = _ref2.items,
              itemValue = _ref2.itemValue,
              hasNull = _ref2.hasNull;
          var result = [];

          if (hasNull) {
            result.push(null);
          }

          for (var key in items) {
            result.push(itemValue !== '_key' && itemValue !== '_value' ? items[key][itemValue] : itemValue === '_value' ? items[key] : key);
          }

          return result;
        };
      }
    }
  };
});

/***/ }),
/* 32 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var x4wp = window.x4wp;
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/request',
    actions: {
      _name: function (context, _ref) {
        var url = _ref.url,
            method = _ref.method,
            contentType = _ref.contentType,
            headers = _ref.headers,
            body = _ref.body,
            name = _ref.name,
            cache = _ref.cache;
        return new Promise(function (resolve, reject) {
          method = method ? method.toUpperCase() : 'GET';
          contentType = contentType ? contentType : 'json';
          headers = headers || {};
          body = body || {};
          name = name || Math.random().toString(36).substr(2, 8);
          cache = cache !== undefined ? cache : method === 'GET' ? true : false;

          var callback = function () {
            var xmlHttp = x4wp.requests[name].xmlHttp;

            if (!x4wp.requests[name].parsed) {
              try {
                xmlHttp.responseParsed = JSON.parse(xmlHttp.responseText);
              } catch (err) {}

              x4wp.requests[name].parsed = true;
            }

            if (xmlHttp.status === 200) {
              resolve(xmlHttp.responseParsed);
            } else {
              reject(xmlHttp);
            }
          };

          if (!cache || !x4wp.requests[name] || cache === parseInt(cache, 10) && new Date().getTime() - x4wp.requests[name].lastTime > cache) {
            var xmlHttp = new XMLHttpRequest();
            x4wp.requests[name] = {
              xmlHttp: xmlHttp,
              callbacks: [callback],
              ready: false,
              parsed: false,
              lastTime: new Date().getTime()
            };
            xmlHttp.open(method, url, true);

            for (var hname in headers) {
              xmlHttp.setRequestHeader(hname, headers[hname]);
            }

            xmlHttp.onreadystatechange = function () {
              if (xmlHttp.readyState === 4) {
                x4wp.requests[name].ready = true;
                var callbacks = x4wp.requests[name].callbacks;
                x4wp.requests[name].callbacks = [];
                callbacks.forEach(function (callback) {
                  return callback(xmlHttp);
                });
              }
            };

            if (method === 'POST') {
              if (contentType === 'urlencoded') {
                body = Object.keys(body).map(function (key) {
                  return key + '=' + encodeURIComponent(body[key] instanceof Object ? JSON.stringify(body[key]) : body[key]);
                }).join('&');
                xmlHttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xmlHttp.send(body);
              }

              if (contentType === 'json') {
                xmlHttp.setRequestHeader('Content-Type', 'application/json');
                xmlHttp.send(JSON.stringify(body));
              }
            } else {
              xmlHttp.send();
            }
          } else {
            if (!x4wp.requests[name].ready) {
              x4wp.requests[name].callbacks.push(callback);
            } else {
              callback();
            }
          }
        });
      }
    }
  };
});

/***/ }),
/* 33 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/wprequest',
    actions: {
      _name: function (_ref, _ref2) {
        var dispatch = _ref.dispatch;
        var action = _ref2.action,
            body = _ref2.body;
        var ajax_url = window.X4WP_ajax_url;
        return new Promise(function (resolve, reject) {
          var options = {
            url: ajax_url,
            method: 'POST',
            contentType: 'urlencoded',
            body: {
              action: action,
              body: body || {}
            },
            cache: false
          };
          dispatch('helpers/request', options).then(function (resp) {
            if (!resp.success) {
              if (false) {}

              return reject(resp.data);
            }

            resolve(resp.data);
          }).catch(function (xmlHttp) {
            reject({
              message: xmlHttp.statusText
            });
          });
        });
      }
    }
  };
});

/***/ }),
/* 34 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/wheelPrevent',
    getters: {
      _name: function () {
        return function (_ref) {
          var el = _ref.el,
              event = _ref.event;

          if (el.scrollTop === 0 && event.deltaY < 0 || Math.abs(el.scrollTop - (el.scrollHeight - el.clientHeight)) <= 1 && event.deltaY > 0) {
            event.preventDefault();
          }

          event.stopPropagation();
        };
      }
    }
  };
});

/***/ }),
/* 35 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Vue = window.Vue;
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'entities/coins',
    state: {},
    mutations: {
      COINS_INIT: function (state, _ref) {
        var coins = _ref.coins;
        state.entities.coins = coins;
      },
      COINS_ADD: function (state, _ref2) {
        var coins = _ref2.coins;

        for (var id in coins) {
          Vue.set(state.entities.coins, id, coins[id]);
        }
      }
    },
    getters: {
      'change/field/add': function (state) {
        return function (_ref3) {
          var coins = _ref3.coins;

          for (var id in coins) {
            coins[id].change = state.entities.coins[id] && state.entities.coins[id].change !== undefined ? state.entities.coins[id].change : 0;
          }
        };
      },
      all: function (state, getters) {
        return getters['helpers/items/get']({
          hash: state.entities.coins,
          sort: 'rank:asc'
        });
      }
    },
    actions: {
      retrieve: function (_ref4, _ref5) {
        var state = _ref4.state,
            getters = _ref4.getters,
            commit = _ref4.commit,
            dispatch = _ref4.dispatch;
        var ids = _ref5.ids,
            cache = _ref5.cache;
        return new Promise(function (resolve, reject) {
          dispatch('coincap/assets/retrieve', {
            ids: ids,
            cache: cache
          }).then(function (_ref6) {
            var assets = _ref6.assets;
            getters['entities/coins/change/field/add']({
              coins: assets
            });

            if (!ids) {
              commit('COINS_INIT', {
                coins: assets
              });
            } else {
              commit('COINS_ADD', {
                coins: assets
              });
            }

            resolve({
              assets: assets
            });
          }).catch(reject);
        });
      }
    }
  };
});

/***/ }),
/* 36 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'entities/fiats',
    state: {
      'united-states-dollar': {
        id: 'united-states-dollar',
        short: 'USD',
        type: 'fiat',
        price: 1,
        symbol: '$',
        name: 'United States Dollar'
      }
    },
    mutations: {
      FIATS_INIT: function (state, _ref) {
        var fiats = _ref.fiats;
        state.entities.fiats = fiats;
      }
    },
    getters: {
      all: function (state, getters) {
        return getters['helpers/items/get']({
          hash: state.entities.fiats,
          sort: 'short:asc'
        });
      }
    },
    actions: {
      retrieve: function (_ref2, _ref3) {
        var commit = _ref2.commit,
            dispatch = _ref2.dispatch;
        var cache = _ref3.cache;
        return dispatch('coincap/rates/retrieve', {
          cache: cache
        }).then(function (_ref4) {
          var fiats = _ref4.fiats;
          commit('FIATS_INIT', {
            fiats: fiats
          });
        }).catch(function (err) {});
      }
    }
  };
});

/***/ }),
/* 37 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Vue = window.Vue;
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'entities/history',
    state: {},
    mutations: {
      HISTORY_INIT: function (state, _ref) {
        var id = _ref.id,
            history = _ref.history,
            period = _ref.period;

        if (!state.entities.history[id]) {
          Vue.set(state.entities.history, id, {});
        }

        Vue.set(state.entities.history[id], period, history);
      }
    },
    actions: {
      ensure: function (_ref2, _ref3) {
        var state = _ref2.state,
            dispatch = _ref2.dispatch;
        var id = _ref3.id,
            period = _ref3.period;

        if (!state.entities.history[id] || !state.entities.history[id][period]) {
          return dispatch('entities/history/retrieve', {
            id: id,
            period: period
          });
        }
      },
      retrieve: function (_ref4, _ref5) {
        var state = _ref4.state,
            commit = _ref4.commit,
            dispatch = _ref4.dispatch;
        var id = _ref5.id,
            interval = _ref5.interval,
            period = _ref5.period,
            cache = _ref5.cache;

        if (!id) {
          return;
        }

        var end = new Date().getTime();
        var start = end - period;
        return new Promise(function (resolve, reject) {
          dispatch('coincap/history/retrieve', {
            id: id,
            interval: interval,
            start: start,
            end: end,
            cache: cache
          }).then(function (_ref6) {
            var history = _ref6.history;
            commit('HISTORY_INIT', {
              id: id,
              history: history,
              period: period
            });
            resolve({
              history: history
            });
          }).catch(reject);
        });
      }
    }
  };
});

/***/ }),
/* 38 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(4);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'helpers/items',
    getters: {
      'format/price': function (state) {
        return function (value, _ref, coin, _ref2) {
          var notags = _ref.notags;
          var fiat = _ref2.fiat;
          return value / fiat.price;
        };
      },
      'format/coin': function (state) {
        return function (value, _ref3, coin) {
          var notags = _ref3.notags;
          value = value.replace(/\[icon\]/g, !notags ? '<img class="x4-icon" src="https://static.coincap.io/assets/icons/' + coin.short.toLowerCase() + '@2x.png" onError="this.src=\'https://coincap.io/static/logo_mark.png\'" />' : '');
          value = value.replace(/\[name\]/g, !notags ? '<div class="x4-name">' + Object(___WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(coin.name) + '</div>' : coin.name);
          value = value.replace(/\[short\]/g, !notags ? '<div class="x4-short">' + Object(___WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(coin.short) + '</div>' : coin.short);
          return value;
        };
      },
      'format/fiat': function () {
        return function (value, _ref4, coin, _ref5) {
          var notags = _ref4.notags;
          var fiat = _ref5.fiat;

          if (value.indexOf('[symbol]') !== -1) {
            if (fiat.symbol) {
              value = value.replace('[symbol]', !notags ? '<div class="x4-symbol">' + fiat.symbol + '</div>' : fiat.symbol);
              value = value.replace(/(&nbsp;)?\[short\](&nbsp;)?/, '');
            } else {
              value = value.replace('[symbol]', '');
            }
          }

          if (value.indexOf('[short]') !== -1) {
            value = value.replace('[short]', !notags ? '<div class="x4-short">' + fiat.short + '</div>' : fiat.short);
          }

          return value;
        };
      }
    }
  };
});

/***/ }),
/* 39 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var x4wp = window.x4wp;
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'coincap/assets',
    state: {
      custom: options.customCoins || []
    },
    getters: {
      format: function () {
        return function (_ref2) {
          var asset = _ref2.asset;
          return {
            id: asset.id,
            short: asset.symbol,
            rank: parseInt(asset.rank),
            price: parseFloat(asset.priceUsd) || 0,
			priceBTC: parseFloat(asset.priceBTC) || 0,
            mktcap: parseFloat(asset.marketCapUsd) || 0,
            change24h: parseFloat(asset.changePercent24Hr) || 0,
            vwap: parseFloat(asset.vwapUsd24Hr || asset.vwap24Hr) || 0,
            volume: parseFloat(asset.volumeUsd24Hr) || 0,
            supply: parseFloat(asset.supply) || 0,
            name: asset.name || '',
            website: asset.website || '',
            explorer: asset.explorer || ''
          };
        };
      }
    },
    actions: {
      retrieve: function (_ref3, _ref4) {
        var state = _ref3.state,
            getters = _ref3.getters,
            dispatch = _ref3.dispatch;
        var ids = _ref4.ids,
            cache = _ref4.cache,
            log = _ref4.log;
        return new Promise(function (resolve, reject) {
          var options = Object.assign({}, {
            "styles": {
              "material-icons": "https://fonts.googleapis.com/icon?family=Material+Icons",
              "roboto": "https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900"
            },
            "scripts": {
              "chartjs": "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js",
              "es6promise": "https://cdnjs.cloudflare.com/ajax/libs/es6-promise/4.1.1/es6-promise.auto.min.js",
              "sortable": "https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.6.0/Sortable.min.js",
              "vue": "https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.17/vue.runtime.min.js",
              "vuex": "https://cdnjs.cloudflare.com/ajax/libs/vuex/3.0.1/vuex.min.js"
            },
            "requests": {
              "coincapRates": {
                "method": "GET",
                "url": "https://api.coincap.io/v2/rates?limit=2000"
              },
              "coincapAssets": {
                "method": "GET",
                "url": "https://api.coincap.io/v2/assets?limit=2000"
              },
              "coincapAssetsMeta": {
                "method": "POST",
                "url": "https://graphql.coincap.io",
                "body": {
                  "query": "query{assets(first:2000,sort:rank,direction:ASC){edges{node{id,website,explorer}}}}"
                }
              }
            }
          }.requests.coincapAssets, {
            log: log,
            cache: cache,
            name: 'coincapAssets'
          });

          if (ids) {
            options.name += '_' + ids.join(',');
            options.url += '&ids=' + ids.join(',');
          }

          dispatch('helpers/request', options).then(function (resp) {
            var assets = {};

            if (!resp || !resp.data) {
              return reject();
            }

            resp.data.forEach(function (data) {
              assets[data.id] = getters['coincap/assets/format']({
                asset: data
              });
            });
            state.coincap.assets.custom.forEach(function (asset) {
              assets[asset.id] = asset;
              asset.custom = true;
            });
            resolve({
              assets: assets
            });
          }).catch(function (xmlHttp) {
            reject();
          });
        });
      }
    }
  };
});

/***/ }),
/* 40 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var x4wp = window.x4wp;
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'coincap/history',
    getters: {
      format: function () {
        return function (_ref) {
          var point = _ref.point;
          return {
            price: parseFloat(point.priceUsd) || 0,
            time: new Date(point.time)
          };
        };
      }
    },
    actions: {
      retrieve: function (_ref2, _ref3) {
        var state = _ref2.state,
            getters = _ref2.getters,
            dispatch = _ref2.dispatch;
        var id = _ref3.id,
            interval = _ref3.interval,
            start = _ref3.start,
            end = _ref3.end,
            cache = _ref3.cache,
            log = _ref3.log;
        return new Promise(function (resolve, reject) {
          if (state.coincap.assets.custom.map(function (coin) {
            return coin.id;
          }).indexOf(id) !== -1) {
            return resolve({
              history: []
            });
          }

          if (!interval) {
            var period = end - start;

            if (period <= 86400000) {
              // 1 day
              interval = 'm5';
            } else if (period > 86400000 && period <= 604800000) {
              // 7 days
              interval = 'm30';
            } else if (period > 604800000 && period <= 2592000000) {
              // 30 days
              interval = 'h2';
            } else if (period > 2592000000 && period <= 7776000000) {
              // 90 days
              interval = 'h6';
            } else if (period > 7776000000 && period <= 15552000000) {
              // 180 days
              interval = 'h12';
            } else if (period > 15552000000) {
              // 365 days
              interval = 'd1';
            }
          }

          var name = 'coincapHistory_' + id + '_' + interval + '_' + (start || '0') + '_' + (end || '0');
          var url = 'https://api.coincap.io/v2/assets/' + id + '/history?interval=' + interval + (start ? '&start=' + start : '') + (end ? '&end=' + end : '');
          dispatch('helpers/request', {
            type: 'GET',
            url: url,
            log: log,
            cache: cache,
            name: name
          }).then(function (resp) {
            if (!resp || !resp.data) {
              return reject();
            }

            var history = resp.data.map(function (point) {
              return getters['coincap/history/format']({
                point: point
              });
            });
            resolve({
              history: history
            });
          }).catch(function (xmlHttp) {
            reject();
          });
        });
      }
    }
  };
});

/***/ }),
/* 41 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'coincap/rates',
    state: {
      custom: options.customFiats || []
    },
    getters: {
      format: function () {
        return function (_ref2) {
          var rate = _ref2.rate;
          return {
            id: rate.id,
            short: rate.symbol,
            type: rate.type || 'fiat',
            price: parseFloat(rate.rateUsd) || 1,
            symbol: rate.currencySymbol || '',
            name: rate.name || ''
          };
        };
      }
    },
    actions: {
      retrieve: function (_ref3, _ref4) {
        var state = _ref3.state,
            getters = _ref3.getters,
            dispatch = _ref3.dispatch;
        var cache = _ref4.cache,
            log = _ref4.log;
        return new Promise(function (resolve, reject) {
          var options = Object.assign({}, {
            "styles": {
              "material-icons": "https://fonts.googleapis.com/icon?family=Material+Icons",
              "roboto": "https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900"
            },
            "scripts": {
              "chartjs": "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js",
              "es6promise": "https://cdnjs.cloudflare.com/ajax/libs/es6-promise/4.1.1/es6-promise.auto.min.js",
              "sortable": "https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.6.0/Sortable.min.js",
              "vue": "https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.17/vue.runtime.min.js",
              "vuex": "https://cdnjs.cloudflare.com/ajax/libs/vuex/3.0.1/vuex.min.js"
            },
            "requests": {
              "coincapRates": {
                "method": "GET",
                "url": "https://api.coincap.io/v2/rates?limit=2000"
              },
              "coincapAssets": {
                "method": "GET",
                "url": "https://api.coincap.io/v2/assets?limit=2000"
              },
              "coincapAssetsMeta": {
                "method": "POST",
                "url": "https://graphql.coincap.io",
                "body": {
                  "query": "query{assets(first:2000,sort:rank,direction:ASC){edges{node{id,website,explorer}}}}"
                }
              }
            }
          }.requests.coincapRates, {
            log: log,
            cache: cache,
            name: 'coincapRates'
          });
          dispatch('helpers/request', options).then(function (resp) {
            var fiats = {};

            if (!resp || !resp.data) {
              return reject();
            }

            resp.data.forEach(function (data) {
              fiats[data.id] = getters['coincap/rates/format']({
                rate: data
              });
            });
            state.coincap.rates.custom.forEach(function (fiat) {
              fiats[fiat.id] = fiat;
              fiat.custom = true;
            });
            resolve({
              fiats: fiats
            });
          }).catch(function (xmlHttp) {
            reject();
          });
        });
      }
    }
  };
});

/***/ }),
/* 42 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var intervals = {
  fiats: null,
  coins: null
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'bootstrap',
    state: {
      isLoaded: false
    },
    mutations: {
      LOADED_CHANGE: function (state, _ref) {
        var isLoaded = _ref.isLoaded;
        state.bootstrap.isLoaded = isLoaded;
      }
    },
    actions: {
      _name: function (_ref2) {
        var state = _ref2.state,
            getters = _ref2.getters,
            commit = _ref2.commit,
            dispatch = _ref2.dispatch;
        var ids = [1, 2, 3, 4].map(function (index) {
          return state.values['coin' + index];
        }).filter(function (id) {
          return !!id;
        });
        var promises = [dispatch('entities/fiats/retrieve'), dispatch('entities/coins/retrieve', {
          ids: ids
        })];

        for (var index = 1; index <= 4; index++) {
          if (state.values['coin' + index]) {
            var id = state.values['coin' + index];
            var period = state.values.period;
            promises.concat(dispatch('entities/history/retrieve', {
              id: id,
              period: period
            }));
          }
        }

        Promise.all(promises).then(function () {
          commit('LOADED_CHANGE', {
            isLoaded: true
          });
        });

        if (state.builder.enabled) {
          dispatch('entities/coins/retrieve');
        }

        intervals.fiats = setInterval(function () {
          dispatch('entities/fiats/retrieve', {
            cache: 5 * 60 * 1000
          });
        }, 5 * 60 * 1000 + 5000);
        intervals.coins = setInterval(function () {
          for (var _index = 1; _index <= 4; _index++) {
            if (state.values['coin' + _index]) {
              var _id = state.values['coin' + _index];
              var _period = state.values.period;
              dispatch('entities/history/retrieve', {
                id: _id,
                period: _period
              }, {
                cache: 5 * 60 * 1000
              });
            }
          }
        }, 5 * 60 * 1000 + 5000);
      },
      reset: function () {
        clearInterval(intervals.fiats);
        clearInterval(intervals.coins);
      }
    }
  };
});

/***/ }),
/* 43 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'controls/lineChart',
    state: options.controls.lineChart
  };
});

/***/ }),
/* 44 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'controls/fiatSelect',
    state: options.controls.fiatSelect,
    getters: {
      _name: function (state, getters) {
        return getters['helpers/items/get']({
          hash: state.entities.fiats,
          strategy: state.controls.fiatSelect.strategy,
          except: state.controls.fiatSelect.except,
          top: state.controls.fiatSelect.top,
          sort: 'short:asc'
        });
      }
    }
  };
});

/***/ }),
/* 45 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return {
    name: 'entities/fiats',
    getters: {
      _name: function (state, getters) {
        return getters['helpers/items/get']({
          hash: state.entities.fiats,
          strategy: state.controls.fiatSelect.strategy,
          except: state.controls.fiatSelect.except,
          top: state.controls.fiatSelect.top,
          sort: 'short:asc'
        });
      }
    }
  };
});

/***/ }),
/* 46 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'controls/periodSelect',
    state: options.controls.periodSelect
  };
});

/***/ }),
/* 47 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'controls/loader',
    state: options.controls.loader
  };
});

/***/ }),
/* 48 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'values/coin1',
    state: options.values.coin1,
    mutations: {
      COIN1_CHANGE: function (state, _ref2) {
        var coin1 = _ref2.coin1;
        state.values.coin1 = coin1;
      }
    }
  };
});

/***/ }),
/* 49 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'values/coin2',
    state: options.values.coin2,
    mutations: {
      COIN2_CHANGE: function (state, _ref2) {
        var coin2 = _ref2.coin2;
        state.values.coin2 = coin2;
      }
    }
  };
});

/***/ }),
/* 50 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'values/coin3',
    state: options.values.coin3,
    mutations: {
      COIN3_CHANGE: function (state, _ref2) {
        var coin3 = _ref2.coin3;
        state.values.coin3 = coin3;
      }
    }
  };
});

/***/ }),
/* 51 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'values/coin4',
    state: options.values.coin4,
    mutations: {
      COIN4_CHANGE: function (state, _ref2) {
        var coin4 = _ref2.coin4;
        state.values.coin4 = coin4;
      }
    }
  };
});

/***/ }),
/* 52 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'values/fiat',
    state: options.values.fiat,
    mutations: {
      FIAT_CHANGE: function (state, _ref2) {
        var fiat = _ref2.fiat;
        state.values.fiat = fiat;
      }
    }
  };
});

/***/ }),
/* 53 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var x4plugin = window['X4CryptoCharts'];
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'defaults/period',
    state: options.values.period,
    mutations: {
      PERIOD_CHANGE: function (state, _ref2) {
        var id = _ref2.id,
            period = _ref2.period;

        if (id === undefined && x4plugin.multiValues[state.type].period) {
          state.defaults.period = period;
        }
      }
    }
  };
});

/***/ }),
/* 54 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'selections/period',
    state: options.selections.period
  };
});

/***/ }),
/* 55 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Vue = window.Vue;
var x4plugin = window['X4CryptoCharts'];
/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var options = _ref.options;
  return {
    name: 'values/period',
    state: !x4plugin.multiValues[options.type].period ? options.values.period : {},
    mutations: {
      PERIOD_CHANGE: function (state, _ref2) {
        var id = _ref2.id,
            period = _ref2.period;

        if (x4plugin.multiValues[state.type].period) {
          if (id !== undefined) {
            Vue.set(state.values.period, id, period);
          }
        } else {
          state.values.period = period;
        }
      }
    },
    getters: {
      _name: function (state) {
        return function (_ref3) {
          var id = _ref3.id;
          return x4plugin.multiValues[state.type].period ? state.values.period[id] || state.defaults.period : state.values.period;
        };
      }
    }
  };
});

/***/ }),
/* 56 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/widgets/crypto/lineChart/components/LineChart.vue?vue&type=template&id=4c558268&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c(_vm.component,{tag:"div"},[_vm._t("base",null,{slot:"base"}),_vm._t("styles",null,{slot:"styles"}),(_vm.dataLoaded)?[_c('FiatSelect',{attrs:{"slot":"fiat-select"},slot:"fiat-select"}),_c('PeriodSelect',{attrs:{"slot":"period-select"},slot:"period-select"}),_c('LineChart',{attrs:{"slot":"line-chart"},slot:"line-chart"}),_c('Controls',{attrs:{"slot":"controls"},slot:"controls"})]:_vm._e()],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/LineChart.vue?vue&type=template&id=4c558268&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/layouts/layouts.js

/* harmony default export */ var layouts = ({
  'full-featured': map["a" /* default */].components({
    '1': __webpack_require__(65)
  })
});
// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/widgets/crypto/lineChart/components/LineChart.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var LineChartvue_type_script_lang_js_ = ({
  components: map["a" /* default */].components({
    FiatSelect: __webpack_require__(9),
    PeriodSelect: __webpack_require__(59),
    LineChart: __webpack_require__(63),
    Controls: __webpack_require__(62)
  }),
  computed: map["a" /* default */].variables({
    component: function (_ref) {
      var state = _ref.state;
      return layouts[state.layout][state.sublayout];
    },
    dataLoaded: function (_ref2) {
      var state = _ref2.state;
      return state.bootstrap === undefined || state.bootstrap.isLoaded === undefined || state.bootstrap.isLoaded === true;
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/LineChart.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_LineChartvue_type_script_lang_js_ = (LineChartvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/LineChart.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  components_LineChartvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "LineChart.vue"
/* harmony default export */ var LineChart = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 57 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/Input.vue?vue&type=template&id=78b15bf9&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.visible)?_c(_vm.themeComponent,{tag:"div",class:_vm.baseClass,attrs:{"is2":_vm.subthemeComponent,"options":_vm.options,"colors":_vm.colors,"scale":_vm.scale},nativeOn:{"click":function($event){return _vm.forceFocus($event)}}},[_c('transition',{attrs:{"slot":"icon","name":"x4"},slot:"icon"},[(_vm.icon)?_c('Icon',{staticClass:"x4-icon x4-transition",attrs:{"icon":_vm.icon}}):_vm._e()],1),(_vm.isSelect)?_c('Icon',{staticClass:"x4-dd-icon x4-transition",attrs:{"slot":"ddicon","icon":"arrow_drop_down"},slot:"ddicon"}):_vm._e(),(_vm.label)?_c('div',{staticClass:"x4-label x4-transition",attrs:{"slot":"label"},slot:"label"},[_vm._v(_vm._s(_vm.label))]):_vm._e(),_c('div',{staticClass:"x4-input-wrapper",attrs:{"slot":"input"},slot:"input"},[(!_vm.isSelect && !_vm.isTextarea)?_c('input',{ref:"input",staticClass:"x4-input",attrs:{"type":_vm.atype,"min":_vm.amin,"max":_vm.amax,"step":_vm.astep},domProps:{"value":_vm.avalue},on:{"input":_vm.input,"mouseup":_vm.mouseup,"focus":_vm.focus,"blur":_vm.blur}}):_vm._e(),(_vm.isSelect)?_c('div',{ref:"input",staticClass:"x4-select",domProps:{"innerHTML":_vm._s(_vm.atitle)}}):_vm._e(),(_vm.isTextarea)?_c('textarea',{ref:"input",staticClass:"x4-textarea x4-scrollable",domProps:{"value":_vm.avalue},on:{"input":_vm.input,"focus":_vm.focus,"blur":_vm.blur}}):_vm._e()]),(_vm.isSelect)?_c('template',{slot:"menu"},[(_vm.focused)?_c('div',{ref:"backdrop",staticClass:"x4-backdrop",on:{"click":function($event){$event.stopPropagation();return _vm.blur($event)},"wheel":_vm.backdropMouseWheel}}):_vm._e(),_c('DropDown',{ref:"menu",staticClass:"x4-menu x4-scrollable",attrs:{"fixed":true,"opened":_vm.focused},nativeOn:{"wheel":function($event){return _vm.menuMouseWheel($event)}}},_vm._l((_vm.menuOptions),function(option,index){return _c('div',{ref:(option === _vm.avalue ? 'option_active' : 'opt_' + option),refInFor:true,staticClass:"x4-option",class:{ 'x4-active': option === _vm.avalue },domProps:{"innerHTML":_vm._s(_vm.menuHash[option])},on:{"click":function($event){$event.stopPropagation();_vm.change({ value: option })}}})}))],1):_vm._e(),_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-input\n  display: inline-block\n  position: relative\n  vertical-align: top\n\n  .x4-icon\n    cursor: pointer\n\n  .x4-dd-icon\n    cursor: pointer\n\n  &.x4-focused .x4-dd-icon\n    transform: rotate(180deg)\n\n  .x4-input\n    height: auto\n    overflow-x: hidden\n\n  .x4-label, .x4-select, .x4-menu .x4-option\n    overflow-x: hidden\n    text-overflow: ellipsis\n    white-space: nowrap\n\n  .x4-backdrop\n    cursor: default\n\n  .x4-menu .x4-option\n    position: relative\n")])],2):_vm._e()}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Input/Input.vue?vue&type=template&id=78b15bf9&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/__.js
var _ = __webpack_require__(4);

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./common/components/ui/Input/themes/themes.js

/* harmony default export */ var themes = ({
  themes: map["a" /* default */].components({
    material: __webpack_require__(72)
  }),
  subthemes: {
    material: map["a" /* default */].components({
      filled: __webpack_require__(71),
      outlined: __webpack_require__(74),
      standard: __webpack_require__(73)
    })
  }
});
// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/Input.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//



/* harmony default export */ var Inputvue_type_script_lang_js_ = ({
  props: ['type', 'value', 'options', 'scale'],
  components: map["a" /* default */].components({
    Style: __webpack_require__(2),
    Icon: __webpack_require__(8),
    DropDown: __webpack_require__(69)
  }),
  data: function () {
    return {
      focused: false,
      dirty: this.value !== null && this.value !== '',
      avalue: this.value,
      keyPhrase: '',
      keyLastTime: new Date().getTime()
    };
  },
  mounted: function () {
    this.$parent.$refs.input = this.$refs.input;
    window.addEventListener('keypress', this.keypress);
  },
  updated: function () {
    this.$parent.$refs.input = this.$refs.input;
  },
  destroyed: function () {
    window.removeEventListener('keypress', this.keypress);
  },
  watch: {
    value: function (value) {
      this.avalue = value;
      this.dirty = value !== null && this.value !== '';
    }
  },
  computed: map["a" /* default */].variables({
    visible: function () {
      return this.options && this.options.visible !== undefined ? this.options.visible : true;
    },
    icon: function () {
      return this.options && this.options.icon ? this.options.icon : null;
    },
    label: function () {
      return this.options && this.options.label ? Object(_["a" /* default */])(this.options.label) : '';
    },
    theme: function (_ref) {
      var state = _ref.state;
      var value = !this.options || !this.options.theme ? this.$root.theme || state.theme : this.options.theme;
      return !themes.themes[value] ? 'material' : value;
    },
    subtheme: function (_ref2) {
      var state = _ref2.state;
      var value = !this.options || !this.options.subtheme ? this.$root.subtheme || state.subtheme : this.options.subtheme;
      return !themes.subthemes[this.theme][value] ? 'filled' : value;
    },
    colors: function (_ref3) {
      var state = _ref3.state;
      var colors = this.$root.colors || state.colors;
      return this.options && this.options.colors ? Object.assign({}, colors, this.options.colors) : colors;
    },
    themeComponent: function () {
      return themes.themes[this.theme];
    },
    subthemeComponent: function () {
      return themes.subthemes[this.theme][this.subtheme];
    },
    atype: function () {
      return this.type || 'text';
    },
    isSelect: function () {
      return this.atype === 'select';
    },
    isTextarea: function () {
      return this.atype === 'textarea';
    },
    amin: function () {
      return this.options && this.options.min !== undefined ? this.options.min : null;
    },
    amax: function () {
      return this.options && this.options.max !== undefined ? this.options.max : null;
    },
    astep: function () {
      return this.options && this.options.step !== undefined ? this.options.step : null;
    },
    baseClass: function () {
      var result = {};
      result['x4-ui-input'] = true;
      result['x4-type-' + this.atype] = true;
      result['x4-theme-' + this.theme] = true;
      result['x4-subtheme-' + this.subtheme] = true;
      result['x4-no-label'] = !this.label;
      result['x4-no-icon'] = !this.icon;
      result['x4-dirty'] = this.dirty;
      result['x4-focused'] = this.focused;
      result['x4-transition'] = true;
      result['x4-clearfix'] = true;
      return result;
    },
    aitems: function (_ref4) {
      var getters = _ref4.getters;
      return getters['helpers/items/src']({
        src: this.options.items
      });
    },
    menuHash: function (_ref5) {
      var getters = _ref5.getters;
      return getters['helpers/items/menu/hash']({
        items: this.aitems,
        itemValue: this.options.itemValue,
        itemTitle: this.options.itemTitle,
        itemTemplate: this.options.itemTemplate,
        hasNull: this.options.hasNull
      });
    },
    menuOptions: function (_ref6) {
      var getters = _ref6.getters;
      return getters['helpers/items/menu/options']({
        items: this.aitems,
        itemValue: this.options.itemValue,
        hasNull: this.options.hasNull
      });
    },
    atitle: function () {
      return this.menuHash[this.avalue];
    }
  }),
  methods: map["a" /* default */].variables({
    focus: function () {
      var _this = this;

      this.focused = true;
      this.$nextTick(function () {
        if (_this.$refs.menu && _this.$refs.option_active && _this.$refs.option_active.length > 0) {
          if (_this.$refs.option_active[0].offsetTop >= 6 * _this.$refs.option_active[0].offsetHeight) {
            _this.$refs.menu.$el.scrollTop = _this.$refs.option_active[0].offsetTop - _this.$refs.option_active[0].offsetHeight;
          }
        }
      });
    },
    blur: function () {
      this.focused = false;
    },
    forceFocus: function () {
      if (this.isSelect) {
        return this.focus();
      }

      this.$refs.input.focus();
    },
    input: function () {
      this.avalue = this.$refs.input.value;
      this.dirty = !!this.$refs.input.value;
      this.$emit('change', {
        value: this.$refs.input.value
      });
    },
    mouseup: function () {
      var _this2 = this;

      var value = this.$refs.input.value;
      this.$nextTick(function () {
        if (value !== _this2.$refs.input.value) {
          _this2.$refs.input.blur();

          _this2.avalue = '';
          _this2.dirty = false;

          if (window.isEdge) {
            _this2.focused = false;

            _this2.$emit('change', {
              value: ''
            });
          }
        }
      });
    },
    change: function (context, _ref7) {
      var value = _ref7.value;
      this.blur();
      this.avalue = value;
      this.dirty = !!value;
      this.$emit('change', {
        value: value
      });
    },
    keypress: function (context, event) {
      if (!this.focused || !event.key || !this.$refs.menu) {
        return;
      }

      if (new Date().getTime() - this.keyLastTime < 1000) {
        this.keyPhrase += event.key;
      } else {
        this.keyPhrase = event.key;
      }

      this.keyLastTime = new Date().getTime();
      var regexp1 = new RegExp('^' + this.keyPhrase, 'i');
      var regexp2 = new RegExp(this.keyPhrase, 'i');

      for (var value in this.menuHash) {
        if (this.menuHash[value].match(regexp1)) {
          if (this.$refs['opt_' + value]) {
            this.$refs.menu.$el.scrollTop = this.$refs['opt_' + value][0].offsetTop;
          }

          return;
        }
      }

      for (var _value in this.menuHash) {
        if (this.menuHash[_value].match(regexp2)) {
          if (this.$refs['opt_' + _value]) {
            this.$refs.menu.$el.scrollTop = this.$refs['opt_' + _value][0].offsetTop;
          }

          return;
        }
      }
    },
    backdropMouseWheel: function (context, event) {
      var _this3 = this;

      this.$refs.backdrop.style.display = 'none';
      setTimeout(function () {
        _this3.$refs.backdrop.style.removeProperty('display');
      });
    },
    menuMouseWheel: function (_ref8, event) {
      var getters = _ref8.getters;
      getters['helpers/wheelPrevent']({
        el: this.$refs.menu.$el,
        event: event
      });
    }
  })
});
// CONCATENATED MODULE: ./common/components/ui/Input/Input.vue?vue&type=script&lang=js&
 /* harmony default export */ var Input_Inputvue_type_script_lang_js_ = (Inputvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Input/Input.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  Input_Inputvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "Input.vue"
/* harmony default export */ var Input = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 58 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/RadioButtons/RadioButtons.vue?vue&type=template&id=8215bcce&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.visible)?_c(_vm.themeComponent,{tag:"div",class:_vm.baseClass,attrs:{"is2":_vm.subthemeComponent,"value":_vm.mvalue,"menuOptions":_vm.menuOptions,"colors":_vm.colors,"scale":_vm.scale},on:{"change":_vm.change},scopedSlots:_vm._u([{key:"label",fn:function(ref){
var option = ref.option;
return _c('div',{staticClass:"x4-label"},[_vm._v(_vm._s(_vm.menuHash[option]))])}}])},[_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-radio-buttons\n  display: inline-block\n  position: relative\n  vertical-align: top\n\n  .x4-label\n    overflow-x: hidden\n    text-overflow: ellipsis\n    white-space: nowrap\n")])],2):_vm._e()}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/RadioButtons/RadioButtons.vue?vue&type=template&id=8215bcce&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./common/components/ui/RadioButtons/themes/themes.js

/* harmony default export */ var themes = ({
  themes: map["a" /* default */].components({
    material: __webpack_require__(60)
  }),
  subthemes: {
    material: map["a" /* default */].components({
      default: __webpack_require__(70)
    })
  }
});
// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/RadioButtons/RadioButtons.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var RadioButtonsvue_type_script_lang_js_ = ({
  props: ['value', 'options', 'scale'],
  components: map["a" /* default */].components({
    Style: __webpack_require__(2)
  }),
  data: function () {
    return {
      mvalue: this.value
    };
  },
  watch: {
    value: function (value) {
      this.mvalue = value;
    }
  },
  computed: map["a" /* default */].variables({
    visible: function () {
      return this.options && this.options.visible !== undefined ? this.options.visible : true;
    },
    theme: function (_ref) {
      var state = _ref.state;
      var value = !this.options || !this.options.theme ? this.$root.theme || state.theme : this.options.theme;
      return !themes.themes[value] ? 'material' : value;
    },
    subtheme: function (_ref2) {
      var state = _ref2.state;
      var value = this.options && this.options.subtheme ? this.options.subtheme : 'default';
      return !themes.subthemes[this.theme][value] ? 'default' : value;
    },
    colors: function (_ref3) {
      var state = _ref3.state;
      var colors = this.$root.colors || state.colors;
      return this.options && this.options.colors ? Object.assign({}, colors, this.options.colors) : colors;
    },
    themeComponent: function () {
      return themes.themes[this.theme];
    },
    subthemeComponent: function () {
      return themes.subthemes[this.theme][this.subtheme];
    },
    baseClass: function () {
      var result = {};
      result['x4-ui-radio-buttons'] = true;
      result['x4-theme-' + this.theme] = true;
      result['x4-subtheme-' + this.subtheme] = true;
      return result;
    },
    aitems: function (_ref4) {
      var getters = _ref4.getters;
      return getters['helpers/items/src']({
        src: this.options.items
      });
    },
    menuHash: function (_ref5) {
      var getters = _ref5.getters;
      return getters['helpers/items/menu/hash']({
        items: this.aitems,
        itemValue: this.options.itemValue,
        itemTitle: this.options.itemTitle
      });
    },
    menuOptions: function (_ref6) {
      var getters = _ref6.getters;
      return getters['helpers/items/menu/options']({
        items: this.aitems,
        itemValue: this.options.itemValue
      });
    }
  }),
  methods: {
    change: function (value) {
      this.mvalue = value;
      this.$emit('change', {
        value: value
      });
    }
  }
});
// CONCATENATED MODULE: ./common/components/ui/RadioButtons/RadioButtons.vue?vue&type=script&lang=js&
 /* harmony default export */ var RadioButtons_RadioButtonsvue_type_script_lang_js_ = (RadioButtonsvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/RadioButtons/RadioButtons.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  RadioButtons_RadioButtonsvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "RadioButtons.vue"
/* harmony default export */ var RadioButtons = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 59 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/periodSelect/components/main/PeriodSelect.vue?vue&type=template&id=758c97aa&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('RadioButtons',{staticClass:"x4-period-select",attrs:{"scale":_vm.scale,"value":_vm.value,"options":_vm.options2},on:{"change":_vm.change}},[_c('Style',[_vm._v(".x4-period-select\n  display: flex!important\n  flex-wrap: wrap\n\n  .x4-radio-button\n    flex-basis: 120px\n    margin-bottom: 8px\n")])],1)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/controls/crypto/periodSelect/components/main/PeriodSelect.vue?vue&type=template&id=758c97aa&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/periodSelect/components/main/PeriodSelect.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//

var x4plugin = window['X4CryptoCharts'];
/* harmony default export */ var PeriodSelectvue_type_script_lang_js_ = ({
  props: ['coin', 'options', 'scale'],
  components: map["a" /* default */].components({
    Style: __webpack_require__(2),
    RadioButtons: __webpack_require__(58)
  }),
  computed: map["a" /* default */].variables({
    value: function (_ref) {
      var state = _ref.state;
      return x4plugin.multiValues[state.type].period ? state.values.period[this.coin] || state.defaults.period : state.values.period;
    },
    options1: function (_ref2) {
      var state = _ref2.state;
      return state.controls.periodSelect;
    },
    options2: function (_ref3) {
      var state = _ref3.state;
      return Object.assign({}, this.options1, {
        theme: this.options1.theme || (this.options ? this.options.theme : null) || state.theme,
        subtheme: this.options1.subtheme || (this.options ? this.options.subtheme : null) || state.subtheme,
        colors: Object.assign({}, state.colors, this.options ? this.options.colors : null, this.options1.colors)
      });
    }
  }),
  methods: map["a" /* default */].variables({
    change: function (_ref4, _ref5) {
      var commit = _ref4.commit;
      var value = _ref5.value;
      commit('PERIOD_CHANGE', {
        id: this.coin,
        period: value
      });
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/controls/crypto/periodSelect/components/main/PeriodSelect.vue?vue&type=script&lang=js&
 /* harmony default export */ var main_PeriodSelectvue_type_script_lang_js_ = (PeriodSelectvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/controls/crypto/periodSelect/components/main/PeriodSelect.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  main_PeriodSelectvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "PeriodSelect.vue"
/* harmony default export */ var PeriodSelect = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 60 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsMaterial.vue?vue&type=template&id=26693ea8&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c(_vm.is2,{tag:"div",attrs:{"value":_vm.value,"menuOptions":_vm.menuOptions,"colors":_vm.colors,"scale":_vm.scale},on:{"change":_vm.change},scopedSlots:_vm._u([{key:"label",fn:function(ref){
var option = ref.option;
return [_vm._t("label",null,{option:option})]}},{key:"shape",fn:function(ref){
var option = ref.option;
return _c('div',{staticClass:"x4-shape"},[_c('div',{staticClass:"x4-shape1 x4-transition"}),_c('div',{staticClass:"x4-shape2 x4-transition"})])}}])},[_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-radio-buttons.x4-theme-material\n  cursor: pointer\n  font-family: 'Roboto',sans-serif\n  font-size: $scale(14px)\n  font-weight: 400\n  line-height: 1.5\n\n  .x4-radio-button\n    position: relative\n\n    &.x4-active .x4-shape\n\n      .x4-shape1\n        border-color: $color(accent)\n\n      .x4-shape2\n        background-color: $color(accent)\n\n  .x4-label\n    color: $color(primary)\n    font-size: $scale(16px)\n    overflow: hidden\n\n  .x4-shape\n    border-radius: $scale(10px)\n    float: left\n    height: $scale(20px)\n    position: relative\n    width: $scale(20px)\n\n    .x4-shape1\n      border: $scale(2px) solid $color(primary, .54)\n      border-radius: $scale(10px)\n      bottom: 0\n      left: 0\n      position: absolute\n      right: 0\n      top: 0\n\n    .x4-shape2\n      border-radius: $scale(5px)\n      height: $scale(10px)\n      left: $scale(5px)\n      position: absolute\n      top: $scale(5px)\n      width: $scale(10px)\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsMaterial.vue?vue&type=template&id=26693ea8&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsMaterial.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var RadioButtonsMaterialvue_type_script_lang_js_ = ({
  props: ['is2', 'value', 'menuOptions', 'colors', 'scale'],
  components: map["a" /* default */].components({
    Style: __webpack_require__(2)
  }),
  methods: {
    change: function (option) {
      this.$emit('change', option);
    }
  }
});
// CONCATENATED MODULE: ./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsMaterial.vue?vue&type=script&lang=js&
 /* harmony default export */ var RadioButtonsMaterial_RadioButtonsMaterialvue_type_script_lang_js_ = (RadioButtonsMaterialvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsMaterial.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  RadioButtonsMaterial_RadioButtonsMaterialvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "RadioButtonsMaterial.vue"
/* harmony default export */ var RadioButtonsMaterial = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 61 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/base/loader/components/main/Loader.vue?vue&type=template&id=2903219c&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.loader && _vm.loader.visible && !_vm.dataLoaded)?_c('Loader',{attrs:{"colorize":_vm.loader.colorize,"size":_vm.loader.size}}):_vm._e()}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/controls/base/loader/components/main/Loader.vue?vue&type=template&id=2903219c&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/base/loader/components/main/Loader.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var Loadervue_type_script_lang_js_ = ({
  components: map["a" /* default */].components({
    Loader: __webpack_require__(7)
  }),
  computed: map["a" /* default */].variables({
    loader: function (_ref) {
      var state = _ref.state;
      return state.controls.loader;
    },
    dataLoaded: function (_ref2) {
      var state = _ref2.state;
      return state.bootstrap === undefined || state.bootstrap.isLoaded === undefined || state.bootstrap.isLoaded === true;
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/controls/base/loader/components/main/Loader.vue?vue&type=script&lang=js&
 /* harmony default export */ var main_Loadervue_type_script_lang_js_ = (Loadervue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/controls/base/loader/components/main/Loader.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  main_Loadervue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "Loader.vue"
/* harmony default export */ var Loader = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 62 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/widgets/crypto/lineChart/components/regions/Controls/Controls.vue?vue&type=template&id=5468ede9&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.controlsVisible)?_c('DIV',{class:_vm.baseClass},[_c('FiatSelect'),_c('Style',[_vm._v(".x4-controls\n  display: flex\n\n  > .x4-fiat-select\n    flex-basis: 180px\n\n  &.x4-medium > .x4-fiat-select\n    flex-basis: 100%\n")])],1):_vm._e()}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/regions/Controls/Controls.vue?vue&type=template&id=5468ede9&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/widgets/crypto/lineChart/components/regions/Controls/Controls.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var Controlsvue_type_script_lang_js_ = ({
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2),
    FiatSelect: __webpack_require__(9)
  }),
  data: function () {
    return {
      medium: false
    };
  },
  watch: {
    margins: {
      handler: function () {
        var _this = this;

        setTimeout(function () {
          return _this.resize();
        });
      },
      deep: true
    }
  },
  created: function () {
    window.addEventListener('resize', this.resize);
  },
  mounted: function () {
    this.resize();
  },
  destroyed: function () {
    window.removeEventListener('resize', this.resize);
  },
  computed: map["a" /* default */].variables({
    margins: function (_ref) {
      var state = _ref.state;
      return state.margins;
    },
    baseClass: function (_ref2) {
      var state = _ref2.state;
      return {
        'x4-controls': true,
        'x4-medium': this.medium
      };
    },
    controlsVisible: function (_ref3) {
      var state = _ref3.state;
      return state.controls.fiatSelect.visible;
    }
  }),
  methods: map["a" /* default */].variables({
    resize: function () {
      this.medium = this.$el.offsetWidth < 688;
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/regions/Controls/Controls.vue?vue&type=script&lang=js&
 /* harmony default export */ var Controls_Controlsvue_type_script_lang_js_ = (Controlsvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/regions/Controls/Controls.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  Controls_Controlsvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "Controls.vue"
/* harmony default export */ var Controls = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 63 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/lineChart/components/main/LineChart.vue?vue&type=template&id=c6652e40&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.options2.visible)?_c('DIV',{class:_vm.baseClass},[(!_vm.isLoaded && _vm.options2.loader.visible)?_c('Loader',{attrs:{"colorize":_vm.options2.loader.colorize,"size":200}}):_vm._e(),(_vm.isLoaded)?[_c('WaterMark',{attrs:{"coin1":_vm.coinInst1,"coin2":_vm.coinInst2,"coin3":_vm.coinInst3,"coin4":_vm.coinInst4,"options":_vm.options2}}),_c('div',{staticClass:"x4-inside",style:(_vm.insideStyle)},[_c('Chart',{attrs:{"coin1":_vm.coinInst1,"coin2":_vm.coinInst2,"coin3":_vm.coinInst3,"coin4":_vm.coinInst4,"period":_vm.period,"options":_vm.options2}})],1)]:_vm._e(),_c('Style',{attrs:{"colors":_vm.options2.colors}},[_vm._v(".x4-line-chart\n  display: flex\n  flex-direction: column\n  flex-basis: "+_vm._s(_vm.chartHeight)+"px\n  overflow: hidden\n  position: relative\n\n  > .x4-inside\n    margin-left: -20px\n    position: relative\n    z-index: 1\n\n    > canvas\n      position: relative\n\n  > .x4-ui-loader\n    left: 50%\n    position: absolute\n    top: 50%\n    transform: translateX(-50%) translateY(-50%)\n")]),(_vm.options2.theme === 'material')?_c('Style',{attrs:{"colors":_vm.options2.colors}},[_vm._v(".x4-line-chart\n  color: $color(primary)\n  font-family: 'Roboto',sans-serif\n  font-size: $scale(14px)\n  font-weight: 400\n  line-height: 1.5\n  ")]):_vm._e()],2):_vm._e()}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/LineChart.vue?vue&type=template&id=c6652e40&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/lineChart/components/main/LineChart.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

var x4plugin = window['X4CryptoCharts'];
/* harmony default export */ var LineChartvue_type_script_lang_js_ = ({
  props: ['coin1', 'coin2', 'coin3', 'coin4', 'options'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2),
    Loader: __webpack_require__(7),
    WaterMark: __webpack_require__(67),
    Chart: __webpack_require__(66)
  }),
  created: function () {
    this.ensureHistory();
  },
  watch: {
    coinInst1: function (value) {
      this.ensureHistory();
    },
    coinInst2: function (value) {
      this.ensureHistory();
    },
    coinInst3: function (value) {
      this.ensureHistory();
    },
    coinInst4: function (value) {
      this.ensureHistory();
    },
    period: function (value) {
      this.ensureHistory();
    }
  },
  computed: map["a" /* default */].variables({
    options1: function (_ref) {
      var state = _ref.state;
      return state.controls.lineChart;
    },
    options2: function (_ref2) {
      var state = _ref2.state;
      return Object.assign({}, this.options1, {
        theme: this.options1.theme || (this.options ? this.options.theme : null) || state.theme,
        subtheme: this.options1.subtheme || (this.options ? this.options.subtheme : null) || state.subtheme,
        colors: Object.assign({}, state.colors, this.options ? this.options.colors : null, this.options1.colors)
      });
    },
    chartHeight: function () {
      return this.options2.height;
    },
    baseClass: function () {
      return {
        'x4-line-chart': true
      };
    },
    coinInst1: function (_ref3) {
      var state = _ref3.state;
      return this.coin1 || state.values.coin1;
    },
    coinInst2: function (_ref4) {
      var state = _ref4.state;
      return this.coin2 || state.values.coin2;
    },
    coinInst3: function (_ref5) {
      var state = _ref5.state;
      return this.coin3 || state.values.coin3;
    },
    coinInst4: function (_ref6) {
      var state = _ref6.state;
      return this.coin4 || state.values.coin4;
    },
    period: function (_ref7) {
      var getters = _ref7.getters;
      var coin = this.coinInst1 || this.coinInst2 || this.coinInst3 || this.coinInst4;
      return getters['values/period']({
        id: coin
      });
    },
    insideStyle: function () {
      return {
        height: this.options2.height + 'px'
      };
    },
    isLoaded: function (_ref8) {
      var state = _ref8.state,
          getters = _ref8.getters;
      var result = true;

      for (var index = 1; index <= 4; index++) {
        if (this['coinInst' + index]) {
          var id = this['coinInst' + index];

          if (!state.entities.history[id] || !state.entities.history[id][this.period]) {
            result = false;
          }
        }
      }

      return result;
    }
  }),
  methods: map["a" /* default */].variables({
    ensureHistory: function (_ref9) {
      var dispatch = _ref9.dispatch;

      for (var index = 1; index <= 4; index++) {
        if (this['coinInst' + index]) {
          dispatch('entities/history/ensure', {
            id: this['coinInst' + index],
            period: this.period
          });
        }
      }
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/LineChart.vue?vue&type=script&lang=js&
 /* harmony default export */ var main_LineChartvue_type_script_lang_js_ = (LineChartvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/LineChart.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  main_LineChartvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "LineChart.vue"
/* harmony default export */ var LineChart = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 64 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/main/components/base/AppMain.vue?vue&type=template&id=c01b7c66&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c(_vm.component,{tag:"div",class:_vm.baseClass,attrs:{"id":_vm.baseID}},[_c('template',{slot:"base"},[_c('BuilderOpen'),_c('Loader')],1),_c('Style',{attrs:{"slot":"styles"},slot:"styles"},[_vm._v(".x4-app\n  box-sizing: border-box\n  color: $color(primary)\n  display: flex\n  flex-direction: column\n  position: relative\n  text-align: left\n  text-rendering: optimizeLegibility\n  user-select: none\n  -moz-osx-font-smoothing: grayscale\n  -webkit-font-smoothing: antialiased\n\n  *\n    box-sizing: border-box\n    -webkit-tap-highlight-color: transparent\n\n  a\n    color: $color(primary)\n\n  img\n    margin: 0\n\n  input, textarea\n    color: $color(primary)\n\n  .x4-transition\n    transition: .3s cubic-bezier(.4,0,.2,1)\n\n  .x4-clearfix:after\n    clear: both\n    content: ''\n    display: table\n.x4-app\n  margin-bottom: "+_vm._s(_vm.margins.bottom + 'px')+"\n  margin-left: "+_vm._s(_vm.margins.fixed ? 'auto' : _vm.margins.left + 'px')+"\n  margin-right: "+_vm._s(_vm.margins.fixed ? 'auto' : _vm.margins.right + 'px')+"\n  margin-top: "+_vm._s(_vm.margins.top + 'px')+"\n  max-width: "+_vm._s(_vm.margins.fixed ? (_vm.margins.width + _vm.margins.left + _vm.margins.right) + 'px' : 'none')+"\n  "+_vm._s(_vm.margins.fixed ? 'padding-left: ' + _vm.margins.left + 'px' : '')+"\n  "+_vm._s(_vm.margins.fixed ? 'padding-right: ' + _vm.margins.right + 'px' : '')+"\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/main/components/base/AppMain.vue?vue&type=template&id=c01b7c66&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/main/components/base/AppMain.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var AppMainvue_type_script_lang_js_ = ({
  components: map["a" /* default */].components({
    Style: __webpack_require__(2),
    BuilderOpen: __webpack_require__(68),
    Loader: __webpack_require__(61)
  }),
  mounted: function () {
    var attributes = this.$root.attributes;

    for (var name in attributes) {
      if (name !== 'id' && name !== 'class') {
        this.$el.setAttribute(name, attributes[name]);
      }
    }
  },
  computed: map["a" /* default */].variables({
    margins: function (_ref) {
      var state = _ref.state;
      return state.margins;
    },
    component: function (_ref2) {
      var state = _ref2.state;
      return {
        lineChart: __webpack_require__(56).default
      }[state.type];
    },
    baseClass: function (_ref3) {
      var state = _ref3.state;
      var result = {};
      result['x4-app'] = true;
      result['x4-' + state.type.replace(/([A-Z])/, '-$1').toLowerCase()] = true;

      if (this.$root.attributes.class) {
        result[this.$root.attributes.class] = true;
      }

      return result;
    },
    baseID: function () {
      return this.$root.attributes.id || null;
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/main/components/base/AppMain.vue?vue&type=script&lang=js&
 /* harmony default export */ var base_AppMainvue_type_script_lang_js_ = (AppMainvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/main/components/base/AppMain.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  base_AppMainvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "AppMain.vue"
/* harmony default export */ var AppMain = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 65 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/widgets/crypto/lineChart/components/layouts/FullFeatured/FullFeatured_1/FullFeatured_1.vue?vue&type=template&id=57741500&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('DIV',{attrs:{"level":3}},[_vm._t("base"),_vm._t("controls"),_vm._t("period-select"),_vm._t("line-chart"),_vm._t("styles"),_c('Style',[_vm._v(".x4-app\n\n  > .x4-controls\n    margin-bottom: 16px\n\n  .x4-period-select\n    margin-bottom: 16px\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/layouts/FullFeatured/FullFeatured_1/FullFeatured_1.vue?vue&type=template&id=57741500&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/widgets/crypto/lineChart/components/layouts/FullFeatured/FullFeatured_1/FullFeatured_1.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var FullFeatured_1vue_type_script_lang_js_ = ({
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2)
  })
});
// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/layouts/FullFeatured/FullFeatured_1/FullFeatured_1.vue?vue&type=script&lang=js&
 /* harmony default export */ var FullFeatured_1_FullFeatured_1vue_type_script_lang_js_ = (FullFeatured_1vue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/widgets/crypto/lineChart/components/layouts/FullFeatured/FullFeatured_1/FullFeatured_1.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  FullFeatured_1_FullFeatured_1vue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "FullFeatured_1.vue"
/* harmony default export */ var FullFeatured_1 = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 66 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/lineChart/components/main/Chart/Chart.vue?vue&type=template&id=51c7cafb&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('canvas',{style:(_vm.baseStyle)})}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/Chart/Chart.vue?vue&type=template&id=51c7cafb&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// EXTERNAL MODULE: ./common/bootstrap/wait.js
var wait = __webpack_require__(5);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/lineChart/components/main/Chart/Chart.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//


/* harmony default export */ var Chartvue_type_script_lang_js_ = ({
  props: ['coin1', 'coin2', 'coin3', 'coin4', 'period', 'options'],
  watch: {
    fiat: function () {
      var _this = this;

      setTimeout(function () {
        _this.chartjs.options = _this.config.options;

        _this.chartjs.update();
      });
    },
    datasets: function (value) {
      var _this2 = this;

      setTimeout(function () {
        _this2.chartjs.data.datasets = value;
        _this2.chartjs.options = _this2.config.options;

        _this2.chartjs.update();
      });
    },
    options: {
      handler: function () {
        var _this3 = this;

        setTimeout(function () {
          _this3.chartjs.options = _this3.config.options;

          _this3.chartjs.update();
        });
      },
      deep: true
    }
  },
  mounted: function () {
    var _this4 = this;

    Object(wait["a" /* default */])(function () {
      return !!window.Chart;
    }, function () {
      var Chart = window.Chart;
      _this4.chartjs = new Chart(_this4.$el, _this4.config);
    });
  },
  computed: map["a" /* default */].variables({
    context: function () {
      return this.$el.getContext('2d');
    },
    height: function () {
      return this.options.height;
    },
    fiat: function (_ref) {
      var state = _ref.state;
      return state.entities.fiats[state.values.fiat];
    },
    baseStyle: function () {
      return {
        height: this.height + 'px',
        width: '100%'
      };
    },
    unit: function (_ref2) {
      var state = _ref2.state;
      var unit = 'month';

      if (this.period <= 86400000) {
        // 1 day
        unit = 'hour';
      } else if (this.period > 86400000 && this.period <= 604800000) {
        // 7 days
        unit = 'day';
      } else if (this.period > 604800000 && this.period <= 2592000000) {
        // 30 days
        unit = 'day';
      } else if (this.period > 2592000000 && this.period <= 7776000000) {
        // 90 days
        unit = 'week';
      } else if (this.period > 7776000000 && this.period <= 15552000000) {
        // 180 days
        unit = 'week';
      } else if (this.period > 15552000000) {
        // 365 days
        unit = 'month';
      }

      return unit;
    },
    datasets: function (_ref3) {
      var _this5 = this;

      var state = _ref3.state,
          getters = _ref3.getters;
      var result = [];

      var _loop = function (index) {
        if (!_this5['coin' + index]) {
          return "continue";
        }

        var id = _this5['coin' + index];
        var coin = state.entities.coins[id];
        var fiat = state.entities.fiats[state.values.fiat];
        var history = state.entities.history[id][_this5.period] || [];
        var divider = Math.round(history.length * _this5.options.line.smoothness / 500);

        if (divider === 0) {
          divider = 1;
        }

        _this5.min = 1000000000;
        _this5.max = 0;
        var color = _this5.options.colors['coin' + index];
        result.push({
          xAxisID: 'x',
          yAxisID: 'y',
          fill: _this5.options.line.fill,
          borderWidth: _this5.options.line.thickness,
          borderColor: _this5.options.line.colorize ? color : getters['helpers/colors/rgba/opacity']({
            rgba: _this5.options.colors.primary,
            opacity: .24
          }),
          backgroundColor: _this5.options.line.colorize ? getters['helpers/colors/rgba/opacity']({
            rgba: color,
            opacity: .24
          }) : getters['helpers/colors/rgba/opacity']({
            rgba: _this5.options.colors.primary,
            opacity: .08
          }),
          label: _this5.options.legend.visible ? getters['helpers/items/format/template'](null, {
            template: _this5.options.legend.template,
            patterns: {
              coin: coin.short,
              fiat: fiat.short
            },
            notags: true
          }) : '',
          data: history.filter(function (point, index) {
            return index % divider === 0;
          }).map(function (point) {
            if (point.price > _this5.max) {
              _this5.max = point.price;
            }

            if (point.price < _this5.min) {
              _this5.min = point.price;
            }

            return {
              x: point.time,
              y: point.price
            };
          })
        });
      };

      for (var index = 1; index <= 4; index++) {
        var _ret = _loop(index);

        if (_ret === "continue") continue;
      }

      return result;
    },
    config: function (_ref4) {
      var state = _ref4.state,
          getters = _ref4.getters;
      var self = this;
      var formatters = ['price', 'number', 'template', 'fiat'];
      var formatOptions = Object.assign({
        notags: true
      }, this.options.format);
      var formatExtra = {
        fiat: this.fiat
      };
      var suggestedMax = this.max + (this.max - this.min) / 20;
      var suggestedMin = this.min - (this.max - this.min) / 20;

      if (suggestedMin < 0) {
        suggestedMin = 0;
      }

      var drawing = false;
      var x,
          y = 0;
      return {
        type: 'line',
        data: {
          datasets: this.datasets
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          elements: {
            point: false
          },
          animation: {
            duration: 0
          },
          legend: this.options.legend.visible ? {
            labels: {
              padding: 12,
              fontFamily: '\'Roboto\',sans-serif',
              fontColor: this.options.colors.primary,
              fontSize: 14
            }
          } : false,
          tooltips: this.options.tooltip.visible ? {
            mode: 'index',
            intersect: false,
            position: 'nearest',
            displayColors: false,
            bodyFontFamily: '\'Roboto\',sans-serif',
            footerFontFamily: '\'Roboto\',sans-serif',
            bodyFontSize: 13,
            footerFontSize: 10,
            bodyFontStyle: '500',
            footerFontStyle: '400',
            yPadding: 12,
            xPadding: 24,
            callbacks: {
              title: function (items, data) {
                return '';
              },
              label: function (item, data) {
                var price = data.datasets[item.datasetIndex].data[item.index].y;
                var id = self['coin' + (item.datasetIndex + 1)];
                var coin = state.entities.coins[id];
                formatters.forEach(function (formatter) {
                  price = getters['helpers/items/format/' + formatter](price, formatOptions, {}, formatExtra, price);
                });
                return price + ' (' + coin.short + ')';
              },
              footer: function (items, data) {
                if (!self.options.tooltip.date) {
                  return '';
                }

                var time = data.datasets[items[0].datasetIndex].data[items[0].index].x;
                var date = time.toDateString().split(' ');
                return date[1] + ' ' + date[2] + ', ' + ('0' + time.getHours()).slice(-2) + ':' + ('0' + time.getMinutes()).slice(-2);
              }
            }
          } : false,
          scales: {
            xAxes: [{
              id: 'x',
              type: 'time',
              display: this.options.scales.visible && this.options.scales.horizontal,
              time: {
                unit: this.unit
              },
              gridLines: {
                drawBorder: false,
                tickMarkLength: 8,
                borderDash: [4, 2],
                color: getters['helpers/colors/rgba/opacity']({
                  rgba: this.options.colors.primary,
                  opacity: .12
                })
              },
              ticks: {
                padding: 4,
                maxRotation: 0,
                fontFamily: '\'Roboto\',sans-serif',
                fontColor: this.options.colors.primary,
                fontSize: 12
              }
            }],
            yAxes: [{
              id: 'y',
              type: 'linear',
              position: 'right',
              display: this.options.scales.visible && this.options.scales.vertical,
              gridLines: {
                drawBorder: false,
                drawTicks: false,
                borderDash: [4, 2],
                color: getters['helpers/colors/rgba/opacity']({
                  rgba: this.options.colors.primary,
                  opacity: .12
                })
              },
              ticks: {
                padding: 8,
                suggestedMax: suggestedMax,
                suggestedMin: suggestedMin,
                fontFamily: '\'Roboto\',sans-serif',
                fontColor: this.options.colors.primary,
                fontSize: 12,
                callback: function (price) {
                  if (!price || price < suggestedMin) {
                    return '';
                  }

                  formatters.forEach(function (formatter) {
                    price = getters['helpers/items/format/' + formatter](price, formatOptions, {}, formatExtra, price);
                  });
                  return price;
                }
              }
            }]
          }
        },
        plugins: [{
          afterEvent: function (chart, event) {
            x = Math.round(event.x);
            y = Math.round(event.y);
            setTimeout(function () {
              if (!drawing) {
                chart.render({
                  duration: 0
                });
              }
            }, 1);
          },
          afterDatasetsDraw: function (chart) {
            if (!self.options.crosshair.visible) {
              return;
            }

            if (x < chart.chartArea.left || x > chart.chartArea.right) {
              return;
            }

            if (y < chart.chartArea.top || y > chart.chartArea.bottom) {
              return;
            }

            var canvas = self.$el;
            var context = self.context;
            context.beginPath();
            context.lineWidth = 1;

            if (self.options.crosshair.dotted) {
              context.setLineDash([2, 2]);
            } else {
              context.setLineDash([]);
            }

            if (self.options.crosshair.vertical) {
              context.moveTo(x + .5, .5);
              context.lineTo(x + .5, canvas.scrollHeight + .5);
            }

            if (self.options.crosshair.horizontal) {
              context.moveTo(.5, y + .5);
              context.lineTo(canvas.scrollWidth + .5, y + .5);
            }

            context.strokeStyle = self.options.colors.crosshair;
            context.stroke();
            context.closePath();
          },
          beforeRender: function (chart) {
            drawing = true;
          },
          afterRender: function (chart) {
            drawing = false;
          }
        }]
      };
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/Chart/Chart.vue?vue&type=script&lang=js&
 /* harmony default export */ var Chart_Chartvue_type_script_lang_js_ = (Chartvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/Chart/Chart.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  Chart_Chartvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "Chart.vue"
/* harmony default export */ var Chart = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 67 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/lineChart/components/main/WaterMark/WaterMark.vue?vue&type=template&id=ae1c5c6e&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.options1.visible)?_c('DIV',{class:_vm.baseClass},[_c('div',{staticClass:"x4-value",domProps:{"innerHTML":_vm._s(_vm.value)}}),_c('Style',{attrs:{"colors":_vm.options.colors}},[_vm._v(".x4-watermark\n  color: $color(primary, .04)\n  font-size: "+_vm._s(_vm.fontSize)+"px\n  font-weight: 500\n  left: 50%\n  margin-top: -10px\n  position: absolute\n  top: 50%\n  transform: translateX(-50%) translateY(-50%)\n\n  > .x4-value\n    white-space: nowrap\n")])],1):_vm._e()}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/WaterMark/WaterMark.vue?vue&type=template&id=ae1c5c6e&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/controls/crypto/lineChart/components/main/WaterMark/WaterMark.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var WaterMarkvue_type_script_lang_js_ = ({
  props: ['coin1', 'coin2', 'coin3', 'coin4', 'options'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2)
  }),
  data: function () {
    return {
      fontSize: 0
    };
  },
  created: function () {
    window.addEventListener('resize', this.resize);
  },
  mounted: function () {
    this.resize();
  },
  destroyed: function () {
    window.removeEventListener('resize', this.resize);
  },
  computed: map["a" /* default */].variables({
    options1: function (_ref) {
      var state = _ref.state;
      return state.controls.lineChart.watermark;
    },
    fiat: function (_ref2) {
      var state = _ref2.state;
      return state.entities.fiats[state.values.fiat];
    },
    baseClass: function () {
      return {
        'x4-watermark': true
      };
    },
    value: function (_ref3) {
      var state = _ref3.state,
          getters = _ref3.getters;
      var coins = state.entities.coins;
      var patterns = {
        fiat: this.fiat.short
      };

      for (var index = 1; index <= 4; index++) {
        patterns['coin' + index] = this['coin' + index] ? state.entities.coins[this['coin' + index]].short : '';
      }

      return getters['helpers/items/format/template'](null, {
        template: this.options1.template,
        patterns: patterns
      });
    }
  }),
  methods: map["a" /* default */].variables({
    resize: function () {
      var width = this.$el.parentNode.offsetWidth;
      this.fontSize = width > 576 ? width > 768 ? width > 992 ? 160 : 128 : 96 : 0;
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/WaterMark/WaterMark.vue?vue&type=script&lang=js&
 /* harmony default export */ var WaterMark_WaterMarkvue_type_script_lang_js_ = (WaterMarkvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/controls/crypto/lineChart/components/main/WaterMark/WaterMark.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  WaterMark_WaterMarkvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "WaterMark.vue"
/* harmony default export */ var WaterMark = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 68 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/main/components/base/BuilderOpen/BuilderOpen.vue?vue&type=template&id=97dccb12&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.visible)?_c('DIV',{class:_vm.baseClass,attrs:{"title":"Visual Customizer"}},[_c('Icon',{staticClass:"x4-icon",attrs:{"icon":"settings"},nativeOn:{"click":function($event){return _vm.open($event)}}}),_c('Style',[_vm._v(".x4-builder-open\n  display: flex\n  flex-direction: column\n  font-size: 0\n  line-height: 0\n  overflow: hidden\n  position: absolute\n  right: "+_vm._s(_vm.marginRight)+"px\n  top: -48px\n  z-index: 1000000\n\n  .x4-icon\n    animation: x4-builder-open-rotation 2s cubic-bezier(.4,0,.2,1) infinite\n    cursor: pointer\n    font-size: 32px\n    user-select: none\n\n@keyframes x4-builder-open-rotation {\n  0%\n    color: $color(primary, .24)\n    transform: rotate(0deg)\n  50%\n    color: $color(accent)\n    transform: rotate(180deg)\n  100%\n    color: $color(primary, .24)\n    transform: rotate(360deg)\n}\n")])],1):_vm._e()}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/src/assets/main/components/base/BuilderOpen/BuilderOpen.vue?vue&type=template&id=97dccb12&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/src/assets/main/components/base/BuilderOpen/BuilderOpen.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var BuilderOpenvue_type_script_lang_js_ = ({
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2),
    Icon: __webpack_require__(8)
  }),
  computed: map["a" /* default */].variables({
    visible: function (_ref) {
      var state = _ref.state;
      return state.builder.enabled && !state.builder.opened;
    },
    baseClass: function () {
      return {
        'x4-builder-open': true,
        'x4-transition': true
      };
    },
    marginRight: function (_ref2) {
      var state = _ref2.state;
      return state.margins.right;
    }
  }),
  methods: map["a" /* default */].variables({
    open: function (_ref3) {
      var dispatch = _ref3.dispatch;
      dispatch('builder/open');
    }
  })
});
// CONCATENATED MODULE: ./common/src/assets/main/components/base/BuilderOpen/BuilderOpen.vue?vue&type=script&lang=js&
 /* harmony default export */ var BuilderOpen_BuilderOpenvue_type_script_lang_js_ = (BuilderOpenvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/src/assets/main/components/base/BuilderOpen/BuilderOpen.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  BuilderOpen_BuilderOpenvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "BuilderOpen.vue"
/* harmony default export */ var BuilderOpen = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 69 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/DropDown/DropDown.vue?vue&type=template&id=042366dc&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('transition',{attrs:{"name":"x4"},on:{"before-enter":_vm.beforeEnter,"enter":_vm.enter,"after-enter":_vm.afterEnter,"before-leave":_vm.beforeLeave,"leave":_vm.leave,"after-leave":_vm.afterLeave}},[(_vm.aopened)?_c('DIV',{class:_vm.baseClass},[_c('div',{staticClass:"x4-inside"},[_vm._t("default")],2)]):_vm._e()],1)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/DropDown/DropDown.vue?vue&type=template&id=042366dc&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/DropDown/DropDown.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var DropDownvue_type_script_lang_js_ = ({
  props: ['fixed', 'opened'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3)
  }),
  computed: map["a" /* default */].variables({
    aopened: function () {
      return this.opened !== undefined ? this.opened : false;
    },
    baseClass: function () {
      return {
        'x4-ui-dropdown': true,
        'x4-transition': true
      };
    }
  }),
  methods: map["a" /* default */].variables({
    beforeEnter: function (context, el) {
      if (this.fixed) return;
      el.style['max-height'] = 0;
      el.style['overflow'] = 'hidden';
    },
    enter: function (context, el, done) {
      if (this.fixed) return;

      if (this.$listeners.enter) {
        this.$emit('enter', {
          scrollHeight: el.scrollHeight
        });
      }

      el.style['max-height'] = el.scrollHeight + 'px';
    },
    afterEnter: function (context, el) {
      if (this.fixed) return;
      el.style.removeProperty('max-height');
      el.style.removeProperty('overflow');
    },
    beforeLeave: function (context, el) {
      if (this.fixed) return;
      el.style['max-height'] = el.scrollHeight + 'px';
      el.style['overflow'] = 'hidden';
    },
    leave: function (context, el) {
      var _this = this;

      if (this.fixed) return;
      setTimeout(function () {
        if (_this.$listeners.leave) {
          _this.$emit('leave');
        }

        el.style['max-height'] = 0;
      }, 50);
    },
    afterLeave: function (context, el) {
      if (this.fixed) return;
      el.style.removeProperty('max-height');
      el.style.removeProperty('overflow');
    }
  })
});
// CONCATENATED MODULE: ./common/components/ui/DropDown/DropDown.vue?vue&type=script&lang=js&
 /* harmony default export */ var DropDown_DropDownvue_type_script_lang_js_ = (DropDownvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/DropDown/DropDown.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  DropDown_DropDownvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "DropDown.vue"
/* harmony default export */ var DropDown = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 70 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsDefault/RadioButtonsDefault.vue?vue&type=template&id=9670d040&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('DIV',{attrs:{"level":3}},[_vm._l((_vm.menuOptions),function(option){return _c('div',{staticClass:"x4-radio-button x4-clearfix",class:{ 'x4-active': option === _vm.value },on:{"click":function($event){_vm.change(option)}}},[_vm._t("shape",null,{option:option}),_vm._t("label",null,{option:option})],2)}),_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-radio-buttons.x4-subtheme-default\n\n  .x4-shape\n    margin-top: $scale(2px)\n    margin-left: $scale(14px)\n\n  .x4-label\n    padding-left: $scale(14px)\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsDefault/RadioButtonsDefault.vue?vue&type=template&id=9670d040&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsDefault/RadioButtonsDefault.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var RadioButtonsDefaultvue_type_script_lang_js_ = ({
  props: ['value', 'menuOptions', 'colors', 'scale'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2)
  }),
  methods: {
    change: function (option) {
      this.$emit('change', option);
    }
  }
});
// CONCATENATED MODULE: ./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsDefault/RadioButtonsDefault.vue?vue&type=script&lang=js&
 /* harmony default export */ var RadioButtonsDefault_RadioButtonsDefaultvue_type_script_lang_js_ = (RadioButtonsDefaultvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/RadioButtons/themes/RadioButtonsMaterial/RadioButtonsDefault/RadioButtonsDefault.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  RadioButtonsDefault_RadioButtonsDefaultvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "RadioButtonsDefault.vue"
/* harmony default export */ var RadioButtonsDefault = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 71 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputFilled/InputFilled.vue?vue&type=template&id=5264e506&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('DIV',{attrs:{"level":3}},[_vm._t("lines"),_vm._t("icon"),_vm._t("ddicon"),_vm._t("label"),_vm._t("input"),_vm._t("menu"),_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-input.x4-subtheme-filled\n  background-color: $color(primary, .04)\n  border-radius: $scale(4px) $scale(4px) 0 0\n\n  &:hover\n    background-color: $color(primary, .06)\n\n  &.x4-focused\n    background-color: $color(primary, .08)\n\n  .x4-icon\n    margin: $scale(16px) 0 0 $scale(15px)\n\n  .x4-dd-icon\n    margin: $scale(16px) $scale(4px) 0 0\n\n  .x4-label\n    left: $scale(48px)\n    right: $scale(16px)\n    top: $scale(17px)\n\n  &.x4-no-icon .x4-label\n    left: $scale(16px)\n\n  &.x4-focused .x4-label, &.x4-dirty .x4-label\n    right: $scale(8px)\n    top: $scale(7px)\n\n  .x4-input-wrapper\n    padding: $scale(26px) $scale(16px) 0 $scale(9px)\n\n  &.x4-no-icon .x4-input-wrapper\n    padding-left: $scale(16px)\n\n  &.x4-no-label .x4-input-wrapper\n    padding-top: $scale(17px)\n\n  &:hover .x4-back\n    background-color: $color(primary, .08)\n\n  &.x4-focused .x4-back\n    background-color: $color(primary, .04)\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputFilled/InputFilled.vue?vue&type=template&id=5264e506&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputFilled/InputFilled.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var InputFilledvue_type_script_lang_js_ = ({
  props: ['options', 'colors', 'scale'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2)
  })
});
// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputFilled/InputFilled.vue?vue&type=script&lang=js&
 /* harmony default export */ var InputFilled_InputFilledvue_type_script_lang_js_ = (InputFilledvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputFilled/InputFilled.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  InputFilled_InputFilledvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "InputFilled.vue"
/* harmony default export */ var InputFilled = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 72 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputMaterial.vue?vue&type=template&id=79520554&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c(_vm.is2,{tag:"div",attrs:{"options":_vm.options,"colors":_vm.colors,"scale":_vm.scale},on:{"focus":function($event){_vm.$emit('focus')}}},[_vm._t("icon",null,{slot:"icon"}),_vm._t("ddicon",null,{slot:"ddicon"}),_vm._t("label",null,{slot:"label"}),_vm._t("input",null,{slot:"input"}),_vm._t("menu",null,{slot:"menu"}),_c('div',{staticClass:"x4-lines",attrs:{"slot":"lines"},slot:"lines"},[_c('div',{staticClass:"x4-line1 x4-transition"}),_c('div',{staticClass:"x4-line2 x4-transition"})]),_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-input.x4-theme-material\n  cursor: text\n  font-family: 'Roboto',sans-serif\n  font-size: $scale(14px)\n  font-weight: 400\n  height: $scale(56px)\n  line-height: 1.5\n\n  &.x4-type-textarea\n    height: $scale(104px)\n\n  &.x4-type-select\n    cursor: pointer\n\n  .x4-icon\n    float: left\n    color: $color(primary, .54)\n    font-size: $scale(24px)\n\n    &.x4-enter, &.x4-leave-to\n      opacity: 0\n\n  .x4-dd-icon\n    color: $color(primary, .54)\n    float: right\n    font-size: $scale(24px)\n\n  .x4-label\n    color: $color(primary, .54)\n    font-size: $scale(16px)\n    position: absolute\n\n  &.x4-focused .x4-label, &.x4-dirty .x4-label\n    font-size: $scale(12px)\n\n  &.x4-focused .x4-label\n    color: $color(accent)\n\n  .x4-input-wrapper\n    height: 100%\n    overflow: hidden\n    position: relative\n\n  &.x4-type-textarea .x4-input-wrapper\n    padding-bottom: $scale(6px)\n\n  &.x4-type-select .x4-input-wrapper\n    padding-right: 0\n\n  .x4-input, .x4-textarea\n    background-color: transparent\n    border: none\n    box-shadow: none\n    font-family: 'Roboto',sans-serif\n    outline: none\n    padding: 0\n    text-shadow: none\n    width: 100%\n\n  .x4-textarea\n    height: 100%\n    resize: none\n\n  .x4-input, .x4-textarea, .x4-select\n    color: $color(primary)\n    font-size: $scale(16px)\n    font-weight: 400\n    line-height: $scale(1.5)\n\n  .x4-lines\n    bottom: 0\n    left: 0\n    position: absolute\n    right: 0\n    z-index: 1\n\n    .x4-line1\n      background-color: $color(accent)\n      height: 2px\n      position: relative\n      transform: scaleX(0)\n      transform-origin: center center 0\n      z-index: 1\n\n    .x4-line2\n      background-color: $color(primary, .42)\n      height: 1px\n      margin-top: -1px\n\n  &:hover .x4-lines .x4-line2\n    background-color: $color(primary, .87)\n\n  &.x4-focused .x4-lines .x4-line1\n    transform: scaleX(1)\n\n  .x4-backdrop\n    bottom: 0\n    left: 0\n    position: fixed\n    right: 0\n    top: 0\n    z-index: 1000\n\n  .x4-menu\n    background-color: $color(inverted)\n    box-shadow: 0 5px 5px -3px $color(primary, .2), 0 8px 10px 1px $color(primary, .14), 0 3px 14px 2px $color(primary, .12)\n    left: 0\n    max-height: $scale(304px)\n    position: absolute\n    right: 0\n    top: 100%\n    z-index: 1001\n\n    &.x4-enter, &.x4-leave-to\n      max-height: 0\n\n    > .x4-inside\n      padding: $scale(8px) 0\n\n    .x4-option\n      color: $color(primary)\n      height: $scale(48px)\n      font-size: $scale(14px)\n      padding: $scale(14px) $scale(16px) 0\n\n      &:hover\n        background-color: $color(primary, .04)\n\n      &.x4-active\n        background-color: $color(primary, .06)\n\n  .x4-scrollable\n    overflow-y: auto\n\n    &::-webkit-scrollbar\n      height: 4px\n      width: 4px\n\n    &::-webkit-scrollbar-button\n      display: none\n      height: 0\n      width: 0\n\n    &::-webkit-scrollbar-corner\n      background-color: transparent\n\n    &::-webkit-scrollbar-thumb\n      background-clip: padding-box\n      background-color: $color(primary, .16)\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputMaterial.vue?vue&type=template&id=79520554&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputMaterial.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var InputMaterialvue_type_script_lang_js_ = ({
  props: ['is2', 'options', 'colors', 'scale'],
  components: map["a" /* default */].components({
    Style: __webpack_require__(2)
  })
});
// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputMaterial.vue?vue&type=script&lang=js&
 /* harmony default export */ var InputMaterial_InputMaterialvue_type_script_lang_js_ = (InputMaterialvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputMaterial.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  InputMaterial_InputMaterialvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "InputMaterial.vue"
/* harmony default export */ var InputMaterial = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 73 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputStandard/InputStandard.vue?vue&type=template&id=c9aae934&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('DIV',{attrs:{"level":3}},[_vm._t("lines"),_vm._t("icon"),_vm._t("ddicon"),_vm._t("label"),_vm._t("input"),_vm._t("menu"),_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-input.x4-subtheme-standard\n\n  .x4-icon\n    margin: $scale(16px) 0 0\n\n  .x4-dd-icon\n    margin: $scale(16px) $scale(4px) 0 0\n\n  .x4-label\n    left: $scale(33px)\n    right: 0\n    top: $scale(17px)\n\n  &.x4-no-icon .x4-label\n    left: 0\n\n  &.x4-focused .x4-label, &.x4-dirty .x4-label\n    top: $scale(7px)\n\n  .x4-input-wrapper\n    padding: $scale(26px) $scale(16px) 0 $scale(9px)\n\n  &.x4-no-icon .x4-input-wrapper\n    padding-left: 0\n\n  &.x4-no-label .x4-input-wrapper\n    padding-top: $scale(17px)\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputStandard/InputStandard.vue?vue&type=template&id=c9aae934&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputStandard/InputStandard.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var InputStandardvue_type_script_lang_js_ = ({
  props: ['options', 'colors', 'scale'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2)
  })
});
// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputStandard/InputStandard.vue?vue&type=script&lang=js&
 /* harmony default export */ var InputStandard_InputStandardvue_type_script_lang_js_ = (InputStandardvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputStandard/InputStandard.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  InputStandard_InputStandardvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "InputStandard.vue"
/* harmony default export */ var InputStandard = __webpack_exports__["default"] = (component.exports);

/***/ }),
/* 74 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/pug-plain-loader??ref--3-0!./node_modules/string-replace-loader??ref--3-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputOutlined/InputOutlined.vue?vue&type=template&id=0cecf008&lang=pug&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('DIV',{attrs:{"level":3}},[_c('div',{staticClass:"x4-borders"},[_c('div',{staticClass:"x4-border1 x4-transition"}),_c('div',{staticClass:"x4-border2 x4-transition"},[_c('div',{staticClass:"x4-shape1"}),_c('div',{staticClass:"x4-shape2"},[(!!_vm.options.label)?_c('div',{staticClass:"x4-plabel"},[_vm._v(_vm._s(_vm.options.label))]):_vm._e()]),_c('div',{staticClass:"x4-shape3"})])]),_vm._t("icon"),_vm._t("ddicon"),_vm._t("label"),_vm._t("input"),_vm._t("menu"),_vm._t("default"),_c('Style',{attrs:{"colors":_vm.colors}},[_vm._v(".x4-ui-input.x4-subtheme-outlined\n\n  .x4-icon\n    margin: $scale(16px) 0 0 $scale(15px)\n\n  .x4-dd-icon\n    margin: $scale(16px) $scale(4px) 0 0\n\n  .x4-label\n    left: $scale(48px)\n    top: $scale(17px)\n\n  &.x4-no-icon .x4-label\n    left: $scale(16px)\n\n  &.x4-focused .x4-label, &.x4-dirty .x4-label\n    left: $scale(16px)\n    top: $scale(-9px)\n\n  .x4-input-wrapper\n    padding: $scale(17px) $scale(16px) 0 $scale(9px)\n\n  &.x4-no-icon .x4-input-wrapper\n    padding-left: $scale(16px)\n\n  .x4-borders\n    bottom: 0\n    left: 0\n    position: absolute\n    right: 0\n    top: 0\n\n    .x4-border1\n      border: 1px solid $color(primary, .24)\n      border-radius: $scale(4px)\n      bottom: 0\n      left: 0\n      opacity: 1\n      position: absolute\n      right: 0\n      top: 0\n\n    .x4-border2\n      bottom: 0\n      left: 0\n      opacity: 0\n      position: absolute\n      right: 0\n      top: 0\n\n      .x4-shape1\n        border: 1px solid $color(primary, .24)\n        border-right: none\n        border-radius: $scale(4px) 0 0 $scale(4px)\n        float: left\n        height: 100%\n        width: 10px\n\n      .x4-shape2\n        border-bottom: 1px solid $color(primary, .24)\n        float: left\n        height: 100%\n\n        .x4-plabel\n          font-size: $scale(12px)\n          margin: 0 $scale(6px)\n          overflow-x: hidden\n          text-overflow: ellipsis\n          visibility: hidden\n          white-space: nowrap\n\n      .x4-shape3\n        border: 1px solid $color(primary, .24)\n        border-left: none\n        border-radius: 0 $scale(4px) $scale(4px) 0\n        height: 100%\n        min-width: $scale(4px)\n        overflow: hidden\n\n  &:hover .x4-borders .x4-border1\n    border-color: $color(primary, .74)\n\n  &:hover .x4-borders .x4-border2\n    .x4-shape1, .x4-shape2, .x4-shape3\n      border-color: $color(primary, .74)\n\n  &.x4-focused .x4-borders .x4-border1, &.x4-dirty .x4-borders .x4-border1\n    opacity: 0\n\n  &.x4-focused .x4-borders .x4-border2, &.x4-dirty .x4-borders .x4-border2\n    opacity: 1\n\n  &.x4-focused .x4-borders .x4-border2\n    .x4-shape1, .x4-shape2, .x4-shape3\n      border-color: $color(accent)\n      border-width: 2px\n")])],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputOutlined/InputOutlined.vue?vue&type=template&id=0cecf008&lang=pug&

// EXTERNAL MODULE: ./common/bootstrap/map.js
var map = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/babel-loader/lib??ref--0-0!./node_modules/string-replace-loader??ref--0-1!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/string-replace-loader??ref--2-1!./common/components/ui/Input/themes/InputMaterial/InputOutlined/InputOutlined.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var InputOutlinedvue_type_script_lang_js_ = ({
  props: ['options', 'colors', 'scale'],
  components: map["a" /* default */].components({
    DIV: __webpack_require__(3),
    Style: __webpack_require__(2)
  })
});
// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputOutlined/InputOutlined.vue?vue&type=script&lang=js&
 /* harmony default export */ var InputOutlined_InputOutlinedvue_type_script_lang_js_ = (InputOutlinedvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(1);

// CONCATENATED MODULE: ./common/components/ui/Input/themes/InputMaterial/InputOutlined/InputOutlined.vue





/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  InputOutlined_InputOutlinedvue_type_script_lang_js_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

component.options.__file = "InputOutlined.vue"
/* harmony default export */ var InputOutlined = __webpack_exports__["default"] = (component.exports);

/***/ })
/******/ ]);