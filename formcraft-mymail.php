<?php

	/*
	Plugin Name: FormCraft MyMail Add-On
	Plugin URI: http://formcraft-wp.com/addons/mymail/
	Description: MyMail Add-On for FormCraft
	Author: nCrafts
	Author URI: http://formcraft-wp.com/
	Version: 1.0.0
	Text Domain: formcraft-mymail
	*/

	global $fc_meta, $fc_forms_table, $fc_submissions_table, $fc_views_table, $fc_files_table, $wpdb;

	add_action('formcraft_after_save', 'formcraft_mymail_trigger', 10, 4);
	function formcraft_mymail_trigger($content, $meta, $raw_content, $integrations)
	{
		global $fc_final_response;
		if(!function_exists('mymail')){ return false; }
		if ( in_array('MyMail', $integrations['not_triggered']) ){ return false; }

		$mymail_data = formcraft_get_addon_data('MyMail', $content['Form ID']);

		if (!$mymail_data){return false;}
		if (!isset($mymail_data['Map'])){return false;}

		$submit_data = array();
		foreach ($mymail_data['Map'] as $key => $line) {
			if ($line['columnID']=='email')
			{
				$email = fc_template($content, $line['formField']);
				if ( !filter_var($email,FILTER_VALIDATE_EMAIL) ) { continue; }
				$submit_data[$line['listID']][$line['columnID']] = $email;
			}
			else
			{
				$name = fc_template($content, $line['formField']);
				$name = trim(preg_replace('/\s*\[[^)]*\]/', '', $name));
				$submit_data[$line['listID']][$line['columnID']] = $name;
			}
		}

		foreach ($submit_data as $key => $list_submit) {
			if ( empty($list_submit['email']) )
			{
				$fc_final_response['debug']['failed'][] = "MyMail Error: No email to add.";
				continue;
			}

			$subscriber_id = mymail('subscribers')->add($list_submit, 1 );
			if ( is_wp_error($subscriber_id) )
			{
				$error_string = $subscriber_id->get_error_message();
				$fc_final_response['debug']['failed'][] = "MyMail Error: (".$list_submit['email'].") ".$error_string;
			}
			else if ( $subscriber_id > 0 )
			{
				$success = mymail('subscribers')->assign_lists($subscriber_id, $key, $remove_old = false);
				$fc_final_response['debug']['success'][] = 'MyMail Added: '.$list_submit['email'].' to list '.$key;
			}
		}
	}

	add_action('formcraft_addon_init', 'formcraft_mymail_addon');
	add_action('formcraft_addon_scripts', 'formcraft_mymail_scripts');

	function formcraft_mymail_addon()
	{
		register_formcraft_addon('MyMail_PrintContent',518,'MyMail','MyMailController',plugins_url('assets/logo.png', __FILE__ ), plugin_dir_path( __FILE__ ).'templates/',1);
	}
	function formcraft_mymail_scripts()
	{
		wp_enqueue_script('formcraft-mymail-main-js', plugins_url( 'assets/builder.js', __FILE__ ));
		wp_enqueue_style('formcraft-mymail-main-css', plugins_url( 'assets/builder.css', __FILE__ ));
	}

	function MyMail_PrintContent()
	{
		if (!function_exists('mymail')) {
			?>
			<div style='text-align: center; padding: 20px; font-size: 15px; line-height: 1.7em; color: #999'>You don't seem to have MyMail installed.<br>The add-on isn't of much use.</div>
			<?php
		}
		else
		{
			$mymail_lists = mymail('lists')->get();

			?>
			<div id='mymail-cover' style='padding: 14px'>
				<div>
					<div id='mapped-mymail' class='nos-{{Addons.MyMail.Map.length}}'>
						<div style='text-align: center'>
							<?php _e('Nothing Here','formcraft-mymail') ?>
						</div>
						<table cellpadding='0' cellspacing='0'>
							<tbody>
								<tr ng-repeat='instance in Addons.MyMail.Map'>
									<td style='width: 30%'>
										<span>{{instance.listName}}</span>
									</td>
									<td style='width: 30%'>
										<span><input type='text' ng-model='instance.columnID'/></span>
									</td>
									<td style='width: 30%'>
										<span><input type='text' ng-model='instance.formField'/></span>
									</td>
									<td style='width: 10%; text-align: center'>
										<i ng-click='removeMap($index)' class='icon-cancel-circled'></i>
									</td>								
								</tr>
							</tbody>
						</table>
					</div>
					<div id='mymail-map'>
						<select class='select-list' ng-model='SelectedList'>
							<option value='' selected="selected">(<?php _e('List','formcraft-mymail') ?>)</option>
							<?php
							foreach($mymail_lists as $list){
								echo "<option value='".$list->ID."'>".$list->name."</option>";
							}
							?>
						</select>

						<select class='select-column' ng-model='SelectedColumn'>
							<option value='' selected="selected">(<?php _e('Column','formcraft-mymail') ?>)</option>
							<option value='email'>E-mail</option>
							<option value='firstname'>Firstname</option>
							<option value='lastname'>Lastname</option>
							<option value=''>Custom Field</option>
						</select>

						<input class='select-field' type='text' ng-model='FieldName' placeholder='<?php _e('Form Field','formcraft-mymail') ?>'><i style='position: absolute; z-index: 101; right: 55px; bottom: 19px' class='icon-help' data-toggle='tooltip' title='Enter the form label, enclosed in square bracket. If the form label is Email, type in <strong>[Email]</strong> here'></i>
						<button class='button' ng-click='addMap()'><i class='icon-plus'></i></button>
					</div>
				</div>
			</div>
			<?php
		}
	}


	?>