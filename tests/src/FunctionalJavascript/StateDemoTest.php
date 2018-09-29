<?php

namespace Drupal\Tests\km_admin\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Url;

/**
 * @group km_admin
 *
 * @ingroup km_admin
 */
class StateDemoTest extends WebDriverTestBase {

  /**
   * Our module dependencies.
   *
   * @var string[]
   */
  static public $modules = ['km_admin'];

  /**
   * Functional tests for the StateDemo example form.
   */
  public function testStateForm() {
    // Visit form route.
    $route = Url::fromRoute('km_admin.state_demo');
    $this->drupalGet($route);

    // Get Mink stuff.
    $page = $this->getSession()->getPage();

    // Verify we can find the diet restrictions textfield, and that by default
    // it is not visible.
    $this->assertNotEmpty($checkbox = $page->find('css', 'input[name="diet"]'));
    $this->assertFalse($checkbox->isVisible(), 'Diet restrictions field is not visible.');

    // Check the needs special accommodation checkbox.
    $page->checkField('needs_accommodation');

    // Verify the textfield is visible now.
    $this->assertTrue($checkbox->isVisible(), 'Diet restrictions field is visible.');
  }

}
