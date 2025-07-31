<?php

/**
 * @since 1.2.6
 */

namespace MPHBR\UsersAndRoles;

class Capabilities {

	const TAX_NAME = 'mphbr_ratings';

	/**
	 * @var array
	 */
	public $capabilities;

	/**
	 * @var array
	 */
	public $roles;

	public function __construct() {

		$this->mapCapabilitiesToRoles();
		$this->mapRolesToCapabilities();
	}

	public static function setup() {

		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}

		$customRoles = MPHBR()->capabilities()->getRoles();

		if ( ! empty( $customRoles ) ) {
			foreach ( $customRoles as $role => $capabilities ) {
				if ( ! empty( $capabilities ) ) {
					foreach ( $capabilities as $cap ) {
						$wp_roles->add_cap( $role, $cap );
					}
				}
			}
		}
	}

	public function mapCapabilitiesToRoles() {

		$plural = self::TAX_NAME;

		$caps = array(
			'manage_terms' => "manage_{$plural}",
			'edit_terms'   => "manage_{$plural}",
			'delete_terms' => "manage_{$plural}",
			'assign_terms' => "edit_{$plural}",
		);

		foreach ( $caps as $cap ) {

			if ( ! isset( $this->capabilities[ $cap ] ) ) {
				$this->capabilities[ $cap ] = array();
			}

			array_push( $this->capabilities[ $cap ], 'administrator' );

			if (
				class_exists( '\MPHB\UsersAndRoles\Roles' ) &&
				defined( '\MPHB\UsersAndRoles\Roles::MANAGER' )
			) {
				array_push( $this->capabilities[ $cap ], \MPHB\UsersAndRoles\Roles::MANAGER );
			}
		}
	}

	public function mapRolesToCapabilities() {

		if ( ! empty( $this->capabilities ) ) {

			foreach ( $this->capabilities as $capability => $roles ) {

				array_map(
					function ( $role ) use ( $capability ) {

						if ( ! isset( $this->roles[ $role ] ) ) {
							$this->roles[ $role ] = array();
						}

						if ( ! in_array( $capability, $this->roles[ $role ] ) ) {
							array_push( $this->roles[ $role ], $capability );
						}
					},
					$roles
				);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getCapabilities() {
		return $this->capabilities;
	}

	/**
	 * @return array
	 */
	public function getRoles() {
		return $this->roles;
	}
}
