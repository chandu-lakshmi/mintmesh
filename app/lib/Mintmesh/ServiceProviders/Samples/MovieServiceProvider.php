<?php namespace Mintmesh\ServiceProviders;

use Illuminate\Support\ServiceProvider;

class MovieServiceProvider extends ServiceProvider {

	public function register()
	{
		// Bind the movie repository interface to our Eloquent-specific implementation
		// This service provider is called every time the application starts
		$this->app->bind(
			'Mintmesh\Repositories\Samples\MovieRepository',
			'Mintmesh\Repositories\Samples\NeoeloquentMovieRepository',
			'Mintmesh\Services\Validators\Samples\MovieValidator'
		);  
	}

}