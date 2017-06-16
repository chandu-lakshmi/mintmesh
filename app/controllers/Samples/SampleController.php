<?php namespace Samples;
use Mintmesh\Gateways\Samples\MovieGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
class SampleController extends \BaseController {

	public function __construct(MovieGateway $movieGateway)
	{
		$this->movieGateway = $movieGateway;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return $this->movieGateway->getMovielist();
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
            // Receiving user input data
            $inputMovieData = \Input::all();
            // Validating movie input data
            $validation = $this->movieGateway->validateCreateMovieInput($inputMovieData);
            if($validation['status'] == 'success') {
                    // creating entry in neo4j DB
                return \Response::json($this->store($inputMovieData));
            } else {
                    // returning validation failuer
                return \Response::json($validation);
            }
	}
        
        /**
	 * Show the form for editing an existing resource.
	 *
	 * @return Response
	 */
	public function edit()
	{
            // Receiving user input data
            $inputMovieData = \Input::all();
            // Validating movie input data
            $validation = $this->movieGateway->validateEditMovieInput($inputMovieData);
            if($validation['status'] == 'success') {
                    // updating existing node in neo4j DB
                return \Response::json($this->update($inputMovieData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store($input)
	{
		// Actually storing the movie data in database
		return $this->movieGateway->createMovie($input);
	}
        
        /**
	 * Update an existing resource in storage.
	 *
	 * @return Response
	 */
	public function update($input)
	{
		// Actually updating the movie data in database
		return $this->movieGateway->updateMovie($input);
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}

         /**
	 * Show the form for destroying an existing resource.
	 *
	 * @return Response
	 */
	public function delete()
	{
            // Receiving user input data
            $inputMovieData = \Input::all();
            // Validating movie input data
            $validation = $this->movieGateway->validateDeleteMovieInput($inputMovieData);
            if($validation['status'] == 'success') {
                    // destroying existing node in neo4j DB
                return \Response::json($this->destroy($inputMovieData['id']));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		// Actually destroying the movie data from database
		return $this->movieGateway->destroyMovie($id);
	}
        
        
        



}
