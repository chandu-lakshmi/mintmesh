<?php

//require 'vendor/autoload.php';


use Guzzle\Http\Client as guzzleClient;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Message\ResponseInterface;
use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
use Mintmesh\Services\IntegrationManager\IntegrationManager;

class ZenefitsController extends BaseController {

    protected $userRepository, $guzzleClient, $IntegrationManager;

    public function __construct(EnterpriseGateway $EnterpriseGateway) {
        $this->guzzleClient = new guzzleClient();
        $this->EnterpriseGateway = $EnterpriseGateway;
        $this->IntegrationManager = new IntegrationManager();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function installMintmeshApp() {
        return View::make('zenefits');
    }

    public function getAccesCode() {
        $inputUserData = \Input::all();
         if ($inputUserData) {
            $info = $this->getAccessTokenRefreshToken($inputUserData);
            return Redirect::to('http://202.63.105.85/mmenterprise/getApp/zenefits');
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

    public function getAccessTokenRefreshToken($inputUserData) {
        $data = array();
        $endPoint = "https://secure.zenefits.com/oauth2/token/";


        $data['client_id'] = "5zr3cWgul9peKR3al1AZ65qcsbWC3JJr8jRd0IDU";
        $data['client_secret'] = "8eLr2WeIukzLVTjqJ3xaAeNFmLNGKDla1UdpRAwZxAdzlQEMdSnrRonqpiGbIm7HQhvvH4UnN8FmaCtVH9gxaKSyPntMwdcj80QBUKnBVA3rzPaJ8xIt1kDsvUVb8aKs";
        $data['code'] = !empty($inputUserData['code']) ? $inputUserData['code'] : '';
        $data['grant_type'] = "authorization_code";
        $data['redirect_uri'] = "http://202.63.105.85/mintmesh/getAccesCode";
        //$data1    = json_encode($data);
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $endPoint);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false); //disable SSL check
        $json_response = curl_exec($curl_handle);
        curl_close($curl_handle);
        $response = json_decode($json_response);
        if ($response) {
            $response_zenefits = $this->EnterpriseGateway->addEditZenefitsAccessToken($json_response, $inputUserData['state']);
            return \Response::json($response_zenefits);
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

    

}
