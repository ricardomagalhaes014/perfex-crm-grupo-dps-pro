<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

add_option('staff_members_create_inline_mindmap_group', 1);

if (!$CI->db->table_exists(db_prefix() . 'mindmap')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mindmap` (
    `id` int(11) NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `description` text,
    `staffid` int(11) DEFAULT '0' ,
    `mindmap_group_id` int(11) DEFAULT '0' ,
    `mindmap_content` text,
    `dateadded` datetime DEFAULT NULL,
    `dateaupdated` datetime DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'mindmap`
    ADD PRIMARY KEY (`id`),
    ADD KEY `staffid` (`staffid`),
    ADD KEY `mindmap_group_id` (`mindmap_group_id`);');
  
  $CI->db->query('ALTER TABLE `' . db_prefix() . 'mindmap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');

  $CI->db->insert(db_prefix() . 'mindmap', array(
      'title' => 'Sales',
      'description' => 'Sales process',
      'staffid' => 0,
      'mindmap_group_id' => 0,
      'mindmap_content' => '{"nodeData":{"id":"root","topic":"Sales process","root":true,"children":[{"topic":"1. Initital contact","id":"360d8067c3cc3810","direction":0,"style":{"fontSize":"15"},"expanded":true,"children":[{"topic":"Cold call","id":"360ef1e3318798a3"},{"topic":"Log call","id":"360ef3dc879d78fe"}]},{"topic":"5.Negotiation","id":"360d80a4bcea0675","direction":1,"style":{"background":"#27ae61","color":"#ecf0f1"},"expanded":true,"children":[{"topic":"Agreement","id":"360f24a7e876629b"},{"topic":"Price","id":"360f259fa25b8317"},{"topic":"Conditions","id":"360f2648418e21b7"}]},{"topic":"2. BANT","id":"360d80b74c078666","direction":0,"style":{"background":"#c0392c","color":"#ecf0f1","fontSize":"15"},"expanded":true,"children":[{"topic":"Budget","id":"360f0024dd662756"},{"topic":"Authority","id":"360f013f626125b1"},{"topic":"Need","id":"360f01cdacc46787"},{"topic":"Timeframe","id":"360f0268b333cddb"}]},{"topic":"6.Deal","id":"360d80c99c9c8578","direction":1,"style":{"background":"#3298db","color":"#ecf0f1"},"expanded":true,"children":[{"topic":"Price","id":"360f2f94b7617209","expanded":true,"children":[{"topic":"Deal","id":"360f32575ead64f0"},{"topic":"No deal","id":"360f35924cb070ea"}]}]},{"topic":"3. Investigation","id":"360d80dc3965b973","direction":0,"style":{"color":"#ecf0f1","background":"#34495e","fontSize":"15"},"expanded":true,"children":[{"topic":"Need","id":"360f0b5fa80de3af"}]},{"topic":"4. Proposal","id":"360d8109885c4059","direction":0,"style":{"background":"#f39c11","color":"#ecf0f1","fontSize":"15","fontWeight":"bold"},"expanded":true,"children":[{"topic":"Offer","id":"360f128176e23b5b"},{"topic":"Budget","id":"360f12f6243ed6d5"},{"topic":"Timeframe","id":"360f136731ae8f86"}]}],"expanded":true},"linkData":{}}',
      'dateadded' => date('Y-m-d H:i:s'),
  ));
}


    
if (!$CI->db->table_exists(db_prefix() . 'mindmap_groups')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mindmap_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mindmap_groups`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mindmap_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
  $mindmap = db_prefix() . 'mindmap';
    //v1

    if (!$CI->db->field_exists('external_url', $mindmap)) {

            $CI->db->query("ALTER TABLE `" . $mindmap . "` ADD `external_url` TEXT NULL DEFAULT NULL   AFTER `description`;");

        }
        if (!$CI->db->field_exists('project_id', $mindmap)) {

            $CI->db->query("ALTER TABLE `" . $mindmap . "` ADD `project_id` INT(11) DEFAULT '0'  AFTER `external_url`;");

        }


  if (!$CI->db->table_exists(db_prefix() . 'mindmapcomments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mindmapcomments` (
    `id` int(11) NOT NULL,
      `mindmap_id` int(11) NOT NULL,
      `discussion_type` varchar(10) NOT NULL,
      `parent` int(11) DEFAULT NULL,
      `created` datetime NOT NULL,
      `modified` datetime DEFAULT NULL,
      `content` text NOT NULL,
      `contact_id` int(11) DEFAULT '0',
      `staff_id` int(11) NOT NULL,
      `fullname` varchar(191) DEFAULT NULL,
      `file_name` varchar(191) DEFAULT NULL,
      `file_mime_type` varchar(70) DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'mindmapcomments`
    ADD PRIMARY KEY (`id`)');
  
  $CI->db->query('ALTER TABLE `' . db_prefix() . 'mindmapcomments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
    
}