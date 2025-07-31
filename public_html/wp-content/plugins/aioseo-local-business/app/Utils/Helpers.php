<?php
namespace AIOSEO\Plugin\Addon\LocalBusiness\Utils;

use AIOSEO\Plugin\Addon\LocalBusiness\Models\Post;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Helpers class.
 *
 * @since 1.1.0
 */
class Helpers {
	/**
	 * Returns the global Local SEO options as an object.
	 *
	 * @since 1.1.0
	 *
	 * @return object Global Local SEO options as an object.
	 */
	public function getLocalBusinessOptions() {
		return json_decode( wp_json_encode( aioseo()->options->localBusiness->all(), JSON_FORCE_OBJECT ) );
	}

	/**
	 * Returns current permalink structure for this post type.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $postTypeName The post type name.
	 * @param  string $postTypeSlug The post type slug.
	 * @return string               The loaded permastruct or a default if not enabled yet.
	 */
	public function getPermaStructure( $postTypeName, $postTypeSlug ) {
		global $wp_rewrite;

		$structure = $wp_rewrite->get_extra_permastruct( $postTypeName );

		if ( ! $structure ) {
			$structure = '/' . $postTypeSlug . '/%' . $postTypeName . '%/';
		}

		// Account for trailing slashes.
		return user_trailingslashit( $structure );
	}

	/**
	 * Gets the data for vue.
	 *
	 * @since 1.1.0
	 *
	 * @param  array  $data       The parent data array to modify.
	 * @param  string $page       The current page.
	 * @return array              An array of data.
	 */
	public function getVueData( $data = [], $page = null ) {
		$data['localBusiness'] = [
			'postTypeName'        => aioseoLocalBusiness()->postType->getName(),
			'postTypeEditLink'    => aioseoLocalBusiness()->postType->getEditLink(),
			'postTypeDefaultSlug' => aioseoLocalBusiness()->postType->getDefaultSlug(),
			'postTypeSingleLabel' => aioseoLocalBusiness()->postType->getSingleLabel(),
			'postTypePluralLabel' => aioseoLocalBusiness()->postType->getPluralLabel(),
			'taxonomyName'        => aioseoLocalBusiness()->taxonomy->getName(),
			'taxonomyDefaultSlug' => aioseoLocalBusiness()->taxonomy->getDefaultSlug(),
			'taxonomySingleLabel' => aioseoLocalBusiness()->taxonomy->getSingleLabel(),
			'taxonomyPluralLabel' => aioseoLocalBusiness()->taxonomy->getPluralLabel(),
			'mapLoadEvent'        => aioseoLocalBusiness()->maps->mapLoadEvent,
			'mapDefaults'         => [
				'center' => [
					'lat' => aioseo()->options->localBusiness->maps->mapOptions->center->getDefault( 'lat' ),
					'lng' => aioseo()->options->localBusiness->maps->mapOptions->center->getDefault( 'lng' )
				],
				'zoom'   => aioseo()->options->localBusiness->maps->mapOptions->getDefault( 'zoom' )
			],
			'importers'           => aioseoLocalBusiness()->import->plugins()
		];

		if ( ! empty( $data['currentPost'] ) && 'post' === $data['currentPost']['context'] && aioseoLocalBusiness()->postType->getName() === $data['currentPost']['postType'] ) {
			$locationData                     = aioseoLocalBusiness()->locations->getLocation( $data['currentPost']['id'] );
			$data['currentPost']['local_seo'] = Post::parseLocalSeoOptions( $locationData );

			$data['currentPost']['local_seo']['maps']['geocodeAddress'] = $this->addressToGeocode( $data['currentPost']['local_seo']['locations']['business']['address'] );

			$locationCategories                           = wp_get_object_terms(
				$data['currentPost']['id'],
				aioseoLocalBusiness()->taxonomy->getName(),
				[ 'fields' => 'ids' ]
			);
			$data['currentPost']['localBusinessCategory'] = current( $locationCategories ) ?: [];

			// Disable TruSEO.
			$data['options']['advanced']['truSeo'] = false;
		}

		if ( aioseo()->helpers->isScreenBase( 'edit' ) && aioseo()->helpers->isScreenPostType( aioseoLocalBusiness()->postType->getName() ) ) {
			// Disable TruSEO.
			$data['options']['advanced']['truSeo'] = false;
		}

		if ( 'local-seo' === $page ) {
			$cptStructure = aioseoLocalBusiness()->postType->getPermaStructure();
			if ( ! empty( $cptStructure ) and stripos( $cptStructure, aioseoLocalBusiness()->postType->getSlug() ) !== false ) {
				$cptStructure = preg_split( '~\/(' . aioseoLocalBusiness()->postType->getSlug() . ')\/~', $cptStructure, - 1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
				foreach ( $cptStructure as &$cptStructureItem ) {
					if ( aioseoLocalBusiness()->postType->getSlug() === $cptStructureItem ) {
						$cptStructureItem = '{slug}';
					} else {
						$cptStructureItem = '/' . $cptStructureItem;
					}
				}
			}

			$taxStructure = aioseoLocalBusiness()->taxonomy->getPermaStructure();
			if ( ! empty( $taxStructure ) and stripos( $taxStructure, aioseoLocalBusiness()->taxonomy->getSlug() ) !== false ) {
				$taxStructure = preg_split( '~\/(' . aioseoLocalBusiness()->taxonomy->getSlug() . ')\/~', $taxStructure, - 1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
				foreach ( $taxStructure as &$taxStructureItem ) {
					if ( aioseoLocalBusiness()->taxonomy->getSlug() === $taxStructureItem ) {
						$taxStructureItem = '{slug}';
					} else {
						$taxStructureItem = '/' . $taxStructureItem;
					}
				}
			}

			$data['localBusiness']['postTypePermalinkStructure'] = $cptStructure;
			$data['localBusiness']['taxonomyPermalinkStructure'] = $taxStructure;

			// getDataObject() pulls from the options state, which is why we use that as the first level here.
			$data['options']['localBusiness']['maps']['geocodeAddress'] = $this->addressToGeocode( aioseo()->options->localBusiness->locations->business->address->all() );

			$data['localBusiness']['enhancedSearchTest'] = aioseoLocalBusiness()->search->testSearch();
		}

		return $data;
	}

	/**
	 * Returns a format address for Google's geocoding.
	 *
	 * @since 1.2.1
	 *
	 * @param  array  $address The address array.
	 * @return string          The formatted address.
	 */
	private function addressToGeocode( $address ) {
		$address = [
			$address['streetLine1'],
			$address['streetLine2'],
			$address['city'],
			$address['state'],
			$address['country'],
			$address['zipCode']
		];

		return implode( ',', array_filter( $address ) );
	}
}