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
    this.$spinElement = this.options.element
      ? $(this.options.element)
      : this.$el;

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
        this.$spinElement.after(
          $(
            '<img src="/assets/v2/images/ajax-loader.gif" alt="Loading spinner" class="spinner" />',
          ),
        );
      } else if (this.options.placement === 'before') {
        this.$spinElement.before(
          $(
            '<img src="/assets/v2/images/ajax-loader.gif" alt="Loading spinner" class="spinner" />',
          ),
        );
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
      if (
        this.element.tagName === 'SELECT' ||
        this.element.tagName === 'BUTTON' ||
        this.element.tagName === 'INPUT'
      ) {
        return true;
      }
    },

    isLink: function () {
      return this.element.tagName === 'A';
    },
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
})(jQuery);
