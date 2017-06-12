<?php

//require 'vendor/autoload.php';


use Guzzle\Http\Client as guzzleClient;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Message\ResponseInterface;
use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
use Mintmesh\Services\IntegrationManager\IntegrationManager;

class ZenefitsController extends BaseController {

    protected $userRepository, $guzzleClient, $IntegrationManager;

    const SUCCESS_RESPONSE_CODE = 200;

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


        $data['client_id'] = Config::get('constants.Zenefits_client_id');
        $data['client_secret'] = Config::get('constants.Zenefits_client_secret');
        $data['code'] = !empty($inputUserData['code']) ? $inputUserData['code'] : '';
        $data['grant_type'] = "authorization_code";
        $data['redirect_uri'] = "http://202.63.105.85/mintmesh/getAccesCode";
        
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
            $this->getActiveApp($response_zenefits['data']['hcm_access_token']);

            return \Response::json($response_zenefits);
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

    public function getActiveApp($access_token) {

        $endPoint = "https://api.zenefits.com/platform/company_installs";

        $request = $this->guzzleClient->get($endPoint);
        $request->setHeader('Authorization', $access_token);


        try {
            $response = $request->send();

            if ($response->isSuccessful() && $response->getStatusCode() == self::SUCCESS_RESPONSE_CODE) {
                $this->getChangeInstallStatus($access_token, $response->getBody());
            } else {
                \Log::info("Error while getting response : $response->getInfo()");
            }
        } catch (ClientErrorResponseException $exception) {

            $responseBody = $exception->getResponse()->getBody(true);
        }
    }

    public function getChangeInstallStatus($access_token, $response) {

        $data = array();
        $array = json_decode($response, TRUE);
        $endPoint = $array['data']['data'][0]['url'] . "/status_changes";
        $data['status'] = "ok";
        
        $authorization = "Authorization: ". $access_token;
        $post = json_encode($data);
        $process = curl_init($endPoint);
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
        curl_setopt($process, CURLOPT_POST, 1);
        curl_setopt($process, CURLOPT_POSTFIELDS, $post);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $return = curl_exec($process);
        curl_close($process);
        return TRUE;
    }

}
