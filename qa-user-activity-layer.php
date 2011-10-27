<?php
/*
	Uses code from:
	
	Question2Answer User Activity Plus plugin, v1.0
	License: http://www.gnu.org/licenses/gpl.html
*/
class qa_html_theme_layer extends qa_html_theme_base
{

	function head_custom() {
		if($this->template == 'user' && qa_opt('user_act_list_active')) {
				$this->output('<style>',str_replace('^',QA_HTML_THEME_LAYER_URLTOROOT,qa_opt('user_act_list_css')),'</style>');
		}
		qa_html_theme_base::head_custom();
	}

	function q_list_and_form($q_list)
	{
		if($this->template == 'user' && qa_opt('user_act_list_active') && qa_opt('user_act_list_replace'))
			return;
			
		qa_html_theme_base::q_list_and_form($q_list);
	}

	function main_parts($content)
	{
		if($this->template == 'user' && qa_opt('event_logger_to_database') && qa_opt('user_act_list_active')) {

			if($content['q_list']) {  // paranoia
			
				$keys = array_keys($content);
				$vals = array_values($content);

				$insertBefore = array_search('q_list', $keys);

				$keys2 = array_splice($keys, $insertBefore);
				$vals2 = array_splice($vals, $insertBefore);

				$keys[] = 'form-activity-list';
				$vals[] = $this->user_activity_form();

				$content = array_merge(array_combine($keys, $vals), array_combine($keys2, $vals2));
			}
			else $content['form-activity-list'] = $this->user_activity_form();  // this shouldn't happen
		}
			

		qa_html_theme_base::main_parts($content);

	}
	
	function user_activity_form() {
		$handle = $this->_user_handle();
		if(!$handle) return;
		$userid = $this->getuserfromhandle($handle);
		
		$events = qa_db_query_sub(
			'SELECT event, params, UNIX_TIMESTAMP(datetime) AS datetime FROM ^eventlog WHERE userid = # AND DATE_SUB(CURDATE(),INTERVAL # DAY) <= datetime ORDER BY datetime DESC',
			$userid, qa_opt('user_act_list_age')
		);

		// no post
		
		$nopost = array(
			'u_password',
			'u_reset',
			'u_save',
			'u_confirmed',
			'u_edit',
			'u_level',
			'u_block',
			'u_unblock',
			'u_register',
			'in_u_edit',
			'in_u_level',
			'in_u_block',
			'in_u_unblock',
			'feedback',
			'search',
		);
		
		// points

		require_once QA_INCLUDE_DIR.'qa-db-points.php';

		$optionnames=qa_db_points_option_names();
		$options=qa_get_options($optionnames);
		$multi = (int)$options['points_multiple'];
		
		$option_events['q_post'] = (int)$options['points_post_q']*$multi;
		$option_events['a_select'] = (int)$options['points_select_a']*$multi;
		$option_events['in_q_vote_up'] = (int)$options['points_per_q_voted']*$multi;
		$option_events['in_q_vote_down'] = (int)$options['points_per_q_voted']*$multi*(-1);
		$option_events['a_post'] = (int)$options['points_post_a']*$multi;
		$option_events['in_a_select'] = (int)$options['points_a_selected']*$multi;
		$option_events['in_a_unselect'] = (int)$options['points_a_selected']*$multi*(-1);
		$option_events['in_a_vote_up'] = (int)$options['points_per_a_voted']*$multi;
		$option_events['in_a_vote_down'] = (int)$options['points_per_a_voted']*$multi*(-1);
		$option_events['q_vote_up'] = (int)$options['points_vote_up_q']*$multi;
		$option_events['q_vote_down'] = (int)$options['points_vote_down_q']*$multi;
		$option_events['a_vote_up'] = (int)$options['points_vote_up_a']*$multi;
		$option_events['a_vote_down'] = (int)$options['points_vote_down_a']*$multi;
		
		$fields = array();
		
		while ( ($event=qa_db_read_one_assoc($events,true)) !== null ) {
			$type = $event['event'];
			
			// hide / show exceptions
			
			if(qa_get_logged_in_level()<QA_USER_LEVEL_ADMIN) {
				if($userid != qa_get_logged_in_userid()) { // show public
					$types = explode("\n",qa_opt('user_act_list_show'));
					if(!in_array($type,$types))
						continue;
				}
				else { // hide from owner
					$types = explode("\n",qa_opt('user_act_list_hide'));
					if(in_array($type,$types))
						continue;
				}
			}

			
			if(!qa_opt('user_act_list_'.$type)) continue;
			
			$params = array();
			
			$paramsa = explode("\t",$event['params']);
			foreach($paramsa as $param) {
				$parama = explode('=',$param);
				$params[$parama[0]]=$parama[1];
				if($type=='in_q_vote_up') qa_error_log($params);
			}
			
			if(in_array($type, $nopost)) {
				if($type == 'search') {
					if((int)$params['start'] != 0)
						continue;
					$link = '<a href="'.qa_path_html('search', array('q'=>$params['query'])).'">'.$params['query'].'</a>';
				}
				else if(in_array($type, array('u_edit','u_level','u_block','u_unblock'))) {
					$ohandle = $this->getHandleFromID($params['userid']);
					$link = '<a href="'.qa_path('user/'.$ohandle, null, qa_opt('site_url')).'">'.$ohandle.'</a>';
				}
				else($link = '');
			}
			else if(strpos($event['event'],'q_') !== 0 && strpos($event['event'],'in_q_') !== 0) { // comment or answer
				$pid = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT parentid FROM ^posts WHERE postid=#',
						$params['postid']
					),
					true
				);

				$parent = qa_db_select_with_pending(
					qa_db_full_post_selectspec(
						$userid,
						$pid
					)
				);
				$anchor = qa_anchor('A', $params['postid']);
				$activity_url = qa_path_html(qa_q_request($parent['postid'], $parent['title']), null, qa_opt('site_url'));
				$link = '<a href="'.$activity_url.'">'.$parent['title'].'</a>';
			}
			else {

				if(!isset($params['title'])) {
					$params['title'] = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT title FROM ^posts WHERE postid=#',
							$params['postid']
						)
					);
				}

				$activity_url = qa_path_html(qa_q_request($params['postid'], $params['title']), null, qa_opt('site_url'));
				$link = '<a href="'.$activity_url.'">'.$params['title'].'</a>';
			}
			
			$time = $event['datetime'];
			$whenhtml=qa_html(qa_time_to_string(qa_opt('db_time')-$time));
			$whenhtml = preg_replace('/([0-9]+)/','<span class="qa-activity-item-date-no">$1</span>',$whenhtml);
			$when = qa_lang_html_sub('main/x_ago', $whenhtml);
			//$when = str_replace(' ','<br/>',$when);
			
			$params = explode("\t",$event['params']);
			$points = @$option_events[$type];
			$fields[] = array(
				'type' => 'static',
				'label'=> '<div class="qa-activity-item-date">'.$when.'</div>',
				'value'=> '<table class="qa-activity-item-table"><tr><td class="qa-activity-item-type-cell"><div class="qa-activity-item-type">'.qa_opt('user_act_list_'.$type).'</div></td><td class="qa-activity-item-title-cell"><div class="qa-activity-item-title">'.$link.'</div></td class="qa-activity-item-points-cell"><td align="right">'.($points?'<div class="qa-activity-item-points qa-activity-item-points-'.($points<0?'neg">':'pos">+').$points.'</div>':'').'</td></tr></table>',
			);
		}		
		
		if(empty($fields)) return;
		
		return array(				
			'style' => 'wide',
			'title' => qa_opt('user_act_list_title'),
			'fields'=>$fields,
		);

	}
	
	// grab the handle of the profile you're looking at
	function _user_handle()
	{
		preg_match( '#user/([^/]+)#', $this->request, $matches );
		return !empty($matches[1]) ? $matches[1] : null;
	}
	function getuserfromhandle($handle) {
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		if (QA_FINAL_EXTERNAL_USERS) {
			$publictouserid=qa_get_userids_from_public(array($handle));
			$userid=@$publictouserid[$handle];
			
		} 
		else {
			$userid = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT userid FROM ^users WHERE handle = $',
					$handle
				),
				true
			);
		}
		if (!isset($userid)) return;
		return $userid;
	}
	function getHandleFromID($uid) {
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		if (QA_FINAL_EXTERNAL_USERS) {
			$publictouserid=qa_get_public_from_userids(array($uid));
			$handle=@$publictouserid[$uid];
			
		} 
		else {
			$handle = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT handle FROM ^users WHERE userid = #',
					$uid
				),
				true
			);
		}
		if (!isset($handle)) return;
		return $handle;
	}
}
