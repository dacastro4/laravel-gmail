<?php

namespace Dacastro4\LaravelGmail\Traits;

trait SendsParameters
{
	/**
	 * Ads parameters to the parameters property which is used to send additional parameter in the request.
	 *
	 * @param $query
	 * @param string $column
	 */
	public function add( $query, $column = 'q' )
	{
		if ( isset( $this->params[ $column ] ) ) {
			$this->params[ $column ] = "{$this->params[$column]} $query";
		} else {
			$this->params = array_add( $this->params, $column, $query );
		}

	}
}