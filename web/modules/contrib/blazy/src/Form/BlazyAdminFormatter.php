<?php

namespace Drupal\blazy\Form;

/**
 * Provides admin form specific to Blazy admin formatter.
 */
class BlazyAdminFormatter extends BlazyAdminFormatterBase {

  /**
   * Defines re-usable form elements.
   */
  public function buildSettingsForm(array &$form, $definition = []) {
    $definition['namespace'] = 'blazy';
    $definition['responsive_image'] = isset($definition['responsive_image']) ? $definition['responsive_image'] : TRUE;

    $this->openingForm($form, $definition);
    $this->basicImageForm($form, $definition);

    if (!empty($definition['grid_form']) && !isset($form['grid'])) {
      $this->gridForm($form, $definition);

      // Blazy doesn't need complex grid with multiple groups.
      unset($form['preserve_keys'], $form['visible_items']);

      if (isset($form['grid'])) {
        $form['grid']['#description'] = $this->t('The amount of block grid columns for large monitors 64.063em+. <br /><strong>Requires</strong>:<ol><li>Display style.</li><li>A reasonable amount of contents.</li></ol>Unless required, leave empty to DIY, or to not build grids.');
      }
    }

    $this->closingForm($form, $definition);
  }

}
