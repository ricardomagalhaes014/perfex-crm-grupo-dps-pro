<div>
<span @click.stop="handleAdd()" class="btn btn-primary pull-left display-block tw-mr-2">
        <i class="fa-regular fa-plus tw-mr-1"></i>&nbsp<?php echo _l('poly_utilities_projects_name_pattern_button_create_new') ?>
    </span>
    <a href="<?php echo admin_url('projects');?>" class="btn btn-primary pull-right display-block">
        <i class="fa-solid fa-diagram-project fa-fw tw-mr-1"></i>&nbsp<?php echo _l('projects') ?>
    </a>
    <div class="clearfix"></div>
    <div><?php echo poly_utilities_common_helper::display_message_help(_l('poly_utilities_projects_name_patterns_message_help'))?></div>
</div>
<div class="dataTables_wrapper">
    <div style="overflow-x: scroll;" class="table-responsive">
        <table class="table">
            <thead>
                <th><?php echo _l('poly_utilities_projects_field_name') ?></th>
                <th class="text-center"><?php echo _l('poly_utilities_projects_field_note') ?></th>
                <th class="text-center"><?php echo _l('poly_utilities_projects_field_active') ?></th>
                <th class="text-center">&nbsp;</th>
            </thead>
            <tbody>
                <tr v-for="(item, index) in data_project_name_patterns" :key="item.id">
                    <td>
                        {{ item.name }}
                    </td>
                    <td class="text-center">
                        <div class="poly-utilities-content-block cursor" v-if="item.note" @click.stop="handleEdit(item)">
                            {{item.note}}
                        </div>
                    </td>
                    <td>
                        <div class="flex-center">
                            <span class="relative poly-utilities-onoffswitch" :data-id="item.id">
                                <div class="onoffswitch">
                                    <input type="checkbox" :id="'poly_utilities_status-'+ index" class="onoffswitch-checkbox" @change="handleActiveStatus(item)" :checked="(item.active && item.active == 1)">
                                    <label class="onoffswitch-label" :for="'poly_utilities_status-'+ index"></label>
                                </div>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="flex-center">
                            <?php
                            if (has_permission('poly_utilities', '', 'create')) {
                            ?>
                                <span class="cursor" @click.stop="handleEdit(item)" :data-id="item.id" :data-username="item.username"><i class="fa-regular fa-pen-to-square"></i></span>

                                <span class="cursor" @click.stop="handleDelete(item)" :data-id="item.id">
                                    <i class="fa fa-trash"></i>
                                </span>

                            <?php
                            }
                            ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="dataTables_info">{{dataInfo}}</div>
        </div>
        <div class="col-md-8 dataTables_paging">
            <div class="dataTables_paginate paging_simple_numbers">
                <ul class="pagination">
                    <li class="paginate_button previous" :class="{ 'disabled': currentPage === 1 }">
                        <a href="#" @click.prevent="changePage(currentPage - 1)" :disabled="currentPage === 1"><?php echo _l('dt_paginate_previous') ?></a>
                    </li>
                    <li v-for="page in totalPages" :key="page" class="paginate_button" :class="{ 'active': currentPage === page }">
                        <a href="#" @click.prevent="changePage(page)">{{ page }}</a>
                    </li>
                    <li class="paginate_button next" :class="{ 'disabled': currentPage === totalPages }">
                        <a href="#" @click.prevent="changePage(currentPage + 1)" :disabled="currentPage === totalPages"><?php echo _l('dt_paginate_next') ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/project_name_patterns.js') . '"></script>'; ?>