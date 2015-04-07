<?php namespace Mintmesh\Repositories\Samples;

use Movie;
use Mintmesh\Repositories\BaseRepository;

class NeoeloquentMovieRepository extends BaseRepository implements MovieRepository {

        protected $movie;

        public function __construct(Movie $movie)
        {
                parent::__construct($movie);
                $this->movie = $movie;
        }

        public function getMovielist() {
                return $this->movie->all();
        }
        
        public function getMovieById($id) {
            return $this->movie->find($id);
        }
}

