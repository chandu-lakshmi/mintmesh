<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Mint Mesh</title>
<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
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

</style>
</head>

<body style="background:#E1E1E1; padding:0px; margin:0px; font-family: 'Roboto', sans-serif;font-weight:300;font-size:18px;" >
<table bgcolor="#FFFFFF"  border="0" cellpadding="0" cellspacing="0" class="main_table" style="background:#fff;  margin:0px auto;" >
  <tr>
    <td align="center" valign="top"  style="padding:20px 20px 10px;"><img src="https://staging.mintmesh.com/public/images/mintmesh-logo.png"   align="center" style="max-width:100%;text-align:center;" alt="logo" title="Text">
      <h2 style="text-align:center;font-weight:normal;font-family: 'Roboto', sans-serif;font-weight:300;font-size:28px;margin-bottom:10px;margin-top:20px;color:#000000;" >Activate your Account</h2>
      <hr style="border-top:1px solid #c8c8c8;margin-top:20px;"></td>
  </tr>
  <tr>
    <td valign="top" class="textContent" style="padding:20px 20px 10px;line-height:135%;letter-spacing:0.2px;font-size:18px; font-weight:normal; color:#777777;"><h2 style="text-align:center;font-weight:normal;font-family: 'Roboto', sans-serif;font-size:30px;margin-bottom:35px;margin-top:0px; color:#cc6e35;letter-spacing:0.4px;font-weight:300; line-height: 40px;" class="wish_txt">Thank you for joining MintMesh</h2>
      <h3 style="color:#000000;font-family: 'Roboto', sans-serif;font-size:20px;font-weight:normal;margin-top:0;margin-bottom:0px;text-align:left;">Hello <?php echo ucfirst($name) ; ?>, </h3>
      <p>You have successfully created MintMesh account for <a href="mailto:<?php echo $email ; ?>" style="color:#000;text-decoration:none;"><?php echo $email ; ?></a>.</p>
<p>To complete the process, activate your account using the below activation link.</p>
<!--<p style="text-align:center; margin-top:50px; margin-bottom:50px;" ><a class="activate_btn" style="color:#FFFFFF;text-decoration:none;font-family: 'Roboto', sans-serif;font-size:20px;padding:10px 15px; text-align: center;  background:#269b7b; width:auto; margin:0px auto; border-radius:5px; line-height:20px; " href="<?php //echo $link ; ?>" target="_blank">Activate Account</a></p>-->
<p style="text-align:center; margin-top:50px; margin-bottom:50px;" >
	<a class="activate_btn" style="color:#FFFFFF;text-decoration:none;font-family: 'Roboto', sans-serif;font-size:20px;padding:10px 15px; text-align: center;  background:#269b7b; width:auto; margin:0px auto; border-radius:5px; line-height:20px; " href="<?php echo $desktop_link ; ?>" target="_blank">Activate Account</a></p>
<!--<p class="fontsize">If this email is not set up on your mobile device, please use the link below to activate your MintMesh account using a browser.
</p>
<p style="text-align:center;" >
    <a style="font-family: 'Roboto', sans-serif;font-size:18px;padding-top:5px;padding-bottom:5px; color:#269b7b; " href="<?php //echo $desktop_link ; ?>" target="_blank">Activate Account Using Browser</a>

                                </p>--></td>
  </tr>
  <tr>
    <td valign="top" class="textContent" style="padding:30px 20px 10px;font-size:18px; font-weight:normal; color:#777777;  line-height:135%;"><p style="color:#333333; font-family: 'Roboto', sans-serif;font-size:18px;font-weight:normal;margin-top:0;margin-bottom:0px;text-align:left;">Sincerely, </p>
      <p style="color:#777777;font-size:18px; margin:5px 0px 0px; ">MintMesh Team</p></td>
  </tr>
  <tr>
    <td valign="top" class="textContent" style="padding:10px 20px 20px;font-size:18px; font-weight:normal; color:#777777; text-align:center;line-height:135%;"><hr style="border-top:1px solid #c8c8c8;margin-top:5px;margin-bottom:20px;">
      If you need assistance, please contact
        MintMesh Support <a style="color:#333333;text-decoration:none;" href="mailto:support@mintmesh.com">(support@mintmesh.com)</a></td>
  </tr>
</table>
</body>
</html>
