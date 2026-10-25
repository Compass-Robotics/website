<?php

/**
 * @file
 * Post-update functions for the Robotics module.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Utility\UpdateException;
use Drupal\file\FileInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\Image;

/**
 * Preserve migrated contact media fields during configuration import.
 */
function robotics_post_update_align_contact_media_config_uuids(): string {
  $sync_storage = \Drupal::service('config.storage.sync');
  $config_factory = \Drupal::configFactory();
  foreach ([
    'field.storage.crm_contact.media',
    'field.field.crm_contact.person.media',
    'field.field.crm_contact.organization.media',
  ] as $config_name) {
    $synced_config = $sync_storage->read($config_name);
    $active_config = $config_factory->getEditable($config_name);
    if (!$active_config->isNew() && !empty($synced_config['uuid']) && $active_config->get('uuid') !== $synced_config['uuid']) {
      $active_config->set('uuid', $synced_config['uuid'])->save(TRUE);
    }
  }

  \Drupal::entityTypeManager()->getStorage('field_storage_config')->resetCache();
  \Drupal::entityTypeManager()->getStorage('field_config')->resetCache();
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

  return robotics_post_update_restore_sponsor_logo_media();
}

/**
 * Restore sponsor logo references lost during CRM's image-to-media migration.
 */
function robotics_post_update_restore_sponsor_logo_media(): string {
  if (!\Drupal::moduleHandler()->moduleExists('crm')) {
    return (string) t('No sponsor logo repairs needed because CRM is not enabled.');
  }

  $logo_files = [
    'f86659d7-4ea7-4bde-a6af-99a2c3618ff6' => 'e3006e07-dd37-4d41-a94c-8c0b2e79dc62',
    '442258ac-6542-4f86-a1bf-912dfb48f249' => 'e95c4082-05b4-4a53-9518-48d76e90ae64',
    'a74c64ed-e7dd-4572-bf97-d34524f9d535' => 'bcea796c-3d58-4849-ab4e-4b5acbd7c54f',
    'b8a4fa1a-9b53-4643-b147-bca70f37adc3' => 'fc05780f-ad13-47b5-be62-3782afaf50ce',
    'a64e2447-6b3b-4908-bedd-f6547e8a4041' => 'f352ac84-5a8a-49db-b554-1de0b51a0eed',
  ];
  $manager = \Drupal::entityTypeManager();
  $contact_storage = $manager->getStorage('crm_contact');
  $file_storage = $manager->getStorage('file');
  $repairs = [];

  foreach ($logo_files as $contact_uuid => $file_uuid) {
    $contacts = $contact_storage->loadByProperties(['uuid' => $contact_uuid]);
    $contact = reset($contacts);
    if (!($contact instanceof ContentEntityInterface)) {
      continue;
    }
    if (!$contact->hasField('media')) {
      throw new UpdateException((string) t('The Media field is missing on @contact. Run the CRM media migration before retrying this update.', [
        '@contact' => $contact->label(),
      ]));
    }
    if (!$contact->get('media')->isEmpty()) {
      continue;
    }

    $files = $file_storage->loadByProperties(['uuid' => $file_uuid]);
    $file = reset($files);
    if (!($file instanceof FileInterface)) {
      throw new UpdateException((string) t('The original logo file @uuid for @contact is missing. Restore the file entity before retrying this update.', [
        '@uuid' => $file_uuid,
        '@contact' => $contact->label(),
      ]));
    }
    $repairs[] = [$contact, $file];
  }

  if ($repairs === []) {
    return (string) t('No sponsor logo references needed repair.');
  }

  $media_type = MediaType::load('image');
  if (!$media_type || !($media_type->getSource() instanceof Image)) {
    throw new UpdateException('The image media type is missing or does not use an image source. Run the CRM media migration before retrying this update.');
  }
  $source_field = $media_type->getSource()->getSourceFieldDefinition($media_type);
  if (!$source_field) {
    throw new UpdateException('The image media source field is missing. Repair the media type before retrying this update.');
  }
  $source_field_name = $source_field->getName();
  $media_storage = $manager->getStorage('media');

  foreach ($repairs as [$contact, $file]) {
    $media_ids = $media_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', $media_type->id())
      ->condition($source_field_name . '.target_id', $file->id())
      ->sort('mid')
      ->range(0, 1)
      ->execute();
    if ($media_ids) {
      $media = $media_storage->load(reset($media_ids));
    }
    else {
      $media = $media_storage->create([
        'bundle' => $media_type->id(),
        'name' => $file->getFilename(),
        'status' => TRUE,
        $source_field_name => [
          'target_id' => $file->id(),
          'alt' => $contact->label(),
        ],
      ]);
      $media->save();
    }

    $contact->set('media', [[
      'target_id' => $media->id(),
      'primary' => 1,
    ]]);
    $contact->setNewRevision(TRUE);
    $contact->save();
  }

  return (string) t('Restored @count sponsor logo media references.', ['@count' => count($repairs)]);
}
