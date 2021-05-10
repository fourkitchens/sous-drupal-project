<?php

namespace Drupal\google_tag\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines shared routines for the container and settings forms.
 */
trait ContainerTrait {

  /**
   * The container entity.
   *
   * @var \Drupal\google_tag\Entity\Container
   */
  protected $container;

  /**
   * The property prefix that allows reuse by container and settings forms.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Fieldset builder for the container settings form.
   */
  public function advancedFieldset(FormStateInterface &$form_state) {
    $container = $this->container;

    // Build form elements.
    $fieldset = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#group' => 'settings',
    ];

    $fieldset['data_layer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data layer'),
      '#description' => $this->t('The name of the data layer. Default value is "dataLayer". In most cases, use the default.'),
      '#default_value' => $container->get("{$this->prefix}data_layer"),
      '#attributes' => ['placeholder' => ['dataLayer']],
      '#required' => TRUE,
    ];

    $fieldset['include_classes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add classes to the data layer'),
      '#description' => $this->t('If checked, then the listed classes will be added to the data layer.'),
      '#default_value' => $container->get("{$this->prefix}include_classes"),
    ];

    $description = $this->t('The types of tags, triggers, and variables <strong>allowed</strong> on a page. Enter one class per line. For more information, refer to the <a href="https://developers.google.com/tag-manager/devguide#security">developer documentation</a>.');

    $fieldset['whitelist_classes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('White-listed classes'),
      '#description' => $description,
      '#default_value' => $container->get("{$this->prefix}whitelist_classes"),
      '#rows' => 5,
      '#states' => $this->statesArray('include_classes'),
    ];

    $fieldset['blacklist_classes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Black-listed classes'),
      '#description' => $this->t('The types of tags, triggers, and variables <strong>forbidden</strong> on a page. Enter one class per line.'),
      '#default_value' => $container->get("{$this->prefix}blacklist_classes"),
      '#rows' => 5,
      '#states' => $this->statesArray('include_classes'),
    ];

    $fieldset['include_environment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include an environment'),
      '#description' => $this->t('If checked, then the applicable snippets will include the environment items below. Enable <strong>only for development</strong> purposes.'),
      '#default_value' => $container->get("{$this->prefix}include_environment"),
    ];

    $description = $this->t('The environment ID to use with this website container. To get an environment ID, <a href="https://tagmanager.google.com/#/admin">select Environments</a>, create an environment, then click the "Get Snippet" action. The environment ID and token will be in the snippet.');

    $fieldset['environment_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment ID'),
      '#description' => $description,
      '#default_value' => $container->get("{$this->prefix}environment_id"),
      '#attributes' => ['placeholder' => ['env-x']],
      '#size' => 10,
      '#maxlength' => 7,
      '#states' => $this->statesArray('include_environment'),
    ];

    $fieldset['environment_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment token'),
      '#description' => $this->t('The authentication token for this environment.'),
      '#default_value' => $container->get("{$this->prefix}environment_token"),
      '#attributes' => ['placeholder' => ['xxxxxxxxxxxxxxxxxxxxxx']],
      '#size' => 20,
      '#maxlength' => 25,
      '#states' => $this->statesArray('include_environment'),
    ];

    return $fieldset;
  }

  /**
   * Returns states array for a form element.
   *
   * @param string $variable
   *   The name of the form element.
   *
   * @return array
   *   The states array.
   */
  public function statesArray($variable) {
    return [
      'required' => [
        ':input[name="' . $variable . '"]' => ['checked' => TRUE],
      ],
      'invisible' => [
        ':input[name="' . $variable . '"]' => ['checked' => FALSE],
      ],
    ];
  }

  /**
   * Fieldset builder for the container settings form.
   */
  public function pathFieldset(FormStateInterface &$form_state) {
    $fieldset_title = $this->t('Request path');
    $fieldset_description = $this->t('On this and the following tabs, specify the conditions on which the GTM JavaScript snippet will either be inserted on or omitted from the page response, thereby enabling or disabling tracking and other analytics. All conditions must be satisfied for the snippet to be inserted. The snippet will be omitted if any condition is not met.');
    $args = [
      '%node' => '/node',
      '%user-wildcard' => '/user/*',
      '%front' => '<front>',
    ];
    $description = $this->t('Enter one relative path per line using the "*" character as a wildcard. Example paths are: "%node" for the node page, "%user-wildcard" for each individual user, and "%front" for the front page.', $args);
    $rows = 10;
    $singular = 'path';
    $plural = 'paths';
    $adjective = 'listed';
    $config = compact(['fieldset_title', 'fieldset_description', 'singular', 'plural', 'adjective', 'description', 'rows']);
    return $this->genericFieldset($config, $form_state);
  }

  /**
   * Fieldset builder for the container settings form.
   */
  public function roleFieldset(FormStateInterface &$form_state) {
    $fieldset_title = $this->t('User role');
    $singular = 'role';
    $plural = 'roles';
    $options = array_map(function ($role) {
      return $role->label();
    }, user_roles());
    $config = compact(['fieldset_title', 'singular', 'plural', 'options']);
    return $this->genericFieldset($config, $form_state);
  }

  /**
   * Fieldset builder for the container settings form.
   */
  public function statusFieldset(FormStateInterface &$form_state) {
    $fieldset_title = $this->t('Response status');
    $description = $this->t('Enter one response status per line. For more information, refer to the <a href="http://en.wikipedia.org/wiki/List_of_HTTP_status_codes">list of HTTP status codes</a>.');
    $rows = 5;
    $singular = 'status';
    $plural = 'statuses';
    $adjective = 'listed';
    $config = compact(['fieldset_title', 'singular', 'plural', 'adjective', 'description', 'rows']);
    return $this->genericFieldset($config, $form_state);
  }

  /**
   * Fieldset builder for the container settings form.
   */
  public function genericFieldset(array $config, FormStateInterface &$form_state) {
    $container = $this->container;

    // Gather data.
    $config += ['fieldset_description' => '', 'adjective' => 'selected'];
    extract($config);
    $toggle = "{$singular}_toggle";
    $list = "{$singular}_list";
    $args = [
      '@adjective' => $adjective,
      '@uc_adjective' => ucfirst($adjective),
      '@plural' => $plural,
    ];

    // Build form elements.
    $fieldset = [
      '#type' => 'details',
      '#title' => $fieldset_title,
      '#description' => $fieldset_description,
      '#group' => 'conditions',
    ];

    $fieldset[$toggle] = [
      '#type' => 'radios',
      '#title' => $this->specialT('Insert snippet for specific @plural', $args),
      '#options' => [
        GOOGLE_TAG_EXCLUDE_LISTED => $this->specialT('All @plural except the @adjective @plural', $args),
        GOOGLE_TAG_INCLUDE_LISTED => $this->specialT('Only the @adjective @plural', $args),
      ],
      '#default_value' => $container->get("{$this->prefix}$toggle"),
    ];

    if ($adjective == 'selected') {
      $fieldset[$list] = [
        '#type' => 'checkboxes',
        '#title' => $this->specialT('@uc_adjective @plural', $args),
        '#options' => $options,
        '#default_value' => $container->get("{$this->prefix}$list"),
      ];
    }
    else {
      $fieldset[$list] = [
        '#type' => 'textarea',
        '#title' => $this->specialT('@uc_adjective @plural', $args),
        '#description' => $description,
        '#default_value' => $container->get("{$this->prefix}$list"),
        '#rows' => $rows,
      ];
    }

    return $fieldset;
  }

  /**
   * Returns a translated string after placeholder substitution.
   *
   * @param string $string
   *   The string to manipulate.
   * @param array $args
   *   The associative array of replacement values.
   *
   * @return string
   *   The translated string.
   */
  protected function specialT($string, array $args) {
    return $this->t(strtr($string, $args));
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormValues(array &$form, FormStateInterface $form_state) {
    // Specific to the container form.
    $container_id = $form_state->getValue('container_id');
    if (!is_null($container_id)) {
      $container_id = trim($container_id);
      $container_id = str_replace(['–', '—', '−'], '-', $container_id);
      $form_state->setValue('container_id', $container_id);

      if (!preg_match('/^GTM-\w{4,}$/', $container_id)) {
        // @todo Is there a more specific regular expression that applies?
        // @todo Is there a way to validate the container ID?
        // It may be valid but not the correct one for the website.
        $form_state->setError($form['general']['container_id'], $this->t('A valid container ID is case sensitive and formatted like GTM-xxxxxx.'));
      }
    }

    // Specific to the settings form.
    $uri = $form_state->getValue('uri');
    if (!is_null($uri) && $form['#form_id'] == 'google_tag_settings') {
      $uri = trim($uri);
      $form_state->setValue('uri', $uri);

      $directory = $uri;
      if (substr($directory, -3) == '://') {
        $args = ['%directory' => $directory];
        $message = 'The snippet parent uri %directory is invalid. Enter a single trailing slash to specify a plain stream wrapper.';
        $form_state->setError($form['module']['uri'], $this->t($message, $args));
      }

      // Allow for a plain stream wrapper with one trailing slash.
      $directory .= substr($directory, -2) == ':/' ? '/' : '';
      if (!is_dir($directory) || !_google_tag_is_writable($directory) || !_google_tag_is_executable($directory)) {
        $args = ['%directory' => $directory];
        $message = 'The snippet parent uri %directory is invalid, possibly due to file system permissions. The directory either does not exist, or is not writable or searchable.';
        $form_state->setError($form['module']['uri'], $this->t($message, $args));
      }
    }

    // Trim the text values.
    $environment_id = trim($form_state->getValue('environment_id'));
    $form_state->setValue('data_layer', trim($form_state->getValue('data_layer')));
    $form_state->setValue('path_list', $this->cleanText($form_state->getValue('path_list')));
    $form_state->setValue('status_list', $this->cleanText($form_state->getValue('status_list')));
    $form_state->setValue('whitelist_classes', $this->cleanText($form_state->getValue('whitelist_classes')));
    $form_state->setValue('blacklist_classes', $this->cleanText($form_state->getValue('blacklist_classes')));

    // Replace all types of dashes (n-dash, m-dash, minus) with a normal dash.
    $environment_id = str_replace(['–', '—', '−'], '-', $environment_id);
    $form_state->setValue('environment_id', $environment_id);

    $form_state->setValue('role_list', array_filter($form_state->getValue('role_list')));

    if ($form_state->getValue('include_environment') && !preg_match('/^env-\d{1,}$/', $environment_id)) {
      $form_state->setError($form['advanced']['environment_id'], $this->t('A valid environment ID is case sensitive and formatted like env-x.'));
    }
  }

  /**
   * Cleans a string representing a list of items.
   *
   * @param string $text
   *   The string to clean.
   * @param string $format
   *   The final format of $text, either 'string' or 'array'.
   *
   * @return string
   *   The clean text.
   */
  public function cleanText($text, $format = 'string') {
    $text = explode("\n", $text);
    $text = array_map('trim', $text);
    $text = array_filter($text, 'trim');
    if ($format == 'string') {
      $text = implode("\n", $text);
    }
    return $text;
  }

}
