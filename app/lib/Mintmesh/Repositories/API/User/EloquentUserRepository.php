<?php namespace Mintmesh\Repositories\API\User;


use User;
use Emails_Logs;
use Levels_Logs;
use Notifications_Logs;
use Config ;
use Mail ;
use DB;
use Mintmesh\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Mintmesh\Services\APPEncode\APPEncode ;
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
class EloquentUserRepository extends BaseRepository implements UserRepository {

        protected $user;
        protected $email, $level, $appEncodeDecode, $notifications,$referralsRepository;
        
        public function __construct(User $user, Emails_Logs $email, Levels_Logs $level, APPEncode $appEncodeDecode, Notifications_Logs $notifications, referralsRepository $referralsRepository)
        {
                parent::__construct($user);
                $this->user = $user;
                $this->email = $email;
                $this->level = $level ;
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->notifications = $notifications ;
                $this->referralsRepository = $referralsRepository;
        }
        // creating new user in storage
        public function createUser($input)
        {
            // md5 the email id and attach with mintmesh constant for verification code
            $emailActivationCode = md5($input['emailid']."_".Config::get('constants.MINTMESH')) ;
            
            $user = array(
                        "firstname" => $this->appEncodeDecode->filterString($input['firstname']),
                        "lastname"  =>$this->appEncodeDecode->filterString($input['lastname']),
                        "emailid"   =>$this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                        "primary_phone"   =>$this->appEncodeDecode->filterString($input['phone']),
                        "login_source"   =>$this->appEncodeDecode->filterString($input['login_source']),
                        "password"  =>Hash::make($input['password']),
                        "emailactivationcode" => $emailActivationCode
            );
            return $this->user->create($user);
        }
        

        public function getUserlist() {
                return $this->user->all();
        }
        
        public function getUserById($id) {
            /*$result = User::whereRaw('id = ? and status = ? and emailverified = ?', array($id,'1','1'))->get();
            if (!empty(count($result)))
            {
                return $result[0] ;
            }
            else
            {
                return false ;
            }*/
            return $this->user->find($id);
        }
        
        public function getRemaningDays($emailid){
            $currentDate = date("Y-m-d h:m:s");
            $sql = "select DATEDIFF('$currentDate',created_at)AS days, status, emailverified from users where emailid='".$emailid."'";
            $result = DB::select($sql);
            if (!empty($result))
            {
//                $result[0]->days = ($result[0]->days <= 14?(14-$result[0]->days):0);
                $result[0]->days = ($result[0]->days <= 60?(60-$result[0]->days):0);
                return $result[0] ;
            }
            else
            {
                return 0;
            }
        }
                
        public function getUserByCode($code) {
            return User::where('emailactivationcode', '=', $code)->first();
            
        }
        
        
        public function getUserByEmail($email) {
            return User::whereRaw('emailid = ?', array($this->appEncodeDecode->filterString(strtolower($email))))->first();
//            return User::whereRaw('emailid = ? and status = 1', array($this->appEncodeDecode->filterString(strtolower($email))))->first();
            //return User::whereRaw('emailid', '=', $this->appEncodeDecode->filterString(strtolower($email)))->first();
            
        }
        
        public function getUserIdforLogin($username, $password) {
            return User::whereRaw('username = ? and password = ?', array($username,$password))->get('id');
        }
        
        public function setActive($id,$emailid) {
            $user = User::find($id);
            $user->status = 1;
            $user->emailverified = 1;
            //$user->emailactivationcode = '';
            $user->save();
            DB::update("update notifications_logs set other_status = '1',status = '0', updated_at='".date('Y-m-d H:i:s')."' where from_email = '".$emailid."' and notifications_types_id = 26 and to_email = '".$emailid."'");
            return true ;
        }
        public function removeActiveCode($id)
        {
            $user = User::find($id);
            $user->emailactivationcode = '';
            $user->save();
            return true ;
        }
        
        public function resetPassword($input)
        {
            if (!empty($input['email']))
            {
                $email = $this->appEncodeDecode->filterString(strtolower($input['email']));
                $password = Hash::make($input['password']);
                $count = DB::update("update users set password=?, resetactivationcode='' where md5(emailid)=?",array($password,$email));
                return $count ;
            }
        }
        
        public function changePassword($input)
        {
            if (!empty($input['email']))
            {
                $email = $input['email'];
                $password = Hash::make($input['password']);
                $count = DB::update("update users set password=? where emailid=?",array($password,$email));
                return $count ;
            }
        }
        
        public function getPushDetails($pushId=0)
        {
            $pushResult = DB::select("select * from notifications_logs where id=? limit 1",array($pushId)) ;
            return $pushResult[0];
        }
        public function closeNotification($input, $pushResult)
        {
           $pushId = $input['push_id'];
           $requestType = $input['request_type'] ;
           if (!empty($pushResult))
           {
               $result = $pushResult ;
               $other_status = 0;
               $is_default = false ;
               switch ($requestType)
               {
                   case 'accept':
                           $other_status = 1;
                            break;
                  case 'decline':
                           $other_status = 2;
                            break;
                  case 'close':
                           $other_status = 3;
                            break;
                   default:
                           $is_default = true ;
                           $other_status = $result->other_status;
               }
               /*if ($result->notifications_types_id == 2)//if accept then put accested in connect notification
               {
                   DB::update("update notifications_logs set other_status='".$other_status."' where from_email=? and notifications_types_id=? and to_email=?",array($result->to_email, 1, $result->from_email));
               }
               else */
               if ($result->notifications_types_id == 7)
               {
                   if ($is_default)
                   {
                       DB::update("update notifications_logs set other_status='".$other_status."', updated_at='".date('Y-m-d H:i:s')."' where from_email=? and notifications_types_id=? and to_email=? and other_status=?",array($result->other_email, 4, $result->from_email, $other_status));
                   }
                   else
                   {
                       DB::update("update notifications_logs set other_status='".$other_status."', updated_at='".date('Y-m-d H:i:s')."' where from_email=? and notifications_types_id=? and to_email=?",array($result->other_email, 4, $result->from_email));
                   }
                   
               }
               if (!empty($result->extra_info))
               {
                   if ($is_default)
                   {
                       return DB::update("update notifications_logs set status='0',other_status='".$other_status."', updated_at='".date('Y-m-d H:i:s')."' where from_email=? and notifications_types_id=? and to_email=? and other_email=? and extra_info=? and other_status=?",array($result->from_email, $result->notifications_types_id, $result->to_email,$result->other_email,$result->extra_info, $other_status));
                   }
                   else
                   {
                       return DB::update("update notifications_logs set status='0',other_status='".$other_status."', updated_at='".date('Y-m-d H:i:s')."' where from_email=? and notifications_types_id=? and to_email=? and other_email=? and extra_info=?",array($result->from_email, $result->notifications_types_id, $result->to_email,$result->other_email,$result->extra_info));
                   }
                   
               }
               else
               {
                   if ($is_default)
                   {
                       return DB::update("update notifications_logs set status='0',other_status='".$other_status."', updated_at='".date('Y-m-d H:i:s')."' where from_email=? and notifications_types_id=? and to_email=? and other_status=?",array($result->from_email, $result->notifications_types_id, $result->to_email, $other_status));
                   }
                   else
                   {
                       return DB::update("update notifications_logs set status='0',other_status='".$other_status."', updated_at='".date('Y-m-d H:i:s')."' where from_email=? and notifications_types_id=? and to_email=? ",array($result->from_email, $result->notifications_types_id, $result->to_email));
                   }
                   
               }
               
           }
           else
           {
               return 0;
           }
           
        }
        public function getNotifications($user, $notification_type=0, $page=0, $isNotificationCount=0)
        {
            if ($user->emailid)
            {
                $type = 0;
                $excludeType = 0;
               switch ($notification_type)
                {
                    case 'request_connect':
                            $types= array(1,3,4,7,10,11,20,17,21,23,26);
                            $type = implode(",",$types);
                            break;
                    default:
                        $type = 0;
                        $excludeTypes=array(21,26,27);
                        $excludeType=implode(",",$excludeTypes);

                } 
                $start = $end = 0;
                if (!empty($page))
                {
                    $end = $page-1 ;
                    $start = $end*10 ;
                }
                $sql = "select nl.*, nt.name as not_type from notifications_logs nl
                         JOIN
                         (
                            SELECT  MAX(id) id
                            FROM    notifications_logs
                            WHERE   to_email = '".$user->emailid."'
                            GROUP   BY from_email,notifications_types_id,message,to_email,
                            CASE WHEN notifications_types_id=17 THEN 1
                            WHEN extra_info IS NULL THEN 1
                            ELSE extra_info END,
                            CASE WHEN other_email IS NULL THEN 1
                            ELSE other_email END,
                            CASE WHEN other_phone IS NULL THEN 1
                            ELSE other_phone END
                         ) b ON nl.id = b.id  
                         left join notifications_types nt on nt.id = nl.notifications_types_id where 1" ;
                /*$sql = "select nl.*, nt.name as not_type from notifications_logs nl 
                        left join notifications_types nt on nt.id = nl.notifications_types_id 
                        where nl.to_email = '".$user->emailid."'";*/
                if (!empty($excludeType))
                {
                    $sql.=" and nl.notifications_types_id NOT IN (".$excludeType.")" ;
                }
                if (!empty($type))
                {
                    $sql.=" and nl.notifications_types_id IN (".$type.")" ;
                    $sql.=" and CASE WHEN nl.notifications_types_id=10 THEN 1 ELSE nl.other_status = '0' END" ;
                    $sql.=" GROUP BY CASE WHEN nl.notifications_types_id=10 OR nl.notifications_types_id=23 THEN nl.extra_info
                            ELSE nl.id END  ";
                }
                
               $sql.=" order by nl.id desc" ;
               //echo $sql ; exit;
               if (!empty($page))
                {
                    $sql.=" limit ".$start.",10" ;
                }
                return $result = DB::select($sql);
            }
        }
        
        public function getNotificationsCount($user, $notification_type=0)
        {
            if ($user->emailid)
            {
                if ($notification_type == 'request_connect')
                {
                    $result = $this->getNotifications($user, $notification_type,0,1);
                    foreach ($result as $key=>$row)
                    {
                        //check if any referred post has pending status
                        if ($row->notifications_types_id == 10 || $row->notifications_types_id == 23)
                        {
                             //check if any pending referrals are their
                            $postId = !empty($row->extra_info)?$row->extra_info:0 ;
                            $activeResult = $this->referralsRepository->checkActivePost($postId);
                            if (!count($activeResult))
                            {
                                unset($result[$key]);
                            }
                        }
                    }
                }
                else
                {
                    $type = 0;
                    $excludeType = 0;
                    $type = 0;
                            $excludeTypes=array(21,26,27);
                            $excludeType=implode(",",$excludeTypes);
                    $sql = "select count(id) as count from notifications_logs nl 
                            where nl.to_email = '".$user->emailid."'";
                    if (!empty($excludeType))
                    {
                        $sql.=" and nl.notifications_types_id NOT IN (".$excludeType.")" ;
                    }
                    if (!empty($type))
                    {
                        $sql.=" and nl.notifications_types_id IN (".$type.")" ;

                    }
                    $sql.=" and nl.status = '1'" ;
                    $sql.=" group by nl.notifications_types_id, 
                                nl.from_email, nl.message,
                                CASE WHEN nl.extra_info IS NULL THEN 1
                                ELSE nl.extra_info END,
                                CASE WHEN nl.other_email IS NULL THEN 1
                                ELSE nl.other_email END" ;
                    $result = DB::select($sql);
                }
                
                 
                 return count($result) ;
            }
            else
            {
                return 0;
            }
        }
        public function getCountryCodes($name='')
        {
            $sql = "select id, name, country_code from country where status = 1 and name like '%".$name."%' order by name";
            $result = DB::select($sql);
            return $result ;
        }
        
        public function getIndustries()
        {
            $sql = "select id, name from industries where status = '1' order by name asc";
            $result = DB::select($sql);
            return $result ;
        }
        
        public function getJobFunctions()
        {
            $sql = "select id, name from job_functions where status = '1' order by name asc";
            $result = DB::select($sql);
            return $result ;
        }
        
        public function updateUserresetpwdcode($input)
        {
            $sql = "update users set resetactivationcode='".$input['resetactivationcode']."' where id='".$input['user_id']."'";
            return $result = DB::statement($sql);
        }
        
        public function getresetcodeNpassword($emailid) 
        {
            $email = $this->appEncodeDecode->filterString(strtolower($emailid));
            $sql = "select password, resetactivationcode, emailid from users where md5(emailid)='".$email."'";//status = '1' and
            $result = DB::select($sql);
            if (!empty($result))
            {
                return $result[0] ;
            }
            else
            {
                return 0;
            }
        }

        public function logEmail($input)
        {
            return $this->email->create($input);
        }
        
        public function logNotification($input)
        {
            return $this->notifications->create($input);
        }
        
        public function getNotification($id = 0)
        {
            $sql = "select nl.*, nt.name as not_type from notifications_logs nl 
                       left join notifications_types nt on nt.id = nl.notifications_types_id 
                       where nl.id = ".$id ;
            return $result = DB::select($sql);
        }
         
        public function logLevel($points_types_id=0,$email="", $from="", $other="",$points=0)
        {
            //first get the level number
            $sql = "select max(levels_id) as latest_level from levels_logs where user_email = '".$email."'" ;
            $levelresult = DB::select($sql);
            if (!empty($levelresult) && isset($levelresult[0]))
            {
                $levels = $this->getLevels();
                $levelNumber = 1 ;
                if (!empty($levelresult[0]->latest_level))
                {
                    $levelNumber = $levelresult[0]->latest_level ;
                    //get max count of levels  and levels_id=$levelNumber
                   $sql1 = "select sum(points) as max_point from levels_logs where user_email='".$email."'" ;
                    $result1 = DB::select($sql1);
                    if (!empty($result1))
                    {
                        if (!empty($result1[0]->max_point))
                        {
                            $thisLevelInfo = $levels[$levelNumber] ; 
                            //check if level to be added
                            $pointsCheck = $result1[0]->max_point + $points ;
                            if ($pointsCheck > $thisLevelInfo['points'])
                            {
                                $nextLevel = $levelNumber+1 ;
                                if (array_key_exists($nextLevel, $levels))//if level exist
                                {
                                    $levelNumber = $levelNumber+1 ;
                                }
                            }
                        }
                    }
                }
                
                //create level log
                $level = array(
                    "levels_id" => $levelNumber,
                    "points_types_id"  =>$points_types_id,
                    "points"   => $points,
                    "user_email"   =>$email,
                    "from_email"   =>$from,
                    "other_email"  =>$other,
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                );
                return $this->level->create($level);
            }
        }
        public function getLevels()
        {
            $levels = array();
            $sql = "select * from levels where status='1'" ;
            $result = DB::select($sql);
            if ($result)
            {
                foreach ($result as $row)
                {
                    $levels[$row->id] = array("level_id"=>$row->id, "name"=>$row->name, "points"=>$row->points);
                }
            }
            return $levels ;
        }
        
        public function getLevelsLogsInfo($email="")
        {
            if (!empty($email))
            {
                $sql = "select sum(points) as max_points, levels_id from levels_logs where user_email='".$email."' group by levels_id" ;
                return $result = DB::select($sql); 
            }
            else
            {
                return false;
            }
        }
        public function getSpecificLevelInfo($id=0, $email="")
        {
            if (!empty($id) && !empty($email))
            {
                $sql = "select ll.*, pt.name as point_type from levels_logs ll
                        left join points_types pt on pt.id=ll.points_types_id where ll.user_email='".$email."' and ll.levels_id=".$id." order by ll.id desc" ;
                return $result = DB::select($sql); 
            }
            else
            {
                return false ;
            }
        }
        
        public function getCreditsCount($email="")
        {
            if (!empty($email))
            {
                $sql = "select sum(points) as credits from levels_logs where user_email='".$email."'" ;
                return $result = DB::select($sql); 
            }
            else
            {
                return false;
            }
        }
        
        public function getCurrentLevelInfo($email="")
        {
            if (!empty($email))
            {
                $sql = "select l.id, sum(ll.points) as earned_points, l.name, l.points as points from levels l 
                        left join  levels_logs ll on l.id=ll.levels_id
                        where ll.levels_id=(select max(levels_id) from levels_logs 
                        where user_email='".$email."') and ll.user_email='".$email."' having sum(ll.points)>=0
                        " ;
                return $result = DB::select($sql); 
            }
        }
        
        public function checkCompleteProfileExistance($email="")
        {
            if (!empty($email))
            {
                $sql = "select count(id) as c_count from levels_logs where user_email='".$email."' and points_types_id=2";
                $result = DB::select($sql); 
                if (!empty($result))
                {
                    return $result[0]->c_count ;
                }
                else
                {
                    return 0 ;
                }
                
            }
        }

        public function getSkills($input=null)
        {
            $sql = "select id,name,status,color from skills where status=1" ;
            if (!empty($input['search_for']))
            {
                $sql.= " and name like '%".  $this->appEncodeDecode->filterString(strtolower($input['search_for']))."%'";
            }
            $sql.=" order by name asc" ;
            return $result = DB::select($sql);
        }
        
        public function getJobFunctionName($functionId=0)
        {
            if (!empty($functionId) && is_numeric($functionId))
            {
                $sql = "select name from job_functions where id=".$functionId ;
                $result = DB::select($sql);
                if (!empty($result))
                {
                    return $result[0]->name ;
                }
                else
                {
                    return "";
                }
            }
            else
            {
                return "";
            }
        }
        public function getIndustryName($industryId)
        {
            if (!empty($industryId) && is_numeric($industryId))
            {
                $sql = "select name from industries where id=".$industryId ;
                $result = DB::select($sql);
                if (!empty($result))
                {
                    return $result[0]->name ;
                }
                else
                {
                    return "";
                }
            }
            else
            {
                return "";
            }
            
        }
        
        public function updateAuthyId($userId=0, $authyId=0)
        {
            if (!empty($userId) && !empty($authyId))
            {
                $sql = "update users set authy_id=".$authyId." where id=".$userId;
                return $result = DB::statement($sql);
                
            }
            else
            {
                return false ;
            }
            
        }
        
        public function getBadWords()
        {
//            $sql = "select word from bad_words";
            $sql = "select trim(word) as word from bad_words order by length(word) desc";
            return $result = DB::select($sql);
        }
        
        public function closeVerifyOtpBattleCard($user)
        {
            if (!empty($user))
            {
                $user = $this->appEncodeDecode->filterString(strtolower($user));
                $sql = "update notifications_logs set status='0', other_status='3' where from_email='".$user."' and to_email='".$user."' and notifications_types_id=21";
                return $result = DB::statement($sql);
            }
            else
            {
                return false ;
            }
            
        }
        
        public function getServices($searchString='', $country=''){
            $searchString = $this->appEncodeDecode->filterString(strtolower($searchString)) ;
            $countryList = "('all')";
            if (!empty($country)){
                if (strtolower($country) == 'india'){
                    $countryList = "('india','all')";
                }
                else if (strtolower(trim($country)) == 'united states'){
                    $countryList = "('united states','all')";
                }
                else{
                    $countryList = "('all')";
                }
            }
            $sql = "select id,name from services where country IN ".$countryList." and status=1" ;
            if (!empty($searchString)){
                $sql.=" and lower(name) like '".$searchString."%'";
            }
            $sql.=" group by name asc";
            return $result = DB::select($sql);
            
        }
        
        public function getJobs($searchString=''){
            $searchString = $this->appEncodeDecode->filterString(strtolower($searchString)) ;
            $sql = "select id,name from jobs where status=1" ;
            if (!empty($searchString)){
                $sql.=" and lower(name) like '".$searchString."%'";
            }
            $sql.=" group by name asc";
            return $result = DB::select($sql);
        }
        public function getYouAreValues(){
            $sql = "select id,name,value from you_are_values where status=1" ;
            return $result = DB::select($sql);
            
        }
        
        public function getYouAreName($id=0,$value){
//            if (!empty($id) && is_numeric($id)){
            if (!empty($id)){
            $sql = "select name,id from you_are_values where ".(is_numeric($id)?"id='".$id."'":"name='".$id."'") ;
            $result = DB::select($sql);
                if (!empty($result)) {
                    if($value == "name")
                        return $result[0]->name ;
                    else
                       return $result[0]->id ; 
                } else {
                    return "";
                }
            } else {
                return '';
            }
        }

     public function getProfessions(){
            $sql = "select id,name from professions where status=1 order by name asc" ;
            return $result = DB::select($sql);
        }
        
        public function getProfessionName($id=0){
            if (!empty($id) && is_numeric($id)){
                $sql = "select name from professions where id=".$id ;
                $result = DB::select($sql);
                if (!empty($result)) {
                    return $result[0]->name ;
                } else {
                    return "";
                }
            }
            else{
                return '';
            }
        }
        
    public function getUserByEmailWithoutStatus($email) {
            return User::whereRaw('emailid = ?', array($this->appEncodeDecode->filterString(strtolower($email))))->first();
            //return User::whereRaw('emailid', '=', $this->appEncodeDecode->filterString(strtolower($email)))->first();
            
        }
        
    public function getExperiences(){
        $sql = "select id,name from experience_ranges where status=1" ;
        return $result = DB::select($sql);
    }
    
    public function getEmploymentTypes(){
        $sql = "select id,name from employment_type where status=1" ;
        return $result = DB::select($sql);
    }
    public function updateNotificationsFromPhoneToEmailId($userEmail='', $userPhone=''){
        if (!empty($userEmail) && !empty($userPhone)){
            $sql = "update notifications_logs set other_email='".$userEmail."',for_mintmesh=1,other_phone='' where other_email='' and other_phone='".$userPhone."'";
            $result = DB::update($sql);
            //update emailid for the notification of 11 type..this comes when p1 accepts p2 referrals of p3
            $sql1 = "update notifications_logs set to_email='".$userEmail."',for_mintmesh=1 where to_email='' and to_phone='".$userPhone."'";
            return $result1 = DB::update($sql1);
        }
    }
}
