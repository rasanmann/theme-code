<?php

/**
 * @file
 * Contains \Drupal\yqb_blocks\Form\HeroSearchBlockForm.
 */

namespace Drupal\yqb_blocks\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search form for the search block.
 */
class HeroSearchBlockForm extends FormBase {

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new SearchBlockForm.
   *
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function __construct(SearchPageRepositoryInterface $search_page_repository, ConfigFactoryInterface $config_factory, RendererInterface $renderer) {
    $this->searchPageRepository = $search_page_repository;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search.search_page_repository'),
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set up the form to submit using GET to the correct search page.
    $entity_id = $this->searchPageRepository->getDefaultSearchPage();
    if (!$entity_id) {
      $form['message'] = [
        '#markup' => $this->t('Search is currently disabled'),
      ];
      return $form;
    }

    $route = 'search.view_' . $entity_id;
    $form['#action'] = $this->url($route);
    $form['#method'] = 'get';

    $form['keys'] = [
      '#type' => 'search',
      '#title' => $this->t('Chercher un vol, un service...'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => '',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      // Prevent op from showing up in the query string.
      '#name' => '',
    ];

    // SearchPageRepository::getDefaultSearchPage() depends on search.settings.
    $this->renderer->addCacheableDependency($form, $this->configFactory->get('search.settings'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
  }
}
