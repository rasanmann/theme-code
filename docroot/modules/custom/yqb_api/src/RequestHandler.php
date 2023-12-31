<?php

namespace Drupal\yqb_api;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rest\RestResourceConfigInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Acts as intermediate request forwarder for resource plugins.
 */
class RequestHandler extends \Drupal\rest\RequestHandler {

  /**
   * {@inheritdoc}
   */
  public function handle(RouteMatchInterface $route_match, Request $request, RestResourceConfigInterface $_rest_resource_config) {
    // Key checks
    $key = \Drupal::request()->headers->get('X-Key');

    if ($this->validApiKey($key) === false) {
      throw new HttpException(403, 'Invalid API key');
    }

    return parent::handle($route_match, $request, $_rest_resource_config);
  }

    protected function validApiKey($key) {
    // TODO : makes keys editable in Drupal dashboard
    return in_array($key, ['Hsgj75B4hVKvnmad', 'hkLLC6PnnstWbCU5']);
  }
}
