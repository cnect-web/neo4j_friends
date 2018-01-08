<?php

namespace Drupal\neo4j_friends\Form;

use Behat\Mink\Exception\Exception;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GraphAware\Neo4j\Client\Client;

class AddFriend extends FormBase {

  public function getFormId() {
    return 'neo4j_friends_add_friend_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['control'] = [
      '#title' => $this->t('+'),
      '#type' => 'details',
      '#tree' => FALSE,
    ];

    $form['control']['messages'] = [
      '#markup' => '<div id="add-friend-result"></div>',
    ];

    $form['control']['search_user'] = [
      '#title' => $this->t("Add Friend"),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#attributes' => [
        'placeholder' => $this->t('Search user'),
      ],
      '#selection_settings' => array(
        'include_anonymous' => FALSE,
      ),
    ];

    $form['control']['search'] = [
      '#value' => $this->t("Add Friend"),
      '#type' => 'button',
      '#attributes' => [
        'class' => [
          'btn',
          'btn-md',
          'btn-primary',
          'use-ajax-submit'
        ],
      ],
      '#ajax' => array(
        'callback' => '::addFriend',
      ),
    ];

    return $form;
  }

  public function addFriend(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Check if User or email exists or not
    $current_uid = \Drupal::currentUser()->id();

    $friend_uid = $form_state->getValue('search_user');

    $client = \Drupal::service('neo4j.client');
    $query = _neo4j_friends_add_friend($current_uid, $friend_uid);
    $result = $client->run($query);
    $query_stats = $result->summarize()->updateStatistics();

    if($query_stats->relationshipsCreated() == 0) {
      drupal_set_message($this->t("You already made a friend request for this user."), 'warning');
    }
    else {
      drupal_set_message($this->t("Request made."), 'status');
    }

    $status_messages = array('#type' => 'status_messages');
    $messages = \Drupal::service('renderer')->renderRoot($status_messages);
    if (!empty($messages)) {
      $response->addCommand(new PrependCommand('#add-friend-result', $messages));
    }
    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
