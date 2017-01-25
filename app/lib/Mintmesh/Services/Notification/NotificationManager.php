<?php
namespace Mintmesh\Services\Notification;

use Aws\Common\Aws;
use Aws\Sns\SnsClient as snsClient;

class NotificationManager {

    // Initiate SNS object
    private $obj = null;

    private function getInstance() {
        if (is_null($this->obj)) {
            $this->obj = \AWS::get('sns');
        }
        return $this->obj;
    }

    public function createPlatformEndpoint($token, $platformApplicationArn) {
        $options = array(
            'PlatformApplicationArn' => $platformApplicationArn,
            'Token'                  => $token,
        );
        try {
            $res = $this->getInstance()->createPlatformEndpoint($options);
        } catch (Exception $e) {
            return false;
        }
        return $res;
    }

    public function publish($message, $EndpointArn) {
        try {
            $res = $this->getInstance()->publish(array(
                'Message'   => $message,
                'TargetArn' => $EndpointArn
            ));
        } catch (Exception $e) {
            return false;
        }
        return $res;
    }

    public function publishJson($args) {
        try {
            $res = $this->getInstance()->publish($args);
        } catch (Exception $e) {
            return false;
        }
        return $res;
    }
}