<?php

namespace Dacastro4\LaravelGmail\Facade;

use Illuminate\Support\Facades\Facade;

class LaravelGmail extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'laravelgmail';
	}
}