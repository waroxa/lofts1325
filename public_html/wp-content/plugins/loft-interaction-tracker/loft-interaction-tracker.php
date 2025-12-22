<?php
/**
 * Plugin Name: Loft Interaction Tracker
 * Description: Tracks room search clicks and checkout submissions with error details for the admin dashboard.
 * Version: 1.0.0
 * Author: Loft 1325
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Loft_Interaction_Tracker {

	const NONCE_ACTION = 'loft-it-nonce';

	/**
	 * @var string
	 */
	private $table_name;

	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'loft_interaction_logs';

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_loft_it_track', array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_loft_it_track', array( $this, 'handle_ajax' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'mphb_sc_checkout_errors_content', array( $this, 'capture_checkout_errors' ), 1 );
		add_action( 'mphb_booking_status_changed', array( $this, 'capture_booking_status' ), 10, 2 );
	}

	public function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $this->table_name;

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(64) NOT NULL,
			status varchar(64) DEFAULT '',
			details longtext,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	public function enqueue_scripts() {
		if ( is_admin() ) {
			return;
		}

		wp_register_script(
			'loft-interaction-tracker',
			plugins_url( 'assets/js/interaction-tracker.js', __FILE__ ),
			array(),
			'1.0.0',
			true
		);

		wp_localize_script(
			'loft-interaction-tracker',
			'LoftInteractionTrackerData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			)
		);

		wp_enqueue_script( 'loft-interaction-tracker' );
	}

	public function handle_ajax() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$event_type = isset( $_POST['eventType'] ) ? sanitize_key( wp_unslash( $_POST['eventType'] ) ) : '';
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$details    = array();

		if ( isset( $_POST['details'] ) ) {
			$decoded_details = json_decode( wp_unslash( $_POST['details'] ), true );
			if ( is_array( $decoded_details ) ) {
				$details = $this->sanitize_details( $decoded_details );
			}
		}

		if ( empty( $event_type ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Event type is required.', 'loft-interaction-tracker' ),
				),
				400
			);
		}

		$this->log_event( $event_type, $status, $details );

		wp_send_json_success();
	}

	public function register_admin_page() {
		add_menu_page(
			__( 'Interaction Tracker', 'loft-interaction-tracker' ),
			__( 'Interaction Tracker', 'loft-interaction-tracker' ),
			'manage_options',
			'loft-interaction-tracker',
			array( $this, 'render_admin_page' ),
			'dashicons-chart-bar',
			58
		);
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		$totals = $wpdb->get_results(
			"SELECT event_type, status, COUNT(*) as total FROM {$this->table_name} GROUP BY event_type, status",
			ARRAY_A
		);

		$recent = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, event_type, status, details, created_at FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
				30
			),
			ARRAY_A
		);

		$organized_totals = $this->organize_totals( $totals );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Interaction Tracker', 'loft-interaction-tracker' ); ?></h1>
			<p><?php esc_html_e( 'Track landing page room searches and checkout submissions, including error details.', 'loft-interaction-tracker' ); ?></p>
			<style>
				.loft-it-summary {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
					gap: 16px;
					margin: 16px 0 24px;
				}
				.loft-it-card {
					background: #fff;
					border: 1px solid #ccd0d4;
					border-radius: 4px;
					padding: 16px;
					box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
				}
				.loft-it-card h3 {
					margin: 0 0 8px;
				}
				.loft-it-total {
					font-size: 28px;
					margin: 0 0 8px;
				}
				.loft-it-breakdown {
					margin: 0;
					padding-left: 18px;
				}
			</style>

			<div class="loft-it-summary">
				<?php $this->render_summary_card( __( 'Search clicks', 'loft-interaction-tracker' ), $organized_totals, 'search_click' ); ?>
				<?php $this->render_summary_card( __( 'Checkout submissions', 'loft-interaction-tracker' ), $organized_totals, 'checkout_submit' ); ?>
				<?php $this->render_summary_card( __( 'Checkout errors', 'loft-interaction-tracker' ), $organized_totals, 'checkout_error' ); ?>
				<?php $this->render_summary_card( __( 'Booking status changes', 'loft-interaction-tracker' ), $organized_totals, 'checkout_status' ); ?>
			</div>

			<h2><?php esc_html_e( 'Recent events', 'loft-interaction-tracker' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'loft-interaction-tracker' ); ?></th>
						<th><?php esc_html_e( 'Event', 'loft-interaction-tracker' ); ?></th>
						<th><?php esc_html_e( 'Status', 'loft-interaction-tracker' ); ?></th>
						<th><?php esc_html_e( 'Details', 'loft-interaction-tracker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['created_at'] ); ?></td>
							<td><?php echo esc_html( $row['event_type'] ); ?></td>
							<td><?php echo esc_html( $row['status'] ); ?></td>
							<td>
								<?php $this->render_details( $row['details'] ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( empty( $recent ) ) : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( 'No activity logged yet.', 'loft-interaction-tracker' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function capture_checkout_errors( $errors ) {
		if ( is_admin() ) {
			return;
		}

		if ( empty( $errors ) || ! is_array( $errors ) ) {
			return;
		}

		$details = array(
			'errors'   => array_map( 'wp_strip_all_tags', $errors ),
			'request'  => $this->filter_checkout_request(),
			'page'     => $this->current_page_url(),
			'is_admin' => is_admin(),
		);

		$this->log_event( 'checkout_error', 'error', $details );
	}

	public function capture_booking_status( $booking, $old_status ) {
		if ( ! is_object( $booking ) || ! method_exists( $booking, 'getId' ) ) {
			return;
		}

		$details = array(
			'booking_id' => $booking->getId(),
			'old_status' => $old_status,
			'new_status' => method_exists( $booking, 'getStatus' ) ? $booking->getStatus() : '',
			'total'      => method_exists( $booking, 'getTotalPrice' ) ? $booking->getTotalPrice() : '',
			'source'     => is_admin() ? 'admin' : 'site',
		);

		$this->log_event( 'checkout_status', $details['new_status'], $details );
	}

	private function log_event( $event_type, $status, $details = array() ) {
		global $wpdb;

		$event_type = sanitize_key( $event_type );
		$status     = sanitize_text_field( $status );
		$payload    = ! empty( $details ) ? $this->sanitize_details( $details ) : array();

		$wpdb->insert(
			$this->table_name,
			array(
				'event_type' => $event_type,
				'status'     => $status,
				'details'    => ! empty( $payload ) ? wp_json_encode( $payload ) : '',
				'created_at' => current_time( 'mysql' ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	private function sanitize_details( $details ) {
		if ( is_array( $details ) ) {
			$sanitized = array();

			foreach ( $details as $key => $value ) {
				$clean_key            = is_string( $key ) ? sanitize_key( $key ) : $key;
				$sanitized[ $clean_key ] = $this->sanitize_details( $value );
			}

			return $sanitized;
		}

		if ( is_scalar( $details ) ) {
			return sanitize_text_field( (string) $details );
		}

		return '';
	}

	private function filter_checkout_request() {
		$fields  = array(
			'mphb_check_in_date',
			'mphb_check_out_date',
			'mphb_adults',
			'mphb_children',
		);
		$payload = array();

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$payload[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		if ( isset( $_POST['mphb_rooms_details'] ) && is_array( $_POST['mphb_rooms_details'] ) ) {
			$payload['rooms'] = array_map( 'absint', wp_unslash( $_POST['mphb_rooms_details'] ) );
		} elseif ( isset( $_POST['mphb_room_details'] ) && is_array( $_POST['mphb_room_details'] ) ) {
			$payload['rooms'] = array_map( 'absint', wp_unslash( $_POST['mphb_room_details'] ) );
		}

		return $payload;
	}

	private function current_page_url() {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return $scheme . $host . $uri;
	}

	private function organize_totals( $totals ) {
		$organized = array();

		foreach ( $totals as $row ) {
			$event_type = isset( $row['event_type'] ) ? $row['event_type'] : '';
			$status     = isset( $row['status'] ) ? $row['status'] : '';
			$total      = isset( $row['total'] ) ? absint( $row['total'] ) : 0;

			if ( ! isset( $organized[ $event_type ] ) ) {
				$organized[ $event_type ] = array(
					'total'   => 0,
					'by_state' => array(),
				);
			}

			$organized[ $event_type ]['total']     += $total;
			$organized[ $event_type ]['by_state'][ $status ] = $total;
		}

		return $organized;
	}

	private function render_summary_card( $label, $totals, $key ) {
		$count    = isset( $totals[ $key ]['total'] ) ? absint( $totals[ $key ]['total'] ) : 0;
		$by_state = isset( $totals[ $key ]['by_state'] ) ? $totals[ $key ]['by_state'] : array();
		?>
		<div class="loft-it-card">
			<h3><?php echo esc_html( $label ); ?></h3>
			<p class="loft-it-total"><?php echo esc_html( number_format_i18n( $count ) ); ?></p>
			<?php if ( ! empty( $by_state ) ) : ?>
				<ul class="loft-it-breakdown">
					<?php foreach ( $by_state as $status => $amount ) : ?>
						<li>
							<strong><?php echo esc_html( $status ? $status : __( 'Unspecified', 'loft-interaction-tracker' ) ); ?>:</strong>
							<?php echo esc_html( number_format_i18n( $amount ) ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_details( $details_json ) {
		$decoded = json_decode( $details_json, true );

		if ( empty( $decoded ) ) {
			esc_html_e( 'No details', 'loft-interaction-tracker' );
			return;
		}

		echo '<details>';
		echo '<summary>' . esc_html__( 'View', 'loft-interaction-tracker' ) . '</summary>';
		echo '<pre style="white-space:pre-wrap;">' . esc_html( wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</pre>';
		echo '</details>';
	}
}

new Loft_Interaction_Tracker();
