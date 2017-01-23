<?php

class InstagramBean extends BeanPlugin {
  /**
   * Declares default block settings.
   */
  public function values() {
    $values = array(
      'authentication' => array(
        'client_id' => NULL,
        'client_secret' => NULL,
        'access_token' => NULL,
      ),
      'settings' => array(
        'get' => 'popular',
        'clientId' => NULL,
        'clientSecret' => NULL,
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
    $query_parameters = drupal_get_query_parameters();

    $form = array();

    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings'),
      '#tree' => TRUE,
      '#collapsed' => FALSE,
      '#collapsible' => TRUE,
      '#weight' => -4,
      '#states' => array(
        'invisible' => array(
          ':input[name$="authentication[access_token]"]' => array('empty' => TRUE),
        ),
      ),
    );

    $form['settings']['accessToken'] = array(
      '#type' => 'hidden',
      '#default_value' => $bean->settings['accessToken'],
    );

    $form['settings']['userId'] = array(
      '#type' => 'textfield',
      '#title' => t('User ID'),
      '#description' => t('Unique id of a user to get.'),
      '#default_value' => $bean->settings['userId'],
    );

    $form['authentication'] = array(
      '#type' => 'fieldset',
      '#title' => t('Authentication'),
      '#description' => t('You need to log in to Instagram with the user you want to query and create a client at !link, and add this URL !url to <strong>Valid redirect URIs</strong>.', array(
        '!link' => l('https://www.instagram.com/developer/clients/manage/', 'https://www.instagram.com/developer/clients/manage/'),
        '!url' => '<span contenteditable="true">' . url('bean_instagram/get_access_token', array('absolute' => TRUE)) . '</span>',
      )),
      '#tree' => TRUE,
      '#weight' => -5,
    );

    $form['authentication']['client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#description' => t('Your API Client ID from Instagram.'),
      '#default_value' => $bean->authentication['client_id'],
      '#required' => TRUE,
    );

    $form['authentication']['client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Client Secret'),
      '#description' => t('Your API Client Secret from Instagram.'),
      '#default_value' => $bean->authentication['client_secret'],
      '#required' => TRUE,
    );

    $form['authentication']['access_token'] = array(
      '#type' => 'textfield',
      '#title' => t('Access Token'),
      '#default_value' => $bean->settings['accessToken'],
      '#required' => TRUE,
    );

    $form['authentication']['get_access_token'] = array(
      '#type' => 'submit',
      '#value' => t('Get Access Token'),
      '#name' => 'get_access_token',
      '#submit' => array('bean_instagram_get_access_token_submit'),
      '#limit_validation_errors' => array(
        array('authentication', 'client_id'),
        array('authentication', 'client_secret'),
        array('form_build_id'),
      ),
    );

    $form['settings']['showMoreLink'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show more link'),
      '#description' => t('Display a More on Instgram link below the list.'),
      '#default_value' => $bean->settings['showMoreLink'],
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
      '#default_value' => isset($bean->settings['limit']) ? $bean->settings['limit'] : 10,
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
      '#default_value' => isset($bean->settings['resolution']) ? $bean->settings['resolution'] : 'thumbnail',
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
      'target' => $id,
      'get' => 'user',
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
