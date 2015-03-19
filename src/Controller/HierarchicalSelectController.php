<?php

/**
 * @file
 * Contains \Drupal\hsd8\Controller\HierarchicalSelectController.
 */

namespace Drupal\hsd8\Controller;

use Drupal\system\Controller\FormAjaxController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\hsd8\Ajax\HsResultsCommand;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\NestedArray;


class HierarchicalSelectController extends FormAjaxController {

  public function ajaxOperation(Request $request) {

    $form_parents = explode('/', $request->query->get('element_parents'));

    /** @var $ajaxForm \Drupal\system\FileAjaxForm */
    $ajaxForm = $this->getForm($request);
    $form = $ajaxForm->getForm();
    $form_state = $ajaxForm->getFormState();
    //$commands = $ajaxForm->getCommands();

    $this->formBuilder->processForm($form['#form_id'], $form, $form_state);
    $element = NestedArray::getValue($form, $form_parents);

    // Render the output.
    $output = \Drupal::service('renderer')->render($element);

    $response = new AjaxResponse();
    $response->addCommand(new HsResultsCommand($output));
    return $response;
  }

}
