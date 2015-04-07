<?php namespace Mintmesh\Gateways\Samples;

/**
 * This is the Movie Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */

use Mintmesh\Repositories\Samples\MovieRepository;

class MovieGateway {

	protected $movieRepository;    

	public function __construct(MovieRepository $movieRepository) {
		$this->movieRepository = $movieRepository;
	}
        
    public function getMovielist() {
        return $this->movieRepository->getMovielist();
    }
    
    // validation on user inputs for create movie
    public function validateCreateMovieInput($input) {
        $movieValidator = new \Mintmesh\Services\Validators\Samples\MovieValidator($input);
        
        if($movieValidator->passes()) {
            /* validation passes successfully */
            return array('status_code' => 200, 'status' => 'success');
        }
        
        /* Return validation errors to the controller */
        return array('status_code' => 404, 'status' => 'error', 'message' => $movieValidator->getErrors()); 
    }
    
    public function createMovie($input) {
        /**
        * NOTE : if you need to access more than one model do this here, All your business
        * logic and validation is handled by this gateway.
        */
        if ($this->movieRepository->create($input)) {
                return array('status_code' => 200,'status' => 'success', 'message' => 'Created Successfully');
        } else {
                return array('status_code' => 404, 'status' => 'error', 'message' => 'Failed to create');
        }
	}
        
        
    public function getMovieById($id) {
        if(!empty($id)) {
            $movieData = $this->movieRepository->getMovieById($id);
        }
        
        return array('status_code' => 404, 'status' => 'error', 'message' => 'Invalid Requeest');
    }

}
