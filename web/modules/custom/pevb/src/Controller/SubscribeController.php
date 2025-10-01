<?php

namespace Drupal\pevb\Controller;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\pevb\Security\LinkToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SubscribeController extends ControllerBase {
  use MessengerTrait;

  public function __construct(private LinkToken $token) {}

  public static function create(ContainerInterface $container): self {
    return new self($container->get('pevb.csrf'));
  }

  public function subscribe(NodeInterface $node, Request $request) : RedirectResponse {
    $account = $this->currentUser();
    $user = \Drupal\user\Entity\User::load($account->id());

    $value = 'og_subscribe:' . $node->id() . ':' . $account->id();
    $token = (string) $request->query->get('token');
    if (!$this->token->validate($value, $token)) {
      $this->messenger()->addError($this->t('Invalid token.'));
      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    }

    $membership = Og::getMembership($node, $user);
    if (!$membership) {
      $membership = Og::createMembership($node, $user);
      $membership->save();
      $this->messenger()->addStatus($this->t('You are now subscribed to %label.', ['%label' => $node->label()]));
    }
   drupal_flush_all_caches();
    return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
  }
}
