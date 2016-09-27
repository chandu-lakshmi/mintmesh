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
<body>
<div class="main" style="color:black;font-family: arial,sans-serif;">
	<div class="sub">
		<img src="<?php echo $company_logo ; ?>" style="float:left;width:80px;height:40px;">
		<span style="font-weight:bold;font-size:26px;margin-top:50px;display:inline-block;border-left:2px solid #CCCCCC;padding-left:14px;margin-left:14px;">Referral Reward Program</span>
	</div>
	<div style="font-size:16px;">
		<p style="margin-top:50px;font-weight:600;">Hello <?php echo ucfirst($name) ; ?>,</p>
		<p>We bring these powerful referral platform to you. Please download the App and Signup.</p>
		<p style="line-height:20px;">We are excited to have you be a part of our success here at <?php echo $company_name; ?>.</p>
	</div>
	<div style="margin-bottom:60px;">
		<p style="font-size:16px;margin-top: 50px;">Download the mobile app from</p>
		<img src="<?php echo $public_url ; ?>images/app_store.png" width="120" style="display:inline-block;">
		<img src="<?php echo $public_url ; ?>images/google_play.png" width="120">
	</div>
	<div style="font-size:16px;line-height:10px;">
		<p>Thanks,</p>
		<p><?php echo $fromName; ?>,</p>
		<p><?php echo $company_name; ?>,</p>
		<p>Sent via MintMesh Enterprise.</p>
	</div>
	<div style="margin-left:10px;margin-top:30px;">
		<img src="<?php echo $public_url ; ?>images/logo.svg" width="120" style="display:inline-block;">
	</div>
</div>
</body>
</html>