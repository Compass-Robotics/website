/*!
 * Morphist - Generic Rotating Plugin for jQuery
 * https://github.com/MrSaints/Morphist
 *
 * Updated for jQuery 4.0.x compatibility
 */

(function ($) {
  "use strict";

  var pluginName = "Morphist",
    defaults = {
      animateIn: "bounceIn",
      animateOut: "rollOut",
      speed: 2000,
      complete: function () {}
    };

  function Plugin(element, options) {
    this.element = $(element);
    this.settings = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._init();
  }

  Plugin.prototype = {
    _init: function () {
      this.children = this.element.children();
      this.element.addClass("morphist");

      this.index = 0;
      this.loop();
    },

    loop: function () {
      var self = this;

      this._animateIn();
      this.timeout = setTimeout(function () {
        var elem = self._animateOut();
        self._attachOutListener(elem);
      }, this.settings.speed);

      if (typeof this.settings.complete === "function") {
        this.settings.complete.call(this);
      }
    },

    _attachOutListener: function ($elem) {
      var self = this;

      $elem.one("animationend", function () {
        if ($elem.hasClass("mis-out")) {
          $elem.removeClass();
          self.index = (self.index + 1) % self.children.length;
          self.loop();
        }
      });
    },

    stop: function () {
      clearTimeout(this.timeout);
    },

    _animateIn: function () {
      return this.children
        .eq(this.index)
        .addClass("animated mis-in " + this.settings.animateIn);
    },

    _animateOut: function () {
      var element = this.children.eq(this.index);
      element.removeClass();

      if (this.settings.animateIn === this.settings.animateOut) {
        // force reflow
        void element[0].offsetWidth;
      }

      return element.addClass(
        "animated mis-out " + this.settings.animateOut
      );
    }
  };

  $.fn[pluginName] = function (options) {
    return this.each(function () {
      if (!$.data(this, "plugin_" + pluginName)) {
        $.data(this, "plugin_" + pluginName, new Plugin(this, options));
      }
    });
  };
})(jQuery);
