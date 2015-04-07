<?php namespace Samples;
use Mintmesh\Gateways\Samples\MovieGateway;
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
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
