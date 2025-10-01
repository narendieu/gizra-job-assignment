<?php

namespace Drupal\Tests\pevb\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Tests the group subscribe CTA on Group nodes.
 *
 * @group pevb
 */
class GroupSubscribeTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'og',
    'pevb',
  ];

  /**
   * A test group node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Group content type.
    $type = NodeType::create([
      'type' => 'group',
      'name' => 'Group',
    ]);
    $type->save();

    // Mark it as an OG group.
    \Drupal::service('og.group_type_manager')->addGroup('node', 'group');

    // Create a group node.
    $this->group = Node::create([
      'type' => 'group',
      'title' => 'Test Group',
    ]);
    $this->group->save();

    // Create and login a test user.
    $this->account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->account->activate();
    $this->account->save();
    $this->drupalLogin($this->account);
  }

  /**
   * Test that subscribe CTA appears for non-members.
   */
  public function testSubscribeCTAForNonMembers() {
    $this->drupalGet($this->group->toUrl());

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hi ' . $this->account->getDisplayName());
    $this->assertSession()->pageTextContains('click here if you would like to subscribe to this group called');
  }

  /**
   * Test that login prompt appears for anonymous users.
   */
  public function testSubscribeCTAForAnonymous() {
    $this->drupalLogout();
    $this->drupalGet($this->group->toUrl());

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Please login to subscribe to this group called');
  }

}