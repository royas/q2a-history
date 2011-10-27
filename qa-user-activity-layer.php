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
		global $qa_root_url_relative;
		$handle = $this->_user_handle();

		// output activity links under recent activity
		if ( $this->template === 'user' )
		{
			$this->output(
				'<div class="qa-useract-page-links">',
				'	More activity: ',
				'	<a href="' . qa_path('user-activity/questions/'.$handle) . '">All questions</a>',
				'	&bull; ',
				'	<a href="' . qa_path('user-activity/answers/'.$handle) . '">All answers</a>',
				'</div>'
			);
		}
	}

	// append activity links to question and answer counts
	function form_fields($form, $columns)
	{
		global $qa_root_url_relative;
		$handle = $this->_user_handle();

		if ( $this->template === 'user' && !empty($form['fields']) )
		{
			foreach ($form['fields'] as $key=>&$field)
			{
				if ( $key === 'questions' )
				{
					$url = qa_path('user-activity/questions/'.$handle);
					$field['value'] .= ' &mdash; <a href="' . $url . '">All questions by ' . qa_html($handle) . ' &rsaquo;</a>';
				}
				else if ( $key === 'answers' )
				{
					$url = qa_path('user-activity/answers/'.$handle);
					$field['value'] .= ' &mdash; <a href="' . $url . '">All answers by ' . qa_html($handle) . ' &rsaquo;</a>';
				}
			}
		}

		qa_html_theme_base::form_fields($form, $columns);
	}

	function main_parts($content)
	{
		if($this->template == 'user') {

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
		if($userid != qa_get_logged_in_userid() && qa_get_logged_in_level()<QA_USER_LEVEL_ADMIN) return;
		
		$events = qa_db_query_sub(
			'SELECT event, params, UNIX_TIMESTAMP(datetime) AS datetime FROM ^eventlog WHERE userid = # AND DATE_SUB(CURDATE(),INTERVAL # DAY) <= datetime ORDER BY datetime DESC',
			$userid, qa_opt('user_act_list_age')
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

			if(!qa_opt('user_act_list_'.$type)) continue;

			$paramsa = explode("\t",$event['params']);
			foreach($paramsa as $param) {
				$parama = explode('=',$param);
				$params[$parama[0]]=$parama[1];
			}
			if(strpos($event['event'],'q_') !== 0 && strpos($event['event'],'in_q_') !== 0) {
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
				'value'=> '<table class="qa-activity-item-table"><tr><td><div class="qa-activity-item-type">'.qa_opt('user_act_list_'.$type).'</div></td><td><div class="qa-activity-item-title">'.$link.'</div></td><td align="right">'.($points?'<div class="qa-activity-item-points-'.($points<0?'neg">':'pos">+').$points.'</div>':'').'</td></tr></table>',
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
}
