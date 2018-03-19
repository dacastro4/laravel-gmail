<?php

namespace Dacastro4\LaravelGmail\Traits;

trait HasHeaders
{

	/**
	 * Gets a single header from an existing email by name.
	 *
	 * @param $headerName
	 *
	 * @param string $regex if this is set, value will be evaluated with the give regular expression.
	 *
	 * @return null|string
	 */
	public function getHeader( $headerName, $regex = null )
	{
		$headers = $this->getHeaders();

		$value = null;

		foreach ( $headers as $header ) {
			if ( $header->key === $headerName ) {
				$value = $header->value;
				if ( ! is_null( $regex ) ) {
					preg_match_all( $regex, $header->value, $value );
				}
				break;
			}
		}

		if ( is_array( $value ) ) {
			return isset( $value[ 1 ] ) ? $value[ 1 ] : null;
		}

		return $value;
	}

	public abstract function getHeaders();

}