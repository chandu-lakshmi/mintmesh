<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Admin
 *
 * @author enterpi
 */

 namespace Models;
 use lib\Email\Parse_manager;
 use lib\Email\Pop_manager;
use Eloquent; // ******** This Line *********
use DB; 
 /*ini_set('display_errors', 0);
error_reporting(0);*/
class EmailcaptureModel extends Eloquent {//put your code here
	var $settings;
	var $link;
	var $parse;
        public function __construct() {
            parent::__construct();
            //Load the required classes
            //$this->load->library('parse_manager');
			//$Parse_manager = new Parse_manager();
			//$Pop_manager = new Pop_manager();
	}
	function db_setting($cm_id='1'){
		$sql="select * from cm_settings where id=".$cm_id." and status=1 limit 1";
		$result=DB::select($sql);
		return $result[0];
	}
		
	

	function getDBMailID(){
		$id = DB::table('cm_mails')->insertGetId(array('settings_id'=>$this->settings->id));
		return $id;
	
	}
	function saveEmailData($mailsData,$mail_att){
		/*echo "<pre>";
		print_r($mailsData);exit;*/
		$headers = $mailsData->headers;
		$data = array();
		$data['subject'] = $headers->subject;
		$data['date'] = $headers->date;
		$data['subject'] = $headers->subject;
		$data['toaddress'] = $headers->toaddress;
		$data['fromaddress'] = $headers->fromaddress;
		$to = '';
		foreach($headers->to as $tm){
			$to .= $tm->mailbox.'@'.$tm->host.',';
		}
		
		$data['to'] = trim($to,',');
		$from = '';
		foreach($headers->from as $fm){
			$from .= $fm->mailbox.'@'.$fm->host.',';
		}
		$data['from'] = trim($from,',');
		$data['textPlain'] = $mailsData->textPlain;
		$data['textHtml'] = $mailsData->textHtml;
		$id = $this->saveEmail($data);
		if($id)
		$this->saveAttchments($id,$mail_att);
		/*print_r($data);
		exit;*/
	}
	function saveEmail($mainHeaders){
	
	$subject=addslashes($mainHeaders["subject"]);
	$to=addslashes($mainHeaders["to"]);
	$from=addslashes($mainHeaders["from"]);
	$email_date=addslashes($mainHeaders["date"]);
	//$partCharset=addslashes($partCharset);
	$body=addslashes($this->filter_string_decode($mainHeaders["textPlain"]));
	$body=str_replace("a:",".myclass a:",$body);
	$body=str_replace("@font",".myclass @font",$body);	
	$charset='';
	$qry_in="";	
	$sql_cnt="SELECT id FROM cm_mails WHERE `from`='".$from."' and email_date='".$email_date."'  limit 1";
	$result_cnt=DB::select($sql_cnt);

	if(count($result_cnt)!=0)
	{
		$id = $result_cnt[0]->id;;
	$sql="update `cm_mails` set `subject`='".$subject."',`to`='".$to."',`from`='".$from."',`email_date`='".$email_date."',`body`='".$body."' ,`charset`='".$charset."'   where `id`='".$id."'";
	DB::statement($sql);
	}else{
		$data = array();
		$data['subject'] = $subject;
		$data['to'] = $to;
		$data['from'] = $from;
		$data['email_date'] = $email_date;
		$data['body'] = $body;
		$data['charset'] = $charset;
		
		$id = DB::table('cm_mails')->insertGetId($data);
		
	}
	
	return $id;
	
	}
	function saveAttchments($mail_id,$atts){
		/*echo "<pre>";
		print_r($atts);exit;*/
		$misc_date = gmdate('Y-m-d h:i:s');
	foreach($atts as $att){	
	/*echo "<pre>";
	print_r($att);
		print_r(pathinfo($att->filePath));exit;*/
	$file_info = pathinfo($att->filePath);	
	$ext = strtolower($file_info['extension']);
	if(in_array($ext,array('pdf','doc','docx','rtf'))){
		$sql="insert into `cm_attachments`(`cm_mails_id`,`fn_or`,`fn_re`,`cnt_type`,`cnt_stype`,`misc_date`) values ('".$mail_id."','".addslashes($att->name)."','".addslashes($file_info['basename'])."','".addslashes($ext)."','".addslashes($ext)."','".addslashes($misc_date)."')";
		DB::statement($sql);
	}else{
		if (file_exists($att->filePath)) {
                    @unlink($att->filePath);
                }
	}
	}
	}
	
	 function getDBResult($sql,$type='object',$db='db'){
            //$query=DB::select($sql);
            $query=DB::select($sql);
            if(count($query)>0)
                $result=$query;
            else
                $result=0;
            return $result;
	}
	function filter_string_decode($str){
              if (isset($str) && $str!='' && $str!='0')
              {
                     $entity=array("&acirc;??","&iexcl;", "&curren;", "&yen;", "&brvbar;", "&sect;", "&uml;",
                         "&copy;", "&ordf;", "&laquo;", "&not;", "&shy;", "&reg;", "&macr;", "&deg;", "&plusmn;",
                         "&sup2;", "&sup3;", "&acute;", "&micro;", "&para;", "&middot;", "&cedil;", "&sup1;",
                         "&ordm;", "&raquo;", "&frac14;", "&frac12;", "&frac34;", "&iquest;", "&Agrave;",
                         "&Aacute;", "&Acirc;", "&Atilde;", "&Auml;", "&Aring;", "&AElig;", "&Ccedil;",
                         "&Egrave;", "&Eacute;", "&Ecirc;", "&Euml;", "&Igrave;", "&Iacute;", "&Icirc;",
                         "&Iuml;", "&ETH;", "&Ntilde;", "&Ograve;", "&Oacute;", "&Ocirc;", "&Otilde;",
                         "&Ouml;", "&times;", "&Oslash;", "&Ugrave;", "&Uacute;", "&Ucirc;", "&Uuml;",
                         "&Yacute;", "&THORN;", "&szlig;", "&agrave;", "&aacute;", "&acirc;", "&atilde;",
                         "&auml;", "&aring;", "&aelig;", "&ccedil;", "&egrave;", "&eacute;", "&ecirc;",
                         "&euml;", "&igrave;", "&iacute;", "&icirc;", "&iuml;", "&eth;", "&ntilde;",
                         "&ograve;", "&oacute;", "&ocirc;", "&otilde;", "&ouml;", "&divide;",
                         "&oslash;", "&ugrave;", "&uacute;", "&ucirc;", "&uuml;", "&yacute;", "&thorn;",
                         "&yuml;", "&fnof;", "&Alpha;", "&Beta;", "&Gamma;", "&Delta;", "&Epsilon;", "&Zeta;", "&Eta;", "&Theta;",
                         "&Iota;", "&Kappa;", "&Lambda;", "&Mu;", "&Nu;", "&Xi;", "&Omicron;", "&Pi;", "&Rho;", "&Sigma;",
                         "&Tau;", "&Upsilon;", "&Phi;", "&Chi;", "&Psi;", "&Omega;", "&alpha;", "&beta;", "&gamma;", "&delta;",
                         "&epsilon;", "&zeta;", "&eta;", "&theta;", "&iota;", "&kappa;", "&lambda;", "&mu;", "&nu;", "&xi;",
                         "&omicron;", "&pi;", "&rho;", "&sigmaf;", "&sigma;", "&tau;", "&upsilon;", "&phi;", "&chi;", "&psi;",
                         "&omega;", "&thetasym;", "&upsih;", "&piv;", "&bull;", "&hellip;", "&prime;", "&Prime;", "&oline;",
                         "&frasl;", "&weierp;", "&image;", "&real;", "&trade;", "&alefsym;", "&larr;", "&uarr;", "&rarr;",
                         "&darr;", "&harr;", "&crarr;", "&lArr;", "&uArr;", "&rArr;", "&dArr;", "&hArr;", "&forall;", "&part;",
                         "&exist;", "&empty;", "&nabla;", "&isin;", "&notin;", "&ni;", "&prod;", "&sum;", "&minus;", "&lowast;",
                         "&radic;", "&prop;", "&infin;", "&ang;", "&and;", "&or;", "&cap;", "&cup;", "&int;", "&there4;", "&sim;",
                         "&cong;", "&asymp;", "&ne;", "&equiv;", "&le;", "&ge;", "&sub;", "&sup;", "&nsub;", "&sube;", "&supe;",
                         "&oplus;", "&otimes;", "&perp;", "&sdot;", "&lceil;", "&rceil;", "&lfloor;", "&rfloor;", "&lang;",
                         "&rang;", "&loz;", "&spades;", "&clubs;", "&hearts;", "&diams;", "&OElig;", "&oelig;", "&Scaron;",
                         "&scaron;", "&Yuml;", "&circ;", "&tilde;", "&ensp;", "&emsp;", "&thinsp;",
                         "&zwnj;", "&zwj;", "&lrm;", "&rlm;", "&ndash;",
                         "&mdash;", "&lsquo;", "&rsquo;", "&sbquo;", "&ldquo;", "&rdquo;", "&bdquo;", "&dagger;", "&Dagger;",
                         "&permil;", "&lsaquo;", "&rsaquo;", "&euro;");
                     $entity_replace=array('&#039;', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', );
                
					$str=str_replace($entity,$entity_replace,$str);
					$str=mb_convert_encoding($str, "HTML-ENTITIES","UTF-8,ISO-8859-1");
					$str=(trim(htmlentities($str,ENT_QUOTES)));
                return trim(html_entity_decode($str,ENT_QUOTES,'UTF-8'));

              // return $str;
              }
              else
              return $str;
          
         }	
		 }
