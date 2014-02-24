(function($, Instafeed) {
  Drupal.behaviors.bean_instagram = {
    attach: function (context, settings) {
      var instances = Drupal.settings.bean_instagram && Drupal.settings.bean_instagram.instances;
      if (instances) {
        for (var i = 0, l = instances.length; i < l; i++) {
          var options = instances[i];
          options.links = !!options.links;
          options.limit = ~~options.limit;
          options.userId = ~~options.userId;

          options.before = Drupal.bean_instagram.before;
          options.after = Drupal.bean_instagram.after;
          options.success = Drupal.bean_instagram.success;
          options.error = Drupal.bean_instagram.error;

          new Instafeed(options).run();
        }
      }
    }
  };
  Drupal.bean_instagram = Drupal.bean_instagram || {};

  function noop() {}
  // Provide overrideable hooks.
  Drupal.bean_instagram.before = noop;
  Drupal.bean_instagram.after = noop;
  Drupal.bean_instagram.success = noop;
  Drupal.bean_instagram.error = noop;
}(jQuery, window.Instafeed));
