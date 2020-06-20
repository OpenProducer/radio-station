<?php
/**
 * Template for master schedule shortcode default (table) style.
 */

// --- get all the required info ---
$schedule = radio_station_get_current_schedule();
$hours = radio_station_get_hours();
$now = radio_station_get_now();
$date = radio_station_get_time( 'date', $now );
$today = radio_station_get_time( 'day', $now );
$am = str_replace( ' ', '', radio_station_translate_meridiem( 'am' ) );
$pm = str_replace( ' ', '', radio_station_translate_meridiem( 'pm' ) );

// --- get schedule days and dates ---
// 2.3.2: allow for start day attibute
if ( isset( $atts['start_day'] ) && $atts['start_day'] ) {
	$weekdays = radio_station_get_schedule_weekdays( $atts['start_day'] );
} else {
	$weekdays = radio_station_get_schedule_weekdays();
}
$weekdates = radio_station_get_schedule_weekdates( $weekdays, $now );

// --- filter avatar size ---
$avatar_size = apply_filters( 'radio_station_schedule_show_avatar_size', 'thumbnail', 'table' );

// --- filter excerpt length and more ---
if ( $atts['show_desc'] ) {
	$length = apply_filters( 'radio_station_schedule_table_excerpt_length', false );
	$more = apply_filters( 'radio_station_schedule_table_excerpt_more', '[&hellip;]' );
}

// --- clear floats ---
$output .= '<div style="clear:both;"></div>';

// --- start master program table ---
$output .= '<table id="master-program-schedule" cellspacing="0" cellpadding="0">';

// --- weekday table headings row ---
// 2.3.2: added hour column heading id
$output .= '<tr class="master-program-day-row">';
$output .= '<th id="master-program-hour-heading"></th>';

foreach ( $weekdays as $i => $weekday ) {

	// --- maybe skip all days but those specified ---
	// 2.3.2: improve days attribute checking logic
	$skip_day = false;
	if ( $atts['days'] ) {
		$days = explode( ',', $atts['days'] );
		$found_day = false;
		foreach ( $days as $day ) {
			if ( trim( strtolower( $day ) ) == strtolower( $weekday ) ) {
				$found_day = true;
			}
		}
		if ( !$found_day ) {
			$skip_day = true;
		}
	}

	if ( !$skip_day ) {
	
		// --- set day column heading ---
		$heading = substr( $weekday, 0, 3 );
		$heading = radio_station_translate_weekday( $heading, true );

		// --- get weekdate subheading ---
		// 2.3.2: set day start and end times
		// 2.3.2: add subheading adjustment for timezone
		// 2.3.2: replace strtotime with to_time function for timezone
		$weekdate = $weekdates[$weekday];
		$day_start_time = radio_station_to_time( $weekdate . ' 00:00:00' );
		$day_end_time = $day_start_time + ( 24 * 60 * 60 );
		// $subheading = date( 'jS M', strtotime( $weekdate ) );
		$subheading = radio_station_get_time( 'jS M', $day_start_time );

		// --- set heading classes ---
		// 2.3.0: add day and check for highlighting
		$classes = array( 'master-program-day', 'day-' . $i, strtolower( $weekday ), 'date-' . $weekdate );
		if ( ( $now > $day_start_time ) && ( $now < $day_end_time ) ) {
			$classes[] = 'current-day';
			$classes[] = 'selected-day';
		}
		$class = implode( ' ', $classes );

		// --- output table column heading ---
		// 2.3.0: added left/right arrow responsive controls
		// 2.3.1: added (negative) return to arrow onclick functions
		$arrows = array( 'right' => '&#9658;', 'left' => '&#9668;' );
		$arrows = apply_filters( 'radio_station_schedule_arrows', $arrows, 'table' );
		$output .= '<th class="' . esc_attr( $class ) . '">';
		$output .= '<div class="shift-left-arrow">';
		$output .= '<a href="javascript:void(0);" onclick="return radio_shift_day(\'left\');" title="' . esc_attr( __( 'Previous Day', 'radio-station' ) ) . '">' . $arrows['left'] . '</a>';
		$output .= '</div>';
		$output .= '<div class="headings">';
		$output .= '<div class="day-heading">' . esc_html( $heading ) . '</div>';
		$output .= '<div class="date-subheading">' . esc_html( $subheading ) . '</div>';
		$output .= '</div>';
		$output .= '<div class="shift-right-arrow">';
		$output .= '<a href="javacript:void(0);" onclick="return radio_shift_day(\'right\');" title="' . esc_attr( __( 'Next Day', 'radio-station' ) ) . '">' . $arrows['right'] . '</a>';
		$output .= '</div>';
		// 2.3.2: add day start and end time date
		$output .= '<span class="rs-time rs-start-time" data="' . esc_attr( $day_start_time ) . '"></span>';
		$output .= '<span class="rs-time rs-end-time" data="' . esc_attr( $day_end_time ) . '"></span>';
		
		$output .= '</th>';
		
	} 
}
$output .= '</tr>';

// --- loop schedule hours ---
$tcount = 0;
foreach ( $hours as $hour ) {

	// 2.3.1: fix to set translated hour for display only
	$raw_hour = $hour;
	$hour_display = radio_station_convert_hour( $hour, $atts['time'] );
	if ( 1 == strlen( $hour ) ) {
		$hour = '0' . $hour;
	}

	// --- start hour row ---
	$output .= '<tr class="master-program-hour-row hour-row-' . esc_attr( $raw_hour ) . '">';

	// --- set data format for timezone conversions ---
	if ( 24 == (int) $atts['time'] ) {
		$data_format = "H:i";
	} else {
		$data_format = "g a";
	}

	// --- set heading classes ---
	// 2.3.0: check current hour for highlighting
	// 2.3.1: fix to use untranslated hour (12 hr format bug)
	// 2.3.2: replace strtotime with to_time function for timezone
	$classes = array( 'master-program-hour' );
	$hour_start = radio_station_to_time( $date . ' ' . $hour . ':00' );
	$hour_end = $hour_start + ( 60 * 60 );
	if ( ( $now > $hour_start ) && ( $now < $hour_end ) ) {
		$classes[] = 'current-hour';
	}
	$class = implode( ' ', $classes );

	// --- hour heading ---
	$output .= '<th class="' . esc_attr( $class ) . '">';

	if ( isset( $_GET['hourdebug'] ) && ( '1' == $_GET['hourdebug'] ) ) {
		$output .= '<span style="display:none;">';
		$output .= 'Now' . $now . '(' . date( 'H:i', $now ) . ')<br>';
		$output .= 'Hour Start' . $hour_start . '(' . date( 'H:i', $hour_start ) . ')<br>';
		$output .= 'Hour End' . $hour_end . '(' . date( 'H:i', $hour_end ) . ')<br>';
		$output .= '</span>';
	}
	
	$output .= '<div class="master-program-server-hour rs-time" data="' . esc_attr( $raw_hour ) . '" data-format="' . esc_attr( $data_format ) . '">';
	$output .= esc_html( $hour_display );
	$output .= '</div>';
	$output .= '<br>';
	$output .= '<div class="master-program-user-hour rs-time" data="' . esc_attr( $raw_hour ) . '" data-format="' . esc_attr( $data_format ) . '"></div>';
	$output .= '</th>';

	foreach ( $weekdays as $i => $weekday ) {

		// --- maybe skip all days but those specified ---
		// 2.3.2: improve days attribute checking logic
		$skip_day = false;
		if ( $atts['days'] ) {
			$days = explode( ',', $atts['days'] );
			$found_day = false;
			foreach ( $days as $day ) {
				if ( trim( strtolower( $day ) ) == strtolower( $weekday ) ) {
					$found_day = true;
				}
			}
			if ( !$found_day ) {
				$skip_day = true;
			}
		}

		if ( !$skip_day ) {

			// --- clear the cell ---
			if ( isset( $cell ) ) {
				unset( $cell );
			}
			$cellcontinued = $showcontinued = $overflow = $newshift = false;
			$cellshifts = 0;

			// --- get shifts for this day ---
			if ( isset( $schedule[$weekday] ) ) {
				$shifts = $schedule[$weekday];
			} else {
				$shifts = array();
			}
			$nextday = radio_station_get_next_day( $weekday );

			// --- get weekdates ---
			$weekdate = $weekdates[$weekday];
			$nextdate = $weekdates[$nextday];		

			// --- get hour and next hour start and end times ---
			// 2.3.1: fix to use untranslated hour (12 hr format bug)
			// 2.3.2: replace strtotime with to_time function for timezone
			$hour_start = radio_station_to_time( $weekdate . ' ' . $hour . ':00' );
			$hour_end = $next_hour_start = $hour_start + ( 60 * 60 );
			$next_hour_end = $hour_end + ( 60 * 60 );

			// --- loop the shifts for this day ---
			foreach ( $shifts as $shift ) {

				if ( !isset( $shift['finished'] ) || !$shift['finished'] ) {

					// --- get shift start and end times ---
					// 2.3.2: replace strtotime with to_time function for timezone
					$display = $nowplaying = false;
					if ( '00:00 am' == $shift['start'] ) {
						$shift_start = radio_station_to_time( $weekdate . ' 12:00 am' );
					} else {
						$shift_start = radio_station_to_time( $weekdate . ' ' . $shift['start'] );
					}
					if ( ( '11:59:59 pm' == $shift['end'] ) || ( '12:00 am' == $shift['end'] ) ) {
						// - bugfixed to not use $nextday here -
						$shift_end = radio_station_to_time( $weekdate . ' 11:59:59 pm' ) + 1;
					} else {
						$shift_end = radio_station_to_time( $weekdate . ' ' . $shift['end'] );
					}

					// --- check if the shift is starting / started ---
					if ( isset( $shift['started'] ) && $shift['started'] ) {
						// - continue display of shift -
						if ( !isset( $cell ) ) {
							$cellcontinued = true;
						}
						$display = $showcontinued = true;
						$cellshifts ++;
					} elseif ( ( $shift_start == $hour_start ) 
						|| ( ( $shift_start > $hour_start ) && ( $shift_start < $next_hour_start ) ) ) {
						// - start display of shift -
						$started = $shift['started'] = true;
						$schedule[$weekday][$shift['start']] = $shift;
						$display = $newshift = true;
						// 2.3.1: reset showcontinued flag
						$showcontinued = false;
						$cellshifts ++;
					}

					// --- check if shift is current ---
					if ( ( $now >= $shift_start ) && ( $now < $shift_end ) ) {
						$nowplaying = true;
					}

					// --- check if shift finishes in this hour ---
					if ( isset( $shift['started'] ) && $shift['started'] ) {
						if ( $shift_end == $hour_end ) {
							$finished = $shift['finished'] = true;
							$schedule[$weekday][$shift['start']] = $shift;
							// $fullcell = true;
						} elseif ( $shift_end < $hour_end ) {
							$finished = $shift['finished'] = true;
							$schedule[$weekday][$shift['start']] = $shift;
							// $percent = round( ( $shift_end - $hour_start ) / 3600 );
							// $partcell = true;
						} else {
							$overflow = true;
						}
					}

					if ( isset( $_GET['shiftdebug'] ) && ( '1' == $_GET['shiftdebug'] ) ) {
						if ( !isset( $shiftdebug ) ) {$shiftdebug = '';}
						$shiftdebug .= 'Now: ' . $now . ' (' . radio_station_get_time( 'datetime', $now ) . ') -- Today: ' . $today . '<br>';
						$shiftdebug .= 'Day: ' . $weekday . ' - Raw Hour: ' . $raw_hour . ' - Hour: ' . $hour . ' - Hour Display: ' . $hour_display . '<br>';
						$shiftdebug .= 'Hour Start: ' . $hour_start . ' (' . date( 'Y-m-d l H:i', $hour_start ) . ' - ' . radio_station_get_time( 'l H:i', $hour_start ) . ')<br>';
						$shiftdebug .= 'Hour End: ' . $hour_end . ' (' . date( 'Y-m-d l H: i', $hour_end ) . ' - ' . radio_station_get_time( 'Y-m-d l H:i', $hour_end ) . ')<br>';
						$shiftdebug .= 'Shift Start: ' . $shift_start . ' (' . date( 'Y-m-d l H: i', $shift_start ) . ' - ' . radio_station_get_time( 'Y-m-d l H:i', $shift_start ) . ')' . '<br>';
						$shiftdebug .= 'Shift End: ' . $shift_end . ' (' . date( 'Y-m-d l H: i', $shift_end ) . ' - ' . radio_station_get_time( 'Y-m-d l H:i', $shift_end ) . ')' . '<br>';
						$shiftdebug .= 'Display: ' . ( $display ? 'yes' : 'no' ) . ' - ';
						$shiftdebug .= 'New Shift: ' . ( $newshift ? 'yes' : 'no' ) . ' - ';
						$shiftdebug .= 'Now Playing: ' . ( $nowplaying ? 'YES' : 'no' ) . ' - ';
						$shiftdebug .= 'Cell Continues: ' . ( $cellcontinued ? 'yes' : 'no' ) . ' - ';
						$shiftdebug .= 'Overflow: ' . ( $overflow ? 'yes' : 'no' ) . ' - ';
						$shiftdebug .= 'Show Continued: ' . ( $showcontinued ? 'yes' : 'no' ) . ' - ';
						// $shiftdebug .= 'Shift: ' . print_r( $shift, true ) . '<br>';
					}

					// --- maybe add shift display to the cell ---
					if ( $display ) {

						$show = $shift['show'];

						// --- set the show div classes ---
						$divclasses = array( 'master-show-entry', 'show-id-' . $show['id'], $show['slug'] );
						if ( $nowplaying ) {
							$divclasses[] = 'nowplaying';
						}
						if ( $overflow ) {
							$divclasses[] = 'overflow';
						}
						if ( $showcontinued ) {
							$divclasses[] = 'continued';
						}
						if ( $newshift ) {
							$divclasses[] = 'newshift';
						}
						if ( isset( $show['genres'] ) && is_array( $show['genres'] ) && ( count( $show['genres'] ) > 0 ) ) {
							foreach ( $show['genres'] as $genre ) {
								$divclasses[] = sanitize_title_with_dashes( $genre );
							}
						}
						$divclass = implode( ' ', $divclasses );

						// --- start the cell contents ---
						if ( !isset( $cell ) ) {
							$cell = '';
						}
						$cell .= '<div class="' . esc_attr( $divclass ) . '">';

						if ( $showcontinued ) {

							// --- display empty div (for highlighting) ---
							$cell .= '&nbsp;';
							
							// 2.3.2: set shift times for highlighting
							$cell .= '<span class="rs-time rs-start-time" data="' . esc_attr( $shift_start ) . '"></span>';
							$cell .= '<span class="rs-time rs-end-time" data="' . esc_attr( $shift_end ) . '"></span>';

						} else {

							// --- set filtered show link ---
							// 2.3.0: filter show link via show ID and context
							$show_link = false;
							if ( $atts['show_link'] ) {
								$show_link = $show['url'];
							}
							$show_link = apply_filters( 'radio_station_schedule_show_link', $show_link, $show['id'], 'table' );

							// --- show logo / thumbnail ---
							// 2.3.0: filter show avatar via show ID and context
							$show_avatar = false;
							if ( $atts['show_image'] ) {							
								$show_avatar = radio_station_get_show_avatar( $show['id'], $avatar_size );
							}
							$show_avatar = apply_filters( 'radio_station_schedule_show_avatar', $show_avatar, $show['id'], 'table' );
							if ( $show_avatar ) {
								$cell .= '<div class="show-image">';
								if ( $show_link ) {
									$cell .= '<a href="' . esc_url( $show_link ) . '">' . $show_avatar . '</a>';
								} else {
									$cell .= $show_avatar;
								}
								$cell .= '</div>';
							}

							// --- show title ---
							$cell .= '<div class="show-title">';
							if ( $show_link ) {
								$cell .= '<a href="' . esc_url( $show_link ) . '">' . esc_html( $show['name'] ) . '</a>';
							} else {
								$cell .= esc_html( $show['name'] );
							}
							$cell .= '</div>';

							// --- show DJs / hosts ---
							if ( $atts['show_hosts'] ) {

								$hosts = '';
								if ( $show['hosts'] && is_array( $show['hosts'] ) && ( count( $show['hosts'] ) > 0 ) ) {

									$hosts .= '<span class="show-dj-names-leader show-host-names-leader"> ';
									$hosts .= esc_html( __( 'with', 'radio-station' ) );
									$hosts .= ' </span>';

									$count = 0;
									$hostcount = count( $show['hosts'] );
									foreach ( $show['hosts'] as $host ) {
										$count ++;
										// 2.3.0: added link_hosts attribute check
										if ( $atts['link_hosts'] && !empty( $host['url'] ) ) {
											$hosts .= '<a href="' . esc_url( $host['url'] ) . '">' . esc_html( $host['name'] ) . '</a>';
										} else {
											$hosts .= esc_html( $host['name'] );
										}

										if ( ( ( 1 === $count ) && ( 2 === $hostcount ) )
											 || ( ( $hostcount > 2 ) && ( ( $hostcount - 1 ) === $count ) ) ) {
											$hosts .= ' ' . esc_html( __( 'and', 'radio-station' ) ) . ' ';
										} elseif ( ( $count < $hostcount ) && ( $hostcount > 2 ) ) {
											$hosts .= ', ';
										}
									}
								}

								$hosts = apply_filters( 'radio_station_schedule_show_hosts', $hosts, $show['id'], 'table' );
								if ( $hosts ) {
									$cell .= '<div class="show-dj-names show-host-names">';
									$cell .= $hosts;
									$cell .= '</div>';
								}
							}

							// --- show time ---
							if ( $atts['show_times'] ) {

								// --- convert shift time for display ---
								// 2.3.2: removed duplicate calculate of shift times
								if ( '00:00 am' == $shift['start'] ) {
									$shift['start'] = '12:00 am';
								}
								if ( '11:59:59 pm' == $shift['end'] ) {
									$shift['end'] = '12:00 am';
								}
								if ( 24 == (int) $atts['time'] ) {
									$start = radio_station_convert_shift_time( $shift['start'], 24 );
									// 2.3.2: display real end of split shift
									if ( isset( $shift['split'] ) && $shift['split'] && isset( $shift['real_end'] ) ) {
										$end = radio_station_convert_shift_time( $shift['real_end'], 24 );
									} else {
										$end = radio_station_convert_shift_time( $shift['end'], 24 );
									}
									$data_format = 'H:i';
								} else {
									$start = str_replace( array( 'am', 'pm'), array( ' ' . $am, ' ' . $pm ), $shift['start'] );
									// 2.3.2: display real end of split shift
									if ( isset( $shift['split'] ) && $shift['split'] && isset( $shift['real_end'] ) ) {
										$end = str_replace( array( 'am', 'pm'), array( ' ' . $am, ' ' . $pm ), $shift['real_end'] );
									} else {
										$end = str_replace( array( 'am', 'pm'), array( ' ' . $am, ' ' . $pm ), $shift['end'] );
									}
									$data_format = 'g:i a';
								}

								$show_time = '<span class="rs-time rs-start-time" data="' . esc_attr( $shift_start ) . '" data-format="' . esc_attr( $data_format ) . '">' . $start . '</span>';
								$show_time .= '<span class="rs-sep"> ' . esc_html( __( 'to', 'radio-station' ) ) . ' </span>';
								$show_time .= '<span class="rs-time rs-end-time" data="' . esc_attr( $shift_end ) . '" data-format="' . esc_attr( $data_format ) . '">' . $end . '</span>';
								$show_time = apply_filters( 'radio_station_schedule_show_time', $show_time, $show['id'], 'table' );
								$cell .= '<div class="show-time" id="show-time-' . esc_attr( $tcount ) . '">' . $show_time . '</div>';
								$cell .= '<div class="show-user-time" id="show-user-time-' . esc_attr( $tcount ) . '"></div>';
								$tcount ++;

							}

							// --- encore airing ---
							// 2.3.1: added isset check for encore switch
							$show_encore = false;
							if ( $atts['show_encore'] && isset( $shift['encore'] ) ) {
								$show_encore = $shift['encore'];
							}
							$show_encore = apply_filters( 'radio_station_schedule_show_encore', $shift['encore'], $show['id'], 'table' );
							if ( 'on' == $show_encore ) {
								$cell .= '<div class="show-encore">';
								$cell .= esc_html( __( 'encore airing', 'radio-station' ) );
								$cell .= '</div>';
							}

							// --- show file ---
							$show_file = false;
							if ( $atts['show_file'] ) {
								$show_file = get_post_meta( $show['id'], 'show_file', true );
							}
							$show_file = apply_filters( 'radio_station_schedule_show_file', $show_file, $show['id'], 'table' );
							// 2.3.2: check disable download meta
							$disable_download = get_post_meta( $show['id'], 'show_download', true );
							if ( $show_file && !empty( $show_file ) && !$disable_download ) {
								$cell .= '<div class="show-file">';
								$cell .= '<a href="' . esc_url( $show_file ) . '">';
								$cell .= esc_html( __( 'Audio File', 'radio-station' ) );
								$cell .= '</a>';
								$cell .= '</div>';
							}

							// --- show description ---
							if ( $atts['show_desc'] ) {

								$show_post = get_post( $show['id'] );
								$permalink = get_permalink( $show_post->ID );

								// --- get show excerpt ---
								if ( !empty( $show_post->post_excerpt ) ) {
									$excerpt = $show_post->post_excerpt;
									$excerpt .= ' <a href="' . esc_url( $permalink ) . '">' . $more . '</a>';
								} else {
									$excerpt = radio_station_trim_excerpt( $show_post->post_content, $length, $more, $permalink );
								}

								// --- filter excerpt by context ---
								$excerpt = apply_filters( 'radio_station_schedule_show_excerpt', $excerpt, $show['id'], 'table' );

								// --- output excerpt ---
								$cell .= '<div class="show-desc">';
								$cell .= $excerpt;
								$cell .= '</div>';

							}

						}
						$cell .= '</div>';

						// 2.3.1: fix to ensure reset showcontinued flag in cell
						if ( isset( $shift['finished'] ) && $shift['finished'] ) {
							$showcontinued = false;
						}
					}
				}
			}

			// --- add cell to hour row - weekday column ---
			$cellclasses = array( 'show-info', 'day-' . $i, strtolower( $weekday ), 'date-' . $weekdate );
			if ( $cellcontinued ) {
				$cellclasses[] = 'continued';
			}
			if ( $overflow ) {
				$cellclasses[] = 'overflow';
			}
			if ( $cellshifts > 0 ) {
				$cellclasses[] = $cellshifts . '-shifts';
			}
			$cellclass = implode( ' ', $cellclasses );
			$output .= '<td class="' . $cellclass . '">';
			$output .= "<div class='show-wrap'>";
			if ( isset( $cell ) ) {
				$output .= $cell;
			}
			$output .= "</div>";
			$output .= '</td>';
		}
	}

	// --- close hour row ---
	$output .= '</tr>';
}

$output .= '</table>';

if ( isset( $_GET['shiftdebug'] ) && ( '1' == $_GET['shiftdebug'] ) ) {
	$output .= $shiftdebug;
}
