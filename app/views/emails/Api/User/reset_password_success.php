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
}
</style>
</head>

<body style="background:#E1E1E1; padding:0px; margin:0px; font-family: 'arial', sans-serif;font-weight:300;font-size:18px;">
<table bgcolor="#FFFFFF"  border="0" width="500" cellpadding="0" cellspacing="0" class="main_table" style="background:#fff;  margin:20px auto 0px;" >
  <tr>
    <td align="center" valign="top"  style="padding:20px 20px 10px;"><img src="<?php echo $public_url ; ?>images/mintmesh-logo.png"   align="center" style="max-width:100%;text-align:center;" alt="logo" title="Text">
      <h2 style="text-align:center;font-weight:normal;font-family: 'arial', sans-serif;font-weight:300;font-size:24px;margin-bottom:10px;margin-top:20px;color:#000000;">Password Change Confirmation</h2>
      <hr style="border-top:1px solid #c8c8c8;margin-top:20px;"></td>
  </tr>
  <tr>
    <td valign="top" class="textContent" style="padding:20px 20px 10px;line-height:135%;letter-spacing:0.2px;font-size:18px; font-weight:normal; color:#777777;">
      <h3 style="color:#000000;line-height:125%;font-family: 'arial', sans-serif;font-size:20px;font-weight:normal;margin-top:0;margin-bottom:0px;text-align:left;">Hello <?php echo ucfirst($name);?>, </h3>
      <p>The password for the MintMesh account: <a href="mailto:<?php echo $email;?>" target="_blank"><?php echo $email;?></a> was recently changed.</p>
      <p>If you requested this change then you can ignore this email.</p>
      <p>If this wasn't you, your account may have been compromised. Please reset your password immediately from MintMesh app.</p>
       </td>
  </tr>
  <tr>
    <td valign="top" class="textContent" style="padding:30px 20px 10px;font-size:18px; font-weight:normal; color:#777777;  line-height:135%;"><p style="color:#333333; font-family: 'arial', sans-serif;font-size:18px;font-weight:normal;margin-top:0;margin-bottom:0px;text-align:left;">Sincerely, </p>
      <p style="color:#777777;font-size:18px; margin:5px 0px 0px; ">MintMesh Team</p></td>
  </tr>
  <tr>
    <td valign="top" class="textContent" style="padding:10px 20px 20px;font-size:18px; font-weight:normal; color:#999999; text-align:center;line-height:135%;"><hr style="border-top:1px solid #c8c8c8;margin-top:5px;margin-bottom:20px;">
      If you need assistance, please contact
        MintMesh Support <a style="color:#1b8c6e;text-decoration:none;" href="mailto:support@mintmesh.com">(support@mintmesh.com)</a></td>
  </tr>
</table>
</body>
</html>
