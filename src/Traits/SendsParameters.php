<?php

namespace Dacastro4\LaravelGmail\Traits;

use Illuminate\Support\Arr;

trait SendsParameters
{

	/**
	 * Adds parameters to the parameters property which is used to send additional parameters in the request.
	 *
	 * @param $query
	 * @param  string  $column
	 */
	public function add($query, $column = 'q')
	{
		$query = urlencode($query);

		if (isset($this->params[$column])) {
			$this->params[$column] = "{$this->params[$column]} $query";
		} else {
			$this->params = Arr::add($this->params, $column, $query);
		}

	}
}
