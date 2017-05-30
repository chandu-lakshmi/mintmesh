<?php
/**
 * Service Provider for mintmesh OAuth 2.0 Server
 *
 */

namespace Mintmesh\ServiceProviders\oauth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use League\OAuth2\Server\Exception\OAuthException;
use Lang;

class OauthServiceProvider extends \LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider
{   
    
    public function register()
    {
        $this->registerErrorHandlers();
        parent::registerAuthorizer();
        parent::registerFilterBindings();
        parent::registerCommands();
    }
    /**
     * Register the OAuth error handlers
     * @return void
     */
    private function registerErrorHandlers()
    {
        $this->app->error(function(OAuthException $e) {
            if($e->shouldRedirect()) {
                return new RedirectResponse($e->getRedirectUri());
            } else {
                //$msg = $e->getMessage();
                $msg = Lang::get('MINTMESH.user.server_access_denied');
                return new JsonResponse([
                    'status_code' => $e->httpStatusCode,
                    'status'      => 'error',
                    'message'     => array('msg'=> $msg),
                    'data'        => array()
                ]);  
            }
        });
    }   
}
