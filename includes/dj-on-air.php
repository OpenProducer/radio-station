<?php
/*
 * DJ and Show scheduling
 * Author: Nikki Blight
 */

//shortcode function for current DJ on-air
function dj_show_widget($atts) {
	extract( shortcode_atts( array(
		'title' => '',	
		'show_avatar' => 0,
		'show_link' => 0,
		'default_name' => '',
		'time' => '12'
	), $atts ) );
	
	//find out which DJ(s) are currently scheduled to be on-air and display them
	$djs = dj_get_current();
	$playlist = myplaylist_get_now_playing();
	
	$dj_str = '';
	
	$dj_str .= '<div class="on-air-embedded">';
	if($title != '') {
		$dj_str .= '<h3>'.$title.'</h3>';
	}
	$dj_str .= '<ul class="on-air-list">';
	
	//echo the show/dj currently on-air
	if(count($djs['all']) > 0) {
		foreach($djs['all'] as $dj) {
			$dj_str .= '<li class="on-air-dj">';
			if($show_avatar) {
				$dj_str .= '<span class="on-air-dj-avatar">'.get_the_post_thumbnail($dj->ID, 'thumbnail').'</span>';
			}

			if($show_link) {
				$dj_str .= '<a href="';
				$dj_str .= get_permalink($dj->ID);
				$dj_str .= '">';
				$dj_str .= $dj->post_title.'</a>';
			}
			else {
				$dj_str .= $dj->post_title;
			}
			
			$dj_str .= '<span class="on-air-dj-playlist"><a href="'.$playlist['playlist_permalink'].'">View Playlist</a></span>';
			
			$dj_str .= '<span class="radio-clear"></span>';
			
			$scheds = get_post_meta($dj->ID, 'show_sched', true);
			foreach($scheds as $sched) {
				$dj_str .= '<span class="on-air-dj-sched">'.$sched['day'].'s, '.$sched['start_hour'].':'.$sched['start_min'].' ';
				if($time == 12) {
					$dj_str .= $sched['start_meridian'];
				}
				
				$dj_str .= '-'.$sched['end_hour'].':'.$sched['end_min'].' ';
				if($time == 12) {
					$dj_str .= $sched['end_meridian'];
				}
				
				$dj_str .= '</span><br />';
			}
			
			$dj_str .= '</li>';
		}
	}
	else {
		$dj_str .= '<li class="on-air-dj default-dj">'.$default_name.'</li>';
	}

	$dj_str .= '</ul>';
	$dj_str .= '</div>';
	
	return $dj_str;
	
}
add_shortcode( 'dj-widget', 'dj_show_widget');

//fetch the current DJ(s) on-air
function dj_get_current() {	
	//load the info for the DJ
	global $wpdb;
	
	//get the current time
	$hour = date('H', strtotime(current_time("mysql")));
	$min = date('i', strtotime(current_time("mysql")));
	$curDay = date('l', strtotime(current_time("mysql")));
	$curDate = date('Y-m-d', strtotime(current_time("mysql")));
	$tomDate = date('Y-m-d', ( strtotime(current_time("mysql")) + 36400)); //get the date for tomorrow
	$now = strtotime(current_time("mysql"));
	
	//first check to see if a show is scheduled
	$show_shifts = $wpdb->get_results("SELECT `meta`.`post_id`, `meta`.`meta_value` FROM ".$wpdb->prefix."postmeta AS `meta`
												WHERE `meta_key` = 'show_sched';");
	
	$show_ids = array();
	foreach($show_shifts as $shift) {
		$shift->meta_value = unserialize($shift->meta_value);
		
		//if a show has no shifts, unserialize() will return false instead of an empty array... fix that to prevent errors in the foreach loop.
		if(!is_array($shift->meta_value)) {
			$shift->meta_value = array();
		}
		
		foreach($shift->meta_value as $time) {
			//check if the shift is for the current day.  If it's not, skip it
			if($time['day'] == $curDay) {
				
				//convert to 24 hour time
				if($time['start_hour'] < 10) {
					$time['start_hour'] = '0'.$time['start_hour'];
				}
				
				if($time['end_hour'] < 10) {
					$time['end_hour'] = '0'.$time['end_hour'];
				}
				
				if($time['start_meridian'] == 'pm' && $time['start_hour'] != 12) {
					$time['start_hour'] = $time['start_hour'] + 12;
				}
				if($time['end_meridian'] == 'pm' && $time['end_hour'] != 12) {
					$time['end_hour'] = $time['end_hour'] + 12;
				}
				
				//get a timestamp for the schedule start and end
				$start_time = strtotime($curDate.' '.$time['start_hour'].':'.$time['start_min']);
				
				if($time['start_meridian'] ==  'pm' && $time['end_meridian'] == 'am') { //check for shows that run overnight into the next morning
					$end_time = strtotime($tomDate.' '.$time['end_hour'].':'.$time['end_min']);
				}
				else {
					$end_time = strtotime($curDate.' '.$time['end_hour'].':'.$time['end_min']);
				}
				
				//compare to the current timestamp
				if($start_time <= $now && $end_time >= $now) {	
					$show_ids[] = $shift->post_id;
				}
			}
		}
	}
	
	$shows = array();
	foreach($show_ids as $id) {	
		$shows['all'][] = get_post($id);
	}
	$shows['type'] = 'shows';
	
	return $shows;
}

//get the next DJ or DJs scheduled to be on air based on the current time
function dj_get_next($limit = 1) {
	//load the info for the DJ
	global $wpdb;

	//get the various times/dates we need
	$curDay = date('l', strtotime(current_time("mysql")));
	$curDate = date('Y-m-d', strtotime(current_time("mysql")));
	$now = strtotime(current_time("mysql"));
	$tomorrow = date( "Y-m-d", (strtotime($curDate) + 86400) );
	$tomorrowDay = date( "l", (strtotime($curDate) + 86400) );
	
	//Fetch all schedules
	$show_shifts = $wpdb->get_results("SELECT `meta`.`post_id`, `meta`.`meta_value` FROM ".$wpdb->prefix."postmeta AS `meta`
			WHERE `meta_key` = 'show_sched';");

	$show_ids = array();
	
	foreach($show_shifts as $shift) {
		$shift->meta_value = unserialize($shift->meta_value);

		//if a show has no shifts, unserialize() will return false instead of an empty array... fix that to prevent errors in the foreach loop.
		if(!is_array($shift->meta_value)) {
			$shift->meta_value = array();
		}

		foreach($shift->meta_value as $time) {

			//check if the shift is for the current day or for tomorrow.  If it's not, skip it
			if($time['day'] != $curDay  && $time['day'] != $tomorrowDay) {
				continue;
			}
			
			//determine is the particular shift is for today or tomorrow and assign a real timestamp accordingly
			if($time['day'] == $tomorrowDay) {
				$curShift = strtotime($tomorrow.' '.$time['start_hour'].':'.$time['start_min'].':00 '.$time['start_meridian']);
			}
			else {
				$curShift = strtotime($curDate.' '.$time['start_hour'].':'.$time['start_min'].':00 '.$time['start_meridian']);
			}
			
			//if the shift occurs later than the current time, we want it
			if($curShift >= $now) {
				$show_ids[$curShift] = $shift->post_id;
			}
			
		}
	}
	
	//sort the shows by start time
	ksort($show_ids);
	
	//grab the number of shows from the list the user wants to display
	$show_ids = array_slice($show_ids, 0, $limit);
	
	//fetch detailed show information
	$shows = array();
	foreach($show_ids as $id) {
		$shows['all'][$id] = get_post($id);
	}
	$shows['type'] = 'shows';
	
	//return the information
	return $shows;
}

//shortcode for displaying upcoming DJs/shows
function dj_coming_up($atts) {
	extract( shortcode_atts( array(
			'title' => '',
			'show_avatar' => 0,
			'show_link' => 0,
			'limit' => 1,
			'time' => '12'
	), $atts ) );

	//find out which DJ(s) are coming up today
	$djs = dj_get_next($limit);

	$dj_str = '';

	$dj_str .= '<div class="on-air-embedded">';
	if($title != '') {
		$dj_str .= '<h3>'.$title.'</h3>';
	}
	$dj_str .= '<ul class="on-air-list">';

	//echo the show/dj currently on-air
	if(count($djs['all']) > 0) {
		foreach($djs['all'] as $dj) {
			//print_r($dj);
			$dj_str .= '<li class="on-air-dj">';
			if($show_avatar) {
				$dj_str .= '<span class="on-air-dj-avatar">'.get_the_post_thumbnail($dj->ID, 'thumbnail').'</span>';
			}

			if($show_link) {
				$dj_str .= '<a href="';
				$dj_str .= get_permalink($dj->ID);
				$dj_str .= '">';
				$dj_str .= $dj->post_title.'</a>';
			}
			else {
				$dj_str .= $dj->post_title;
			}
			
			$scheds = get_post_meta($dj->ID, 'show_sched', true);
				
			$dj_str .= '<span class="radio-clear"></span>';
			
			foreach($scheds as $sched) {
				$dj_str .= '<span class="on-air-dj-sched">'.$sched['day'].'s, '.$sched['start_hour'].':'.$sched['start_min'].' ';
				if($time == 12) {
					$dj_str .= $sched['start_meridian'];
				}
			
				$dj_str .= '-'.$sched['end_hour'].':'.$sched['end_min'].' ';
				if($time == 12) {
					$dj_str .= $sched['end_meridian'];
				}
			
				$dj_str .= '</span><br />';
			}
			
				
			$dj_str .= '</li>';
		}
	}
	else {
		$dj_str .= '<li class="on-air-dj default-dj">None Upcoming</li>';
	}

	$dj_str .= '</ul>';
	$dj_str .= '</div>';

	return $dj_str;

}
add_shortcode( 'dj-coming-up-widget', 'dj_coming_up');

/* Sidebar widget functions */
class DJ_Widget extends WP_Widget {
	
	function DJ_Widget() {
		$widget_ops = array('classname' => 'DJ_Widget', 'description' => 'The current on-air DJ.');
		$this->WP_Widget('DJ_Widget', 'Radio Station: DJ On-Air', $widget_ops);
	}
 
	function form($instance) {
		$instance = wp_parse_args((array) $instance, array( 'title' => '' ));
		$title = $instance['title'];
		$djavatar = $instance['djavatar'];
		$default = $instance['default'];
		$link = $instance['link'];
		$time = $instance['time'];
		
		?>
			<p>
		  		<label for="<?php echo $this->get_field_id('title'); ?>">Title: 
		  		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		  		</label>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('djavatar'); ?>"> 
		  		<input id="<?php echo $this->get_field_id('djavatar'); ?>" name="<?php echo $this->get_field_name('djavatar'); ?>" type="checkbox" <?php if($djavatar) { echo 'checked="checked"'; } ?> /> 
		  		Show Avatars
		  		</label>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('link'); ?>"> 
		  		<input id="<?php echo $this->get_field_id('link'); ?>" name="<?php echo $this->get_field_name('link'); ?>" type="checkbox" <?php if($link) { echo 'checked="checked"'; } ?> /> 
		  		Link to DJ's user profile
		  		</label>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('default'); ?>">Default DJ Name: 
		  		<input class="widefat" id="<?php echo $this->get_field_id('default'); ?>" name="<?php echo $this->get_field_name('default'); ?>" type="text" value="<?php echo esc_attr($default); ?>" />
		  		</label>
		  		<small>If no DJ is scheduled for the current hour, display this name/text.</small>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('time'); ?>">Time Format:<br /> 
		  		<select id="<?php echo $this->get_field_id('time'); ?>" name="<?php echo $this->get_field_name('time'); ?>">
		  			<option value="12" <?php if(esc_attr($time) == 12) { echo 'selected="selected"'; } ?>>12-hour</option>
		  			<option value="24" <?php if(esc_attr($time) == 24) { echo 'selected="selected"'; } ?>>24-hour</option>
		  		</select>
		  		</label><br />
		  		<small>Choose time format for displayed schedules.</small>
		  	</p>
		<?php
	}
 
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['djavatar'] = ( isset( $new_instance['djavatar'] ) ? 1 : 0 );
		$instance['link'] = ( isset( $new_instance['link'] ) ? 1 : 0 );
		$instance['default'] = $new_instance['default'];
		$instance['time'] = $new_instance['time'];
		return $instance;
	}
 
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
 
		echo $before_widget;
		$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		$djavatar = $instance['djavatar'];
		$link = $instance['link'];
 		$default = empty($instance['default']) ? '' : $instance['default'];
 		$time = empty($instance['time']) ? '' : $instance['time'];
		
 		//fetch the current DJs
		$djs = dj_get_current();
		$playlist = myplaylist_get_now_playing();
		?>
		<div class="widget">
			<?php 
				if (!empty($title)) {
					echo $before_title . $title . $after_title;
				}
				else {
					echo $before_title.$after_title;
				}
			?>
			
			<ul class="on-air-list">
				<?php 
				//find out which DJ/show is currently scheduled to be on-air and display them
				
				if(isset($djs['all']) && count($djs['all']) > 0) {
					foreach($djs['all'] as $dj) {
						
						$scheds = get_post_meta($dj->ID, 'show_sched', true);
						
						echo '<li class="on-air-dj">';
						if($djavatar) {
							echo '<span class="on-air-dj-avatar">'.get_the_post_thumbnail($dj->ID, 'thumbnail').'</span>';
						}
							
						if($link) {
							echo '<a href="';
							echo get_permalink($dj->ID);
							echo '">';
							echo $dj->post_title.'</a>';
						}
						else {
							echo $dj->post_title;
						}
						
						echo '<span class="on-air-dj-playlist"><a href="'.$playlist['playlist_permalink'].'">View Playlist</a></span>';
						
						echo '<span class="radio-clear"></span>';
						foreach($scheds as $sched) {
							if($time == 12) {
								echo '<span class="on-air-dj-sched">'.$sched['day'].'s, '.$sched['start_hour'].':'.$sched['start_min'].' '.$sched['start_meridian'].'-'.$sched['end_hour'].':'.$sched['end_min'].' '.$sched['end_meridian'].'</span><br />';
							}
							else {
								if($sched['start_meridian'] == 'pm' && $sched['start_hour'] != 12) {
									$sched['start_hour'] = $sched['start_hour'] + 12;
								}
								if($sched['start_meridian'] == 'am' && $sched['start_hour'] < 10) {
									$sched['start_hour'] = "0".$sched['start_hour'];
								}
								if($sched['start_meridian'] == 'am' && $sched['start_hour'] == 12) {
									$sched['start_hour'] = '00';
								}
								
								if($sched['end_meridian'] == 'pm' && $sched['end_hour'] != 12) {
									$sched['end_hour'] = $sched['end_hour'] + 12;
								}
								if($sched['end_meridian'] == 'am' && $sched['end_hour'] < 10) {
									$sched['end_hour'] = "0".$sched['end_hour'];
								}
								if($sched['end_meridian'] == 'am' && $sched['end_hour'] == 12) {
									$sched['end_hour'] = '00';
								}
								
								echo '<span class="on-air-dj-sched">'.$sched['day'].'s, '.$sched['start_hour'].':'.$sched['start_min'].' '.'-'.$sched['end_hour'].':'.$sched['end_min'].'</span><br />';
							}
						}
						echo '</li>';
						
					}
				}
				else {
					echo '<li class="on-air-dj default-dj">'.$default.'</li>';
				}
				
				?>
			</ul>
		</div>
		<?php
 
		echo $after_widget;
	}
}
add_action( 'widgets_init', create_function('', 'return register_widget("DJ_Widget");') );


/* Sidebar widget functions */
class DJ_Upcoming_Widget extends WP_Widget {

	function DJ_Upcoming_Widget() {
		$widget_ops = array('classname' => 'DJ_Upcoming_Widget', 'description' => 'The upcoming DJs/Shows.');
		$this->WP_Widget('DJ_Upcoming_Widget', 'Radio Station: Upcoming DJ On-Air', $widget_ops);
	}

	function form($instance) {
		$instance = wp_parse_args((array) $instance, array( 'title' => '' ));
		$title = $instance['title'];
		$djavatar = $instance['djavatar'];
		$default = $instance['default'];
		$link = $instance['link'];
		$limit = $instance['limit'];
		$time = $instance['time'];

		?>
			<p>
		  		<label for="<?php echo $this->get_field_id('title'); ?>">Title: 
		  		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		  		</label>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('djavatar'); ?>"> 
		  		<input id="<?php echo $this->get_field_id('djavatar'); ?>" name="<?php echo $this->get_field_name('djavatar'); ?>" type="checkbox" <?php if($djavatar) { echo 'checked="checked"'; } ?> /> 
		  		Show Avatars
		  		</label>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('link'); ?>"> 
		  		<input id="<?php echo $this->get_field_id('link'); ?>" name="<?php echo $this->get_field_name('link'); ?>" type="checkbox" <?php if($link) { echo 'checked="checked"'; } ?> /> 
		  		Link to DJ's user profile
		  		</label>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('default'); ?>">No Additional Schedules: 
		  		<input class="widefat" id="<?php echo $this->get_field_id('default'); ?>" name="<?php echo $this->get_field_name('default'); ?>" type="text" value="<?php echo esc_attr($default); ?>" />
		  		</label>
		  		<small>If no DJ is scheduled for the current hour, display this name/text.</small>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('limit'); ?>">Limit: 
		  		<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo esc_attr($limit); ?>" />
		  		</label>
		  		<small>Number of upcoming DJs/Shows to display.</small>
		  	</p>
		  	
		  	<p>
		  		<label for="<?php echo $this->get_field_id('time'); ?>">Time Format:<br /> 
		  		<select id="<?php echo $this->get_field_id('time'); ?>" name="<?php echo $this->get_field_name('time'); ?>">
		  			<option value="12" <?php if(esc_attr($time) == 12) { echo 'selected="selected"'; } ?>>12-hour</option>
		  			<option value="24" <?php if(esc_attr($time) == 24) { echo 'selected="selected"'; } ?>>24-hour</option>
		  		</select>
		  		</label><br />
		  		<small>Choose time format for displayed schedules.</small>
		  	</p>
		<?php
	}
 
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['djavatar'] = ( isset( $new_instance['djavatar'] ) ? 1 : 0 );
		$instance['link'] = ( isset( $new_instance['link'] ) ? 1 : 0 );
		$instance['default'] = $new_instance['default'];
		$instance['limit'] = $new_instance['limit'];
		$instance['time'] = $new_instance['time'];
		return $instance;
	}
 
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
 
		echo $before_widget;
		$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		$djavatar = $instance['djavatar'];
		$link = $instance['link'];
 		$default = empty($instance['default']) ? '' : $instance['default'];
 		$limit = empty($instance['limit']) ? '1' : $instance['limit'];
 		$time = empty($instance['time']) ? '' : $instance['time'];

 		//find out which DJ(s) are coming up today
 		$djs = dj_get_next($limit);
 		?>
 		
 		<div class="widget">
 		<?php
 		if (!empty($title)) {
 			echo $before_title . $title . $after_title;
 		}
 		else {
 			echo $before_title.$after_title;
 		}
 		?>
 		<ul class="on-air-upcoming-list">
			<?php 
		 		//echo the show/dj currently on-air
		 		if(count($djs['all']) > 0) {
		 			foreach($djs['all'] as $dj) {
		 				//print_r($dj);
		 				echo '<li class="on-air-dj">';
		 				if($djavatar) {
		 					echo '<span class="on-air-dj-avatar">'.get_the_post_thumbnail($dj->ID, 'thumbnail').'</span>';
		 				}
		 		
		 				if($link) {
		 					echo '<a href="';
		 					echo get_permalink($dj->ID);
		 					echo '">';
		 					echo $dj->post_title.'</a>';
		 				}
		 				else {
		 					echo $dj->post_title;
		 				}
		 		
		 				$scheds = get_post_meta($dj->ID, 'show_sched', true);
		 				
		 				echo '<span class="radio-clear"></span>';
		 				foreach($scheds as $sched) {
		 					if($time == 12) {
		 						echo '<span class="on-air-dj-sched">'.$sched['day'].'s, '.$sched['start_hour'].':'.$sched['start_min'].' '.$sched['start_meridian'].'-'.$sched['end_hour'].':'.$sched['end_min'].' '.$sched['end_meridian'].'</span><br />';
		 					}
		 					else {
		 						if($sched['start_meridian'] == 'pm' && $sched['start_hour'] != 12) {
		 							$sched['start_hour'] = $sched['start_hour'] + 12;
		 						}
		 						if($sched['start_meridian'] == 'am' && $sched['start_hour'] < 10) {
		 							$sched['start_hour'] = "0".$sched['start_hour'];
		 						}
		 						if($sched['start_meridian'] == 'am' && $sched['start_hour'] == 12) {
		 							$sched['start_hour'] = '00';
		 						}
		 						
		 						if($sched['end_meridian'] == 'pm' && $sched['end_hour'] != 12) {
		 							$sched['end_hour'] = $sched['end_hour'] + 12;
		 						}
		 						if($sched['end_meridian'] == 'am' && $sched['end_hour'] < 10) {
		 							$sched['end_hour'] = "0".$sched['end_hour'];
		 						}
		 						if($sched['end_meridian'] == 'am' && $sched['end_hour'] == 12) {
		 							$sched['end_hour'] = '00';
		 						}
		 					
		 						echo '<span class="on-air-dj-sched">'.$sched['day'].'s, '.$sched['start_hour'].':'.$sched['start_min'].' '.'-'.$sched['end_hour'].':'.$sched['end_min'].'</span><br />';
		 					}
		 				}
		 		
		 				echo '</li>';
		 			}
		 		}
		 		else {
		 			if($default != '') {
		 				echo '<li class="on-air-dj default-dj">'.$default.'</li>';
		 			}
		 		}
			?>
			</ul>
		</div>
		<?php
 
		echo $after_widget;
	}
}
add_action( 'widgets_init', create_function('', 'return register_widget("DJ_Upcoming_Widget");') );

?>