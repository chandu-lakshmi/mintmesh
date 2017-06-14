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
        /*
         * Get all movies list
         */
        public function getMovielist() {
                return $this->movie->all();
        }
        /*
         * Get a movie provided by its Id attribute
         */
        public function getMovieById($id) {
            return $this->movie->find($id);
        }
        /*
         * Create new movie resource in storage
         */
        public function createMovie($input) {
            return $this->movie->create($input);
        }
        /*
         * Update existing movie resource in storage
         */
        public function updateMovie($input) {
            
            $movie = $this->movie->find($input['id']);
            //unset id attribute to exclude id update
            unset($input['id']);
            
            foreach ($input as $key=>$value)
            {
                $movie->$key = $value ;
            }

            return $movie->save();
        }
        /*
         * Destroy existing movie resource in storage
         */
        public function destroyMovie($id) {
            $movie = $this->movie->find($id);

            return $movie->delete();
        }
}

