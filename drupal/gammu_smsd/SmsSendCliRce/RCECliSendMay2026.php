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


final class RCECliSendMay2026 extends BrowserTestBase {

  protected $defaultTheme = 'stark';
  protected static $modules = ['gammu_smsd'];
  protected $strictConfigSchema = FALSE;
  protected string $target_filename;
  protected string $raw_file_path;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $dbinfo = Database::getConnectionInfo('default');
    $this->config('gammu_smsd.settings')
      // Set a token to be used latter.
      ->set('gammu_token', 'abcd12345')
      // gammu_smsd must be configured to use CLI executor for RCE exploit path.
      ->set('gammu_cli', TRUE)
      // Baseline config for module operations.
      ->set('gammu_dbhost', $dbinfo['default']['host'])
      ->set('gammu_dbengine', $dbinfo['default']['driver'])
      ->set('gammu_dbport', $dbinfo['default']['port'])
      ->set('gammu_dbname', $dbinfo['default']['database'])
      ->set('gammu_dbuser', $dbinfo['default']['username'])
      ->set('gammu_dbpass', $dbinfo['default']['password'])
      ->save();

    $random = $this->getRandomGenerator()->machineName(6);
    $this->target_filename = 'vulnerable' . $random . '.txt';
    $this->raw_file_path= \Drupal::root() . "/" . $this->target_filename;


  }

  /**
   *  Use number field to exec `echo` and create file on disk through
   *  the SMS send form.
   */
  public function testRceNumberFieldWeb(): void {
    $this->assertFileDoesNotExist($this->raw_file_path);

    $user = $this->createUser(['administer gammu']);
    $this->drupalLogin($user);

    $this->drupalGet(Url::fromRoute('gammu_smsd.admin.canonical')->toString());
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm(
      [
        'gammu_number' => "echo 'RCE Success' > $this->target_filename",
        'text' => 'abc123',
      ],
      'Send SMS'
    );

    $this->assertFileDoesNotExist($this->raw_file_path);
  }

  /**
   *  Use number field to exec `echo` and create file on disk through REST API.
   */
  public function testRceNumberFieldRestApi(): void {
    $this->assertFileDoesNotExist($this->raw_file_path);

    $target_url = Url::fromRoute('gammu_smsd.api.send')->setAbsolute(TRUE)->toString();
    $payload = [
      'number' => "echo 'RCE Success' > $this->target_filename",
      'text' => 'abc123',
    ];
    $headers = [
      "Authorization" => 'abcd12345',
    ];

    $this->getHttpClient()->request(
      'POST',
      $target_url,
      [
        'headers' => $headers,
        'json' => $payload,
      ],
    );

    $this->assertFileDoesNotExist($this->raw_file_path);
  }

  /**
   *  Use text field to create file on disk through the SMS send form.
   *  using backtick escape.
   */
  public function testRceTextFieldWeb(): void {
    $this->assertFileDoesNotExist($this->raw_file_path);

    $user = $this->createUser(['administer gammu']);
    $this->drupalLogin($user);

    $this->drupalGet(Url::fromRoute('gammu_smsd.admin.canonical')->toString());
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm(
      [
        'gammu_number' => "123456789",
        'text' => '`touch $this->target_filename`',
      ],
      'Send SMS'
    );

    $this->assertFileDoesNotExist($this->raw_file_path);
  }

  /**
   *  Use text field to create file on disk through the SMS send form.
   *  using backtick escape.
   */
  public function testRceTextFieldestApi(): void {

    $this->assertFileDoesNotExist($this->raw_file_path);

    $target_url = Url::fromRoute('gammu_smsd.api.send')->setAbsolute(TRUE)->toString();
    $payload = [
      'number' => '123456789',
      'text' => " `touch $this->target_filename`",
    ];
    $headers = [
      "Authorization" => 'abcd12345',
    ];

    $this->getHttpClient()->request(
      'POST',
      $target_url,
      [
        'headers' => $headers,
        'json' => $payload,
      ],
    );

    $this->assertFileDoesNotExist($this->raw_file_path);
  }

}
