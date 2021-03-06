<!-- Inliner Build Version 4380b7741bb759d6cb997545f3add21ad48f010b -->
<!DOCTYPE html>
<html>
<head>
<title>Mintmesh Email Template</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width">
</head>
<body width="500" style="background:#E1E1E1;min-width: 100%; -webkit-text-size-adjust: auto; -ms-text-size-adjust: auto; text-size-adjust: auto; color: #000000; font-family: 'arial', 'Arial','sans-serif'; font-weight: 200; text-align: left; line-height: 18px; font-size: 20px;margin: 0; padding: 0;">
<style type="text/css">
@font-face {
font-family: 'arial'; font-style: normal; font-weight: 300; src: local('arial Light'), local('arial-Light'), url('https://fonts.gstatic.com/s/roboto/v15/Hgo13k-tfSpn0qi1SFdUfaCWcynf_cDxXwCLxiixG1c.ttf') format('truetype');
}
@font-face {
font-family: 'arial'; font-style: normal; font-weight: 400; src: local('arial'), local('arial-Regular'), url('https://fonts.gstatic.com/s/roboto/v15/zN7GBFwfMP4uA6AR0HCoLQ.ttf') format('truetype');
}
@font-face {
font-family: 'arial'; font-style: normal; font-weight: 500; src: local('arial Medium'), local('arial-Medium'), url('https://fonts.gstatic.com/s/roboto/v15/RxZJdnzeo3R5zSexge8UUaCWcynf_cDxXwCLxiixG1c.ttf') format('truetype');
}
@font-face {
font-family: 'arial'; font-style: normal; font-weight: 700; src: local('arial Bold'), local('arial-Bold'), url('https://fonts.gstatic.com/s/roboto/v15/d-6IYplOFocCacKzxwXSOKCWcynf_cDxXwCLxiixG1c.ttf') format('truetype');
}
@font-face {
font-family: 'arial'; font-style: italic; font-weight: 400; src: local('arial Italic'), local('arial-Italic'), url('https://fonts.gstatic.com/s/roboto/v15/W4wDsBUluyw0tK3tykhXEfesZW2xOQ-xsNqO47m55DA.ttf') format('truetype');
}
</style>
<!-- Email Header --><table cellspacing="0" cellpadding="0" width="500" style="margin: 0 auto;border-spacing: 0;">
<tr>
<td>
<table width="500" class="header-wrap" style="font-size: 28px; text-align: center; border-bottom-color: #cccccc; border-bottom-width: 1px; border-bottom-style: solid; background:#f5f8f7; padding: 10px;margin:0px auto 0px;">
<tr><td class="logo" style="display: block; line-height: 22px; margin-bottom: 0;"><img src="<?php echo $public_url; ?>images/mintmesh-logo.png" alt="MintMesh"></td></tr>
<tr><td class="template-title" style="display: block; line-height: 22px; font-weight: 500; font-size: 22px; margin: 10px 0 5px;">MintMesh India Pvt. Ltd.</td></tr>
<tr><td class="address-title" style="display: block; line-height: 22px; font-size: 16px;">8-2-269/S/33/A, Sagar Co-op Society Road no. 2,</td></tr>
<tr><td class="address-title" style="display: block; line-height: 22px; font-size: 16px;">Banjara Hills, Hyderabad - 500034, Telangana, India.</td></tr>
</table>
</td>
</tr>
<tr>
<td>
<table class="content-wrap" style="width: 100%;background:#f5f8f7;">
<tr>
<td>
<table style="margin-bottom: 20px; margin-top: 10px; padding: 10px 20px;">
<tr><td style="font-weight: 400; display: block; font-size: 22px; color: #666666; margin-bottom: 15px;">To,</td></tr>
<tr><td style="font-weight: 500; margin-bottom: 12px; display: block; color: #000000; font-size: 22px;"><?php echo $name; ?>,</td></tr>
<tr><td style="font-size: 16px; margin-bottom: 5px; display: block;"><?php echo $location; ?>,</td></tr>
<tr><td style="font-size: 16px;"><?php echo $phone_country_name;?>.</td></tr>
</table>
</td>
</tr>
<tr>
<td>
<table style="padding: 10px 20px; border-bottom-style: solid; border-bottom-width: 1px; border-bottom-color: #cccccc; width: 100%; font-size: 22px;">
<tr>
<th style="font-weight: 400; color: #666666;">Invoice</th>
<th align="right" style="font-weight: 400; color: #666666;">Date</th>
</tr>
<tr>
<td style="font-weight: 400; display: block; font-size: 18px; margin: 10px 0;">#<?php echo $transaction_id;?></td>
<td align="right" style="font-weight: 400; font-size: 18px;"><?php echo $date_of_payment;?></td>
</tr>
</table>
</td>
</tr>
<tr>
<td>
<table style="padding: 10px 20px; border-bottom-style: solid; border-bottom-width: 1px; border-bottom-color: #cccccc; width: 100%; font-size: 22px;">
<tr>
<td style="font-weight: 400; color: #666666;">Platform Fee</td>
<td align="right" style="font-weight: 500;">
<span class="WebRupee">₹</span><?php echo $tax;?></td>
</tr>
<tr><td style="font-weight: 400; color: #999999; font-size: 14px; display: block; margin: 3px 0;">(MintMesh platform fee 20%)</td></tr>
</table>
</td>
</tr>
<tr><td>
<table style="width: 100%; font-size: 22px; padding: 10px 20px;"><tr>
<td style="font-weight: 400; color: #555555;">Total</td>
<td align="right" style="color: #555555; font-weight: 500;">
<span class="WebRupee">₹</span><?php echo $tax;?></td>
</tr></table>
</td></tr>
</table>
</td>
</tr>
<tr>
<td>
<table class="footer-wrap" style="font-size: 14px; width: 100%; text-align: center; margin-top: -3px; margin-bottom: 30px; padding-bottom: 15px; background: #f5f8f7 url('<?php echo $public_url; ?>images/pattern.png') repeat-x;" bgcolor="#f5f8f7" background="<?php echo $public_url; ?>images/pattern.png">
<tr>
<td colspan="2">
<table width="100%" style="padding: 18px 20px 13px; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #cccccc; font-size: 14px;"><tr>
<td align="left" style="font-weight: 400; color: #666666;">Service tax registration number:</td>
<td align="right">AABCU6223HSD001</td>
</tr></table>
</td>
</tr>
<tr>
<td colspan="2" align="center">
<table style="width: 100%; margin: 0 auto; padding: 10px 0 0;"><tr><td colspan="2" align="center" style="font-weight: 400; font-size: 18px; line-height:135%; color: #999999;">This is computer generated<br> statement and requires no signature.</td></tr></table>
</td>
</tr>
<tr>
<td>
<table style="width: 100%; margin: 0 auto; padding: 10px 0 0;"><tr>
        <td colspan="2" style="font-size:18px; font-weight:normal; color:#999999; text-align:center;line-height:135%;" align="center">If you need assistance, please contact <br> MintMesh Support. <a style="color: #1b8c6e; text-decoration: none;" href="mailto:support@mintmesh.com">(support@mintmesh.com)</a>
</td>
</tr></table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
