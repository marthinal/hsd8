<?php

/**
 * @file
 * Contains \Drupal\hsd8\Element\HierarchicalSelect.
 */

namespace Drupal\hsd8\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Component\Utility\String;
use Drupal\Core\Url;

/**
 * Provides a form element for Hierarchical Select.
 *
 * @FormElement("hierarchical_select")
 */
class HierarchicalSelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => array(
        array($class, 'processHierarchicalSelect'),
        array('Drupal\Core\Render\Element\RenderElement', 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderHierarchicalSelect'),
      ),
      '#theme' => 'hierarchical_select',
      '#theme_wrappers' => array('form_element'),
      '#config' => array(
        'module' => 'some_module',
        'params' => array(),
        'save_lineage'    => 0,
        'enforce_deepest' => 0,
        'entity_count'    => 0,
        'require_entity'  => 0,
        'resizable'       => 1,
        'level_labels' => array(
          'status' => 0,
          'labels' => array(),
        ),
        'dropbox' => array(
          'status'   => 0,
          'title'    => t('All selections'),
          'limit'    => 0,
          'reset_hs' => 1,
        ),
        'editability' => array(
          'status'           => 0,
          'item_types'       => array(),
          'allowed_levels'   => array(),
          'allow_new_levels' => 0,
          'max_levels'       => 3,
        ),
        //'animation_delay'    => variable_get('hierarchical_select_animation_delay', 400),
        'special_items'      => array(),
        'render_flat_select' => 0,
      ),
      '#default_value' => -1,
    );
  }

  /**
   * Processes a hierarchical select form element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see _form_validate()
   */
  public static function processHierarchicalSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    // Determine the HSID.
    $hsid = self::determineHsid($element, $form_state);

    // Config.
    $config = $element['#config'];

    // Attach CSS/JS files and JS settings.
    $element = self::hsProcessAttachCssJs($element, $hsid, $form_state, $complete_form);

    // Developer mode diagnostics, return immediately in case of a config error.
    /*if (!_hs_process_developer_mode_log_diagnostics($element)) {
      return $element;
    }*/

    // Calculate the selections in both the hierarchical select and the dropbox,
    // we need these before we can render anything.
    $hs_selection = $db_selection = array();
    list($hs_selection, $db_selection) = self::processCalculateSelections($element, $hsid, $form_state);

    // Developer mode logging: log selections.
    //_hs_process_developer_mode_log_selections($config, $hs_selection, $db_selection);

    // Dynamically disable the dropbox when an exclusive item has been selected.
    // When this happens, the configuration is dynamically altered. Hence, we
    // need to update $config.
    //list($element, $hs_selection, $db_selection) = _hs_process_exclusive_lineages($element, $hs_selection, $db_selection);
    //$config = $element['#config'];

    // Generate the $hierarchy and $dropbox objects using the selections that
    // were just calculated.
    //$dropbox = (!$config['dropbox']['status']) ? FALSE : _hierarchical_select_dropbox_generate($config, $db_selection);
    $hierarchy = self::hierarchyGenerate($config, $hs_selection, $element['#required'], FALSE);

    // Developer mode logging: log $hierarchy and $dropbox objects.
    //_hs_process_developer_mode_log_hierarchy_and_dropbox($config, $hierarchy, $dropbox);

    // Finally, calculate the return value of this hierarchical_select form
    // element. This will be set in _hierarchical_select_validate(). (If we'd
    // set it now, it would be overridden again.)
    /*$element['#return_value'] = _hierarchical_select_process_calculate_return_value($hierarchy, ($config['dropbox']['status']) ? $dropbox : FALSE, $config['module'], $config['params'], $config['save_lineage']);
    if (!is_array($element['#return_value'])) {
      $element['#return_value'] = array($element['#return_value']);
    }*/

    // Add a validate callback, which will:
    // - validate that the dropbox limit was not exceeded.
    // - set the return value of this form element.
    // Also make sure it is the *first* validate callback.
    /*$element['#element_validate'] = (isset($element['#element_validate'])) ? $element['#element_validate'] : array();*/
    //$element['#element_validate'] = array_merge(array('_hierarchical_select_validate'), $element['#element_validate']);

    // Ensure the form is cached, for AJAX to work.
    //$form_state['cache'] = TRUE;

    //
    // Rendering.
    //

    // Ensure that #tree is enabled!
    $element['#tree'] = TRUE;

    // Store the HSID in a hidden form element; when an AJAX callback comes in,
    // we'll know which HS was updated.
    $element['hsid'] = array('#type' => 'hidden', '#value' => $hsid);


    // If render_flat_select is enabled, render a flat select.
    /*if ($config['render_flat_select']) {
      $element['flat_select'] = _hs_process_render_flat_select($hierarchy, $dropbox, $config);
      // See https://www.drupal.org/node/994820
      if (empty($element['flat_select']['#options'])) {
        unset($element['flat_select']);
      }
    }*/

    // Render the hierarchical select.
    $element['hierarchical_select'] = array(
      '#theme' => 'hierarchical_select_selects_container',
    );
    $size = isset($element['#size']) ? $element['#size'] : 0;
    $element['hierarchical_select']['selects'] = static::renderSelects($hsid, $hierarchy, $size);//_hs_process_render_hs_selects($hsid, $hierarchy, $size);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      if (isset($element['#multiple']) && $element['#multiple']) {
        // If an enabled multi-select submits NULL, it means all items are
        // unselected. A disabled multi-select always submits NULL, and the
        // default value should be used.
        if (empty($element['#disabled'])) {
          return (is_array($input)) ? array_combine($input, $input) : array();
        }
        else {
          return (isset($element['#default_value']) && is_array($element['#default_value'])) ? $element['#default_value'] : array();
        }
      }
      // Non-multiple select elements may have an empty option prepended to them
      // (see \Drupal\Core\Render\Element\Select::processSelect()). When this
      // occurs, usually #empty_value is an empty string, but some forms set
      // #empty_value to integer 0 or some other non-string constant. PHP
      // receives all submitted form input as strings, but if the empty option
      // is selected, set the value to match the empty value exactly.
      elseif (isset($element['#empty_value']) && $input === (string) $element['#empty_value']) {
        return $element['#empty_value'];
      }
      else {
        return $input;
      }
    }
  }
  /**
   * Prepares a select render element.
   */
  public static function preRenderHierarchicalSelect($element) {
    /*Element::setAttributes($element, array('id', 'name', 'size'));
    static::setAttributes($element, array('form-select'));
    return $element;*/

    // Update $element['#attributes']['class'].
    if (!isset($element['#attributes']['class'])) {
      $element['#attributes']['class'] = array();
    }
    $hsid = $element['hsid']['#value'];
    $level_labels_style = 'none';//variable_get('hierarchical_select_level_labels_style', 'none');
    $classes = array(
      'hierarchical-select-wrapper',
      "hierarchical-select-level-labels-style-$level_labels_style",
      // Classes that make it possible to override the styling of specific
      // instances of Hierarchical Select, based on either the ID of the form
      // element or the config that it uses.
      'hierarchical-select-wrapper-for-name-' . $element['#id'],
      (isset($element['#config']['config_id'])) ? 'hierarchical-select-wrapper-for-config-' . $element['#config']['config_id'] : NULL,
    );
    $element['#attributes']['class'] = array_merge($element['#attributes']['class'], $classes);
    $element['#attributes']['id'] = "hierarchical-select-$hsid-wrapper";
    $element['#id'] = "hierarchical-select-$hsid-wrapper"; // This ensures the label's for attribute is correct.

    return $element;

  }

  protected function determineHsid($element, &$form_state) {
    // Determine the HSID to use: either the existing one that is received, or
    // generate a new one based on the last HSID used (which is
    // stored in form state storage).
    if (!isset($element['#value']) || !is_array($element['#value']) || !array_key_exists('hsid', $element['#value'])) {
      // Do we have a default value?
      if (!empty($element['#default_value']) && $element['#config']['module'] == 'hs_taxonomy') {
        $hsid = is_array($element['#default_value']) ? reset($element['#default_value']) : $element['#default_value'];
      }
      else {
        if (!$hsid = $form_state->get(array('hs', 'last_hsid'))) {
          $form_state->set(array('hs', 'last_hsid'), -1);
        }
        $form_state->set(array('hs', 'last_hsid'), $form_state->get(array('hs', 'last_hsid'))+1);
        $hsid = $form_state->get(array('hs', 'last_hsid'));
      }
    }
    else {
      $hsid = String::checkPlain($element['#value']['hsid']);
    }

    $last_hsid = $form_state->get(array('hs', 'last_hsid'));
    if ($last_hsid <= $hsid) {
      $form_state->set(array('hs', 'last_hsid'), $hsid);
    }

    return $hsid;

  }

  protected function hierarchyGenerate($config, $selection, $required, $dropbox = FALSE) {
    $hierarchy = new \stdClass();

    // When nothing is currently selected, set the root level to:
    // - "<none>" (or its equivalent special item) when:
    //    - enforce_deepest is enabled *and* level labels are enabled *and*
    //      no root level label is set (1), or
    //    - the dropbox is enabled *and* at least one selection has been added
    //      to the dropbox (2)
    // - "label_0" (the root level label) in all other cases.
    if ($selection == -1) {
      $hierarchy->lineage[0] = 'none';
    }
    else {
      //$hierarchy->lineage = module_invoke($config['module'], 'hierarchical_select_lineage', $selection, $config['params']);
      //$hierarchy->lineage = \Drupal::moduleHandler()->invoke($config['module'], 'hierarchical_select_lineage', $args = array($selection, $config['params']));
      $hierarchy->lineage = $selection;
    }
    // Add none when the field is not required.
    /*if (!required) {
      $hierarchy->levels[0] = array('_none' => '-None-');
    }*/
    // Start building the levels, initialize with the root level.
    //$hierarchy->levels[0] = array_push($hierarchy->levels[0], static::getRootLevel($config['params']));//module_invoke($config['module'], 'hierarchical_select_root_level', $config['params']);
    $hierarchy->levels[0] = \Drupal::moduleHandler()->invoke($config['module'], 'hierarchical_select_root_level', $args = array($config['params']));

    $hierarchy->levels[0] = array('_none' => '-None-') + $hierarchy->levels[0];

    // Building children when there's an option selected.
    if (is_array($selection)) {
      // Calculate the lineage's depth.
      $max_depth = count($hierarchy->lineage);

      // Build all sublevels, based on the lineage.
      for ($depth = 1; $depth <= $max_depth; $depth++) {
        //$hierarchy->levels[$depth] = module_invoke($config['module'], 'hierarchical_select_children', $hierarchy->lineage[$depth - 1], $config['params']);
        $hierarchy->levels[$depth] = \Drupal::moduleHandler()->invoke($config['module'], 'hierarchical_select_children', $args = array($hierarchy->lineage[$depth - 1], $config['params']));
        //$hierarchy->levels[$depth] = _hierarchical_select_apply_entity_settings($hierarchy->levels[$depth], $config);
        // TODO. Detect if the last term has children to avoid this unset. Needs work and to refactor.
        if (empty($hierarchy->levels[$depth])) {
          unset($hierarchy->levels[$depth]);
        }
      }
    }
    return $hierarchy;
  }


  protected static function renderSelects($hsid, $hierarchy, $size) {
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div class="selects">';
    $form['#suffix'] = '</div>';

    foreach ($hierarchy->levels as $depth => $options) {
      $form[$depth] = array(
        '#type' => 'select',
        '#options' => $hierarchy->levels[$depth],
        '#default_value' => $hierarchy->lineage[$depth],
        '#size' => $size,
        // Prevent the select from being wrapped in a div. This simplifies the
        // CSS and JS code.
        '#theme_wrappers' => array(),
        // This alternative to theme_select ets a special class on the level
        // label option, if any, to make level label styles possible.
        '#theme' => 'hierarchical_select_select',
        // Add child information. When a child has no children, its
        // corresponding "option" element will be marked as such.
      //'#childinfo' => (isset($hierarchy->childinfo[$depth])) ? $hierarchy->childinfo[$depth] : NULL,
        // Drupal 7's Forms API insists on validating "select" form elements,
        // despite the fact that this form element is merely part of a larger
        // whole, with its own #element_validate callback. This disables that
        // validation.
        '#validated' => TRUE,
      );
    }

    return $form;
  }

  /**
   * Attach CSS/JS files and JS settings.
   *
   * @param $element
   * @param $hsid
   * @param $form_state
   * @param $complete_form
   * @return mixed
   */
  protected function hsProcessAttachCssJs(&$element, $hsid, &$form_state, $complete_form) {
    // Set up Javascript and add settings specifically for the current
    // hierarchical select.
    // TODO we need to add #ajax settings for D8 so probably we need to study
    // how to refactor the settings here.
    $ajax_settings = [
      'url' => Url::fromRoute('hsd8.hierarchical_select_ajax'),
    ];
    $element['#ajax'] = $ajax_settings;

    $element['#attached']['library'][] = 'core/jquery.form';
    $element['#attached']['library'][] = 'core/jquery.ui.effects.core';
    $element['#attached']['library'][] = 'core/jquery.ui.effects.drop';
    $element['#attached']['library'][] = 'hsd8/hierarchical_select.config';
    /*if (variable_get('hierarchical_select_js_cache_system', 0) == 1) {
      $element['#attached']['js'][] = drupal_get_path('module', 'hierarchical_select') . '/hierarchical_select_cache.js';
    }

    if (!isset($form_state['storage']['hs']['js_settings_sent'])) {
      $form_state['storage']['hs']['js_settings_sent'] = array();
    }

    // Form was submitted; this is a newly loaded page, thus ensure that all JS
    // settings are resent.
    if ($form_state['process_input'] === TRUE) {
      $form_state['storage']['hs']['js_settings_sent'] = array();
    }*/

    // TODO missing triggering element.
    if (!$form_state->get(array('hs', 'js_settings_sent', $hsid))) {
      $parents = array_slice($element['#array_parents'], 0, -1);
      $element_parents = array(
        'query' => array(
          'element_parents' => implode('/', $parents),
        ),
      );

      //$config = _hierarchical_select_inherit_default_config($element['#config']);
      //$config = self::hierarchicalSelectInheritDefaultConfig($element['#config']);
      $settings =  array(
        //'HierarchicalSelect' => array(
          'settings' => array(
            "hs-$hsid" => array(
              //'animationDelay'   => ($config['animation_delay'] == 0) ? (int) variable_get('hierarchical_select_animation_delay', 400) : $config['animation_delay'],
              //'cacheId'          => $config['module'] . '_' . md5(serialize($config['params'])),
              //'renderFlatSelect' => (isset($config['render_flat_select'])) ? (int) $config['render_flat_select'] : 0,
              //'createNewItems'   => (isset($config['editability']['status'])) ? (int) $config['editability']['status'] : 0,
              //'createNewLevels'  => (isset($config['editability']['allow_new_levels'])) ? (int) $config['editability']['allow_new_levels'] : 0,
              //'resizable'        => (isset($config['resizable'])) ? (int) $config['resizable'] : 0,
              'ajax_path'         => \Drupal::url('hsd8.hierarchical_select_ajax', array(), $element_parents)
            ),
          ),
        //)
      );

      if (!isset($_POST['hsid'])) {
        $element['#attached']['drupalSettings']['HierarchicalSelect'] = $settings;
      }

      /*if (!isset($_POST['hsid'])) {
        $element['#attached']['drupalSettings'] = array(
          'type' => 'setting',
          'data' => $settings,
        );
      }*/
      /*else {
        $element['#attached']['_hs_new_setting_ajax'][] = array($hsid, $settings['HierarchicalSelect']['settings']["hs-$hsid"]);
      }*/

      //$form_state['storage']['hs']['js_settings_sent'][$hsid] = TRUE;
    }

    return $element;
  }

  /**
   * Inherit the default config from Hierarchical Selects' hook_elements().
   *
   * @param $config
   *   A config array with at least the following settings:
   *   - module
   *   - params
   * @return
   *   An updated config array.
   */
  protected function hierarchicalSelectInheritDefaultConfig($config, $defaults_override = array()) {
    // Set defaults for unconfigured settings. Get the defaults from our
    // hook_elements() implementation. Default properties from this hook are
    // applied automatically, but properties inside properties, such as is the
    // case for Hierarchical Select's #config property, aren't applied.
    /*$type = hierarchical_select_element_info();
    $defaults = $type['hierarchical_select']['#config'];
    // Don't inherit the module and params settings.
    unset($defaults['module']);
    unset($defaults['params']);

    // Allow the defaults to be overridden.
    $defaults = array_smart_merge($defaults, $defaults_override);

    // Apply the defaults to the config.
    $config = array_smart_merge($defaults, $config);*/

    return $config;
  }

  /**
   * Calculates the flat selections of both the hierarchical select and the
   * dropbox.
   *
   * @param $element
   *   A hierarchical_select form element.
   * @param $form_state
   *   The $form_state array. We need to look at $form_state['input']['op'], to
   *   know which operation has occurred.
   * @return array
   *   An array of the following structure:
   *   array(
   *     $hierarchical_select_selection = array(), // Flat list of selected ids.
   *     $dropbox_selection = array(),
   *   )
   *   with both of the subarrays flat lists of selected ids. The
   *   _hierarchical_select_hierarchy_generate() and
   *   _hierarchical_select_dropbox_generate() functions should be applied on
   *   these respective subarrays.
   *
   * @see _hierarchical_select_hierarchy_generate()
   * @see _hierarchical_select_dropbox_generate()
   */
  protected function processCalculateSelections(&$element, $hsid, $form_state) {
    $hs_selection = array(); // hierarchical select selection
    $db_selection = array(); // dropbox selection

    // For the moment only hs_selection
    $hs_selection = self::processGetHsSelection($element);

    return array($hs_selection, $db_selection);
  }

  /**
   * Get the current (flat) selection of the hierarchical select.
   *
   * This selection is updatable by the user, because the values are retrieved
   * from the selects in $element['hierarchical_select']['selects'].
   *
   * @param $element
   *   A hierarchical_select form element.
   * @return array
   *   An array (bag) containing the ids of the selected items in the
   *   hierarchical select.
   */
  protected static function processGetHsSelection($element) {
    $hs_selection = array();
    //$config = _hierarchical_select_inherit_default_config($element['#config']);

    if (!empty($element['#value']['hierarchical_select']['selects'])) {
      //if ($config['save_lineage']) {
        foreach ($element['#value']['hierarchical_select']['selects'] as $key => $value) {
          $hs_selection[] = $value;
        }
      //}
      //else {
        //foreach ($element['#value']['hierarchical_select']['selects'] as $key => $value) {
          //$hs_selection[] = $value;
        //}
        //$hs_selection = _hierarchical_select_hierarchy_validate($hs_selection, $config['module'], $config['params']);

        // Get the last valid value. (Only the deepest item gets saved). Make
        // sure $hs_selection is an array at all times.
        //$hs_selection = ($hs_selection != -1) ? array(end($hs_selection)) : array();
      //}
    }

    return $hs_selection;
  }

}
