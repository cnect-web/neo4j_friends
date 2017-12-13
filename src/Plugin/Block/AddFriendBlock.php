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
 *   id = "neo4j_friends_add_friend_block",
 *   admin_label = @Translation("Add Friend"),
 * )
 */
class AddFriendBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $builtForm = \Drupal::formBuilder()->getForm('Drupal\neo4j_friends\Form\AddFriend');

    return [
      '#title' => $this->t('Add a Friend'),
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
