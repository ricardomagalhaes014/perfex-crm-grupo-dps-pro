<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_104 extends App_module_migration
{
     public function up()
    {
    	$CI = &get_instance();
        if (!$CI->db->table_exists(db_prefix() . 'whiteboardcomments')) {
                $CI->db->query('CREATE TABLE `' . db_prefix() . "whiteboardcomments` (
                `id` int(11) NOT NULL,
                  `whiteboard_id` int(11) NOT NULL,
                  `discussion_type` varchar(10) NOT NULL,
                  `parent` int(11) DEFAULT NULL,
                  `created` datetime NOT NULL,
                  `modified` datetime DEFAULT NULL,
                  `content` text NOT NULL,
                  `rating` TEXT NULL DEFAULT NULL,
                  `contact_id` int(11) DEFAULT '0',
                  `staff_id` int(11) NOT NULL,
                  `fullname` varchar(191) DEFAULT NULL,
                  `file_name` varchar(191) DEFAULT NULL,
                  `file_mime_type` varchar(70) DEFAULT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

              $CI->db->query('ALTER TABLE `' . db_prefix() . 'whiteboardcomments`
                ADD PRIMARY KEY (`id`)');
              
              $CI->db->query('ALTER TABLE `' . db_prefix() . 'whiteboardcomments`
              MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
                
            }

        $whiteboard = db_prefix() . 'whiteboard';
        if (!$CI->db->field_exists('project_id', $whiteboard)) {

                $CI->db->query("ALTER TABLE `" . $whiteboard . "` ADD `project_id` INT(11) DEFAULT '0'  AFTER `staffid`;");

         }
        if (!$CI->db->field_exists('hash', $whiteboard)) {
            $CI->db->query("ALTER TABLE `" . $whiteboard . "` ADD `hash` TEXT NULL DEFAULT NULL   AFTER `whiteboard_content`;");
        }
		  
		
         $CI->db->query("ALTER TABLE `" . $whiteboard . "` CHANGE `whiteboard_content` `whiteboard_content` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");
    }
}