<?php

require_once("config.php");
require_once($CFG->dirroot . '/course/lib.php');

//Teles
use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

use core_user\table\participants;

//Teles
		function send_course_user_roles_to_file(){
        global $DB,$PAGE;
        
		$id = required_param('id', PARAM_INT);
		$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
		
		$context = context_course::instance($id);
		$PAGE->set_context($context);
		
		    $userids = optional_param_array('userid', array(), PARAM_INT);
		    if (empty($userids)) {
			// The first time list hack.
			if (empty($userids) and $post = data_submitted()) {//ha ures az $userids akkor azt a postból fogja megkapni // user1,user2
			    foreach ($post as $k => $v) {
				if (preg_match('/^user(\d+)$/', $k, $m)) {
				    $userids[] = $m[1];
				}
			    }
			}
		    }

		    $coursecontext = context_course::instance($id);
		    $users = get_enrolled_users($coursecontext,'',0,'u.id');
		    
		    foreach($users as $key=>$value){
                $userids[] = $key;		        
		    }
		    
                    $columnnames = array(
                        'course_short_name'=>'course_short_name',
                        'course_id'=>'course_id',
                        'neptun_id' => "neptun_id",			            
                    );

                    $identityfields = get_extra_user_fields($context);
                    $identityfieldsselect = '';

                    foreach ($identityfields as $field) {
                        //$columnnames[$field] = get_string($field);
                        //$identityfieldsselect .= ', u.' . $field . ' ';
                    }
                    $columnnames['roles'] = "Roles";

                    if (!empty($userids)) {
                        list($insql, $inparams) = $DB->get_in_or_equal($userids);
                    }

                    $sql = "SELECT u.id,u.username,u.firstname, u.lastname" . $identityfieldsselect . "
                              FROM {user} u
                             WHERE u.id $insql";
                    
                    
                    
                    //Teles
                    $records = $DB->get_records_sql($sql,$inparams);
		


                    $participanttable = new \core_user\table\participants("user-index-participants-{$course->id}");
                    $filterset = new \core_user\table\participants_filterset();
                    $filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$course->id]));
                                        
		                    

                    //$participanttable->out_without_html_table(20, true,true);
                ob_start();    
                    $participanttable->set_filterset($filterset);
                    //$participanttable->out_without_html_table(2000,true);
                    $participanttable->out(2000,true);
                ob_end_clean();    
                    
		print_object($participanttable);                
    
			foreach($records as $record){
			    unset($record->firstname);
			    unset($record->lastname);
			    
			    $temp = $record->username;
			    unset($record->username);
			    
			    $record->course_short_name = $course->shortname;
			    $record->username = $temp;
			    $record->fullname = fullname($DB->get_record('user',['id'=>$record->id]));
			    
			    
			    
			    $record->roles = null;
			    $record->roles .= '[';
			    foreach($participanttable->public_allroleassignments[$record->id] as $allroleassignment){
				/*if(!(end($allroleassignment))){	
				    $r->roles .= $allroleassignment->shortname.',';
				}else{
				    $r->roles .= $allroleassignment->shortname;				
				}*/
				    $record->roles .= $allroleassignment->shortname.' ';
				}
				unset($record->id);
				$record->roles = trim($record->roles);
				
				$record->roles = str_replace(" ",",",$record->roles);
				$record->roles .= ']';
				print_object($record);
			}


			
			$line = null;
			foreach ($records as $record) {
			    
			    foreach($record as $key=>$value){
			        $line.= $value.',';
			    }
			    $line = substr($line, 0, -1);
			    $line.="<br>";
			    
			}
			print($line);
			
		}
    
    send_course_user_roles_to_file();
