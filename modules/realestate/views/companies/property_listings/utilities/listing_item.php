<?php $base_currency_id = get_base_currency_id() ?>
<?php foreach($properties as $property){ ?>
    <?php 
    $total_bath = $property['full_baths'] + $property['half_baths'];
    $site_url = main_photo($property['id'], $property['primary_image']);
    ?>

    <div class="<?php if(isset($property_col)){ echo html_entity_decode($property_col);}else{ echo ' col-lg-4 col-md-6';} ?>">
        <div class="room-items">
            <div class="room-img set-bg" data-setbg="img/rooms/1.jpg">
                <a href="<?php echo site_url('realestate/client/property_listing_detail/'.$property['id']); ?>">
                <img class="room-img set-bg" src="<?php echo html_entity_decode($site_url); ?>" alt="<?php echo new_html_entity_decode($property['primary_image']) ?>">
                <div class="property-button quantity-button">
                    <span class="btn rent-btn"><?php echo html_entity_decode(_l('rel_'.$property['status'])); ?></span>
                </div>
                </a>
            </div>
            <div class="room-text">
                <div class="room-details">
                    <div class="room-title">
                        <h5><?php echo html_entity_decode($property['description']); ?></h5>
                        <a href="#"><i class="fa-solid fa-location-dot mtop5"></i> <span><?php echo get_country_name($property['country']); ?></span></span></a>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo html_entity_decode($property['latitude']) ?>,<?php echo html_entity_decode($property['longitude']) ?>" class="large-width" target="_blank"><i class="fa-solid fa-location-arrow mtop5"></i> <span><?php echo _l('real_show_on_map'); ?></span></a>
                    </div>
                </div>

                <div class="room-features">
                    <div class="room-info tw-flex tw-justify-between">
                        <div class="size">
                            <p><?php echo _l('rel_lot_size_acres'); ?></p>
                            <img src="#" alt="">
                            <i class="fa-solid fa-expand"></i>
                            <span><?php echo html_entity_decode($property['lot_size_acres'] ?? 0); ?></span>
                        </div>
                        <div class="beds">
                            <p><?php echo _l('real_Beds'); ?></p>
                            <i class="fa-solid fa-bed"></i>
                            <span><?php echo html_entity_decode($property['beds'] ?? 0); ?></span>

                        </div>
                        <div class="baths">
                            <p><?php echo _l('real_baths'); ?></p>
                            <i class="fa-solid fa-bath"></i>
                            <span><?php echo html_entity_decode($total_bath); ?></span>

                        </div>
                        <div class="garage">
                            <p><?php echo _l('real_garage'); ?></p>
                            <i class="fa-solid fa-warehouse"></i>
                            <span><?php echo html_entity_decode($property['garage'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="room-price">
                    <?php if($property['transaction_type'] == 'Sale' || $property['transaction_type'] == 'sold'){ ?>
                        <p><?php echo _l('real_for_sale'); ?></p>
                        <span><?php echo app_format_money($property['rate'], $base_currency_id); ?></span>
                    <?php }else{ ?>
                        <p><?php echo _l('real_for_rent'); ?></p>
                        <span><?php echo app_format_money($property['rent_price'], $base_currency_id).' per '.$property['rental_type']; ?></span>
                    <?php } ?>
                </div>
                <a href="#" class="site-btn btn-line hide">View Property</a>
            </div>
        </div>
    </div>
<?php } ?>
