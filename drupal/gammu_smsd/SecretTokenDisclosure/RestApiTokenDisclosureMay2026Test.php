<?php

/*
 * SPDX-FileCopyrightText: (C) 2026 Conrad Lara <cmlara@cmlara.com>
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Cmlara\Poc\Drupal\gammu_smsd;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;


final class RestApiTokenDisclosureMay2026Test extends BrowserTestBase {

  protected $defaultTheme = 'stark';
  protected static $modules = ['gammu_smsd', 'help'];
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('gammu_smsd.settings')
      ->set('gammu_token', 'abcd12345')
      ->save();
  }

  /**
   *  Demonstrate that the help page displays the REST API
   *  authorization token to a user with only the 'access help pages'
   *  permission.
   */
  public function testRceNumberFieldWeb(): void {

    $user = $this->createUser(['access help pages']);
    $this->drupalLogin($user);

    $this->drupalGet('admin/help/gammu_smsd');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Authorization: abcd12345');
  }

}
