<?php

namespace Drupal\neo4j_friends\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class FriendsPageController {
  public function friendsList(AccountInterface $user, Request $request) {

    $current_uid = \Drupal::currentUser()->id();
    $client = \Drupal::service('neo4j.client');
    $query = _neo4j_friends_get_friends($current_uid);
    $result = $client->run($query);

    $users = [];
    if ($results = _process_collection($result)) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');
      foreach($results as $result_node) {
        $entity_type = $result_node->get('entity_type');
        $entity_id = $result_node->get('entity_id');
        $user_entity = entity_load($entity_type, $entity_id);
        $users[] = $view_builder->view($user_entity, "picture_and_name");
      }
    }

    $markup = empty($users)
      ? t("No friends :'(")
      : render($users);

    return [
      '#markup' => $markup,
      'form' => $builtForm,
    ];
  }

  public function friendRequests(AccountInterface $user, Request $request) {
    $current_uid = \Drupal::currentUser()->id();
    $client = \Drupal::service('neo4j.client');
    $query = _neo4j_friends_pending_requests($current_uid);
    $result = $client->run($query);

    $count = 0;
    $users = [];
    if ($results = _process_collection($result)) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');
      foreach($results as $result_node) {
        $entity_type = $result_node->get('entity_type');
        $entity_id = $result_node->get('entity_id');
        $user_entity = entity_load($entity_type, $entity_id);

        $user = $view_builder->view($user_entity, "picture_and_name");
        $links = $this->generateRequestLinks($entity_id);
        $user['#suffix'] = implode($links);

        $users[] = $user;
        $count++;
      }
    }

    $markup = empty($users)
      ? t("No pending requests.")
      : render($users);

    return [
      '#title' => t('Friend Requests (@count)', array('@count' => $count)),
      '#markup' => $markup,
    ];
  }

  public function friendInvites(AccountInterface $user, Request $request) {
    $current_uid = \Drupal::currentUser()->id();
    $client = \Drupal::service('neo4j.client');
    $query = _neo4j_friends_pending_invites($current_uid);
    $result = $client->run($query);

    $count = 0;
    $users = [];
    if ($results = _process_collection($result)) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');
      foreach($results as $result_node) {
        $entity_type = $result_node->get('entity_type');
        $entity_id = $result_node->get('entity_id');
        $user_entity = entity_load($entity_type, $entity_id);
        $users[] = $view_builder->view($user_entity, "picture_and_name");
        $count++;
      }
    }

    $markup = empty($users)
      ? t("No pending invites.")
      : render($users);

    $builtForm = \Drupal::formBuilder()->getForm('Drupal\neo4j_friends\Form\AddFriend');

    return [
      '#title' => t('Friendship Requests Sent (@count)', array('@count' => $count)),
      '#markup' => $markup,
      'form' => $builtForm,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function generateRequestLinks($entity_id) {

    $current_uid = \Drupal::currentUser()->id();

    $url = Url::fromRoute('user.friend_requests.confirm', array('user' => $current_uid, 'target_uid' => $entity_id));
    $token = \Drupal::csrfToken()->get($url->getInternalPath());
    $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);

    $accept_request = Link::fromTextAndUrl(t('Confirm'), $url);
    $accept_request = $accept_request->toRenderable();
    $accept_request['#attributes'] = ['class' => ['use-ajax']];
    $links[] = render($accept_request);

    return $links;
  }

}
