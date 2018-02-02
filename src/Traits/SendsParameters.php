<?php

namespace Dacastro4\LaravelGmail\Traits;

trait SendsParameters
{
	public function add( $query, $column = 'q' )
	{
		if(isset($this->params[$column])) {
			$this->params[$column] = "{$this->params[$column]} $query";
		} else {
			$this->params = array_add( $this->params, $column, $query );
		}

	}
}