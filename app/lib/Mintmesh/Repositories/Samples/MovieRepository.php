<?php namespace Mintmesh\Repositories\Samples;

interface MovieRepository {

    public function getMovielist();
    
    public function getMovieById($id);
}
