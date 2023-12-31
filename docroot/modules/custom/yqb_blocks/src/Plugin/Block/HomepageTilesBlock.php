<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_blocks\Plugin\Block;

use Drupal;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides a 'Homepage Tiles Block' Block
 *
 * @Block(
 *   id = "homepage-tiles-block",
 *   admin_label = @Translation("Homepage Tiles Block"),
 * )
 */
class HomepageTilesBlock extends PreviewBlock {

  const SIZE_LABELS = [
    'embed-responsive-1by1' => 'Simple',
    'embed-responsive-2by1' => 'Double',
    '' => '',
  ];

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $rowKeys = $form_state->get('row_keys');
    if (empty($rowKeys)) {
      $rowKeys = (isset($config['row_keys']) && is_array($config['row_keys'])) ? $config['row_keys'] : [1];
    }
    $form_state->set('row_keys', $rowKeys);

    $form['#attached']['library'][] = 'yqb_blocks/admin_tiles';

    $form['container'] = [
      '#type' => 'container',
      '#id' => 'table-ajax-container',
    ];

    $form['container']['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Tile'),
        $this->t('Size'),
        $this->t('Remove'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No tile found.'),
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    foreach ($rowKeys as $rowKey) {
      $node = isset($config['rows'][$rowKey]) ? Node::load($config['rows'][$rowKey]['tile']) : NULL;
      $size = '';
      if ($node instanceof NodeInterface) {
        $size = $node->field_format->value;
      }

      $weight = isset($config['rows'][$rowKey]) ? $config['rows'][$rowKey]['weight'] : $rowKey;

      $form['container']['table-row'][$weight]['#attributes']['class'][] = 'draggable';
      $form['container']['table-row'][$weight]['#weight'] = $weight;
      $form['container']['table-row'][$weight]['tile'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#title' => $this->t('Tile'),
        '#title_display' => 'invisible',
        '#selection_settings' => [
          'target_bundles' => ['homepage_tiles'],
        ],
        '#default_value' => $node,
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'updateRowSize'],
          'wrapper' => 'size' . $weight,
          'event' => 'autocompleteclose',
        ],
      ];
      $form['container']['table-row'][$weight]['size'] = [
        '#markup' => '<span><strong>' . static::SIZE_LABELS[$size] . '</strong></span>',
        '#prefix' => '<div id="size' . $weight . '">',
        '#suffix' => '</div>',
      ];
      $buttonName = 'remove' . $weight;
      $form['container']['table-row'][$weight][$buttonName] = [
        '#type' => 'submit',
        '#limit_validation_errors' => TRUE,
        '#value' => $this->t('Remove tile'),
        '#name' => $buttonName,
        '#submit' => [[$this, 'removeRow']],
        '#ajax' => [
          'callback' => [$this, 'updateForm'],
          'wrapper' => 'table-ajax-container',
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ],
        ],
        '#attributes' => [
          'class' => ['tile-button'],
        ],
        '#disabled' => count($rowKeys) == 1,
      ];
      $form['container']['table-row'][$weight]['weight'] = [
        '#type' => 'weight',
        '#title' => 'Weight for this line',
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => [
          'class' => [
            'table-sort-weight',
          ],
        ],
      ];
    }

    $form['help_text'] = [
      '#theme' => 'status_messages',
      '#weight' => 150,
      '#message_list' => [
        'warning' => [
          $this->t('Always add or remove tiles before changing their position otherwise new positions will be lost. <strong>"Preview" must be used before saving.</strong>'),
        ],
      ],
      '#status_headings' => [
        'warning' => t('Warning message'),
      ],
    ];

    $form['actions']['add'] = [
      '#type' => 'submit',
      '#limit_validation_errors' => [],
      '#value' => $this->t('Add new tile'),
      '#name' => 'add',
      '#submit' => [[$this, 'addRow']],
      '#attributes' => [
        'class' => ['tile-button'],
      ],
      '#ajax' => [
        'callback' => [$this, 'updateForm'],
        'wrapper' => 'table-ajax-container',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    return $form;
  }

  public function updateRowSize($form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    $values = $form_state->getValues();
    $nid = NestedArray::getValue($values, $triggeringElement['#array_parents']);
    $weightParents = array_slice($triggeringElement['#array_parents'], 0, count($triggeringElement['#array_parents']) - 1);
    array_push($weightParents, 'weight');
    $weight = NestedArray::getValue($values, $weightParents);
    $size = '';

    if (!empty($nid)) {
      $node = Node::load($nid);
      if ($node instanceof NodeInterface) {
        $size = $node->field_format->value;
      }
    }

    return [
      '#markup' => '<span><strong>' . static::SIZE_LABELS[$size] . '</strong></span>',
      '#prefix' => '<div id="size' . $weight . '">',
      '#suffix' => '</div>',
    ];
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['container', 'table-row']);
    $rowKeys = [];
    $rows = [];

    foreach ($values as $row) {
      $rowKeys[] = $row['weight'];
      $rows[$row['weight']] = $row;
    }

    sort($rowKeys);
    $this->configuration['rows'] = $rows;
    $this->configuration['row_keys'] = $rowKeys;
  }

  public function updateForm($form, FormStateInterface $form_state) {
    return $form['settings']['container'];
  }

  public function removeRow($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parentKey = count($button['#array_parents']) - 2;
    $rowKey = $button['#array_parents'][$parentKey];

    $rowKeys = $form_state->get('row_keys');
    $keyToRemove = array_search($rowKey, $rowKeys);

    unset($rowKeys[$keyToRemove]);
    $form_state->set('row_keys', array_values($rowKeys));

    $form_state->setRebuild();
  }

  public function addRow($form, FormStateInterface $form_state) {
    $rowKeys = $form_state->get('row_keys');
    $newKey = max($rowKeys) + 1;
    $rowKeys[] = $newKey;
    $form_state->set('row_keys', $rowKeys);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $tiles = [];

    if (Drupal::currentUser()->hasPermission('preview homepage tiles') &&
        $rows = $this->tempStore->get('rows')) {
      $this->tempStore->delete('rows');
    }
    else {
      $rows = $this->configuration['rows'];
    }

    if (!empty($rows) && is_array($rows)) {
      $ids = [];
      foreach ($rows as $row) {
        $ids[] = $row['tile'];
      }

      if (!empty($ids)) {
        $nodes = Node::loadMultiple($ids);

        if (!empty($nodes)) {
          foreach ($rows as $row) {
            $node = $nodes[$row['tile']];
            if (!$node instanceof NodeInterface) {
              continue;
            }

            if ($node->hasTranslation($language)) {
              $node = $node->getTranslation($language);

              $tile = [
                'id' => $node->id(),
                'title' => $node->title->value,
                'description' => $node->field_tile_body->value,
                'hide_content' => (bool) $node->field_tile_hide_content->value,
                'icon_class' => $node->field_tile_icon_list->value,
                'background_color' => $node->field_tile_background_color->value,
                'format' => $node->field_format->value,
                'trigger_nav' => $node->field_trigger_nav->value,
              ];

              if (isset($node->field_tile_link->first()->uri)) {
                $uri = $node->field_tile_link->first()->uri;
                $tile['url'] = Url::fromUri($uri);
                $tile['target'] = (preg_match('/^internal|^entity|aeroportdequebec\.com/',
                  $uri)) ? '_self' : '_blank';
              }

              if (isset($node->field_tile_link->first()->uri)) {
                $tile['cta'] = $node->field_tile_link->first()->title;
              }

              if (isset($node->get('field_tile_background_image')->entity)) {
                $tile['background_image'] = $node->get('field_tile_background_image')->entity->getFileUri();
              }

              if (isset($node->get('field_tile_icon')->entity)) {
                $tile['icon'] = file_create_url($node->get('field_tile_icon')->entity->getFileUri());
              }

              $tiles[] = $tile;
            }
          }
        }
      }
    }

    return [
      '#theme' => 'homepage-tiles-block',
      '#tiles' => $tiles,
    ];
  }

  protected function getPreviewUrl() {
    return Url::fromRoute('<front>')->toString();
  }

  protected function previewSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['settings', 'container', 'table-row']);
    $rowKeys = [];
    $rows = [];

    foreach ($values as $row) {
      $rowKeys[] = $row['weight'];
      $rows[$row['weight']] = $row;
    }

    sort($rowKeys);
    $this->tempStore->set('rows', $rows);
  }
}
