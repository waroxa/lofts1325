<?php

namespace MPHB\Notifier\Entities;

/**
 * @since 1.0
 */
class Notification {

	public $id         = 0;
	public $originalId = 0;
	public $title      = '';
	public $isDisabled = false;
	public $type       = 'email';
	public $trigger    = array(
		'period'  => 1,
		'unit'    => 'day',
		'compare' => 'before',
		'field'   => 'check-in',
	);
	public $is_disabled_for_reservation_after_trigger = false;
	public $accommodationTypeIds                                   = array();
	public $recipients   = array(); // "admin", "customer", "custom"
	public $customEmails = array(); // Comma separated emails
	public $subject      = '';
	public $header       = '';
	public $message      = '';

    
	public function __construct( $args = array() ) {

		$args = array_merge(
			array(
				'id'                     => 0,
				'originalId'             => 0,
				'title'                  => '',
				'disabled'               => false,
				'type'                   => 'email',
				'trigger'                => $this->trigger,
				'is_disabled_for_reservation_after_trigger' => false,
				'accommodation_type_ids' => array(),
				'recipients'             => array(),
				'custom_emails'          => array(),
				'email_subject'          => mphb_notifier()->settings()->getDefaultSubject(),
				'email_header'           => mphb_notifier()->settings()->getDefaultHeader(),
				'email_message'          => mphb_notifier()->settings()->getDefaultMessage(),
			),
			$args
		);

		$this->id         = $args['id'];
		$this->originalId = $args['originalId'];
		$this->title      = $args['title'];
		$this->isDisabled = $args['disabled'];
		$this->type       = $args['type'];
		$this->trigger    = $args['trigger'];
		$this->is_disabled_for_reservation_after_trigger = $args['is_disabled_for_reservation_after_trigger'];
		$this->accommodationTypeIds                                   = $args['accommodation_type_ids'];
		$this->recipients   = $args['recipients'];
		$this->customEmails = $args['custom_emails'];
		$this->subject      = $args['email_subject'];
		$this->header       = $args['email_header'];
		$this->message      = $args['email_message'];
	}

	/**
	 * @return bool
	 */
	public function hasRecipients() {
		return ! empty( $this->recipients );
	}

	/**
	 * @param \MPHB\Entities\Booking $booking Optional. Current booking.
	 * @return string[]
	 */
	public function getReceivers( $booking = null ) {

		if ( null == $booking ) {
			return array();
		}

		$emails = array();

		// Is this a translation?
		$originalId = apply_filters(
			'wpml_object_id',
			absint( $this->id ),
			'mphb_notification',
			true,
			$booking->getLanguage()
		);

		if ( absint( $originalId ) != absint( $this->id ) ) {

			$notification = mphb_notifier_get_notification( $originalId );

			return $notification->getReceivers( $booking );
		}

		if ( in_array( 'admin', $this->recipients ) &&
			( ! $booking->isImported() ||
			! mphb_notifier()->settings()->isDoNotSendImportedBookingsToAdmin() ) ) {

			$emails[] = mphb()->settings()->emails()->getHotelAdminEmail();
		}

		if ( in_array( 'customer', $this->recipients ) &&
			( ! $booking->isImported() ||
			! mphb_notifier()->settings()->isDoNotSendImportedBookingsToCustomer() ) ) {

			$customerEmail = $booking->getCustomer()->getEmail();

			if ( ! empty( $customerEmail ) ) {

				$emails[] = $customerEmail;
			}
		}

		if ( in_array( 'custom', $this->recipients ) &&
			( ! $booking->isImported() ||
			! mphb_notifier()->settings()->isDoNotSendImportedBookingsToCustomEmails() ) ) {

			$emails = array_merge( $emails, $this->customEmails );
		}

		return array_unique( $emails );
	}

	/**
	 * @return string
	 */
	public function getSlug() {

		// Decode any %## encoding in the title
		$slug = urldecode( $this->title );

		// Generate slug
		$slug = sanitize_title( $slug, (string) $this->id );

		// Decode any %## encoding again after function sanitize_title(), to
		// translate something like "%d0%be%d0%b4%d0%b8%d0%bd" into "один"
		$slug = urldecode( $slug );

		return $slug;
	}

	/**
	 * Some classes like repositories call getId() to get an ID of the entity.
	 */
	public function getId() {

		return apply_filters( '_mphb_notifier_translate_notfification_id', $this->id );
	}
}
