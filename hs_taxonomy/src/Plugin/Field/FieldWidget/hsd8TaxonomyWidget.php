<?php
/**
 * @file
 * Contains \Drupal\hs_taxonomy\Plugin\Field\FieldWidget\hsd8TaxonomyWidget.
 */

namespace Drupal\hs_taxonomy\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Plugin implementation of the 'hierarchical select' widget.
 *
 * @FieldWidget(
 *   id = "hierarchical_select",
 *   label = @Translation("Hierarchical Select"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   }
 * )
 */
class hsd8TaxonomyWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the vocabulary id from the form and then add as default value.
    $allowed_values = $this->fieldDefinition->getSetting('allowed_values');//Vocabulary::load($this->getSetting('allowed_values'));//taxonomy_vocabulary_machine_name_load($field['settings']['allowed_values'][0]['vocabulary']);
    $vocabulary = Vocabulary::load($allowed_values[0]['vocabulary']);

    /*if (!$items->isEmpty()) {
      foreach ($items as $item) {
        $tags[] = isset($item->entity) ? $item->entity : $this->termStorage->load($item->target_id);
      }
    }*/

    // Build an array of existing term IDs.
    $tids = array();
    /*foreach ($items as $delta => $item) {
      if (!empty($item['tid']) && $item['tid'] != 'autocreate') {
        $tids[] = $item['tid'];
      }
    }*/

    // TODO add #default_values.
    $element += array(
      '#type' => 'hierarchical_select',
      '#config'        => array(
        'module' => 'hs_taxonomy',
        'params' => array(
          'vid'                        => $vocabulary->id(),
          'exclude_tid'                => NULL,
          'root_term'                  => (int) $allowed_values[0]['parent'],
          'entity_count_for_node_type' => NULL,
        ),
      ),
      '#default_value' => $tids
    );

    //hierarchical_select_common_config_apply($element, hs_taxonomy_get_config_id($vocabulary));

    // Append another #process callback that transforms #return_value to the
    // format that Field API/Taxonomy Field expects.
    // However, HS' default #process callback has not yet been set, since this
    // typically happens automatically during FAPI processing. To ensure the
    // order is right, we already set HS' own #process callback here explicitly.
    /*$element_info = element_info('hierarchical_select');
    $element['#process'] = array_merge($element_info['#process'], array('hs_taxonomy_widget_process'));*/
    // D8
    /*$element_info = \Drupal::service('element_info')->getInfo('hierarchical_select');
    $class = get_class($this);
    $element['#process'] = array_merge($element_info['#process'], array($class, 'processHierarchicalSelectTaxonomyWidget'));*/

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!empty($item['hierarchical_select'])) {
        $item['target_id'] = $item['hierarchical_select']['selects'][0];
      }
    }

    return $values;
  }

}
