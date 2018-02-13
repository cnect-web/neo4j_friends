<?php

namespace Drupal\neo4j_friends\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Element\Table;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;

class FriendsPageController {

  /**
   * List of friends.
   */
  public function friendsList(AccountInterface $user, Request $request) {

    $current_uid = \Drupal::currentUser()->id();
    $query = _neo4j_friends_get_friends($current_uid);

    try {
      $client = \Drupal::service('neo4j.client');
      $result = $client->run($query);
    }
    catch (Exception $e) {
      watchdog_exception('Neo4j Friends', $e);
    }

    if ($results = _process_collection($result)) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');
      foreach($results as $result_node) {
        $entity_type = $result_node->get('entity_type');
        $entity_id = $result_node->get('entity_id');
        $user_entity = entity_load($entity_type, $entity_id);
        $user = $view_builder->view($user_entity, "picture_and_name");

        $friends[] = array(
          'pic_name' => render($user),
        );

        $count++;
      }
    }

    $friends_table = array(
      '#type' => 'table',
      '#empty' => t("No Friends."),
    );

    foreach ($friends as $k => $friend) {
      $friends_table[$k]['pic_name'] = [
        '#markup' => $friend['pic_name'],
        '#wrapper_attributes' => [
          'class' => ['pic-name'],
        ],
      ];
      $friends_table[$k]['links'] = [
        '#markup' => $friend['links'],
        '#wrapper_attributes' => [
          'class' => ['friend-links'],
        ],
      ];
    }

    return [
      '#markup' => render($friends_table)
    ];
  }

  /**
   * List of received requests.
   */
  public function friendRequests(AccountInterface $user, Request $request) {
    $current_uid = \Drupal::currentUser()->id();
    $query = _neo4j_friends_pending_requests($current_uid);

    try {
      $client = \Drupal::service('neo4j.client');
      $result = $client->run($query);
    }
    catch (Exception $e) {
      watchdog_exception('Neo4j Friends', $e);
    }

    $count = 0;
    if ($results = _process_collection($result)) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');
      foreach($results as $result_node) {
        $entity_type = $result_node->get('entity_type');
        $entity_id = $result_node->get('entity_id');
        $user_entity = entity_load($entity_type, $entity_id);

        $user = $view_builder->view($user_entity, "picture_and_name");
        $raw_links = $this->generateRequestLinks($entity_id, ['accept', 'reject']);

        $links = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#attributes' => [
            'class' => [
              'inline',
            ],
          ],
          '#items' => $raw_links,
        ];

        $friends[] = array(
          'pic_name' => render($user),
          'links' => render($links),
        );

        $count++;
      }
    }

    $friends_table = array(
      '#type' => 'table',
      '#empty' => t("No pending requests."),
    );

    foreach ($friends as $k => $friend) {
      $friends_table[$k]['pic_name'] = [
        '#markup' => $friend['pic_name'],
        '#wrapper_attributes' => [
          'class' => ['pic-name'],
        ],
      ];
      $friends_table[$k]['links'] = [
        '#markup' => $friend['links'],
        '#wrapper_attributes' => [
          'class' => ['friend-links'],
        ],
      ];
    }

    return [
      '#title' => t('Friend Requests (@count)', array('@count' => $count)),
      '#markup' => render($friends_table)
    ];
  }

  /**
   * List of sent requests.
   */
  public function friendInvites(AccountInterface $user, Request $request) {
    $current_uid = \Drupal::currentUser()->id();
    $query = _neo4j_friends_pending_invites($current_uid);

    try {
      $client = \Drupal::service('neo4j.client');
      $result = $client->run($query);
    }
    catch (Exception $e) {
      watchdog_exception('Neo4j Friends', $e);
    }

    $count = 0;
    if ($results = _process_collection($result)) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');
      foreach($results as $result_node) {
        $entity_type = $result_node->get('entity_type');
        $entity_id = $result_node->get('entity_id');
        $user_entity = entity_load($entity_type, $entity_id);
        $user = $view_builder->view($user_entity, "picture_and_name");

        $friends[] = array(
          'pic_name' => render($user),
        );

        $count++;
      }
    }

    $friends_table = array(
      '#type' => 'table',
      '#empty' => t("No pending invites."),
    );

    foreach ($friends as $k => $friend) {
      $friends_table[$k]['pic_name'] = [
        '#markup' => $friend['pic_name'],
        '#wrapper_attributes' => [
          'class' => ['pic-name'],
        ],
      ];
      $friends_table[$k]['links'] = [
        '#markup' => $friend['links'],
        '#wrapper_attributes' => [
          'class' => ['friend-links'],
        ],
      ];
    }

    $builtForm = \Drupal::formBuilder()->getForm('Drupal\neo4j_friends\Form\AddFriend');

    return [
      '#title' => t('Friendship Invites Sent (@count)', array('@count' => $count)),
      '#markup' => render($friends_table),
      'form' => $builtForm,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function generateRequestLinks($entity_id, $links = []) {

    $current_uid = \Drupal::currentUser()->id();

    $url = Url::fromRoute('user.friend_requests.confirm', array('user' => $current_uid, 'target_uid' => $entity_id));
    $token = \Drupal::csrfToken()->get($url->getInternalPath());
    $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);

    if (in_array('accept', $links)) {
      $accept_request = Link::fromTextAndUrl(t('Confirm'), $url);
      $accept_request = $accept_request->toRenderable();
      $accept_request['#attributes'] = [
        'class' => [
          'use-ajax',
          'accept-request'
        ]
      ];
      $return_links[] = render($accept_request);
    }

    if (in_array('reject', $links)) {
      $url = Url::fromRoute('user.friend_requests.reject', array('user' => $current_uid, 'target_uid' => $entity_id));
      $reject_request = Link::fromTextAndUrl(t('Delete Request'), $url);
      $reject_request = $reject_request->toRenderable();
      $reject_request['#attributes'] = [
        'class' => [
          'use-ajax',
          'reject-request'
        ]
      ];
      $return_links[] = render($reject_request);
    }

    return $return_links;
  }

}
