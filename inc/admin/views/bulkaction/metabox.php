<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class bulkaction_metabox
{
    /**
     * Add meta box to bulk action screen
     */
    function octolio_metabox_init()
    {
        if ('octolio_bulkaction' == get_post_type()) {
            octolio_load_assets('bulkaction');
            add_meta_box('octolio_cpt_metabox', __('Manual Actions', 'octolio'), [$this, 'generate_bulkactions_metabox'], 'octolio_bulkaction', 'normal', 'high');
        }
    }

    /**
     * Display metabox parameters
     *
     * @param $post
     */
    function generate_bulkactions_metabox($post)
    {

        $all_filters = [];
        $all_actions = [];

        $filter_dropdown_values = [];
        $action_dropdown_values = [];
        $integration_helper = octolio_get('helper.integration');

        $available_integrations = $integration_helper->get_available_integrations();
        if (empty($available_integrations)) {
            echo __('No integration available');
            exit;
        }

        //Load bulk action type (Posts / Users / more...)
        $bulkaction_type_meta = get_post_meta($post->ID, 'bulkaction_type', true);
        $selected_bulkaction_type = empty($bulkaction_type_meta) ? 'user' : $bulkaction_type_meta;

        $bulkaction_type_dropdown = octolio_select($available_integrations, 'bulkaction_type', $selected_bulkaction_type, '');

        foreach ($available_integrations as $one_integration_value => $one_integration_label) {

            //Load all filters and their params belonging to this integration
            $integration_filters = $integration_helper->get_filters_list($one_integration_value);
            if (empty($integration_filters)) {
                echo sprintf('Erreur while trying to collect filters from this integration: %s', $one_integration_label);
                exit;
            }

            foreach ($integration_filters as $one_filter_value => $one_filter) {
                $all_filters[$one_integration_value][$one_filter_value]['name'] = $one_filter->text;

                if (!$one_filter->disable) {
                    $all_filters[$one_integration_value][$one_filter_value]['params'] = $integration_helper->get_filter_params($one_filter_value, $integration_filters, 'bulkaction_filters[__nb_or__][__nb_and__]');
                } else $all_filters[$one_integration_value][$one_filter_value]['params'] = '';

                $filter_dropdown_values[$one_integration_value][$one_filter_value] = $one_filter;
            }

            //Load all actions and their params belonging to this integration
            $integration_actions = $integration_helper->get_actions_list($one_integration_value);
            if (empty($integration_actions)) {
                echo sprintf('Erreur while trying to collect actions from this integration: %s', $one_integration_label);
                exit;
            }
            foreach ($integration_actions as $one_action_value => $one_action) {
                $all_actions[$one_integration_value][$one_action_value]['name'] = $one_action->text;

                if (!$one_filter->disable) {
                    $all_actions[$one_integration_value][$one_action_value]['params'] = $integration_helper->get_action_params($one_action_value, $integration_actions, 'bulkaction_actions[__nb_action__]');
                }
                $action_dropdown_values[$one_integration_value][$one_action_value] = $one_action;
            }

            $lib_filter_dropdowns[$one_integration_value] = octolio_select($filter_dropdown_values[$one_integration_value], 'bulkaction_filter_type', '', 'class="bulkaction_filter_type"');
            $lib_action_dropdowns[$one_integration_value] = octolio_select($action_dropdown_values[$one_integration_value], 'bulkaction_action_type', '', 'class="bulkaction_action_type"');
        }

        //Load the current bulkaction filter values
        $saved_filters = get_post_meta($post->ID, 'bulkaction_filters', true);
        if (empty($saved_filters)) $saved_filters = [[['user_last_login' => ['time_value' => 'didnt_log', 'nb_days' => '90']]]];

        //Load the current bulkaction actions
        $saved_actions = get_post_meta($post->ID, 'bulkaction_actions', true);
        if (empty($saved_actions)) $saved_actions = [[key($all_actions[$selected_bulkaction_type]) => []]];
        ?>
		<div id="octolio_container" autocomplete="off">
			<div class="cpt_type_section"><?php echo sprintf(__('Execute bulk action on: %s'), $bulkaction_type_dropdown); ?></div>
			<hr class="rounded">

			<div>
				<h2 class="octolio_cpt_block_title"><?php __('Select your conditions'); ?></h2>
				<div class="or_conditions_container">
                    <?php
                    foreach ($saved_filters as $or_condition_number => $one_or_condition) {
                        if (0 < $or_condition_number) { ?>
							<div class="or_link"><?php echo __('OR'); ?></div>
                        <?php } ?>

						<div class="or_condition">
							<div class="or_condition_wrapper">

								<div class="or_delete">
                                    <?php if (0 < $or_condition_number) { ?><i class="octolio-trash-o"></i><?php } ?>
								</div>
								<div class="and_conditions_container">
                                    <?php
                                    foreach ($one_or_condition as $and_condition_number => $one_and_condition) {
                                        $filter_type = array_keys($one_and_condition)[0];

                                        $filter_values = $saved_filters[$or_condition_number][$and_condition_number][$filter_type];
                                        $filter_name = 'bulkaction_filters['.$or_condition_number.']['.$and_condition_number.']';

                                        if (0 < $and_condition_number) { ?>
											<div class="and_link"><?php echo __('AND'); ?></div>
                                        <?php } ?>
										<div class="and_condition">
											<div class="and_condition_wrapper">
												<div class="and_delete">
                                                    <?php if (0 < $and_condition_number) { ?><i class="octolio-trash-o"></i> <?php } ?>
												</div>
												<div class="and_params">
                                                    <?php
                                                    echo octolio_select($filter_dropdown_values[$selected_bulkaction_type], 'bulkaction_filter_type', $filter_type, 'class="bulkaction_filter_type"');
                                                    ?>
													<div class="octolio_filter_params">
                                                        <?php
                                                        $integration_helper->display_filter_params($filter_type, $filter_values, $filter_name);
                                                        ?>
													</div>
												</div>
											</div>
										</div>
                                        <?php
                                    }
                                    ?>
									<button type="button" class="button octolio_add_section add_and_section" data-type="and_condition"><?php echo __('Add an "AND" condition', 'octolio') ?></button>
								</div>
							</div>
						</div>

                        <?php
                    }
                    ?>
					<button type="button" class="button octolio_add_section add_or_section" data-type="or_condition"><?php echo __('Add an "OR" condition', 'octolio') ?></button>
				</div>
			</div>
			<hr class="rounded">
			<div>
				<h2 class="octolio_cpt_block_title">Now select your actions:</h2>
				<div class="actions_container">
                    <?php
                    if (!empty($saved_actions)) {
                        $num = 0;
                        foreach ($saved_actions as $action_number => $one_action) {
                            $action_name = array_keys($one_action)[0];
                            $action_params = array_shift($one_action);

                            if (1 < $action_number) { ?>
								<div class="action_link"><?php echo __('AND'); ?></div>
                            <?php } ?>

							<div class="one_action">
								<div class="one_action_wrapper">
									<div class="action_delete">
                                        <?php if (0 < $action_number) { ?><i class="octolio-trash-o"></i> <?php } ?>
									</div>
									<div class="action_params">
                                        <?php
                                        echo octolio_select($action_dropdown_values[$selected_bulkaction_type], 'bulkaction_action_type', $action_name, 'class="bulkaction_action_type"')
                                        ?>
										<div class="octolio_action_params">
                                            <?php
                                            $integration_helper->display_action_params($action_name, $action_params, 'bulkaction_actions['.$num.']');
                                            $num++;
                                            ?>
										</div>
									</div>
								</div>
							</div>
                            <?php
                        }
                    }
                    ?>
					<button type="button" class="button octolio_add_section" data-type="action"><?php echo __('Add an action', 'octolio') ?></button>
				</div>

			</div>
		</div>
        <?php

        echo '<input id="lib_filter_dropdowns" type="hidden" value="'.octolio_escape(json_encode($lib_filter_dropdowns)).'"/>';
        echo '<input id="lib_filters_params_list" type="hidden" value="'.octolio_escape(json_encode($all_filters)).'"/>';

        echo '<input id="lib_action_dropdowns" type="hidden" value="'.octolio_escape(json_encode($lib_action_dropdowns)).'"/>';
        echo '<input id="lib_actions_params_list" type="hidden" value="'.octolio_escape(json_encode($all_actions)).'"/>';
    }

}
