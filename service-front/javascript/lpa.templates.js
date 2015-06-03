this["lpa"] = this["lpa"] || {};
this["lpa"]["templates"] = this["lpa"]["templates"] || {};

this["lpa"]["templates"]["popup.close"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<p class=\"close\">\n  <a href=\"#\" class=\"js-popup-close\" title=\"Click or press escape to close this window\">Close</a>\n</p>";
  });

this["lpa"]["templates"]["popup.container"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div id=\"popup\" role=\"dialog\"></div>";
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

this["lpa"]["templates"]["postcodeLookup.address-toggle"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, self=this;

function program1(depth0,data) {
  
  
  return "\n  <li><a href=\"#\" data-address-type=\"postal\" class=\"js-PostcodeLookup__toggle-address\">Enter address manually</a></li>\n  ";
  }

function program3(depth0,data) {
  
  
  return "\n  <li><a href=\"#\" data-address-type=\"dx\" class=\"js-PostcodeLookup__toggle-address\">Enter DX address</a></li>\n  ";
  }

  buffer += "<ul class=\"address-type-toggle\">\n  ";
  stack1 = helpers['if'].call(depth0, (depth0 && depth0.postal), {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n  ";
  stack1 = helpers['if'].call(depth0, (depth0 && depth0.dx), {hash:{},inverse:self.noop,fn:self.program(3, program3, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n</ul>";
  return buffer;
  });

this["lpa"]["templates"]["postcodeLookup.search-field"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<p class=\"group js-PostcodeLookup__search\">\n  <label for=\"postcode-lookup\">Postcode</label>\n  <input autocomplete=\"off\" type=\"text\" id=\"postcode-lookup\" class=\"postcode js-PostcodeLookup__query\">\n  <a href=\"#\" id=\"find_uk_address\" class=\"postcode-lookup button-secondary js-PostcodeLookup__search-btn\" role=\"button\">Find UK address</a>\n</p>";
  });

this["lpa"]["templates"]["postcodeLookup.search-result"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression, self=this;

function program1(depth0,data) {
  
  var buffer = "", stack1;
  buffer += "\n      <option value=\"";
  if (stack1 = helpers.id) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = (depth0 && depth0.id); stack1 = typeof stack1 === functionType ? stack1.call(depth0, {hash:{},data:data}) : stack1; }
  buffer += escapeExpression(stack1)
    + "\">";
  if (stack1 = helpers.description) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = (depth0 && depth0.description); stack1 = typeof stack1 === functionType ? stack1.call(depth0, {hash:{},data:data}) : stack1; }
  buffer += escapeExpression(stack1)
    + "</option>\n    ";
  return buffer;
  }

  buffer += "<div class=\"group\">\n  <label for=\"address-search-result\">Address</label>\n  <select class=\"js-PostcodeLookup__search-results\" id=\"address-search-result\">\n    <option value=\"\">Please select an address...</option>\n    ";
  stack1 = helpers.each.call(depth0, (depth0 && depth0.results), {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n  </select>\n</div>";
  return buffer;
  });

this["lpa"]["templates"]["shared.loading-popup"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "<div class=\"loading\">\n  <p><img src=\"/static/images/ajax/ajax-loader.gif\" class=\"spinner\"> Loading</p>\n</div>\n";
  });