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
        public function checkUserBank($input)
        {
            $result = array();
            if (!empty($input))
            {
                $sql = "select * from user_bank_details where account_number='".$input['account_number']."' and user=".$input['user']." and status=1 ".(isset($input['bank_id'])?" and id<>'".$input['bank_id']."'":"") ;
                $result = DB::select($sql);
                return $result ;
            }
            else
            {
                return $result;
            }
        }

        public function saveUserBank($input)
        {
            $sql = "insert into user_bank_details (`user`,`bank_name`,`account_name`,`account_number`,`ifsc_code`,`address`,`ip_address`,`created_at`,`modified_at`)" ;
            $sql.=" values('".$input['user']."','".$input['bank_name']."',
                            '".$input['account_name']."','".$input['account_number']."','".$input['ifsc_code']."','".$input['address']."'
                                ,'".$_SERVER['REMOTE_ADDR']."','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."')" ;
            //echo $sql ; exit;
            return $result = DB::statement($sql);
            //return DB::getPdo()->lastInsertId();
        }
        public function editUserBank($input)
        {
            if (!empty($input))
            {
                $sql = "update user_bank_details set "
                        . "user='".$input['user']."',"
                        . "bank_name='".$input['bank_name']."',"
                        . "account_name='".$input['account_name']."',"
                        . "account_number='".$input['account_number']."',"
                        . "ifsc_code='".$input['ifsc_code']."',"
                        . "address='".$input['address']."',"
                        . "ip_address='".$_SERVER['REMOTE_ADDR']."',"
                        . " modified_at='".date('Y-m-d H:i:s')."' "
                        . "where id='".$input['bank_id']."'" ;
                return $result = DB::statement($sql);
            }
            else
            {
                return false ;
            }
        }
        public function deleteUserBank($input)
        {
            $sql = "update user_bank_details set status=0 where id=".$input['bank_id'] ;
//            $sql = "delete from user_bank_details where id='".$input['bank_id']."'";
            return $result = DB::statement($sql);
        }
        public function listUserBanks($input)
        {
            $sql = "select * from user_bank_details where status=1 and user=".$input['user_id'] ;
            $result = DB::select($sql);
            return $result ;
        }
        public function updatePaymentTransaction($input)
        {
            if (!empty($input['mm_transaction_id']))
            {
                $sql = "update payment_transactions set status='".$input['status']."', last_modified_at='".date('Y-m-d H:i:s')."' 
                        where mm_transaction_id='".$input['mm_transaction_id']."'" ;
                $result = DB::statement($sql);
                return true;
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
                $sql = "select id,service_id,for_user,from_user,to_user,last_modified_at,amount,payment_type from payment_transactions where to_user='".$email."'
                        and payment_reason=".$paymentReason." and status='".Config::get('constants.PAYMENTS.STATUSES.SUCCESS')."' order by id desc" ;
            
                $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $start = $limit - 10 ;
                    $sql.=" limit ".$start.",".$limit ;
                }

                $result = DB::select($sql);
//                echo "<pre>";print_r($result);exit;
//                $result = DB::select($sql1);
                return $result;
            }
            else
            {
                return 0 ;
            }
        }
        
        public function getPayoutTransactions($email="", $page=0)
        {
            if (!empty($email))
            {
                $sql = "select id,to_provided_user as for_user,from_user,to_mintmesh_user as to_user,created_at,amount,payout_types_id,payout_transaction_id from payout_logs where to_mintmesh_user='".$email."' and status='".Config::get('constants.PAYMENTS.STATUSES.SUCCESS')."' order by id desc";
            
                $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $start = $limit - 10 ;
                    $sql.=" limit ".$start.",".$limit ;
                }
                $result = DB::select($sql);
//                echo "<pre>";print_r($result);exit;
//                $result = DB::select($sql1);
                return $result;
            }
            else
            {
                return 0 ;
            }
        }
        
        public function getPaymentTotalCash($email="", $paymentReason=0)
        {
             if (!empty($email))
            {
               
                 $sql = "select balance_cash as total_cash from balance_cash where user_email='$email' ";
                 /* $sql = "select sum(amount) as total_cash from payment_transactions where to_user='".$email."'
                        and status='".Config::get('constants.PAYMENTS.STATUSES.SUCCESS')."'" ;
                if (!empty($paymentReason))
                {
                    $sql.=" and payment_reason=".$paymentReason."" ;
                }
            */
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
        
        public function insertTransactionIdBT($mm_transaction_id)
        {
            $sql = "insert into transaction_ids_braintree (`payment_transaction_id`)" ;
            $sql.=" values('".$mm_transaction_id."')" ;
            //echo $sql ; exit;
            $result = DB::statement($sql);
            return $last_insert_id = DB::Select("SELECT LAST_INSERT_ID() as last_id");
        }

        public function insertTransactionIdCitrus($mm_transaction_id)
        {
            $sql = "insert into transaction_ids_citrus (`payment_transaction_id`)" ;
            $sql.=" values('".$mm_transaction_id."')" ;
            //echo $sql ; exit;
            $result = DB::statement($sql);
            return $last_insert_id = DB::Select("SELECT LAST_INSERT_ID() as last_id");
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
        
        public function logPayout($input = array())
        {
            $sql = "insert into payout_logs (`from_user`,`to_mintmesh_user`,`to_provided_user`,`amount`,`payout_types_id`,`status`,`ip_address`,`service_response`,`paypal_item_id`,`paypal_batch_id`,`bank_id`,`payout_transaction_id`)" ;
            $sql.=" values('".$input['from_user']."','".$input['to_mintmesh_user']."',
                            '".$input['to_provided_user']."',".$input['amount'].","
                            . "".$input['payout_types_id'].",'".$input['status']."',"
                            . "'".$_SERVER['REMOTE_ADDR']."','".$input['service_response']."',"
                            . "'".$input['paypal_item_id']."','".$input['paypal_batch_id']."',"
                            . "'".$input['bank_id']."','".$input['payout_transaction_id']."')" ;
            //echo $sql ; exit;
            return $result = DB::statement($sql);
        }
        
        public function getBalanceCash($userEmail='')
        {
            if (!empty($userEmail))
            {
                $sql = "select * from balanse_cash where user_email='".$userEmail."'";
                return $result = DB::select($sql);
            }
            else
            {
                return 0;
            }
        }
        public function getbankInfo($bank_id='')
        {
            if (!empty($bank_id))
            {
                $sql = "select * from user_bank_details where id='".$bank_id."'";
                $result = DB::select($sql);
                if (!empty($result))
                {
                    return $result[0];
                }
                else{
                    return 0;
                }
                
            }
            else
            {
                return 0;
            }
        }
        public function getbalanceCashInfo($userEmail='')
        {
            if (!empty($userEmail))
            {
                $sql ="select * from balance_cash where user_email='".$userEmail."'";
                $result = DB::select($sql);
                if (!empty($result))
                {
                    return $result[0];
                }
                else
                {
                    return 0;
                }
            }
            else
            {
                return 0;
            }
        }
        
        public function editBalanceCash($bid=0, $input=array())
        {
            if (!empty($bid))
            {
                if (!empty($input))
                {
                    $sql = "update balance_cash set last_modified_at='".date('Y-m-d H:i:s')."',ip_address='".$_SERVER['REMOTE_ADDR']."'";
                    foreach ($input as $k=>$v)
                    {
                        if ($k == 'balance_cash')
                        {
                            $sql.=",".$k."=".$v ;
                        }
                        else
                        {
                            $sql.=",".$k."='".$v."'" ;
                        }
                    }
                    $sql.=" where id=".$bid ;
                    return $result = DB::statement($sql);
                    
                }
                else
                {
                    return 0;
                }
            }
            else
            {
                return 0;
            }
        }
        
        public function insertBalanceCash($input=array())
        {
            if (!empty($input))
            {
                $sql = "insert into balance_cash (`user_id`, `user_email`,`balance_cash`,`currency`,`last_modified_at`,`ip_address`)";
                $sql.=" values(" ;
                foreach ($input as $k=>$v)
                {
                    if ($k == 'balance_cash' || $k=='user_id')
                    {
                        $sql.=$v."," ;
                    }
                    else
                    {
                        $sql.="'".$v."'," ;
                    }
                }
                $sql.="'".date('Y-m-d H:i:s')."','".$_SERVER['REMOTE_ADDR']."')";
                return $result = DB::statement($sql);

            }
            else
            {
                return 0;
            }
        }
            
        
}
