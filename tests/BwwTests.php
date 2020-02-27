<?php
/**
 * @file
 * Initial test suite for Backdrop Drush Extension.
 */

use PHPUnit\Framework\TestCase;

class BwwTests extends TestCase {
  /**
   * Test drush ctl command.
   */
  public function testGetNewReleasesSinceDate() {
    $output = shell_exec('php get-releases.php 2020-02-26T01:08:20Z TRUE');
    $this->assertStringContainsString(
      'This is a test run and only the first 30 results will be used',
      $output
    );
    $this->assertStringContainsString(
      'new releases since 2020-02-26T01:08:20Z',
      $output
    );
  }
}
