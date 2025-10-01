<?php

namespace Drupal\pevb\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\GroupTypeManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Route access for the subscribe controller.
 */
class SubscribeAccessCheck implements AccessCheckInterface {

  public function __construct(
    private AccountProxyInterface $currentUser,
    private MembershipManagerInterface $membershipManager,
    private GroupTypeManagerInterface $groupTypeManager,
  ) {}

  public function applies(Route $route) : bool {
    return (bool) $route->getRequirement('_og_subscribe_access');
  }

  public function access(Route $route, RouteMatchInterface $route_match, $account = NULL) : AccessResult {
    $node = $route_match->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return AccessResult::forbidden();
    }

    if (!$this->groupTypeManager->isGroup('node', $node->bundle())) {
      return AccessResult::forbidden();
    }

    if ($this->currentUser->isAnonymous()) {
      return AccessResult::forbidden();
    }

    $is_member = $this->membershipManager->isMember($node, $this->currentUser->getAccount());
    return $is_member ? AccessResult::forbidden() : AccessResult::allowed();
  }
}
