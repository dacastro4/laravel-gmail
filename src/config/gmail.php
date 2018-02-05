<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Gmail Configuration
	|--------------------------------------------------------------------------
	|
	|
	|
	|  Scopes Available:
	|
	|   *
	|
	|  Credentials File Name
	|
	*/

	'project_id'    => env( 'GOOGLE_PROJECT_ID' ),
	'client_id'     => env( 'GOOGLE_CLIENT_ID' ),
	'client_secret' => env( 'GOOGLE_CLIENT_SECRET' ),
	'redirect_url'  => env( 'GOOGLE_REDIRECT_URI', '/' ),

	'scopes' => [

	],

	/*
	|--------------------------------------------------------------------------
	| Credentials File Name
	|--------------------------------------------------------------------------
	|
	|   :email to use, clients email on the file
	|
	|
	*/

	'credentials_file_name' => env( 'GOOGLE_CREDENTIALS_NAME', 'gmail-json' ),

];
