<?php

namespace Drupal\fsa_es\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a search keyword block.
 *
 * @Block(
 *   id = "search_keyword",
 *   admin_label = @Translation("Search keyword")
 * )
 */
class SearchKeyword extends BlockBase implements FormInterface, ContainerFactoryPluginInterface {

  /** @var \Drupal\Core\Form\FormBuilderInterface $formBuilder */
  protected $formBuilder;

  /** @var null|\Symfony\Component\HttpFoundation\Request $request */
  protected $request;

  /** @var \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch */
  protected $currentRouteMatch;

  /**
   * SearchKeyword constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, RequestStack $request_stack, RouteMatchInterface $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->request = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['action_url'] = NULL;
    $configuration['form_method'] = 'post';
    $configuration['form_element'] = [
      'name' => NULL,
      'title' => NULL,
      'placeholder' => NULL,
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['action_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action URL'),
      '#description' => $this->t('Provide an action URL for the form element.'),
      '#default_value' => $this->configuration['action_url'],
    ];

    $form['form_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Form method'),
      '#description' => $this->t('Select a form method.'),
      '#options' => [
        'post' => $this->t('POST'),
        'get' => $this->t('GET'),
      ],
      '#default_value' => $this->configuration['form_method'],
    ];

    $form['form_element_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form element name'),
      '#description' => $this->t('Provide a name for the form element.'),
      '#default_value' => $this->configuration['form_element']['name'],
      '#required' => TRUE,
    ];

    $form['form_element_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form element title'),
      '#description' => $this->t('Provide a title for the form element.'),
      '#default_value' => $this->configuration['form_element']['title'],
    ];

    $form['form_element_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form element placeholder'),
      '#description' => $this->t('Provide a placeholder for the form element.'),
      '#default_value' => $this->configuration['form_element']['placeholder'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Special handling for regular block placement into site regions as there
    // are two forms involved (parent form).
    $source_form_state = $form_state;
    $value_parents = [];

    if ($form_state instanceof SubformStateInterface) {
      $source_form_state = $form_state->getCompleteFormState();
      $value_parents[] = 'settings';
    }

    $this->configuration['action_url'] = $source_form_state->getValue(array_merge($value_parents, ['action_url']));
    $this->configuration['form_method'] = $source_form_state->getValue(array_merge($value_parents, ['form_method']));

    $form_element_values = [];
    foreach (['name', 'title', 'placeholder'] as $field) {
      $form_element_values[$field] = $source_form_state->getValue(array_merge($value_parents, ['form_element_' . $field]));
    }

    $this->configuration['form_element'] = $form_element_values;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->formBuilder->getForm($this);

    // Remove unnecessary form elements the way View does it.
    if ($this->configuration['form_method'] == 'get' && function_exists('views_form_views_exposed_form_alter')) {
      views_form_views_exposed_form_alter($form, new FormState());
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_keyword';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parameter_value = $this->request->get($this->configuration['form_element']['name']);

    $form[$this->configuration['form_element']['name']] = [
      '#type' => 'textfield',
      '#title' => $this->configuration['form_element']['title'],
      '#placeholder' => $this->configuration['form_element']['placeholder'],
      '#default_value' => $parameter_value,
      '#cache' => [
        'contexts' => [
          'url'
        ],
      ],
    ];

    $form['#action'] = !empty($this->configuration['action_url']) ? $this->configuration['action_url'] : $this->request->getPathInfo();
    $form['#method'] = $this->configuration['form_method'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}