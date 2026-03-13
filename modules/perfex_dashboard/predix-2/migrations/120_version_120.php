<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_120 extends App_module_migration
{
    public function up()
    {

        add_option('predix_ai_reply_agent_name', 'Alex');
        add_option('predix_ai_reply_agent_title', 'Technical Support');
        add_option('predix_ai_reply_agent_staff_id', '');
        add_option('predix_ai_autoreply_on_opening_ticket', '0');

        $CI = &get_instance();

        if (!$CI->db->table_exists(db_prefix() . 'predix_template_categories')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "predix_template_categories` (
  `id` int(11) NOT NULL,
  `category_name` text,
  `category_description` text,
  `is_enabled` int default 1,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

            $CI->db->query('ALTER TABLE `' . db_prefix() . 'predix_template_categories`
  ADD PRIMARY KEY (`id`);');

            $CI->db->query('ALTER TABLE `' . db_prefix() . 'predix_template_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
        }

        if (!$CI->db->table_exists(db_prefix() . 'predix_templates')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "predix_templates` (
  `id` int(11) NOT NULL,
  `template_name` text,
  `perfex_core` int default 0,
  `template_description` text,
  `template_category_id` int,
  `template_icon` text,
  `custom_prompt` text,
  `custom_inputs` text,
  `is_enabled` int default 1,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

            $CI->db->query('ALTER TABLE `' . db_prefix() . 'predix_templates`
  ADD PRIMARY KEY (`id`);');

            $CI->db->query('ALTER TABLE `' . db_prefix() . 'predix_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
        }

        if (!$CI->db->table_exists(db_prefix() . 'predix_documents')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "predix_documents` (
  `id` int(11) NOT NULL,
  `user_id` int,
  `document_name` text,
  `document_description` text,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

            $CI->db->query('ALTER TABLE `' . db_prefix() . 'predix_documents`
  ADD PRIMARY KEY (`id`);');

            $CI->db->query('ALTER TABLE `' . db_prefix() . 'predix_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
        }


        $CI->db->query("INSERT INTO `".db_prefix()."predix_template_categories` (`id`,`category_name`, `category_description`, `is_enabled`, `created_at`) VALUES (3, 'Emails', 'Creative templates for email creation',1,'2023-06-18 01:47:26');");
        $CI->db->query("INSERT INTO `".db_prefix()."predix_template_categories` (`id`,`category_name`, `category_description`, `is_enabled`, `created_at`) VALUES (4, 'Social Media', 'Flash tools for social media content',1,'2023-06-18 01:47:26');");
        $CI->db->query("INSERT INTO `".db_prefix()."predix_template_categories` (`id`,`category_name`, `category_description`, `is_enabled`, `created_at`) VALUES (5, 'Contents', 'Tools for writing creatives for different moods and tasks',1,'2023-06-18 01:47:26');");
        $CI->db->query("INSERT INTO `".db_prefix()."predix_template_categories` (`id`,`category_name`, `category_description`, `is_enabled`, `created_at`) VALUES (6, 'Blog Posts', 'Content for the generating articles, blog post',1,'2023-06-18 01:47:26');");
        $CI->db->query("INSERT INTO `".db_prefix()."predix_template_categories` (`id`,`category_name`, `category_description`, `is_enabled`, `created_at`) VALUES (7, 'Video', 'Video creation tools from idea to script for millions of views',1,'2023-06-18 01:47:26');");
        $CI->db->query("INSERT INTO `".db_prefix()."predix_template_categories` (`id`,`category_name`, `category_description`, `is_enabled`, `created_at`) VALUES (8, 'Perfex', 'Templates for Perfex CRM',1,'2023-06-18 01:47:26');");

        $customPrompt = '[{"input_name":"services","input_label":"Services Provided","input_field_type":"textarea"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Write Proposal', 0,'Crafting a Comprehensive Proposal for Your Exceptional Services',8,'far fa-file-alt','Write a proposal about these services {{services}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"product","input_label":"Product\/Service","input_field_type":"text"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Facebook Ads', 0,4,'fab fa-facebook-square','Write facebook ads about this product or service {{product}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"short_contract_content","input_label":"Short Description Of Contract","input_field_type":"textarea"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Write Contract', 0,'Prepare a Well-Crafted and Customized Contract Agreement to Safeguard Your Interests',8,'fa-solid fa-file-contract','Write a contract about this {{short_contract_content}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"subject","input_label":"Email Subject","input_field_type":"text"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Cold Email', 0,'Create professional cold emails with the help of AI',3,'fa-solid fa-envelope','Write a cold email about this subject {{subject}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"subject","input_label":"Subject","input_field_type":"text"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Welcome Email', 0,'Create welcome emails for your customers',3,'fa-solid fa-envelope-open-text','Write a welcome email about this subject {{subject}} ','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"subject","input_label":"Subject","input_field_type":"text"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Follow-Up Email', 0,'Create professional email follow up with just few clicks',3,'fa-solid fa-reply-all','Write a follow up email about this subject {{subject}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"article_title","input_label":"Article Title","input_field_type":"text"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Article Generator', 0,'Turn a title text into a fully complete high quality article within seconds',5,'fa-solid fa-file-lines','Write a very high-quality article about this subject {{article_title}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"content","input_label":"Content To Rewrite","input_field_type":"textarea"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Content Rewrite', 0,'Take a piece of content and rewrite it to make it more interesting, creative, and engaging',5,'fa-solid fa-square-check','Rewrite this content in the very high-quality way : {{content}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"text","input_label":"Text","input_field_type":"textarea"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Summarize Text', 0,'Summmarize any text in a short and easy to understand concise way',5,'fa-solid fa-file-contract','Summarize this text {{text}} in a short and easy-to-understand concise way','".$customPrompt."', 1,'2023-06-18 03:11:16');");

        $customPrompt = '[{"input_name":"product_name","input_label":"Product Name","input_field_type":"text"},{"input_name":"short_description","input_label":"Product Short Description","input_field_type":"textarea"}]';
        $customPrompt = $CI->db->escape_str($customPrompt);  // Escape the value
        $CI->db->query("INSERT INTO `".db_prefix()."predix_templates` (`template_name`, `perfex_core`, `template_description`, `template_category_id`, `template_icon`, `custom_prompt`, `custom_inputs`, `is_enabled`, `created_at`) VALUES ('Product Desciption', 0,'Create creative product descriptions from examples words',5,'fa-solid fa-info-circle','Write a product description about a product with name {{product_name}} and here is a short description about this product {{short_description}}','".$customPrompt."', 1,'2023-06-18 03:11:16');");
    }
}