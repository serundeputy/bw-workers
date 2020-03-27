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
    $output = shell_exec(
      'php get-releases.php 2020-02-26T01:08:20Z backdrop-contrib TRUE'
    );
    $this->assertStringContainsString(
      'This is a test run and only the first 30 results will be used',
      $output
    );
    $stringTest = strpos($output, 'new releases since') ||
      strpos($output, 'new release since');
    $this->assertTrue($stringTest);
  }
}
