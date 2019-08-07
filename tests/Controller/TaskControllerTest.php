<?php

namespace Drupal\small_messages\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the small_messages module.
 */
class TaskControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "small_messages TaskController's controller functionality",
      'description' => 'Test Unit for module small_messages and controller TaskController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests small_messages functionality.
   */
  public function testTaskController() {
    // Check that the basic functions of module small_messages.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
