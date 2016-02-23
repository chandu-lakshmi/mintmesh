<!--<html>
    <body>
        <h1>Payment success</h1>
        <h3>Name : </h3><?php //echo $name; ?>
        <h3>Transaction Id : </h3><?php //echo $transaction_id; ?>
        <h3>Date Of Payment : </h3><?php //echo $date_of_payment;?>
        <h3>MintMesh service fees : </h3><?php //echo $tax; ?>

    </body>
</html>-->
<!DOCTYPE html>
<html>
<head>
	<title>Mintmesh Email Template</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<link href='https://fonts.googleapis.com/css?family=Roboto:400,300,400italic,700,500' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" type="text/css" href="http://cdn.webrupee.com/font">
</head>
<body style="min-width: 100%;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;margin: 0;padding: 0;color: #000000;
font-family: 'Roboto', 'Arial','sans-serif';font-weight: 200;text-align: left;line-height: 18px;font-size: 20px;width: 100% !important;">
<!-- Email Header -->
<table style="width: 800px;margin: 0 auto;">
	<tr>
		<td>
			<table class="header-wrap" style="background-color:#f5f8f7;text-align: center;width: 100%;border-bottom: 1px solid #cccccc;padding: 10px;">	
				<tr><td><img src="https://staging.mintmesh.com/public/images/mintmesh-logo.png" alt="MintMesh" /></td></tr>
				<tr><td style="font-weight: 400;font-size: 28px;margin: 18px 0;display: block;">MintMesh India Pvt. Ltd</td></tr>
				<tr><td style="margin-bottom: 18px;display: block;font-size: 28px;">8-2-269/S/33/A, Sagar Co-op Society Road no. 2,</td></tr>
				<tr><td style="margin-bottom: 18px;display: block;font-size: 28px;">Banjara Hills, Hyderabad - 500034, Telangana, India</td></tr>	
			</table>
		</td>
	</tr>
	<tr><td>
		<table class="content-wrap" style="width: 100%;">
			<tr>
				<td>
					<table style="margin-bottom: 20px;padding: 10px 25px;margin-top: 25px;">	
						<tr><td style="font-weight: 400;display: block;font-size: 26px;color: #666666;margin-bottom: 15px;">To</td></tr>
						<tr><td style="font-weight: 500;margin-bottom: 12px;display: block;color: #000000;font-size: 30px;"><?php echo $name; ?></td></tr>
						<tr><td style="font-size: 22px;margin-bottom: 10px;display: block;"><?php echo $location; ?></td></tr>
						<tr><td style="font-size: 22px;"><?php echo $phone_country_name;?></td></tr>	
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table style="border-bottom: 1px solid #cccccc;width: 100%;padding-bottom: 20px;padding: 10px 25px;font-size: 26px;">
						<tr><th style="font-weight: 400;color: #666666;">Invoice</th><th align="right" style="font-weight: 400;color: #666666;">Date</th></tr>
						<tr><td style="font-weight: 400;margin: 15px 0;display: block;">#<?php echo $transaction_id;?></td><td align="right" style="font-weight: 400"><?php echo $date_of_payment;?></td></tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table style="border-bottom: 1px solid #cccccc;width: 100%;padding: 20px 25px;font-size: 26px;">
						<tr><td style="font-weight: 400;color: #666666;">Platform Fee</td><td align="right" style="font-weight: 500;"><span class="WebRupee">&#x20B9;</span> <?php echo $tax;?></td></tr>
						<tr><td style="font-weight: 400; color: #999999;font-size: 18px; margin: 3px 0;display: block;">(MintMesh platform fee 20%)</td></tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table style="width: 100%;padding: 20px 25px;font-size: 26px;">
						<tr><td style="font-weight: 400;color: #555555;">Total</td><td align="right" style="color: #555555;font-weight: 500"><span class="WebRupee">&#x20B9;</span> <?php echo $tax;?></td></tr>		
					</table>
				</td></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
		<table class="footer-wrap" style="font-size:26px;width: 100%;text-align: center;margin-top: -3px;margin-bottom:30px;background: url('https://staging.mintmesh.com/public/images/pattern.png') #f5f8f7 repeat-x;">
				<tr>
					<td colspan="2">
						<table width="100%" style="border-bottom: 1px solid #cccccc;padding: 35px 25px 33px;font-size: 24px;"><tr>
							<td align="left" style="font-weight: 400;color: #666666;">Service tax registration number:</td><td align="right">AABCU6223HSD001</td>
						</tr>
					</table>
				</td>
			</tr>				
			<tr><td colspan="2" align="center">
				<table style="width: 100%;padding: 10px 0;">
					<tr><td colspan="2" align="center" style="font-weight: 400;color: #999999;line-height: 30px;">This is computer generated statament and<br/> requires no signature</td></tr>		
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table style="width: 100%;padding: 10px 0;">
					<tr>
<td colspan="2" style="font-weight: 400;color: #999999;line-height: 30px;">If you need assistance, please contact <br/> MintMesh Support <a style="color: #1c8d6e;text-decoration: none;"href="mailto:support@mintmesh.com">(support@mintmesh.com)</a></td>
						</tr>		
					</table>	
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</body>
</html>