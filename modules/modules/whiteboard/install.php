<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

add_option('staff_members_create_inline_whiteboard_group', 1);

if (!$CI->db->table_exists(db_prefix() . 'whiteboard')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "whiteboard` (
    `id` int(11) NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `description` text,
    `staffid` int(11) DEFAULT '0' ,
    `whiteboard_group_id` int(11) DEFAULT '0' ,
    `whiteboard_content` text,
    `dateadded` datetime DEFAULT NULL,
    `dateaupdated` datetime DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'whiteboard`
    ADD PRIMARY KEY (`id`),
    ADD KEY `staffid` (`staffid`),
    ADD KEY `whiteboard_group_id` (`whiteboard_group_id`);');
  
  $CI->db->query('ALTER TABLE `' . db_prefix() . 'whiteboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');

  $CI->db->insert(db_prefix() . 'whiteboard', array(
      'title' => 'Sales',
      'description' => 'Sales process',
      'staffid' => 0,
      'whiteboard_group_id' => 0,
      'whiteboard_content' => '{"colors":{"primary":"hsla(0, 0%, 0%, 1)","secondary":"hsla(0, 0%, 100%, 1)","background":"transparent"},"position":{"x":0,"y":0},"scale":1,"shapes":[{"className":"Ellipse","data":{"x":129,"y":62,"width":165,"height":155,"strokeWidth":5,"strokeColor":"hsla(0, 0%, 0%, 1)","fillColor":"hsla(0, 0%, 100%, 1)"},"id":"999d2c0b-a5cb-57d3-5e8b-3eac11d4e52c"},{"className":"Ellipse","data":{"x":388,"y":74,"width":158,"height":152,"strokeWidth":5,"strokeColor":"hsla(0, 0%, 0%, 1)","fillColor":"hsla(0, 0%, 100%, 1)"},"id":"a053e7f4-6f96-f84e-0171-923f97396ba1"},{"className":"Ellipse","data":{"x":692,"y":87,"width":161,"height":150,"strokeWidth":5,"strokeColor":"hsla(0, 0%, 0%, 1)","fillColor":"hsla(0, 0%, 100%, 1)"},"id":"72de307f-7803-0aab-1682-ffacec654476"}],"backgroundShapes":[],"imageSize":{"width":"infinite","height":"infinite"}}',
      'dateadded' => date('Y-m-d H:i:s'),
  ));
}

if (!$CI->db->table_exists(db_prefix() . 'whiteboard_groups')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "whiteboard_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'whiteboard_groups`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'whiteboard_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}



