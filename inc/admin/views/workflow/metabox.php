<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class workflow_metabox
{
    /**
     * Add meta box to Workflow screen
     */
    function octolio_metabox_init()
    {
        if ('octolio_workflow' == get_post_type()) {
            octolio_load_assets('workflow');
            add_meta_box('octolio_cpt_metabox', __('Automatic Actions', 'octolio'), [$this, 'generate_workflows_metabox'], 'octolio_workflow', 'normal', 'high');
        }
    }

    /**
     * Display metabox parameters
     *
     * @param $post
     */
    function generate_workflows_metabox($post)
    {

        $all_hooks = [];
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

        //Load Workflow type (Posts / Users / more...)
        $workflow_type_meta = get_post_meta($post->ID, 'workflow_type', true);
        if (empty($workflow_type_meta)) {
            if (array_key_exists('woocommerce_order', $available_integrations)) {
                $selected_workflow_type = 'woocommerce_order';
            } else $selected_workflow_type = 'user';
        } else {
            $selected_workflow_type = $workflow_type_meta;
        }

        foreach ($available_integrations as $one_integration_value => $one_integration_label) {

            //Load all filters and their params belonging to this integration
            $integration_hooks = $integration_helper->get_hooks_list($one_integration_value);
            if (empty($integration_hooks)) {
                unset($available_integrations[$one_integration_value]);
                continue;
            }


            foreach ($integration_hooks as $one__value => $one_hook_label) {
                $all_hooks[$one_integration_value][$one_hook_label]['name'] = $one_hook_label;
                $hook_dropdown_values[$one_integration_value][$one__value] = $one_hook_label;
            }

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

            $lib_hook_dropdowns[$one_integration_value] = octolio_select($hook_dropdown_values[$one_integration_value], 'workflow_hook_type', '', 'class="workflow_hooks"');
            $lib_filter_dropdowns[$one_integration_value] = octolio_select($filter_dropdown_values[$one_integration_value], 'workflow_filter_type', '', 'class="workflow_filter_type"');
            $lib_action_dropdowns[$one_integration_value] = octolio_select($action_dropdown_values[$one_integration_value], 'workflow_action_type', '', 'class="workflow_action_type"');
        }

        //Create the integration dropdown
        $workflow_type_dropdown = octolio_select($available_integrations, 'workflow_type', $selected_workflow_type, '');

        //Load the current workflow filter values
        $saved_hook = get_post_meta($post->ID, 'workflow_hook', true);
        if (empty($saved_hook)) $saved_hook = '';
        $hook_dropdown = octolio_select($hook_dropdown_values[$selected_workflow_type], 'workflow_hook', $saved_hook, 'class="workflow_hook"');

        //Load the current workflow filter values
        $saved_filters = get_post_meta($post->ID, 'workflow_filters', true);
        if (empty($saved_filters)) {
            if (array_key_exists('woocommerce_order', $available_integrations)) {
                $saved_filters = [[['woocommerce_order_total' => ['operator' => '>', 'value' => '1000']]]];
            } else {
                $saved_filters = [[[key($all_filters[$selected_workflow_type]) => []]]];
            }
        }

        //Load the current workflow actions
        $saved_actions = get_post_meta($post->ID, 'workflow_actions', true);
        if (empty($saved_actions)) $saved_actions = [[key($all_actions[$selected_workflow_type]) => []]];
        ?>
		<div id="octolio_container" autocomplete="off">
			<div class="cpt_type_section"><?php echo sprintf(__('When a: %s'), $workflow_type_dropdown); ?></div>
			<div class="cpt_type_section"><?php echo sprintf(__('Does: %s'), $hook_dropdown); ?></div>
			<hr class="rounded">

			<div>
				<h2 class="octolio_cpt_block_title"><?php echo __('Select your conditions'); ?></h2>
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
                                        $filter_name = 'workflow_filters['.$or_condition_number.']['.$and_condition_number.']';

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
                                                    echo octolio_select($filter_dropdown_values[$selected_workflow_type], 'workflow_filter_type', $filter_type, 'class="workflow_filter_type"');
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
                                        echo octolio_select($action_dropdown_values[$selected_workflow_type], 'workflow_action_type', $action_name, 'class="workflow_action_type"')
                                        ?>
										<div class="octolio_action_params">
                                            <?php
                                            $integration_helper->display_action_params($action_name, $action_params, 'workflow_actions['.$num.']');
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

        echo '<input id="lib_hook_dropdowns" type="hidden" value="'.octolio_escape(json_encode($lib_hook_dropdowns)).'"/>';
        echo '<input id="lib_hook_params_list" type="hidden" value="'.octolio_escape(json_encode($all_hooks)).'"/>';
    }

}
