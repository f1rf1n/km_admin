<?php

namespace Drupal\km_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
//use Drupal\file\FileInterface;
//use Drupal\Core\File\FileSystemInterface;

// voor bestandopslaan

/**
 * Implements the SimpleForm form controller.
 *
 * This example demonstrates a simple form with a singe text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ImageImportForm extends FormBase {

  /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Op deze pagina kan je de plaatjes importeren.'),
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importeer!'),
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('Give a directory if it should be different from default ( public://ean).'),
      '#default_value' => 'public://ean',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller. It must be
   * unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'km_admin_image_import_form';
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {


    $title = $form_state->getValue('path');
    //mck todo: check if path is valid, else return closest match.
//    if (strlen($title) < 5) {
//      // Set an error for the form element with a key of "title".
//      $form_state->setErrorByName('title', $this->t('The title must be at least 5 characters long.'));
//    }
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   * For Image import it gets the filelist from the given path and runs through
   * it.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * This scan the directory and processes the files found.
     */
    $path = $form_state->getValue('path');
    $this->messenger()->addStatus($this->t('You specified a path of %path.
      These files were found: ', ['%path' => $path]), TRUE);

    // Match all files in directory ending in .jpg. Return assoc array keyed by
    // the name.
    $mask = '/.*\.jpg/';
    $filelist = file_scan_directory($path, $mask, ['key' => 'name']);

    // Get a piece of the filelist, whole list is too much for direct processing
    // TODO: build a queue and worker for this.
    $stukje = array_slice($filelist, 0, 500);
//    ksm($stukje, 'stukje');
//    ksm( array_keys($filelist), 'keys filelist');

    // Loop through the names and assign them to their products.
    foreach ( $stukje as $file) {
//      $imgUri = file_build_uri("import/default_image.jpg");
      // Create the file in DB and save the file.
      $imgFile = File::Create(['uri' => $file->uri]);
      $imgFile->save();
//      ksm($imgFile);

      // Get the products, by their field_ean value.
      $product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
      $query = \Drupal::entityQuery('commerce_product')
        ->condition('type', 'default')
        ->condition('field_ean', $file->name);
      $pids = $query->execute();
      $products = $product_storage->loadMultiple($pids);
//      ksm($nodes);
      // And set their imagefi
      foreach ($products as $product) {
        $product->field_image = $imgFile;
        $product->save();
      }
    }
  }
}
