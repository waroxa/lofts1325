<?php

namespace MPHB\Notifier\PostTypes;

use MPHB\Notifier\Admin\CPTPages\EditNotificationPage;
use MPHB\Notifier\Admin\CPTPages\ManageNotificationsPage;
use MPHB\Admin\Fields\FieldFactory;
use MPHB\Admin\Groups\MetaBoxGroup;
use MPHB\PostTypes\EditableCPT;

/**
 * @since 1.0
 */
class NotificationCPT extends EditableCPT {

	protected $postType = 'mphb_notification';


	public function addActions() {

		parent::addActions();

		add_action( 'admin_menu', array( $this, 'moveSubmenu' ), 1000 );
	}

	public function createManagePage() {

		return new ManageNotificationsPage( $this->postType );
	}

	protected function createEditPage() {

		return new EditNotificationPage( $this->postType, $this->getFieldGroups() );
	}

	public function register() {

		$labels = array(
			'name'                  => esc_html__( 'Notifications', 'mphb-notifier' ),
			'singular_name'         => esc_html__( 'Notification', 'mphb-notifier' ),
			'add_new'               => esc_html_x( 'Add New', 'Add new notification', 'mphb-notifier' ),
			'add_new_item'          => esc_html__( 'Add New Notification', 'mphb-notifier' ),
			'edit_item'             => esc_html__( 'Edit Notification', 'mphb-notifier' ),
			'new_item'              => esc_html__( 'New Notification', 'mphb-notifier' ),
			'view_item'             => esc_html__( 'View Notification', 'mphb-notifier' ),
			'search_items'          => esc_html__( 'Search Notification', 'mphb-notifier' ),
			'not_found'             => esc_html__( 'No notifications found', 'mphb-notifier' ),
			'not_found_in_trash'    => esc_html__( 'No notifications found in Trash', 'mphb-notifier' ),
			'all_items'             => esc_html__( 'Notifications', 'mphb-notifier' ),
			'insert_into_item'      => esc_html__( 'Insert into notification description', 'mphb-notifier' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this notification', 'mphb-notifier' ),
		);

		$args = array(
			'labels'               => $labels,
			'public'               => false,
			'show_ui'              => true,
			'show_in_menu'         => mphb()->menus()->getMainMenuSlug(),
			'supports'             => array( 'title' ),
			'register_meta_box_cb' => array( $this, 'registerMetaBoxes' ),
			'rewrite'              => false,
			'show_in_rest'         => true,
			'map_meta_cap'         => true,
			'capability_type'      => array( 'mphb_notification', 'mphb_notifications' ),
		);

		register_post_type( $this->postType, $args );
	}

	public function getFieldGroups() {

		$settingsGroup = new MetaBoxGroup( 'mphb_notification_settings', esc_html__( 'Settings', 'mphb-notifier' ), $this->postType );
		$emailGroup    = new MetaBoxGroup( 'mphb_notification_email', esc_html__( 'Email', 'mphb-notifier' ), $this->postType );

		$roomTypes = MPHB()->getRoomTypePersistence()->getIdTitleList( array(), array( 0 => __( 'All', 'mphb-notifier' ) ) );

		// Add fields to "Settings" metabox
		$notificationTypes = mphb_notifier_get_notification_types();

		$settingsFields = array(
			FieldFactory::create(
				'mphb_notification_type',
				array(
					'type'    => 'select',
					'label'   => esc_html__( 'Type', 'mphb-notifier' ),
					'list'    => $notificationTypes,
					'default' => 'email',
				)
			),
			FieldFactory::create(
				'mphb_notification_trigger',
				array(
					'type'  => 'trigger-date',
					'label' => esc_html__( 'Trigger', 'mphb-notifier' ),
				)
			),
			FieldFactory::create(
				'mphb_is_disabled_for_reservation_after_trigger',
				array(
					'type'        => 'checkbox',
					'inner_label' => __( 'Skip this notification for reservations made later than the set time frame.', 'mphb-notifier' ),
					'default'     => false,
				)
			),
			FieldFactory::create(
				'mphb_notification_accommodation_type_ids',
				array(
					'type'      => 'multiple-checkbox',
					'label'     => __( 'Accommodations', 'mphb-notifier' ),
					'all_value' => 0,
					'default'   => array( 0 ),
					'list'      => $roomTypes,
				)
			),
			FieldFactory::create(
				'mphb_notification_recipients',
				array(
					'type'                => 'multiple-checkbox',
					'label'               => esc_html__( 'Recipients', 'mphb-notifier' ),
					'list'                => array(
						'admin'    => esc_html__( 'Admin', 'mphb-notifier' ),
						'customer' => esc_html__( 'Customer', 'mphb-notifier' ),
						'custom'   => esc_html__( 'Custom Email Addresses', 'mphb-notifier' ),
					),
					'allow_group_actions' => false,
					'default'             => array(),
				)
			),
			FieldFactory::create(
				'mphb_notification_custom_emails',
				array(
					'type'        => 'text',
					'label'       => esc_html__( 'Custom Email Addresses', 'mphb-notifier' ),
					'description' => esc_html__( 'You can use multiple comma-separated email addresses.', 'mphb-notifier' ),
					'size'        => 'large',
				)
			),
		);

		$settingsGroup->addFields( $settingsFields );

		// Add fields to "Email" metabox
		$emailFields = array(
			'email_subject' => FieldFactory::create(
				'mphb_notification_email_subject',
				array(
					'type'         => 'text',
					'label'        => esc_html__( 'Subject', 'mphb-notifier' ),
					'size'         => 'large',
					'default'      => mphb_notifier()->settings()->getDefaultSubject(),
					'translatable' => true,
				)
			),
			'email_header'  => FieldFactory::create(
				'mphb_notification_email_header',
				array(
					'type'         => 'text',
					'label'        => esc_html__( 'Header', 'mphb-notifier' ),
					'size'         => 'large',
					'default'      => mphb_notifier()->settings()->getDefaultHeader(),
					'translatable' => true,
				)
			),
			'email_message' => FieldFactory::create(
				'mphb_notification_email_message',
				array(
					'type'         => 'rich-editor',
					'label'        => esc_html__( 'Message', 'mphb-notifier' ),
					// We will add "Possible tags:" later in EditNotificationPage
					'description'  => esc_html__( 'To replace the Accommodation Notice 1/Notice 2 tags you use in the email with custom property information, go to Accommodation types to fill in the respective fields.', 'mphb-notifier' ),
					'rows'         => 21,
					'default'      => mphb_notifier()->settings()->getDefaultMessage(),
					'translatable' => true,
				)
			),
		);

		$emailGroup->addFields( $emailFields );

		return array(
			'settings' => $settingsGroup,
			'email'    => $emailGroup,
		);
	}

	/**
	 * Callback for action "admin_menu".
	 * @global array $submenu
	 */
	public function moveSubmenu() {

		global $submenu;

		if ( ! isset( $submenu['mphb_booking_menu'] ) ) {
			return;
		}

		$bookingMenu = &$submenu['mphb_booking_menu'];

		$notificationsIndex = false;
		$syncIndex          = false;

		$currentScreen = 'edit.php?post_type=' . $this->postType;

		foreach ( $bookingMenu as $index => $bookingSubmenu ) {
			if ( ! isset( $bookingSubmenu[2] ) ) {
				continue;
			}

			$screen = $bookingSubmenu[2];

			if ( $screen === $currentScreen ) {
				$notificationsIndex = $index;
			} elseif ( $screen === 'mphb_ical' ) {
				$syncIndex = $index;
			}
		}

		if ( $notificationsIndex !== false && $syncIndex !== false ) {
			$notificationSubmenu = array_splice( $bookingMenu, $notificationsIndex, 1 );
			if ( $notificationsIndex < $syncIndex ) {
				$syncIndex--;
			}
			array_splice( $bookingMenu, $syncIndex, 0, $notificationSubmenu );
		}

		unset( $bookingMenu );
	}
}
