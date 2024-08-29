<?php

declare(strict_types=1);

namespace Drupal\investigation_documents\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\Entity\File;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\investigation_documents\Entity\InvestigationDocuments;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use function array_keys;

/**
 * Represents Investigation Document Post Create records as resources.
 *
 * @RestResource (
 *   id = "investigation_document_post_create_resource",
 *   label = @Translation("Investigation Document Post Create"),
 *   uri_paths = {
 *     "canonical" = "/rest/investigation/document/post/{id}",
 *     "create" = "/rest/investigation/document/post"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively, you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
final class InvestigationDocumentPostCreateResource extends ResourceBase {

  /**
   * The key-value storage.
   */
  private readonly KeyValueStoreInterface $storage;

 /**
   * The current user.
   */
  private AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array                    $configuration,
                             $plugin_id,
                             $plugin_definition,
    array                    $serializer_formats,
    LoggerInterface          $logger,
    KeyValueFactoryInterface $keyValueFactory,
    AccountProxyInterface    $currentUser
  )
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('investigation_document_post_create_resource');
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self
  {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('keyvalue'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post(array $data): ModifiedResourceResponse
  {

    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    // Validate required fields.
    if (empty($data['stepId']) || empty($data['investigationId']) || empty($data['fid'])) {
      throw new BadRequestHttpException('Missing required fields');
    }

        // Load the file entity using the provided fid.
    $file_entity = File::load($data['fid']);
    if (!$file_entity) {
      throw new NotFoundHttpException('File not found');
    }

    // Create new InvestigationDocuments entity.
    $investigation_document = InvestigationDocuments::create([
      'label' => $data['label'] ?? $file_entity->getFilename(),
      'notes' => $data['notes'] ?? '',
      'stepId' => $data['stepId'],
      'investigationId' => $data['investigationId'],
      'visible' => $data['visible'] ?? TRUE,
      'file' => [
        'target_id' => $file_entity->id(),
      ],
    ]);

    $investigation_document->save();

    return new ModifiedResourceResponse(['message' => 'Investigation Document created successfully', 'id' => $investigation_document->id()], 201);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method !== 'POST') {
      $route->setRequirement('id', '\d+');
    }
    return $route;
  }

  /**
   * Returns next available ID.
   */
  private function getNextId(): int {
    $ids = \array_keys($this->storage->getAll());
    return count($ids) > 0 ? max($ids) + 1 : 1;
  }

}