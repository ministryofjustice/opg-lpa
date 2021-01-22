this["lpa"] = this["lpa"] || {};
this["lpa"]["templates"] = this["lpa"]["templates"] || {};

this["lpa"]["templates"]["alert.withinForm"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-group "
    + alias4(((helper = (helper = lookupProperty(helpers,"elementJSref") || (depth0 != null ? lookupProperty(depth0,"elementJSref") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"elementJSref","hash":{},"data":data,"loc":{"start":{"line":1,"column":23},"end":{"line":1,"column":39}}}) : helper)))
    + "\">\n	<div class=\"alert panel text\" role=\"alert\" tabindex=\"-1\">\n		<i class=\"icon icon-"
    + alias4(((helper = (helper = lookupProperty(helpers,"alertType") || (depth0 != null ? lookupProperty(depth0,"alertType") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"alertType","hash":{},"data":data,"loc":{"start":{"line":3,"column":22},"end":{"line":3,"column":35}}}) : helper)))
    + "\" role=\"presentation\"></i>\n		<div class=\"alert-message\">\n			"
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"alertMessage") || (depth0 != null ? lookupProperty(depth0,"alertMessage") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"alertMessage","hash":{},"data":data,"loc":{"start":{"line":5,"column":3},"end":{"line":5,"column":21}}}) : helper))) != null ? stack1 : "")
    + "\n		</div>\n	</div>\n</div>";
},"useData":true});

this["lpa"]["templates"]["dialog.confirmRepeatApplication"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"dialog-container\" role=\"dialog\" aria-labelledby=\"dialog-title\" aria-describedby=\"dialog-message\">\n\n    <h2 id=\"dialog-title\" class=\"dialog-title-block\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"dialogTitle") || (depth0 != null ? lookupProperty(depth0,"dialogTitle") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"dialogTitle","hash":{},"data":data,"loc":{"start":{"line":3,"column":53},"end":{"line":3,"column":68}}}) : helper)))
    + "</h2>\n\n    <div id=\"dialog-message\" class=\"dialog-message-block\"><p>"
    + alias4(((helper = (helper = lookupProperty(helpers,"dialogMessage") || (depth0 != null ? lookupProperty(depth0,"dialogMessage") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"dialogMessage","hash":{},"data":data,"loc":{"start":{"line":5,"column":61},"end":{"line":5,"column":78}}}) : helper)))
    + "</p></div>\n\n    <div class=\"dialog__button-bar\">\n        <a href=\"#\" class=\"button dialog__button--accept "
    + alias4(((helper = (helper = lookupProperty(helpers,"acceptClass") || (depth0 != null ? lookupProperty(depth0,"acceptClass") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"acceptClass","hash":{},"data":data,"loc":{"start":{"line":8,"column":57},"end":{"line":8,"column":72}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"acceptButtonText") || (depth0 != null ? lookupProperty(depth0,"acceptButtonText") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"acceptButtonText","hash":{},"data":data,"loc":{"start":{"line":8,"column":74},"end":{"line":8,"column":94}}}) : helper)))
    + "</a>\n        <a href=\"#\" class=\"button-secondary dialog__button--cancel  "
    + alias4(((helper = (helper = lookupProperty(helpers,"cancelClass") || (depth0 != null ? lookupProperty(depth0,"cancelClass") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cancelClass","hash":{},"data":data,"loc":{"start":{"line":9,"column":68},"end":{"line":9,"column":83}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"cancelButtonText") || (depth0 != null ? lookupProperty(depth0,"cancelButtonText") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cancelButtonText","hash":{},"data":data,"loc":{"start":{"line":9,"column":85},"end":{"line":9,"column":105}}}) : helper)))
    + "</a>\n    </div>\n\n</div>\n";
},"useData":true});

this["lpa"]["templates"]["errors.formElement"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-element-errors\">\n    <div class=\"group validation\">\n        <span class=\"validation-message\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"validationMessage") || (depth0 != null ? lookupProperty(depth0,"validationMessage") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"validationMessage","hash":{},"data":data,"loc":{"start":{"line":3,"column":41},"end":{"line":3,"column":62}}}) : helper)))
    + "</span>\n    </div>\n</div>";
},"useData":true});

this["lpa"]["templates"]["errors.formMessage"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<span class=\"error-message text\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"errorMessage") || (depth0 != null ? lookupProperty(depth0,"errorMessage") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"errorMessage","hash":{},"data":data,"loc":{"start":{"line":1,"column":33},"end":{"line":1,"column":49}}}) : helper)))
    + "</span>";
},"useData":true});

this["lpa"]["templates"]["errors.formSummary"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div class=\"validation-summary group\" role=\"alert\" aria-labelledby=\"error-heading\" tabindex=\"-1\">\n    <h1 id=\"error-heading\">There was a problem submitting the form</h1>\n\n    <p>Because of the following problems:</p>\n    <ol>\n    </ol>\n</div>\n";
},"useData":true});

this["lpa"]["templates"]["input.checkbox"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<fieldset class=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"elementJSref") || (depth0 != null ? lookupProperty(depth0,"elementJSref") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"elementJSref","hash":{},"data":data,"loc":{"start":{"line":1,"column":17},"end":{"line":1,"column":33}}}) : helper)))
    + "\">\n    <div class=\"input-checkbox group\">\n        <label for=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"elementName") || (depth0 != null ? lookupProperty(depth0,"elementName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"elementName","hash":{},"data":data,"loc":{"start":{"line":3,"column":20},"end":{"line":3,"column":35}}}) : helper)))
    + "\">\n            <input type=\"checkbox\" name=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"elementName") || (depth0 != null ? lookupProperty(depth0,"elementName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"elementName","hash":{},"data":data,"loc":{"start":{"line":4,"column":41},"end":{"line":4,"column":56}}}) : helper)))
    + "\" id=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"elementName") || (depth0 != null ? lookupProperty(depth0,"elementName") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"elementName","hash":{},"data":data,"loc":{"start":{"line":4,"column":62},"end":{"line":4,"column":77}}}) : helper)))
    + "\" class=\"confirmation-validation\" value=\"1\"\n                   required=\"required\">\n            "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"elementLabel") || (depth0 != null ? lookupProperty(depth0,"elementLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"elementLabel","hash":{},"data":data,"loc":{"start":{"line":6,"column":12},"end":{"line":6,"column":30}}}) : helper))) != null ? stack1 : "")
    + "\n        </label>\n    </div>\n</fieldset>\n";
},"useData":true});

this["lpa"]["templates"]["popup.close"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<p class=\"close\">\n  <a href=\"#\" class=\"js-popup-close button-close\" title=\"Click or press escape to close this window\">Close layer</a>\n</p>";
},"useData":true});

this["lpa"]["templates"]["popup.container"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div id=\"popup\" role=\"dialog\" class=\"popup\"></div>";
},"useData":true});

this["lpa"]["templates"]["popup.content"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div id=\"popup-content\"></div>";
},"useData":true});

this["lpa"]["templates"]["popup.mask"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div id=\"mask\" class=\"popover-mask\"></div>";
},"useData":true});

this["lpa"]["templates"]["postcodeLookup.address-change"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div class=\"form-group\">\n	<ul class=\"address-type-toggle\">\n		<li><a href=\"#\" class=\"js-PostcodeLookup__change\" title=\"Search for UK Postcode\">Search for UK postcode</a></li>\n	</ul>\n</div>\n";
},"useData":true});

this["lpa"]["templates"]["postcodeLookup.address-toggle"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div class=\"form-group\">\n	<ul class=\"address-type-toggle\">\n		<li><a href=\"#\" class=\"js-PostcodeLookup__toggle-address\">Enter address manually</a></li>\n	</ul>\n</div>\n";
},"useData":true});

this["lpa"]["templates"]["postcodeLookup.search-field"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div class=\"form-group js-PostcodeLookup__search\">\n  <label class=\"form-label\" for=\"postcode-lookup\">Postcode lookup</label>\n  <input autocomplete=\"off\" type=\"text\" id=\"postcode-lookup\" class=\"postcode-input form-control js-PostcodeLookup__query\">\n  <a href=\"#\" id=\"find_uk_address\" class=\"postcode-button button js-PostcodeLookup__search-btn\" role=\"button\">Find UK address</a>\n</div>\n";
},"useData":true});

this["lpa"]["templates"]["postcodeLookup.search-result"] = Handlebars.template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "      <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":6,"column":21},"end":{"line":6,"column":27}}}) : helper)))
    + "\" data-line1=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"line1") || (depth0 != null ? lookupProperty(depth0,"line1") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"line1","hash":{},"data":data,"loc":{"start":{"line":6,"column":41},"end":{"line":6,"column":50}}}) : helper)))
    + "\" data-line2=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"line2") || (depth0 != null ? lookupProperty(depth0,"line2") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"line2","hash":{},"data":data,"loc":{"start":{"line":6,"column":64},"end":{"line":6,"column":73}}}) : helper)))
    + "\" data-line3=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"line3") || (depth0 != null ? lookupProperty(depth0,"line3") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"line3","hash":{},"data":data,"loc":{"start":{"line":6,"column":87},"end":{"line":6,"column":96}}}) : helper)))
    + "\" data-postcode=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"postcode") || (depth0 != null ? lookupProperty(depth0,"postcode") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"postcode","hash":{},"data":data,"loc":{"start":{"line":6,"column":113},"end":{"line":6,"column":125}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"description","hash":{},"data":data,"loc":{"start":{"line":6,"column":127},"end":{"line":6,"column":142}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-group\">\n  <label class=\"form-label\" for=\"address-search-result\">Address</label>\n  <select class=\"form-control js-PostcodeLookup__search-results\" id=\"address-search-result\">\n    <option value=\"\">Please select an address...</option>\n"
    + ((stack1 = lookupProperty(helpers,"each").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"results") : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":4},"end":{"line":7,"column":13}}})) != null ? stack1 : "")
    + "  </select>\n</div>\n";
},"useData":true});

this["lpa"]["templates"]["shared.loading-popup"] = Handlebars.template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div class=\"loading\">\n	<img src=\"/assets/v2/images/ajax-loader.gif\" class=\"spinner\">\n	<p>Loading</p>\n</div>\n";
},"useData":true});