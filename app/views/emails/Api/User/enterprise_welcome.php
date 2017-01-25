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

</style>
</head>
<body style="margin:0px; font-family: 'arial', sans-serif;font-weight:300;font-size:18px;">
    <table style='min-width:550px;width: 550px' align="center" bgcolor="#EAEFF2 ">
        <tr align="center"><td><img src="<?php echo $public_url ; ?>images/logo.jpg" alt="brand-logo" height="60px"></td></tr>
        <tr>
            <td style="padding:20px">
                <table  width="100%" style="background-color:#fff">
                    <tr>
                        <td>
                            <h2 style="text-align:center;color:#cc6c5a ;margin:0;padding:30px 0">Thank you for joining MintMesh Enterprise</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center;padding: 20px 0">
                            <img src="<?php echo $public_url ; ?>images/Email-icon.png" alt="mail-icon" width="100px" height="100px">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 15px">
                            <strong style="font-size: 20px;color: #403B3B ">Hello <?php echo ucfirst($name) ; ?>,</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 15px 0px">
                            <p style="color: #666666 ;line-height: 1.6em">You have successfully created an account with <span><?php echo $email ; ?></span> for <span style="font-weight:bold"><?php echo $company ; ?></span>.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0px 15px 50px;">
                            <p style="color: #666666 ;line-height: 1.6em">To complete the process, please verify your email account.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center;padding:0px 0px 100px"> 
                             
                                <a class="activate_btn" style="color:#FFFFFF;text-decoration:none;font-family:'arial',sans-serif;font-size:20px;padding:10px 15px; text-align: center;  background:#269b7b; width:auto; margin:0px auto; border-radius:5px; line-height:20px;" href="<?php echo $desktop_link ; ?>" target="_blank"> VERIFY YOUR ACCOUNT                          
                                </a> 
                            
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-left:15px;line-height: 1.6em;font-size: 16px">
                            <span style="font-size: 18px">Sincerely,</span><br>
                            <span style="color: #666666 ">MintMesh Team.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;color: #666;font-size: 18px;line-height: 1.6em">
                <p>If you need assistance, please contact MintMesh Support. (<a href="" style="text-decoration: none;color: black"><mark style="background: rgba(255, 255, 0, 0.5);">support@mintmesh.com</mark></a>)</p>
            </td>
        </tr>
    </table>
</body>
</html>