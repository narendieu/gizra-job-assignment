<?php

namespace Drupal\pevb\Plugin\EntityViewBuilder;
use Drupal\pevb\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\MembershipManager;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract {

  
public function __construct(
  array $configuration,
  $plugin_id,
  $plugin_definition,
  EntityTypeManagerInterface $entity_type_manager,
  MembershipManager $membershipManager,
  GroupTypeManager $groupTypeManager 
) {
  //parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
  @$this->membershipManager = $membershipManager;
  @$this->groupTypeManager = $groupTypeManager;
}

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
  return new self(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('entity_type.manager'),          
    $container->get('og.membership_manager'),        
    $container->get('og.group_type_manager')         
  );
}



  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {

    // The node's label.


    
    if (!$entity instanceof NodeInterface) {
      return [];
    }

    if (!$this->groupTypeManager->isGroup('node', $entity->bundle())) {
      return [];
    }
    
    $account = \Drupal::currentUser();
$can_offer = $account->isAuthenticated()
  && !$this->membershipManager->isMember($entity, $account->getAccount());

if (!$can_offer) {
  if ($account->isAuthenticated()) {
    // Logged-in user but already subscribed.
    $build['og_group_subscribe_cta'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['og-group-subscribe-cta']],
      'text' => [
        '#type' => 'inline_template',
        '#template' => 'Hi {{ name }}, you are already subscribed to this group called {{ label }}.',
        '#context' => [
          'name' => $account->getDisplayName(),
          'label' => $entity->label(),
        ],
      ],
      '#weight' => -100,
    ];
  }
  else {
    // Anonymous user → show login message.
    $build['og_group_subscribe_cta'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['og-group-subscribe-cta']],
      'text' => [
        '#type' => 'inline_template',
        '#template' => 'Please login to subscribe to this group called {{ label }}.',
        '#context' => [
          'label' => $entity->label(),
        ],
      ],
      '#weight' => -100,
    ];
  }

  return $build;
}
    
    $value = 'og_subscribe:' . $entity->id() . ':' . $account->id();
    $token = \Drupal::service('pevb.csrf')->get($value);
    $url = Url::fromRoute('pevb.subscribe', [
      'node' => $entity->id(),
    ], [
      'query' => ['token' => $token],
    ]);
    $link = Link::fromTextAndUrl($this->t('click here'), $url)->toRenderable();


    $build['og_group_subscribe_cta'] = [
  '#type' => 'container',
  '#attributes' => ['class' => ['og-group-subscribe-cta']],
  'text' => [
    '#type' => 'inline_template',
    '#template' => 'Hi {{ name }}, {{ link }} if you would like to subscribe to this group called {{ label }}.',
    '#context' => [
      'name' => $account->getDisplayName(),
      'label' => $entity->label(),
      'link' => $link, // Pass the render array directly
    ],
  ],
  '#weight' => -100,
];

    return $build;
  }

  /**
   * Build Teaser view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildTeaser(array $build, NodeInterface $entity) {
    $media = $this->getReferencedEntityFromField($entity, 'field_featured_image');
    $image = $media instanceof MediaInterface ? $this->buildImageStyle($media, 'card', 'field_media_image') : [];
    $title = $entity->label();
    $url = $entity->toUrl();
    $summary = $this->buildProcessedTextTrimmed($entity, 'field_body');
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = $this->buildElementNewsTeaser(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $build[] = $element;

    return $build;
  }

  /**
   * Build "Featured" view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFeatured(array $build, NodeInterface $entity) {
    $media = $this->getReferencedEntityFromField($entity, 'field_featured_image');
    $image = $media instanceof MediaInterface ? $this->buildImageStyle($media, 'card', 'field_media_image') : NULL;
    $title = $entity->label();
    $url = $entity->toUrl();
    $summary = $this->buildProcessedText($entity, 'field_body');
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');

    $element = $this->buildElementNewsTeaserFeatured(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $build[] = $element;

    return $build;
  }

  /**
   * Build "Search index" view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildSearchIndex(array $build, NodeInterface $entity) {
    $element = $this->buildElementSearchResult(
      $this->t('News'),
      $entity->label(),
      $entity->toUrl(),
      $this->buildProcessedText($entity, 'field_body'),
      $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date')
    );

    $build[] = $element;

    return $build;
  }

}
