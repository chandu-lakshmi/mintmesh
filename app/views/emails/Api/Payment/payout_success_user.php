<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="https://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="format-detection" content="telephone=no" /> <!-- disable auto telephone linking in iOS -->
<title>Mint Mesh</title>
<link href='https://fonts.googleapis.com/css?family=arial:300,400,500,700' rel='stylesheet' type='text/css'>
<style type="text/css">
/* RESET STYLES */
html { background-color:#E1E1E1; margin:0; padding:0; }
body, #bodyTable, #bodyCell, #bodyCell{height:100% !important; margin:0; padding:0; width:100% !important;font-family: 'arial', sans-serif;}
table{border-collapse:collapse;}
table[id=bodyTable] {width:100%!important;margin:auto;max-width:500px!important;color:#7A7A7A;font-weight:normal;}
img, a img{border:0; outline:none; text-decoration:none;height:auto; line-height:100%;}
a {text-decoration:none !important;}
h1, h2, h3, h4, h5, h6{color:#5F5F5F; font-weight:normal; font-family: 'arial', sans-serif; font-size:20px; line-height:125%; text-align:Left; letter-spacing:normal;margin-top:0;margin-right:0;margin-bottom:10px;margin-left:0;padding-top:0;padding-bottom:0;padding-left:0;padding-right:0;}

/* CLIENT-SPECIFIC STYLES */
.ReadMsgBody{width:100%;} .ExternalClass{width:100%;} /* Force Hotmail/Outlook.com to display emails at full width. */
.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div{line-height:100%;} /* Force Hotmail/Outlook.com to display line heights normally. */
table, td{mso-table-lspace:0pt; mso-table-rspace:0pt;} /* Remove spacing between tables in Outlook 2007 and up. */
#outlook a{padding:0;} /* Force Outlook 2007 and up to provide a "view in browser" message. */
img{-ms-interpolation-mode: bicubic;display:block;outline:none; text-decoration:none;} /* Force IE to smoothly render resized images. */
body, table, td, p, a, li, blockquote{-ms-text-size-adjust:100%; -webkit-text-size-adjust:100%; font-weight:normal!important;} /* Prevent Windows- and Webkit-based mobile platforms from changing declared text sizes. */
.ExternalClass td[class="ecxflexibleContainerBox"] h3 {padding-top: 10px !important;} /* Force hotmail to push 2-grid sub headers down */

/* /\/\/\/\/\/\/\/\/ TEMPLATE STYLES /\/\/\/\/\/\/\/\/ */

/* ========== Page Styles ========== */
h1{display:block;font-size:26px;font-style:normal;font-weight:normal;line-height:100%;}
h2{display:block;font-size:20px;font-style:normal;font-weight:normal;line-height:120%;}
h3{display:block;font-size:17px;font-style:normal;font-weight:normal;line-height:110%;}
h4{display:block;font-size:18px;font-style:italic;font-weight:normal;line-height:100%;}
.flexibleImage{height:auto;}
.linkRemoveBorder{border-bottom:0 !important;}
table[class=flexibleContainerCellDivider] {padding-bottom:0 !important;padding-top:0 !important;}

body, #bodyTable{background-color:#E1E1E1;}
#emailHeader{background-color:#E1E1E1;}
#emailBody{background-color:#FFFFFF;}
#emailFooter{background-color:#E1E1E1;}
.nestedContainer{background-color:#F8F8F8; border:1px solid #CCCCCC;}
.emailButton{background-color:#205478; border-collapse:separate;}
.buttonContent{color:#FFFFFF; font-family: 'arial', sans-serif; font-size:18px; font-weight:bold; line-height:100%;text-align:center;}
.buttonContent a{color:#FFFFFF; display:block; text-decoration:none!important; border:0!important;}
.emailCalendar{background-color:#FFFFFF; border:1px solid #CCCCCC;}
.emailCalendarMonth{background-color:#205478; color:#FFFFFF; font-family:Helvetica, Arial, sans-serif; font-size:16px; font-weight:bold; padding-top:10px; padding-bottom:10px; text-align:center;}
.emailCalendarDay{color:#205478; font-family:Helvetica, Arial, sans-serif; font-size:60px; font-weight:bold; line-height:100%; padding-top:20px; padding-bottom:20px; text-align:center;}
.imageContentText {margin-top: 10px;line-height:0;}
.imageContentText a {line-height:0;}
#invisibleIntroduction {display:none !important;} /* Removing the introduction text from the view */

/*FRAMEWORK HACKS & OVERRIDES */
span[class=ios-color-hack] a {color:#275100!important;text-decoration:none!important;} /* Remove all link colors in IOS (below are duplicates based on the color preference) */
span[class=ios-color-hack2] a {color:#205478!important;text-decoration:none!important;}
span[class=ios-color-hack3] a {color:#8B8B8B!important;text-decoration:none!important;}
.a[href^="tel"], a[href^="sms"] {text-decoration:none!important;color:#606060!important;pointer-events:none!important;cursor:default!important;}
.mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {text-decoration:none!important;color:#606060!important;pointer-events:auto!important;cursor:default!important;}


/* MOBILE STYLES */
@media only screen and (max-width: 480px){
/*////// CLIENT-SPECIFIC STYLES //////*/
body{width:100% !important; min-width:100% !important;} /* Force iOS Mail to render the email at full width. */

/*td[class="textContent"], td[class="flexibleContainerCell"] { width: 100%; padding-left: 10px !important; padding-right: 10px !important; }*/
table[id="emailHeader"],
table[id="emailBody"],
table[id="emailFooter"],
table[class="flexibleContainer"],
td[class="flexibleContainerCell"] {width:100% !important;}
td[class="flexibleContainerBox"], td[class="flexibleContainerBox"] table {display: block;width: 100%;text-align: left;}
td[class="imageContent"] img {height:auto !important; width:100% !important; max-width:100% !important; }
img[class="flexibleImage"]{height:auto !important; width:100% !important;max-width:100% !important;}
img[class="flexibleImageSmall"]{height:auto !important; width:auto !important;}


/*
Create top space for every second element in a block
*/
table[class="flexibleContainerBoxNext"]{padding-top: 10px !important;}

/*
Make buttons in the email span the
full width of their container, allowing
for left- or right-handed ease of use.
*/
table[class="emailButton"]{width:100% !important;}
td[class="buttonContent"]{padding:0 !important;}
td[class="buttonContent"] a{padding:15px !important;}

}

/*  CONDITIONS FOR ANDROID DEVICES ONLY
*   https://developer.android.com/guide/webapps/targeting.html
*   https://pugetworks.com/2011/04/css-media-queries-for-targeting-different-mobile-devices/ ;
=====================================================*/

@media only screen and (-webkit-device-pixel-ratio:.75){
			img{  max-width:80%!important}
.main_heading{font-size:24px !important;}
.wish_txt{  font-size: 26px !important;}
p{font-size:18px !important;}
.footer_txt{  font-size: 12px !important;  padding: 0px !important;}
}
/* Put CSS for low density (ldpi) Android layouts in here */
/*}*/

@media only screen and (-webkit-device-pixel-ratio:1){
			img{  max-width:80%!important}
.main_heading{font-size:24px !important;}
.wish_txt{  font-size: 26px !important;}
p{font-size:18px !important;}
.footer_txt{  font-size: 12px !important;  padding: 0px !important;}
}
/* Put CSS for medium density (mdpi) Android layouts in here */
/*}*/

@media only screen and (-webkit-device-pixel-ratio:1.5){
			img{  max-width:80%!important}
.main_heading{font-size:24px !important;}
.wish_txt{  font-size: 26px !important;}
p{font-size:18px !important;}
.footer_txt{  font-size: 12px !important;  padding: 0px !important;}
}
/* Put CSS for high density (hdpi) Android layouts in here */
/*}*/
/* end Android targeting */

/* CONDITIONS FOR IOS DEVICES ONLY
=====================================================*/
@media only screen and (min-width : 320px) and (max-width:568px) {
		img{  max-width:80% !important}
.main_heading{font-size:24px !important;}
.wish_txt{  font-size: 26px !important;}
p{font-size:18px !important;}
.footer_txt{  font-size: 12px !important;  padding: 0px !important;}
}
/* end IOS targeting */
</style>

</head>
<body bgcolor="#E1E1E1" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">

<center style="background-color:#E1E1E1;">
<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="table-layout: fixed; max-width:100% !important; width: 100% !important; min-width: 100% !important;">
<tr>
<td align="center" valign="top" id="bodyCell">
<!-- // EMAIL HEADER -->

<table bgcolor="#FFFFFF"  border="0" cellpadding="0" cellspacing="0" width="500" id="emailBody">

<tr>
    <td align="center" valign="top">
        <!-- CENTERING TABLE // -->
      
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:#FFFFFF;" bgcolor="#fff">
            <tr>
                <td align="center" valign="top">
                    <!-- FLEXIBLE CONTAINER // -->
                  
                    <table border="0" cellpadding="0" cellspacing="0" width="500" class="flexibleContainer">
                        <tr>
                            <td align="center" valign="top" width="500" class="flexibleContainerCell">

                                <table border="0"  cellspacing="0" width="100%">
                                    <tr>
<td align="center" valign="top" class="textContent" style="padding:20px 30px 10px;">
<img src="<?php echo $public_url; ?>images/mintmesh-logo.png"   align="center" style="max-width:100%;text-align:center;" alt="logo" title="Text">
<h2 style="text-align:center;font-weight:normal;font-family: 'arial', sans-serif;font-weight:300;font-size:28px;margin-bottom:10px;margin-top:20px;color:#000000;" class="main_heading">Payout Successful</h2>

<hr style="border-top:1px solid #c8c8c8;margin-top:20px;">

</td>
                                    </tr>
                                </table>
                                <!-- // CONTENT TABLE -->

                            </td>
                        </tr>
                    </table>
                    <!-- // FLEXIBLE CONTAINER -->
                </td>
            </tr>
        </table>
        <!-- // CENTERING TABLE -->
    </td>
</tr>
<!-- // MODULE ROW -->

<tr>
    <td align="center" valign="top">
        <!-- CENTERING TABLE // -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td align="center" valign="top">
                    <!-- FLEXIBLE CONTAINER // -->
                    <table border="0" cellpadding="0" cellspacing="0" width="500" class="flexibleContainer" style="margin-top:0px;">
                        <tr>
                            <td align="center" valign="top" width="500" class="flexibleContainerCell">
                                <table border="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td align="center" valign="top">

                                            <!-- CONTENT TABLE // -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                <tr>
                                                    <td valign="top" class="textContent" style="padding:10px 30px 10px;">
                                                    <h2 style="text-align:left;font-weight:normal;font-family: 'arial', sans-serif;font-size:30px;margin-bottom:35px;margin-top:0px; color:#cc6e35;line-height:135%;letter-spacing:0.4px;font-weight:300;" class="wish_txt">Thank you for using MintMesh.</h2>
                                                        <h3 style="color:#000000;line-height:125%;font-family: 'arial', sans-serif;font-size:20px;font-weight:normal;margin-top:0;margin-bottom:0px;text-align:left;">Hello <?php echo ucfirst($name) ; ?>, </h3>
                                                        
                                                        <div style="text-align:left;font-family: 'arial', sans-serif;font-size:20px;margin-bottom:0;color:#777777;line-height:135%;letter-spacing:0.2px;">
<p>You have requested for a payout of $<?php echo $amount;?> amount.</p>
<p>It will be credited to your PayPal account provided during Payout.</p>
</div>
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- // CONTENT TABLE -->

                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <!-- // FLEXIBLE CONTAINER -->
                </td>
            </tr>
        </table>
        <!-- // CENTERING TABLE -->
    </td>
</tr>
<!-- // MODULE ROW -->
 <!-- // MODULE ROW -->


<!-- MODULE ROW // -->
<tr>
    <td align="center" valign="top">
        <!-- CENTERING TABLE // -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr style="padding-top:0;">
                <td align="center" valign="top">
                    <!-- FLEXIBLE CONTAINER // -->
                    <table border="0" cellpadding="30" cellspacing="0" width="500" class="flexibleContainer">
                        <tr>
                            <td style="padding-top:0;" align="center" valign="top" width="500" class="flexibleContainerCell">

                                

                            </td>
                        </tr>
                    </table>
                    <!-- // FLEXIBLE CONTAINER -->
                </td>
            </tr>
        </table>
        <!-- // CENTERING TABLE -->
    </td>
</tr>
<!-- // MODULE ROW -->

<!--    last from-->
<tr>
                            <td align="center" valign="top">
                                <!-- CENTERING TABLE // -->
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tbody><tr>
                                        <td align="center" valign="top">
                                            <!-- FLEXIBLE CONTAINER // -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="500" class="flexibleContainer">
                                                <tbody><tr>
                                                    <td align="center" valign="top" width="500" class="flexibleContainerCell">
                                                        <table border="0"  cellspacing="0" width="100%">
                                                            <tbody><tr>
                                                                <td align="center" valign="top">

                                                                    <!-- CONTENT TABLE // -->
                                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                        <tbody><tr>
<td valign="top" class="textContent" style="padding:50px 30px 10px;">
                                                                                <p style="color:#333333;line-height:15%;font-family: 'arial', sans-serif;font-size:20px;font-weight:normal;margin-top:0;margin-bottom:0px;text-align:left;">Sincerely,
</p>
<p style="color:#777777;font-size:18px;">(MintMesh Team)</p>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody></table>
                                                                    <!-- // CONTENT TABLE -->

                                                                </td>
                                                            </tr>
                                                        </tbody></table>
                                                    </td>
                                                </tr>
                                            </tbody></table>
                                            <!-- // FLEXIBLE CONTAINER -->
                                        </td>
                                    </tr>
                                </tbody></table>
                                <!-- // CENTERING TABLE -->
                            </td>
                        </tr>



<!-- MODULE ROW // -->
<tr>
    <td align="center" valign="top">
        <!-- CENTERING TABLE // -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td align="center" valign="top">
                    <!-- FLEXIBLE CONTAINER // -->
                    <table border="0" cellpadding="0" cellspacing="0" width="500" class="flexibleContainer" style="margin-top:0px;">
                        <tr>
                            <td align="center" valign="top" width="500" class="flexibleContainerCell">
                                <table border="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td align="center" valign="top">

                                            <!-- CONTENT TABLE // -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                <tr>
                                                    <td valign="top" class="textContent" style="padding:10px 30px 20px;">
                                                    <hr style="border-top:1px solid #c8c8c8;margin-top:5px;margin-bottom:20px;">
                                                        <div style="text-align:center;font-family: 'arial', sans-serif;font-size:20px;margin-bottom:0;margin-top:3px;color:#999999;line-height:135%;padding:0px 15px;" class="footer_txt">If you need assistance, please contact
MintMesh Support. <a style="color:#1b8c6e;text-decoration:none;" href="mailto:support@mintmesh.com">(support@mintmesh.com)</a></div>
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- // CONTENT TABLE -->

                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <!-- // FLEXIBLE CONTAINER -->
                </td>
            </tr>
        </table>
        <!-- // CENTERING TABLE -->
    </td>
</tr>
<!-- // MODULE ROW -->

</table>
<!-- // END -->

</td>
</tr>
</table>
</center>
</body>
</html>
