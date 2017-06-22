<?php namespace Mintmesh\Repositories\Samples;

interface MovieRepository {

    /*
     * Get all movies list
     */
    public function getMovielist();
    /*
     * Get a movie provided by its Id attribute
     */
    public function getMovieById($id);
    /*
     * Create new movie resource in storage
     */
     public function createMovie($input);
     /*
     * Update existing movie resource in storage
     */
     public function updateMovie($input);
     
     /*
     * Destroy existing movie resource in storage
     */
     public function destroyMovie($id);
}
