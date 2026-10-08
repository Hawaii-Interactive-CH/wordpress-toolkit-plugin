<?php

namespace Toolkit\controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Toolkit\utils\ApiAuthService;

class AuthController {

	/**
	 * Erreur renvoyée quand l'extension PHP OpenSSL, nécessaire aux tokens, est absente
	 *
	 * @return WP_Error
	 */
	private static function encryption_unavailable_error() {
		return new WP_Error(
			'encryption_unavailable',
			esc_html__( 'API authentication is unavailable: the OpenSSL PHP extension is not enabled on this server.', 'hi-theme-toolkit' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Vérifie le token master et génère un token transient
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_transient_token( WP_REST_Request $request ) {
		if ( ! ApiAuthService::is_encryption_available() ) {
			return self::encryption_unavailable_error();
		}

		$master_token = $request->get_header( 'Authorization' );

		if ( $master_token && preg_match( '/Bearer\s+(.*)/', $master_token, $matches ) ) {
			$master_token = $matches[1];
		}

		if ( ! ApiAuthService::verify_master_token( $master_token ) ) {
			return new WP_Error( 'invalid_master_token', 'Invalid or missing master token', array( 'status' => 403 ) );
		}

		if ( ! session_id() ) {
			session_start();
		}

		$transient_token = ApiAuthService::generate_transient_token();
		// Stocker le token transient dans la session
		$_SESSION['transient_token'] = $transient_token;
		return new WP_REST_Response( array( 'transient_token' => $transient_token ), 200 );
	}

	/**
	 * Vérifie si le token transient est valide
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return array|WP_Error
	 */
	public static function verify_transient_token( $request ) {
		if ( ! ApiAuthService::is_encryption_available() ) {
			return self::encryption_unavailable_error();
		}

		if ( ! session_id() ) {
			session_start();
		}

		$transient_token = isset( $_SESSION['transient_token'] ) ? sanitize_text_field( wp_unslash( $_SESSION['transient_token'] ) ) : '';
		if ( ! ApiAuthService::verify_token( $transient_token ) ) {
			return new WP_Error( 'invalid_transient_token', 'Token expired or invalid, please provide master token to generate a new transient token', array( 'status' => 403 ) );
		}

		$remaining_time = ApiAuthService::get_transient_remaining_time();
		return array( 'is_valid' => true, 'remaining_time' => $remaining_time . ' second' );
	}

	/**
	 * Vérifie si l'adresse IP de la requête est dans la liste blanche
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return true|WP_Error
	 */
	public function check_whitelist( WP_REST_Request $request ) {
		$whitelist = ApiAuthService::get_whitelist();
		// Si la liste blanche est vide, permettre l'accès
		if ( empty( $whitelist ) ) {
			return true;
		}

		$client_ip = $this->get_client_ip();

		if ( in_array( $client_ip, $whitelist, true ) ) {
			return true;
		} else {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Your IP address is not allowed to access this endpoint.', 'hi-theme-toolkit' ), array( 'status' => 403 ) );
		}
	}

	/**
	 * Récupère l'adresse IP du client, en tenant compte des proxys potentiels.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ipaddress = '';

		// Vérifie les différentes variables de serveur pour trouver l'adresse IP du client
		$client_ip       = $this->get_server_value( 'HTTP_CLIENT_IP' );
		$x_forwarded_for = $this->get_server_value( 'HTTP_X_FORWARDED_FOR' );
		$x_forwarded     = $this->get_server_value( 'HTTP_X_FORWARDED' );
		$forwarded_for   = $this->get_server_value( 'HTTP_FORWARDED_FOR' );
		$forwarded       = $this->get_server_value( 'HTTP_FORWARDED' );
		$remote_addr     = $this->get_server_value( 'REMOTE_ADDR' );

		if ( $this->is_valid_ip( $client_ip ) ) {
			$ipaddress = $client_ip;
		} elseif ( $this->is_valid_ip( $x_forwarded_for ) ) {
			// HTTP_X_FORWARDED_FOR peut contenir une liste d'adresses IP
			$ip_list = explode( ',', $x_forwarded_for );
			$ipaddress = trim( reset( $ip_list ) ); // Utilise la première adresse IP de la liste
		} elseif ( $this->is_valid_ip( $x_forwarded ) ) {
			$ipaddress = $x_forwarded;
		} elseif ( $this->is_valid_ip( $forwarded_for ) ) {
			$ipaddress = $forwarded_for;
		} elseif ( $this->is_valid_ip( $forwarded ) ) {
			$ipaddress = $forwarded;
		} elseif ( $this->is_valid_ip( $remote_addr ) ) {
			$ipaddress = $remote_addr;
		} else {
			$ipaddress = 'UNKNOWN';
		}

		return $ipaddress;
	}

	/**
	 * Récupère une valeur de $_SERVER, unslashée et nettoyée.
	 *
	 * @param string $key The $_SERVER key.
	 * @return string Empty string if the key is not set.
	 */
	private function get_server_value( $key ) {
		return isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : '';
	}

	/**
	 * Valide si une adresse IP est correcte, en tenant compte des adresses IPv4 et IPv
	 *
	 * @param string $ip The IP address to validate.
	 * @return bool
	 */
	private function is_valid_ip( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) !== false;
	}
}
