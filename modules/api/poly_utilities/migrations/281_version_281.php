<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_281 extends App_module_migration
{
    public function up()
    {
        // Perform database upgrade here
    }
    public function down()
    {
        // Perform database downgrade here
    }
    public function logChanged()
    {
        /*
        -------- 2.8.1 (December 23, 2024) --------
        P/S: In case you encounter any conflicts during usage, please leave feedback or contact me at polyxgo@gmail.com. I will support you right away! Thanks.
        NEW
        - Right-click menu with multi-level access to system components. Supports role-based permissions. Each staff account or role will see a menu customized to the tools they use.
        - Todo input popup now only closes when clicking the Close buttons. This prevents accidental closure and loss of input content when clicking outside the popup while editing.

        CHANGED
        - Adjusted the popup height when viewing, editing, or adding new Todo items to better fit the screen height.

        FIXED
        - Fixed a permission issue where Settings > Users can access PolyUtilities was not specified.
        - Fixed incorrect display positions of the quick-create labels for contracts and proposals on the project details page.
    
        TASKS in PROGRESS
        - Support for managing, categorizing, and creating a list of task templates for new project creation. This eliminates the need to recreate generic tasks for most projects, such as gathering client requirements, feature lists, design, feature integration, handover, etc.
        */
    }
}
