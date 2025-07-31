<?php
namespace AIOSEO\Plugin\Addon\LocalBusiness\Schema\Graphs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LocalBusiness graph class.
 *
 * @since 1.0.0
 */
class LocalBusiness extends Base {
	/**
	 * The Local SEO post ID.
	 *
	 * @since 1.1.0
	 *
	 * @var integer
	 */
	private $postId = null;

	/**
	 *The data for the CPT or the global options.
	 *
	 * @since 1.1.0
	 *
	 * @var object|null
	 */
	protected $dataObject = null;

	/**
	 * Class constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->dataObject = aioseoLocalBusiness()->helpers->getLocalBusinessOptions();

		$postId = get_the_ID();
		if ( $postId && get_post_type( $postId ) === aioseoLocalBusiness()->postType->getName() ) {
			$this->postId     = $postId;
			$this->dataObject = aioseoLocalBusiness()->locations->getLocation( $postId );
		}
	}

	/**
	 * Returns the graph data.
	 *
	 * @since 1.0.0
	 *
	 * @return array The graph data.
	 */
	public function get() {
		if ( empty( $this->dataObject ) ) {
			return [];
		}

		$data = [
			'@type'                     => $this->businessType(),
			'@id'                       => trailingslashit( home_url() ) . '#localbusiness',
			'name'                      => $this->name(),
			'brand'                     => [
				'@id' => trailingslashit( home_url() ) . '#organization'
			],
			'url'                       => $this->postId ? get_permalink( $this->postId ) : trailingslashit( home_url() ),
			'image'                     => $this->businessImage(),
			'logo'                      => $this->postId ? get_permalink( $this->postId ) . '#logo' : trailingslashit( home_url() ) . '#logo',
			'address'                   => $this->address(),
			'email'                     => $this->dataObject->locations->business->contact->email,
			'telephone'                 => $this->phoneNumber(),
			'faxNumber'                 => $this->dataObject->locations->business->contact->fax,
			'priceRange'                => $this->dataObject->locations->business->payment->priceRange,
			'currenciesAccepted'        => $this->acceptedCurrencies(),
			'paymentAccepted'           => $this->dataObject->locations->business->payment->methods,
			'areaServed'                => $this->dataObject->locations->business->areaServed,
			'openingHoursSpecification' => $this->openingHours()
		];

		if ( $data['address'] ) {
			$data['location'] = [
				'@id' => trailingslashit( home_url() ) . '#postaladdress'
			];
		}

		$data += $this->ids();

		return $data;
	}

	/**
	 * Returns the business type.
	 *
	 * @since 1.0.0
	 *
	 * @return string $businessType The business type.
	 */
	private function businessType() {
		$businessType = $this->dataObject->locations->business->businessType;
		if ( is_object( $businessType ) ) {
			$businessType = $businessType->value;
		}

		return ! empty( $businessType ) ? $businessType : 'LocalBusiness';
	}

	/**
	 * Returns the name.
	 *
	 * @since 1.0.0
	 *
	 * @return string $name The name.
	 */
	private function name() {
		$name = $this->dataObject->locations->business->name;
		if ( $name ) {
			return $name;
		}

		// Default a single location to its post title.
		if ( $this->postId ) {
			$name = get_the_title( $this->postId );
			if ( $name ) {
				return $name;
			}
		}

		$name = aioseo()->tags->replaceTags( aioseo()->options->searchAppearance->global->schema->organizationName );
		if ( $name ) {
			return $name;
		}

		return aioseo()->helpers->decodeHtmlEntities( get_bloginfo( 'name' ) );
	}

	/**
	 * Returns the image.
	 *
	 * @since 1.0.0
	 *
	 * @return string $image The image.
	 */
	private function businessImage() {
		$image = $this->dataObject->locations->business->image;
		if ( $image ) {
			return $image;
		}

		// Default a single location to it's post title.
		if ( $this->postId ) {
			$image = get_the_post_thumbnail_url( $this->postId, 'full' );
			if ( $image ) {
				return $image;
			}
		}

		// Fallback to Organization Logo.
		$image = aioseo()->options->searchAppearance->global->schema->organizationLogo;
		if ( $image ) {
			return $image;
		}

		// Fallback to Site Logo.
		return aioseo()->helpers->getSiteLogoUrl();
	}

	/**
	 * Returns the phone number.
	 *
	 * @since 1.0.0
	 *
	 * @return string The phone number.
	 */
	private function phoneNumber() {
		$phoneNumber = $this->dataObject->locations->business->contact->phone;
		if ( $phoneNumber ) {
			return $phoneNumber;
		}
		// Fallback to Organization Phone.
		$phoneNumber = aioseo()->options->searchAppearance->global->schema->phone;
		if ( $phoneNumber ) {
			return $phoneNumber;
		}

		return false;
	}

	/**
	 * Returns the accepted currencies.
	 *
	 * @since 1.0.0
	 *
	 * @return string The accepted currencies as a string, separated by commas.
	 */
	private function acceptedCurrencies() {
		$currencies    = $this->dataObject->locations->business->payment->currenciesAccepted;
		$rawCurrencies = ! empty( $currencies ) ? json_decode( $currencies ) : $currencies;
		if ( ! $rawCurrencies ) {
			return '';
		}

		$currencies = [];
		foreach ( $rawCurrencies as $currency ) {
			$currencies[] = $currency->value;
		}

		return ! empty( $currencies ) ? implode( ', ', $currencies ) : '';
	}

	/**
	 * Returns the opening hours.
	 *
	 * @since 1.0.0
	 *
	 * @return array The opening hours.
	 */
	private function openingHours() {
		$openingHoursObject = $this->dataObject->openingHours;
		if ( isset( $openingHoursObject->useDefaults ) && true === $openingHoursObject->useDefaults ) {
			$openingHoursObject = aioseoLocalBusiness()->helpers->getLocalBusinessOptions()->openingHours;
		}

		if ( ! $openingHoursObject->show ) {
			return [];
		}

		if ( $openingHoursObject->alwaysOpen ) {
			return [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => [
					'https://schema.org/Monday',
					'https://schema.org/Tuesday',
					'https://schema.org/Wednesday',
					'https://schema.org/Thursday',
					'https://schema.org/Friday',
					'https://schema.org/Saturday',
					'https://schema.org/Sunday'
				],
				'opens'     => '00:00',
				'closes'    => '23:59'
			];
		}

		$days = [];
		foreach ( $openingHoursObject->days as $day => $values ) {
			if ( $values->closed ) {
				continue;
			}
			if ( $values->open24h ) {
				$days[] = [
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => [
						'https://schema.org/' . ucfirst( $day )
					],
					'opens'     => '00:00',
					'closes'    => '23:59'
				];
				continue;
			}
			$days[] = [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => [
					'https://schema.org/' . ucfirst( $day )
				],
				'opens'     => $values->openTime,
				'closes'    => $values->closeTime
			];
		}

		return $days;
	}
}