<?php

class InstagramBean extends BeanPlugin {
  /**
   * Declares default block settings.
   */
  public function values() {
    $values = array(
      'settings' => array(
        'get' => 'popular',
        'clientId' => NULL,
        'userId' => NULL,
        'userName' => NULL,
        'accessToken' => NULL,
        'locationId' => NULL,
        'tagName' => NULL,
        'sortBy' => 'most-recent',
        'links' => FALSE,
        'limit' => '10',
        'resolution' => 'thumbnail',
        'template' => '',
        'showMoreLink' => FALSE,
      )
    );
    return array_merge(parent::values(), $values);
  }

  /**
   * Builds extra settings for the block edit form.
   */
  public function form($bean, $form, &$form_state) {
    $form = array();
    $form['settings'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => t('Settings'),
    );

    $form['settings']['get'] = array(
      '#type' => 'select',
      '#title' => t('Type'),
      '#options' => array(
        'popular' => t('Popular'),
        'tagged' => t('Tagged'),
        'location' => t('Location'),
        'user' => t('User'),
      ),
      '#required' => TRUE,
      '#default_value' => $bean->settings['get'],
      '#description' => t('Customize what Instafeed fetches.'),
    );

    $form['settings']['clientId'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#description' => t('Your API client id from Instagram. Required.'),
      '#default_value' => $bean->settings['clientId'],
      '#required' => TRUE,
    );

    $form['settings']['userId'] = array(
      '#type' => 'textfield',
      '#title' => t('User ID'),
      '#description' => t('Unique id of a user to get. Required if you wish to search by user id.'),
      '#default_value' => $bean->settings['userId'],
      '#states' => array(
        'visible' => array(
          ':input[name$="settings[get]"]' => array('value' => 'user'),
        ),
      ),
    );
    $form['settings']['userName'] = array(
      '#type' => 'textfield',
      '#title' => t('User Name'),
      '#description' => t('Name of the instagram account.'),
      '#default_value' => $bean->settings['userName'],
      '#states' => array(
        'visible' => array(
          ':input[name$="settings[get]"]' => array('value' => 'user'),
        ),
      ),
    );
    $form['settings']['showMoreLink'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show more link'),
      '#description' => t('Display a More on Instgram link below the list.'),
      '#default_value' => $bean->settings['showMoreLink'],
      '#states' => array(
        'visible' => array(
          ':input[name$="settings[userName]"]' => array('filled' => TRUE),
        ),
      ),
    );

    $form['settings']['accessToken'] = array(
      '#type' => 'textfield',
      '#title' => t('Access Token'),
      '#description' => t('A valid oAuth token. Required if you wish to search by user id.'),
      '#default_value' => $bean->settings['accessToken'],
      '#states' => array(
        'visible' => array(
          ':input[name$="settings[get]"]' => array('filled' => 'user'),
        ),
      ),
    );

    $form['settings']['locationId'] = array(
      '#type' => 'textfield',
      '#title' => t('Location ID'),
      '#description' => t('Unique id of a location to get.'),
      '#default_value' => $bean->settings['locationId'],
      '#states' => array(
        'visible' => array(
          ':input[name$="settings[get]"]' => array('value' => 'location'),
        ),
      ),
    );

    $form['settings']['tagName'] = array(
      '#type' => 'textfield',
      '#title' => t('Tag Name'),
      '#description' => t('Name of the tag to get.'),
      '#default_value' => $bean->settings['tagName'],
      '#states' => array(
        'visible' => array(
          ':input[name$="settings[get]"]' => array('value' => 'tagged'),
        ),
      ),
    );

    $form['settings']['sortBy'] = array(
      '#type' => 'select',
      '#title' => t('Sort by'),
      '#description' => t('Sort the images in a set order.'),
      '#options' => array(
        'most-recent' => t('Most Recent'),
        'least-recent' => t('Least Recent'),
        'most-liked' => t('Most Liked'),
        'least-liked' => t('Least Liked'),
        'most-commented' => t('Most Commented'),
        'least-commented' => t('Least Commented'),
        'random' => t('Random'),
      ),
      '#required' => TRUE,
      '#default_value' => $bean->settings['sortBy'],
    );

    $form['settings']['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Links'),
      '#description' => t('Wrap the images with a link to the photo on Instagram.'),
      '#default_value' => $bean->settings['links'],
    );

    $form['settings']['limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Limit'),
      '#description' => t('Maximum number of Images to add. Max of 60.'),
      '#default_value' => $bean->settings['limit'],
      '#required' => TRUE,
    );

    $form['settings']['resolution'] = array(
      '#type' => 'select',
      '#title' => t('Resolution'),
      '#description' => t('Size of the images to get.'),
      '#options' => array(
        'thumbnail' => t('Thumbnail'),
        'low_resolution' => t('Low Resolution'),
        'standard_resolution' => t('Standard Resolution'),
      ),
      '#required' => TRUE,
      '#default_value' => $bean->settings['resolution'],
    );

    $form['settings']['template'] = array(
      '#type' => 'textarea',
      '#title' => t('Template'),
      '#description' => t('Override the default template. See the !link for available tags.', array('!link' => l(t('Instafeed documentation'), 'http://instafeedjs.com/#templating', array('absolute' => TRUE)))),
      '#default_value' => $bean->settings['template'],
    );

    return $form;
  }


  /**
   * Displays the bean.
   */
  public function view($bean, $content, $view_mode = 'default', $langcode = NULL) {
    $entity_id = entity_id('bean', $bean);

    // Attach instafeed JavaScript to the bean
    $build['#attached']['library'] = array(
      array('bean_instagram', 'bean_instagram')
    );

    $id = 'bean_instagram-' . $entity_id;
    $settings = $bean->settings + array(
      'target' => $id
    );
    // Filter out null/empty settings.
    $settings = array_filter($settings, 'strlen');

    $build['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('bean_instagram' => array('instances' => array($settings)))
    );

    // Copy over all custom fields such as title to the output. We dont attach
    // our custom element to the existing array as DS would prevent it from
    // rendering.
    $key = key($content['bean']);
    foreach (element_children($content['bean'][$key]) as $field) {
      $build[] = $content['bean'][$key][$field];
    }
    $show_more = '';
    if ($settings['showMoreLink']) {
      $show_more = '<p>' . l(t('More on instagram'), 'https://instagram.com/' . $settings['userName'], array('external' => TRUE, 'attributes' => array('class' => array('show-more')))) . '</p>';
    }
    $build['content'] = array(
      '#markup' => '<div class="bean-instagram-wrapper"><div id="' . $id . '" class="bean-instagram"></div>' . $show_more . '</div>',
      '#weight' => 10,
    );
    return $build;
  }

}

?>
