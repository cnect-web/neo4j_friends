<?php

namespace Drupal\neo4j_friends\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class FriendsPageController {
  public function friendsList(AccountInterface $user, Request $request) {
    $build = [
      '#markup' => t('Hello World 1!'),
    ];
    return $build;
  }

  public function friendRequests(AccountInterface $user, Request $request) {
    $build = [
      '#markup' => t('Hello World 2!'),
    ];
    return $build;
  }

  public function friendInvites(AccountInterface $user, Request $request) {
    $build = [
      '#markup' => t('Hello World 3!'),
    ];
    return $build;
  }
}
