Drupal.behaviors.bean_instagram = {
  attach: function (context, settings) {
    var instances = Drupal.settings.bean_instagram && Drupal.settings.bean_instagram.instances;
    if (instances) {
      for (var i = 0; l = instances.length, i < l; i++) {
       new Instafeed(instances[i]).run();
     }
   }
 }
};