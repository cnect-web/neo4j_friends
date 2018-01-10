<?php

namespace Drupal\neo4j_friends\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class FriendRequestController {

  public function acceptRequest(AccountInterface $user, Request $request) {
    $path = substr($request->getPathInfo(), 1);
    $path_args = explode('/', $path);

    $op = $path_args[4];
    $target_uid = $path_args[5];

    switch ($op) {
      case 'confirm':
        $client = \Drupal::service('neo4j.client');
        $query = _neo4j_friends_accept_request($user->id(), $target_uid);
        $result = $client->run($query);
        break;
    }

    return TRUE;
  }

  public function rejectRequest(AccountInterface $user, Request $request) {
    $path = substr($request->getPathInfo(), 1);
    $path_args = explode('/', $path);

    $op = $path_args[4];
    $target_uid = $path_args[5];

    switch ($op) {
      case 'reject':
        $client = \Drupal::service('neo4j.client');
        $query = _neo4j_friends_accept_request($user->id(), $target_uid);
        $result = $client->run($query);
        break;
    }

    return TRUE;
  }

}
