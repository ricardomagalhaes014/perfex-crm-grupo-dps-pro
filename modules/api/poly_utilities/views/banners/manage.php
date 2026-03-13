<div>
  <span @click="handleAdd()" class="btn btn-primary pull-left display-block">
    <i class="fa-regular fa-plus tw-mr-1"></i>&nbsp<?php echo _l('poly_utilities_banner_media_button_create_new') ?>
  </span>
  <div class="clearfix"></div>
</div>
<div class="dataTables_wrapper">
  <div style="overflow-x: scroll;" class="table-responsive">
    <table class="table">
      <thead>
        <th><?php echo _l('poly_utilities_banner_media_title') ?></th>
        <th class="text-center">Media</th>
        <th>Widgets area</th>
        <th><?php echo _l('poly_utilities_banner_media_schedule') ?></th>
        <th class="text-center"><?php echo _l('poly_utilities_banner_media_activate') ?></th>
        <th class="text-center">&nbsp;</th>
      </thead>
      <tbody>
        <tr v-for="(item, index) in data_banners" :key="item.id">
          <td>
            <a v-if="item.url" :href="`${item.url}`" :target="item.target" :rel="item.rel">
              {{ item.title }}
            </a>
            <span v-else>{{ item.title }}</span>
          </td>
          <td class="text-center">
            <div class="poly-utilities-media-block cursor" @click.stop="handleEdit(item)" v-if="item.media">
              <div class="media-preview" v-if="!item.embed">
                <div class="media-preview__wrap"><img class="media" :src="item.media" /></div>
              </div>
            </div>
            <div class="poly-utilities-media-block cursor" v-if="item.embed">
              <div class="media-preview__wrap">
                <div v-html="decodeHtml(item.embed)" class="poly-utilities-embed text-center"></div>
              </div>
            </div>
          </td>
          <td class="col-md-3">
            <div v-for="area in WidgetsArea(item)" :key="area.id">
              <div><strong>{{area.name}}</strong></div>
              <div class="poly-help-message-small">{{decodeHtml(area.description)}}</div>
              <div>
          </td>
          <td>
            <div><i class="fa-solid fa-calendar-check fa-fw green"></i> {{ item.date_from }} </div>
            <div><i class="fa-solid fa-calendar-xmark fa-fw red"></i> {{ item.date_to }} </div>
            <div><i class="fa-solid fa-clock-rotate-left fa-fw"></i> {{DaysBetween(item.date_from, item.date_to)}}</div>
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
<?php echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/banners.js') . '"></script>'; ?>