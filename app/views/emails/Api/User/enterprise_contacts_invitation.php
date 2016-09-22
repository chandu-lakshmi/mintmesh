<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Mint Mesh</title>
<link href='http://fonts.googleapis.com/css?family=arial:300,400,500,700' rel='stylesheet' type='text/css'>
<style type="text/css">
.main_table {
  width: 500px;
}
@media only screen and (max-width: 520px) {
.main_table {
  width: 100% !important;
}
.textContent p {
  font-size: 16px !important;
}
.textContent .wish_txt { font-size:24px !important; line-height:30px; }
.activate_btn {
    width: 90% !important;
}
}
​
</style>
</head>
<body style="background-color:#F1F1F1">
<table style="margin:0px auto;background-color:#fff;width:700px;margin-top:10px;padding-left:20px">
	<tr class="clear">
		<td style="width:22%">
			<img src="<?php echo $company_logo ; ?>" style="margin-left:-17px;max-width:174px;max-height:100px">
		</td>
		<td>
		    <span style="border-left:2px solid gray;padding-top:0px;padding-bottom:82px;border-color:#E3E3E3"></span>
			<p style="font-weight:500;font-size:27px;font-family:sans-serif;margin-top:-22px;margin-left:50px">Referral Reward Program</p>
		</td>
	</tr>
	<tr class="clear">
		<td colspan="2">
			<p style="font-size:15px;font-family:sans-serif;margin-top:35px;">
				Hello <?php echo ucfirst($name) ; ?>,
			</p>
			<p style="font-size:14px;color:#6B6B6B;position:relative;top:-5px;font-family:sans-serif">
				We bring these powerful referral platform to you. Please download the app and singup.
			</p>
			<p style="font-size:14px;color:#6B6B6B;position:relative;top:-10px;font-family:sans-serif">
				We are excited to have you be a part of our success here at MintMesh Enterprise.
			</p>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<p style="font-size:15px;color:#494949;font-family:sans-serif;margin-top:15px">Download MintMesh Mobile app</p>
		</td>
	</tr>
	<tr>
		<td >
		<a href="https://appsto.re/in/0jF98.i">
		    <img src="<?php echo $public_url ; ?>images/app_store.png" style="margin-top:10px">
		</a>    
		</td>
		<td>
		<a href="https://play.google.com/store/apps/details?id=com.mintmesh.mintmesh">
			<img src="<?php echo $public_url ; ?>images/google_play.png" style="margin-top:10px;margin-left:-29px">
		</a>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<p style="margin-top:44px;font-family:sans-serif;font-size:15px;color:#404040;margin-bottom:5px">Thanks,</p>
			<p style="font-family:sans-serif;font-size:15px;color:#404040;margin-top:0px;margin-bottom:5px"><?php echo $fromName; ?></p>
			<p style="font-family:sans-serif;font-size:15px;color:#404040;margin-top:0px;margin-bottom:5px"><?php echo $company_name; ?>,</p>
			<p style="font-family:sans-serif;font-size:15px;color:#404040;margin-top:0px;margin-bottom:5px">Sent via MintMesh Enterprise.</p>
		</td>
	</tr>
	<tr>
		<td>
			<img src="<?php echo $public_url ; ?>images/logo.svg" style="margin-left:-10px">
		</td>
	</tr>
</table>
</body>
</html>