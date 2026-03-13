<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_105 extends App_module_migration
{
    public function up()
    {
    	$CI = &get_instance();

		$mindmap = db_prefix() . 'mindmap';

		

		// //v1

     
      if (!$CI->db->field_exists('project_id', $mindmap)) {

            $CI->db->query("ALTER TABLE `" . $mindmap . "` ADD `project_id` INT(11) DEFAULT '0'  AFTER `description`;");

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

   if (!is_dir(MINDMAP_DISCUSSION_ATTACHMENT_FOLDER)) {
      mkdir(MINDMAP_DISCUSSION_ATTACHMENT_FOLDER, 0755);
      fopen(MINDMAP_DISCUSSION_ATTACHMENT_FOLDER . 'index.html', 'w');
      $fp = fopen(MINDMAP_DISCUSSION_ATTACHMENT_FOLDER . 'index.html', 'a+');
      if ($fp) {
        fclose($fp);
      }
    }
    $mindmapcomments = db_prefix() . 'mindmapcomments';

    if (!$CI->db->field_exists('rating', $mindmapcomments)) {
      $CI->db->query("ALTER TABLE `" . $mindmapcomments . "` ADD `rating` TEXT NULL DEFAULT NULL   AFTER `content`;");
      }
    if (!$CI->db->field_exists('hash', $mindmap)) {
      $CI->db->query("ALTER TABLE `" . $mindmap . "` ADD `hash` TEXT NULL DEFAULT NULL   AFTER `mindmap_content`;");
      }

    }
}
?>
