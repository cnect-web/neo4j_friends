<?php

namespace Drupal\neo4j_friends\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\neo4j_friends\Controller\EntityAutocompleteController::handleAutocomplete');
    }
  }

}
