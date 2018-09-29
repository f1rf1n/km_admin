<?php

namespace Drupal\Tests\km_admin\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests \Drupal\km_admin\Plugin\Block\SimpleFormBlock.
 *
 * @group km_admin
 * @group examples
 */
class SimpleFormBlockTest extends BrowserTestBase {

  public static $modules = ['block', 'km_admin'];

  /**
   * Test of paths through the example wizard form.
   */
  public function testSimpleFormBlock() {
    $assert = $this->assertSession();

    // Create user.
    $web_user = $this->drupalCreateUser(['administer blocks']);
    // Login the admin user.
    $this->drupalLogin($web_user);

    $theme_name = \Drupal::config('system.theme')->get('default');

    // Place the block.
    $label = 'SimpleFormBlock-' . $this->randomString();
    $settings = [
      'label' => $label,
      'id' => 'km_admin_simple_form_block',
      'theme' => $theme_name,
    ];
    $this->drupalPlaceBlock('km_admin_simple_form_block', $settings);

    // Verify the block is present.
    $this->drupalGet('');
    $assert->pageTextContains($label);
    $assert->fieldExists('title');

    // And that the form works.
    $edit = [];
    $edit['title'] = 'SimpleFormBlock title example';
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $assert->pageTextContains('You specified a title of SimpleFormBlock title example');
  }

}
