<?php

namespace Drupal\Core\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Render\PreviewFallbackInterface;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 *
 * @ingroup block_api
 */
abstract class BlockBase extends ContextAwarePluginBase implements BlockPluginInterface, PluginWithFormsInterface, PreviewFallbackInterface {

  use BlockPluginTrait {
    buildConfigurationForm as traitBuildConfigurationForm;
  }
  use ContextAwarePluginAssignmentTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->traitBuildConfigurationForm($form, $form_state);

    // Add context mapping UI form elements.
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);

    return $form;
  }

}
