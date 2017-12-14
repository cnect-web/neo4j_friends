<?php

namespace Drupal\neo4j_friends\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "neo4j_friends_pending_friendship_requests_block",
 *   admin_label = @Translation("Pending Friend Requests"),
 * )
 */
class PendingFriendshipRequests extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

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
      ? $this->t("No pending requests.")
      : render($users);

    return [
      '#title' => $this->t('Friend Requests (@count)', array('@count' => $count)),
      '#markup' => $markup,
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

/*
    $url = Url::fromRoute('entity.user.edit_form', array('user' => $entity_id));
    $reject_request = Link::fromTextAndUrl(t('Delete Request'), $url);
    $reject_request = $reject_request->toRenderable();
    $reject_request['#attributes'] = [
      'class' => ['use-ajax']
    ];
    $links[] = render($reject_request);
*/
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if (!\Drupal::currentUser()->isAnonymous()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
