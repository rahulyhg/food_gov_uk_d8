<?php

namespace Drupal\fsa_notify;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class FsaNotifyStorage {

  protected $themed = [];

  // due do large volume of data (entity loads)
  // system tends to crash -- out of memory
  // therefore need batching / chunking
  // it returns an array which has keys as uid and value as string of email text body
  // if no more digests are left, it return empty array
  public function getAllType(string $type, int $batch_size = 1000) {
  
    $query = \Drupal::entityQuery('user');
    $query->condition('uid', 0, '>');
    $query->condition('status', 1);
    $query->condition('field_notification_method', $type);
    $query->Exists('field_notification_cache');
    $query->range(0, $batch_size);
    // $query->sort('uid');
    $uids = $query->execute();
  
    $theme_map = [
      'sms' => 'themeSms',
      'daily' => 'themeDaily',
      'weekly' => 'themeWeekly',
    ];
    $themer = $theme_map[$type];
  
    $assembly_map = [
      'sms' => function (&$items) {return $items;},
      'daily' => function (&$items) {return implode("\n", $items);},
      'weekly' => function (&$items) {return implode("\n", $items);},
    ];
    $assembler = $assembly_map[$type];
  
    $notifications = [];
    foreach ($uids as $uid) {
      $u = User::load($uid);
      $nids = $u->field_notification_cache->getValue();
      $nids = array_map(function ($nid) {return (int) $nid['target_id'];}, $nids);
      $nids = array_unique($nids);
      sort($nids, SORT_NUMERIC);
      $items = [];
      foreach ($nids as $nid) {
        $alert = Node::load($nid);
        $key = "$type|$nid";
        if (empty($this->themed[$key])) { // cahing
          $this->themed[$key] = $this->$themer($alert);
        }
        $items[] = $this->themed[$key];
      }
      $data = $assembler($items);
      $notifications[$uid] = $data;
    }

    // try to kill caches
    // to prevent succumb to pressures of the memory
    \Drupal::entityManager()->getStorage('node')->resetCache();
    \Drupal::entityManager()->getStorage('user')->resetCache();
  
    return $notifications;
  }

  private function themeSms($alert) {
    $link = Url::fromRoute('entity.node.canonical', ['node' => $alert->id()], ['absolute' => TRUE]);
    $link = $link->toString();
    return $link;
  }
  
  private function themeDaily($alert) {
    $title = $alert->getTitle();
    $line1 = sprintf('%s', $title);
  
    $link = Url::fromRoute('entity.node.canonical', ['node' => $alert->id()], ['absolute' => TRUE]);
    $link = $link->toString();
    $more = t('Read more');
    $line2 = sprintF('%s %s', $more, $link);
  
    $item = "$line1\n$line2\n";
    return $item;
  }
  
  private function themeWeekly($alert) {
    $title = $alert->getTitle();
    $created = $alert->getCreatedTime();
    $created = \Drupal::service('date.formatter')->format($created, 'custom', 'F j, Y');
    $line1 = sprintf('%s: %s', $created, $title);
  
    $link = Url::fromRoute('entity.node.canonical', ['node' => $alert->id()], ['absolute' => TRUE]);
    $link = $link->toString();
    $more = t('Read more');
    $line2 = sprintF('%s %s', $more, $link);
  
    $item = "$line1\n$line2\n";
    return $item;
  }

  // store alert to all relevant users
  // by matching allergy taxonomy terms
  public function store(Node $alert) {
  
    $nid = $alert->id();
    $allergens = $alert->field_alert_allergen->getValue();
    $allergens = array_map(function ($a) {return $a['target_id'];}, $allergens);
  
    $query = \Drupal::entityQuery('user');
    $query->condition('uid', 0, '>');
    $query->condition('status', 1);
    $query->condition('field_notification_method', 'none', '!=');
    $query->condition('field_notification_allergys', $allergens, 'in');
    // $query->sort('uid');
    $uids = $query->execute();
  
    foreach ($uids as $uid) {
      $u = User::load($uid);
      $u->field_notification_cache[] = $nid;
      $u->save();
    }

    \Drupal::entityManager()->getStorage('user')->resetCache();
  
  }

  // clear cache of notifications for particular user
  public function reset(User $user, bool $save = TRUE) {
    $user->field_notification_cache = NULL;
    if ($save) {
      $user->save();
    }
  }

}
