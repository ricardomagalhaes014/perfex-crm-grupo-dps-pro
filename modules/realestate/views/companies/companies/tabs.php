<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked customer-tabs" role="tablist">
	<?php
	$i = 0;
	foreach($tab as $group){
		?>
		<li<?php if($i == 0){echo " class='active'"; } ?>>
				<?php if($group['name'] == 'add_edit_company'){ ?>
					<?php if($related_type == 'company'){ ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
						<?php echo html_entity_decode($group['icon']).' '._l('real_real_estate_agent_detail'); ?></a>
					<?php }else{ ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
						<?php echo html_entity_decode($group['icon']).' '._l('real_business_broker_detail'); ?></a>
					<?php } ?>
				<?php } elseif($group['name'] == 'staffs') { ?>

					<?php if($related_type == 'company'){ ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
					<?php echo html_entity_decode($group['icon']).' '._l('real_real_estate_agent_staff'); ?></a>
				<?php }else{ ?>
					<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group=broker_staffs'); ?>" data-group="<?php echo html_entity_decode('broker_staffs'); ?>">
					<?php echo html_entity_decode($group['icon']).' '._l('real_business_broker_staffs'); ?></a>
				<?php } ?>

				<?php } elseif($group['name'] == 'company_listings') { ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
						<?php echo html_entity_decode($group['icon']).' '._l('real_real_estate_agent_listings'); ?></a>
				
				<?php } elseif($group['name'] == 'review') { ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
						<?php echo html_entity_decode($group['icon']).' '._l('real_review'); ?></a>

				<?php } elseif($group['name'] == 'company_agents') { ?>
					<?php if($related_type == 'company'){ ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
						<?php echo html_entity_decode($group['icon']).' '._l('real_real_estate_agent_agents'); ?></a>
					<?php }else{ ?>

					<?php } ?>
				<?php }elseif($group['name'] == 'agent_employees') { ?>
					<?php if($related_type == 'company'){ ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
						<?php echo html_entity_decode($group['icon']).' '._l('real_agent_employees'); ?></a>
					<?php }else{ ?>

					<?php } ?>
				<?php }elseif($group['name'] == 'business_brokers') { ?> 
					<?php if($related_type == 'company'){ ?>
						<a href="<?php echo admin_url('realestate/add_edit_company/'.$construction_company->id.'?group='.$group['name']); ?>" data-group="<?php echo html_entity_decode($group['name']); ?>">
						<?php echo html_entity_decode($group['icon']).' '._l('real_business_brokers'); ?></a>
					<?php }else{ ?>

					<?php } ?>
				<?php } else{ ?>
						<?php echo html_entity_decode($group['icon']).' '._l($group['name']); ?></a>
					
				<?php } ?>
			</li>
			<?php $i++; } ?>
		</ul>
