<?php

namespace Drupal\neo4j_friends\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "neo4j_friends_friends_block",
 *   admin_label = @Translation("Friends"),
 * )
 */
class Friends extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

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
      ? $this->t("No friends :'(")
      : render($users);

    return [
      '#title' => $this->t('Friends'),
      '#markup' => $markup,
      'form' => $builtForm,
    ];
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
