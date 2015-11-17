<?php namespace Mintmesh\Repositories\API\Payment;

use DB;
use User;
use COnfig;
use Mintmesh\Repositories\BaseRepository;
use Mintmesh\Services\APPEncode\APPEncode ;
class EloquentPaymentRepository extends BaseRepository implements PaymentRepository {

    protected $appEncodeDecode ,$user;
        public function __construct(User $user, APPEncode $appEncodeDecode)
        {
                parent::__construct($user);
                $this->appEncodeDecode = $appEncodeDecode ;
        }
        public function insertTransaction($input)
        {
            $sql = "insert into payment_transactions (`from_user`,`to_user`,`for_user`,`amount`,`comission_percentage`,`payment_type`,`payment_reason`,`service_id`,`status`,`ip_address`,`mm_transaction_id`,`relation_id`)" ;
            $sql.=" values('".$input['from_user']."','".$input['to_user']."',
                            '".$input['for_user']."',".$input['amount'].",'".$input['comission_percentage']."',".$input['payment_type'].",".$input['payment_reason']."
                                ,'".$input['payed_for_id']."','".$input['status']."','".$_SERVER['REMOTE_ADDR']."','".$input['mm_transaction_id']."','".$input['relation_id']."')" ;
            //echo $sql ; exit;
            return $result = DB::statement($sql);
            //return DB::getPdo()->lastInsertId();
        }
        
        public function updatePaymentTransaction($input)
        {
            if (!empty($input['mm_transaction_id']))
            {
                $sql = "update payment_transactions set status='".$input['status']."', last_modified_at=now() 
                        where mm_transaction_id='".$input['mm_transaction_id']."'" ;
                return $result = DB::statement($sql);
            }
            else
            {
                return false ;
            }
        }
        
        public function logPayment($input)
        {
            $sql = "insert into payment_logs (`response`,`mm_transaction_id`)
                    values('".$input['response']."','".$input['mm_transaction_id']."') ";
        
            return $result = DB::statement($sql);
        }
        public function getComissionPercentage($payment_reason=0)
        {
            if (!empty($payment_reason))
            {
                $sql = "select * from payment_reasons where id=".$payment_reason ;
                $result = DB::select($sql);
                return $result[0] ;
            }
            else
            {
                return 0;
            }
        }
        
        public function getPaymentTransactions($email="", $paymentReason=0,$page=0)
        {
            if (!empty($email) && !empty($paymentReason))
            {
                $sql = "select * from payment_transactions where to_user='".$email."'
                        and payment_reason=".$paymentReason." and status='".Config::get('constants.PAYMENTS.STATUSES.SUCCESS')."' order by id desc" ;
            
               $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $start = $limit - 10 ;
                    $sql.=" limit ".$start.",".$limit ;
                }
                return $result = DB::select($sql);
            }
            else
            {
                return 0 ;
            }
        }
        
        public function getPaymentTotalCash($email="", $paymentReason=0)
        {
             if (!empty($email) && !empty($paymentReason))
            {
                $sql = "select sum(amount) as total_cash from payment_transactions where to_user='".$email."'
                        and payment_reason=".$paymentReason." and status='".Config::get('constants.PAYMENTS.STATUSES.SUCCESS')."'" ;
            
               return $result = DB::select($sql);
            }
            else
            {
                return 0 ;
            }
        }
        
        public function insertGatewayInput($input)
        {
            $sql = "insert into payment_gateway_inputs (`token`,`bill`,`mm_transaction_id`)" ;
            $sql.=" values('".$input['token']."','".$input['bill']."','".$input['mm_transaction_id']."')" ;
            //echo $sql ; exit;
            return $result = DB::statement($sql);
        }
        
        public function getTransactionById($transactionId=0)
        {
            if (!empty($transactionId))
            {
                $sql = "select * from payment_transactions where mm_transaction_id='".$transactionId."' limit 1";
                $result = DB::Select($sql) ;
                if (!empty($result))
                {
                    return $result[0];
                }
                else
                {
                    return false ;
                }
            }
            return 0 ;
        }
        
        public function getTransactionDetails($input)
        {
            if (!empty($input['from_user']) && !empty($input['to_user']) && !empty($input['for_user']) && !empty($input['service_id']) && !empty($input['relation_id']))
            {
                $sql = "select * from payment_transactions where from_user='".$input['from_user']."' and to_user='".$input['to_user']."' 
                        and for_user='".$input['for_user']."' and REPLACE(service_id,',','')=".$input['service_id']." and REPLACE(relation_id,',','')=".$input['relation_id']." and status='".$input['status']."' limit 1" ;   
            
                $result = DB::Select($sql) ;
                if (!empty($result))
                {
                    return $result[0];
                }
                else
                {
                    return false ;
                }
            }
            else
            {
                return 0 ;
            }
        }
        
        public function cancelOtherTransactions($postId=0,$relationId=0)
        {
            if (!empty($postId) && !empty($relationId))
            {
                $sql = "update payment_transactions set status='".Config::get('constants.PAYMENTS.STATUSES.CANCELLED')."'
                         where REPLACE(service_id,',','')=".$postId." and REPLACE(relation_id,',','')=".$relationId." and "
                        . " status IN('".Config::get('constants.PAYMENTS.STATUSES.PENDING')."')";
                return $result = DB::statement($sql);
            }
        }
            
        
}
