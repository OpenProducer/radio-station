<?php
/**
 * Template for master schedule shortcode tabs style.
 * ref: http://nlb-creations.com/2014/06/06/radio-station-tutorial-creating-a-tabbed-programming-schedule/
 */

// --- get all the required info ---
$schedule = radio_station_get_current_schedule();
$hours = radio_station_get_hours();
$now = strtotime( current_time( 'mysql' ) );
$date = date( 'Y-m-d', $now );
$today =  strtolower( date( 'l', $now ) );
$am = str_replace( ' ', '', radio_station_translate_meridiem( 'am' ) );
$pm = str_replace( ' ', '', radio_station_translate_meridiem( 'pm' ) );
$weekdays = radio_station_get_schedule_weekdays();
$weekdates = radio_station_get_schedule_weekdates( $weekdays, $now );

// --- filter show avatar size ---
$avatar_size = apply_filters( 'radio_station_schedule_show_avatar_size', 'thumbnail', 'tabs' );

// --- filter excerpt length and more ---
if ( $atts['show_desc'] ) {
	$length = apply_filters( 'radio_station_schedule_tabs_excerpt_length', false );
	$more = apply_filters( 'radio_station_schedule_tabs_excerpt_more', '[&hellip;]' );
}

// --- start tabbed schedule output ---
$output .= '<ul id="master-schedule-tabs">';

$panels = '';
$tcount = 0;
// 2.3.0: loop weekdays instead of legacy master list
foreach ( $weekdays as $i => $weekday ) {

	// --- maybe skip all days but those specified ---
	$skip_day = false;
	if ( $atts['days'] ) {
		$days = explode( ',', $atts['day'] );
		$days = trim( $days );
		if ( !in_array( $day, $days ) ) {
			$skip_day = true;
		}
	}

	if ( !$skip_day ) {

		// --- set tab classes ---	
		$weekdate = $weekdates[$weekday];
		$classes = array( 'master-schedule-tabs-day', 'day-' . $i );
		if ( $weekdate == $date ) {
			$classes[] = 'current-day';
			$classes[] = 'selected-day';
		}
		$class = implode( ' ', $classes );

		// 2.2.2: use translate function for weekday string
		$display_day = radio_station_translate_weekday( $weekday );

		// 2.3.0: added left/right arrow responsive controls
		// 2.3.1: added (negative) return to arrow onclick functions
		$arrows = array( 'right' => '&#9658;', 'left' => '&#9668;' );
		$arrows = apply_filters( 'radio_station_schedule_arrows', $arrows, 'tabs' );
		$output .= '<li id="master-schedule-tabs-header-' . strtolower( $weekday ) . '" class="' . esc_attr( $class ) . '">';
		$output .= '<div class="shift-left-arrow">';
		$output .= '<a href="javacript:void(0);" onclick="return radio_shift_tab(\'left\');" title="' . esc_attr( __( 'Previous Day', 'radio-station' ) ) . '">' . $arrows['left'] . '</a>';
		$output .= '</div>';
		$output .= '<div class="master-schedule-tabs-day-name">' . esc_html( $display_day ) . '</div>';
		$output .= '<div class="shift-right-arrow">';
		$output .= '<a href="javacript:void(0);" onclick="return radio_shift_tab(\'right\');" title="' . esc_attr( __( 'Next Day', 'radio-station' ) ) . '">' . $arrows['right'] . '</a>';
		$output .= '</div>';
		$output .= '<div id="master-schedule-tab-bottom-' . strtolower( $weekday ) . '" class="master-schedule-tab-bottom"></div>';
		$output .= '</li>';

		// 2.2.7: separate headings from panels for tab view
		$panels .= '<ul class="master-schedule-tabs-panel" id="master-schedule-tabs-day-' . strtolower( $weekday ) . '">';

		// --- get shifts for this day ---
		if ( isset( $schedule[$weekday] ) ) {
			$shifts = $schedule[$weekday];
		} else {
			$shifts = array();
		}

		$foundshows = false;

		// 2.3.0: loop schedule day shifts instead of hours and minutes
		if ( count( $shifts ) > 0 ) {

			$foundshows = true;

			foreach ( $shifts as $shift ) {

				$show = $shift['show'];

				$show_link = false;
				if ( $atts['show_link'] ) {
					$show_link = $show['url'];
				}
				$show_link = apply_filters( 'radio_station_schedule_show_link', $show_link, $show['id'], 'tabs' );

				// 2.3.0: add genre classes for highlighting
				$classes = array( 'master-schedule-tabs-show' );
				$terms = wp_get_post_terms( $show['id'], RADIO_STATION_GENRES_SLUG, array() );
				if ( $terms && ( count( $terms ) > 0 ) ) {
					foreach ( $terms as $term ) {
						$classes[] = strtolower( $term->slug );
					}
				}
				$class = implode( ' ' , $classes );

				$panels .= '<li class="' . esc_attr( $class ) . '">';

				// --- Show Image ---
				// (defaults to display on)
				if ( $atts['show_image'] ) {
					// 2.3.0: filter show avatar by show and context
					// 2.3.0: maybe link avatar to show
					$show_avatar = radio_station_get_show_avatar( $show['id'], $avatar_size );
					$show_avatar = apply_filters( 'radio_station_schedule_show_avatar', $show_avatar, $show['id'], 'tabs' );
					if ( $show_avatar ) {
						$panels .= '<div class="show-image">';
						if ( $show_link ) {
							$panels .= '<a href="' . esc_url( $show_link ) . '">' . $show_avatar . '</a>';
						} else {
							$panels .= $show_avatar;
						}
						$panels .= '</div>';
					} else {
						$panels .= '<div class="show-image"></div>';
					}
				}

				// --- Show Information ---
				$panels .= '<div class="show-info">';

				// --- show title ---
				if ( $show_link ) {
					$show_title = '<a href="' . esc_url( $show_link ) . '">' . esc_html( $show['name'] ) . '</a>';
				} else {
					$show_title = esc_html( $show['name'] );
				}
				$panels .= '<div class="show-title">';
				$panels .= $show_title;
				$panels .= '</div>';

				// --- show hosts ---
				if ( $atts['show_hosts'] ) {

					$hosts = '';
					if ( $show['hosts'] && is_array( $show['hosts'] ) && ( count( $show['hosts'] ) > 0 ) ) {

						$count = 0;
						$host_count = count( $show['hosts'] );
						$hosts .= '<span class="show-dj-names-leader"> ';
						$hosts .= esc_html( __( 'with', 'radio-station' ) );
						$hosts .= ' </span>';

						foreach ( $show['hosts'] as $host ) {
							$count ++;
							// 2.3.0: added link_hosts attribute check
							if ( $atts['link_hosts'] && !empty( $host['url'] ) ) {
								$hosts .= '<a href="' . esc_url( $host['url'] ) . '">' . esc_html( $host['name'] ) . '</a>';
							} else {
								$hosts .= esc_html( $host['name'] );
							}

							if ( ( ( 1 === $count ) && ( 2 === $host_count ) )
								 || ( ( $host_count > 2 ) && ( ( $host_count - 1 ) === $count ) ) ) {
								$hosts .= ' ' . esc_html( __( 'and', 'radio-station' ) ) . ' ';
							} elseif ( ( $count < $host_count ) && ( $host_count > 2 ) ) {
								$hosts .= ', ';
							}
						}
					}

					$hosts = apply_filters( 'radio_station_schedule_show_hosts', $hosts, $show['id'], 'tabs' );
					if ( $hosts ) {
						$panels .= '<div class="show-dj-names show-host-names">';
						// phpcs:ignore WordPress.Security.OutputNotEscaped
						$panels .= $hosts;
						$panels .= '</div>';
					}
				}

				// --- show times ---
				if ( $atts['show_times'] ) {

					// --- convert shift time data ---
					$shift_start_time = strtotime( $shift['day'] . ' ' . $shift['start'] );
					$shift_end_time = strtotime( $shift['day'] . ' ' . $shift['end'] );

					// --- convert shift time for display ---
					// 2.3.0: updated to use new schedule data
					if ( '00:00 am' == $shift['start'] ) {
						$shift['start'] = '12:00 am';
					}
					if ( '11:59:59 pm' == $shift['end'] ) {
						$shift['end'] = '12:00 am';
					}
					if ( 24 == (int) $atts['time'] ) {
						$start = radio_station_convert_shift_time( $shift['start'], 24 );
						$end = radio_station_convert_shift_time( $shift['end'], 24 );
						$data_format = 'H:i';
					} else {
						$start = str_replace( array( 'am', 'pm'), array( ' ' . $am, ' ' . $pm), $shift['start'] );
						$end = str_replace( array( 'am', 'pm'), array( ' ' . $am, ' ' . $pm), $shift['end'] );
						$data_format = 'g:i a';
					}

					// 2.3.0: filter show time by show and context
					$show_time = '<span class="rs-time rs-start-time" data="' . esc_attr( $shift_start_time ) . '" data-format="H:i">' . $start . '</span>';
					$show_time .= '<span class="rs-sep"> ' . esc_html( __( 'to', 'radio-station' ) ) . ' </span>';
					$show_time .= '<span class="rs-time rs-end-time" data="' . esc_attr( $shift_end_time ) . '" data-format="H:i">' . $end . '</span>';
					$show_time = apply_filters( 'radio_station_schedule_show_time', $show_time, $show['id'], 'tabs' );

					$panels .= '<div class="show-time" id="show-time-' . esc_attr( $tcount ) . '">' . $show_time . '</div>';
					$panels .= '<div class="show-user-time" id="show-user-time-' . esc_attr( $tcount ) . '"></div>';
					$tcount ++;

				}

				// --- encore ---
				// 2.3.0: filter encore switch by show and context
				if ( $atts['show_encore'] ) {
					if ( isset( $shift['encore'] ) ) {
						$show_encore = $shift['encore'];
					} else {
						$show_encore = false;
					}
					$show_encore = apply_filters( 'radio_station_schedule_show_encore', $show_encore, $show['id'], 'tabs' );
					if ( 'on' == $show_encore ) {
						$panels .= '<div class="show-encore">';
						$panels .= esc_html( __( 'encore airing', 'radio-station' ) );
						$panels .= '</div>';
					}
				}

				// --- show audio file ---
				if ( $atts['show_file'] ) {
					// 2.3.0: filter audio file by show and context
					$show_file = get_post_meta( $show['id'], 'show_file', true );
					$show_file = apply_filters( 'radio_station_schedule_show_link', $show_file, $show['id'], 'tabs' );
					if ( $show_file && !empty( $show_file ) ) {
						$panels .= '<div class="show-file">';
						$panels .= '<a href="' . esc_url( $show_file ) . '">';
						$panels .= esc_html( __( 'Audio File', 'radio-station' ) );
						$panels .= '</a>';
						$panels .= '</div>';
					}
				}

				// --- show genres ---
				// (defaults to display on)
				if ( $atts['show_genres'] ) {
					$panels .= '<div class="show-genres">';
					$genres = array();
					if ( count( $terms ) > 0 ) {
						foreach ( $terms as $term ) {
							$genres[] = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
						}
						$genre_display = implode( ', ', $genres );
						$panels .= esc_html( __( 'Genres', 'radio-station' ) ) . ': ' . $genre_display;
					}
					$panels .= '</div>';
				}

				$panels .= '</div>';

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
					$excerpt = apply_filters( 'radio_station_schedule_show_excerpt', $excerpt, $show['id'], 'tabs' );

					// --- output excerpt ---
					$panels .= '<div class="show-desc">';
					$panels .= $excerpt;
					$panels .= '</div>';

				}

				$panels .= '</li>';
			}
		}

		if ( !$foundshows ) {
			$panels .= '<li class="master-schedule-tabs-show">';
			$panels .= esc_html( __( 'No Shows scheduled for this day.', 'radio-station' ) );
			$panels .= '</li>';
		}
	}

	$panels .= '</ul>';
}

$output .= '</ul>';

$output .= '<div id="master-schedule-tab-panels">';
$output .= $panels;
$output .= '</div>';
