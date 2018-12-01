<?php

namespace Drupal\km_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
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

//    $default_date = DrupalDateTime::createFromTimestamp(strtotime($config->get('date')));
    $default_date =  DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime());
    // TODO: set default values to the one saved from last time (when it gets saved)
    $form['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Importeer vanaf'),
      '#description' => $this->t('Hier kan je opgeven vanaf welke datum er geïmporteerd moet worden. Standaard staat hier de huidige datum ingevuld. DEZE DIEN JE DUS AAN TE PASSEN.'),
      '#default_value' => $default_date,
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


//    $title = $form_state->getValue('path');
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
    // TODO: Save the filled out $date to let it be used on the next run.
//    $datetime_now =  DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime());
    $datetime_set = $form_state->getValue('date');
//    ksm( 'dates now and set', $datetime_now, $datetime_set);
    $batch = $this->generateImageMatchBatch($form_state->getValue('path'), $datetime_set);
    batch_set($batch);
  }


  /*
   * This scan the directory and batches the files found.
   */
  public function generateImageMatchBatch($path, $date) {
//    $path = $form_state->getValue('path');
    // Send some feedback: path and date used.
    $this->messenger()->addStatus($this->t('You specified a path of %path.
      Laatste import was %date.', ['%path' => $path, '%date' => $date]), TRUE);
    // Change the date back to a timestamp so we can compare it to mtime later.
    $last = $date->getTimestamp();


    // Initialize operations to prevent errors on NULL.
    $operations = [];
    // Match all files in directory ending in .jpg or .png. Return assoc array
    // keyed by the name.
    $mask = '/.*\.[png|jpg]/'; # [png|jpg]
    $filelist = file_scan_directory($path, $mask, ['key' => 'name']);
//    ksm($filelist);
    $total_files = count($filelist);

    // Loop through the files and place them in the batches' operation.
    foreach ( $filelist as $file) {
      // Check if file is newer than the specified date.
//      ksm($file, 'file');
      $output_file_time = filemtime($file->uri);
//      ksm('output_file_time', $output_file_time, 'last', $last);
      //  Skip if the file was modified before the last run.
      if ( $output_file_time < $last) {
//        ksm('kleiner', $file->name);
        continue;
      }
      // Call image_match_op($file) from km_admin_module.
      $operations[] = [
        'image_match_op',
        [ $file ],
      ];
    }
//    ksm('operations', $operations);
    $num_files = count( $operations );
    // If there are operations, finish building the batch and return it.
    $batch = [
      'title' => $this->t('Processing a list of @num files (of @total found)', ['@num' => $num_files, '@total' => $total_files]),
      'operations' => $operations,
      'finished' => 'image_match_batch_finished',
      'progress_message' => t('Completed @current of @total' ),
    ];
    return $batch;
  }
}
