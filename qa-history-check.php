<?php

	class history_check {
		
// main event processing function
		
		function process_event($event, $userid, $handle, $cookieid, $params) {
			
			if(!qa_opt('event_logger_to_database')) return;
			
			$twoway = array(
				'a_select',
				'a_unselect',
				'q_vote_up',
				'a_vote_up',
				'q_vote_down',
				'a_vote_down',
				'q_vote_nil',
				'a_vote_nil',
				'q_flag',
				'a_flag',
				'c_flag',
				'q_unflag',
				'a_unflag',
				'c_unflag',
				'u_edit',
				'u_level',
				'u_block',
				'u_unblock',
			 );
			 
			 $special = array(
				'a_post',
				'c_post'
			);
			 
			if(in_array($event, $twoway)) {
				
				if(strpos($event,'u_') === 0) {
					$uid = $params['userid'];
				}
				else {
					$uid = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT userid FROM ^posts WHERE postid=#',
							$params['postid']
						),
						true
					);
				}
				
				if($uid != $userid) {
					$ohandle = $this->getHandleFromId($uid);
					
					$oevent = 'in_'.$event;
					
					$paramstring='';
					
					foreach ($params as $key => $value)
						$paramstring.=(strlen($paramstring) ? "\t" : '').$key.'='.$this->value_to_text($value);
					
					qa_db_query_sub(
						'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) '.
						'VALUES (NOW(), $, $, $, #, $, $)',
						qa_remote_ip_address(), $uid, $ohandle, $cookieid, $oevent, $paramstring
					);
				}
			}
			
			// comments and answers
			
			if(in_array($event,$special)) {
				$pid = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT userid FROM ^posts WHERE postid=#',
						$params['parentid']
					),
					true
				);
				if($pid != $userid) {
			
					$ohandle = $this->getHandleFromId($pid);
					
					
					switch($event) {
						case 'a_post':
								$oevent = 'in_a_question';
							break;
						case 'c_post':
							if ($params['parenttype'] == 'Q')
								$oevent = 'in_c_question';
							else 
								$oevent = 'in_c_answer';
							break;
					}
					
					$paramstring='';
					
					foreach ($params as $key => $value)
						$paramstring.=(strlen($paramstring) ? "\t" : '').$key.'='.$this->value_to_text($value);
					
					qa_db_query_sub(
						'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) '.
						'VALUES (NOW(), $, $, $, #, $, $)',
						qa_remote_ip_address(), $pid, $ohandle, $cookieid, $oevent, $paramstring
					);				
				}
			}
		
		}


// worker functions
		function value_to_text($value)
		{
			if (is_array($value))
				$text='array('.count($value).')';
			elseif (strlen($value)>40)
				$text=substr($value, 0, 38).'...';
			else
				$text=$value;
				
			return strtr($text, "\t\n\r", '   ');
		}
		
		function getHandleFromId($userid) {
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			if (QA_FINAL_EXTERNAL_USERS) {
				$publictohandle=qa_get_public_from_userids(array($userid));
				$handle=@$publictohandle[$userid];
				
			} 
			else {
				$user = qa_db_single_select(qa_db_user_account_selectspec($userid, true));
				$handle = @$user['handle'];
			}
			return $handle;
		}
	}
