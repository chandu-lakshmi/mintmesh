<?php namespace Mintmesh\Repositories\API\Payment;


interface PaymentRepository {
    /*
     * Create new user resource in storage
     */
     public function insertTransaction($input);
     
     public function updatePaymentTransaction($input);
     
     
     

}
?>
