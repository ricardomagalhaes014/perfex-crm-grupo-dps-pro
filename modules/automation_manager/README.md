# Automation Manager
![GitHub Version](https://img.shields.io/badge/version-1.2.1-brightgreen)
![Perfex clients](https://img.shields.io/badge/clients-8-blue) 


Module for [Perfex crm](https://www.perfexcrm.com/) adding functionalities of automating tasks.

## Table of contents
* [General info](#general-info)
* [Technologies](#technologies)
* [Database schema](#database-schema)
* [Setup](#setup)
* [Autors](#autors)

 ---

## General info
Module adds "Automations" submenu in Utilities tab. In this tab you can add, edit and delete automations. For each automation you can set:

- name
- conditional joining type (and/or)
- rules with parameters
- effects with parameters

Then when task is changes we match the rules with the task. If it matches we apply the effects. For example:
 - when finish date is today, change task status to Finished
 - when task status is Finished or when finish date is today add comment "Good Job!"
 - and much more

We can specify folowing triggers:
 - task status has beed changed to {<i>value</i>}
 - task was created
 - start date is today
 - finish date is today
 - start date has changed
 - finish date has changed
 - priority has been changed to {<i>value</i>}
 <!-- - custom field {<i>additional param</i>} has been changed to {<i>value</i>} -->
 - task is inactive for {<i>value</i>} days

We can specify following effects:
 - change task status to {<i>value</i>}
 - add comment {<i>value</i>}
 - add timer for {<i>value</i>} with comment {<i>additional param</i>}
 - change priority for {<i>value</i>}
 - add person {<i>value </i>} as follower 
 - set person {<i>value </i>} as only follower 
 - add person {<i>value </i>} as assignee
 - set person {<i>value </i>} as only assignee
 - set {<i>value </i>} as content of custom field {<i>additional param</i>}
 - add/remove/remove_all_and_add tag {<i>value </i>}
 - change due date by {<i>value </i>} days
 - add reminder

---

## Technologies
Module adopts default Perfex crm technologies (ver. 2.9.3)

 - Mysql
 - Php ^8.0
 - CodeIgniter ^3.1.11


Additionally module make use of:

- Actions
    - task_comment_added
    - task_comment_updated
    - task_status_changed
- Filters
    - before_update_task
- Helpers
    - get_staff_full_name
    - register_cron_task
    - get_staff_user_id
    - get_instance
    - admin_url
    - is_admin

---

## Database schema
Module add 3 tables:
- automations
    - id
    - name
    - type
    - join
- automation_triggers
    - id
    - type
    - value
    - automation_id
    - additional_argument
- automation_actions
    - id
    - type
    - value
    - automation_id
    - additional_argunent

---

## Setup
Module needs to be in folder with name automation_manager then zip it to archive.

After that module can be instaled through Setup->Modules.

Additional documentation is in documentation folder.

---

## Autors
Copyright © 2024, [Unlimitech Sp. z o.o.](https://unlimitech.pl/). Released under the ??? License.
