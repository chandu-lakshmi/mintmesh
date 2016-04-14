<!DOCTYPE html>
<html>
<head>
	<title></title>
	 <link href="http://fonts.googleapis.com/css?family=arial:300,400,500,700" rel="stylesheet" type="text/css">
         <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	
</head>
<body width="500" style="font-family: 'arial', sans-serif;background:#E1E1E1;">
	<table id="tab" bgcolor="#ffffff" style="max-width:500px;width:95%;margin:0px auto 0px;padding:10px 30px;">
		<tr>
			<td align="center" valign="top" class="textContent" style="padding:20px 30px 10px;">
<img src="<?php echo $public_url; ?>images/mintmesh-logo.png"   align="center" style="max-width:100%;text-align:center;" alt="logo" title="Text">
<h2 style="text-align:center;font-family: 'arial', sans-serif;font-weight:300;font-size:28px;margin-bottom:10px;margin-top:20px;color:#000000;" class="main_heading">Payout Request</h2>


</td>	
		</tr>
		<tr>
			<td>
			<hr style="border-top:1px solid #c8c8c8;margin-bottom:20px;">

				<table>
				<tr>
					<td>
						 <h3 style="color:#000000;line-height:125%;font-family: 'arial', sans-serif;font-size:20px;font-weight:normal;margin-top:10px;margin-bottom:0px;text-align:left;">Hello Admin, </h3>
					</td>
				</tr>
				<tr>
					
						 <td style="text-align:left;font-family: 'arial', sans-serif;font-size:20px;margin-bottom:0;color:#777777;line-height:135%;letter-spacing:0.2px;">
						<p>Please find the details for payout.</p>
						
						</td>
					
					
				</tr>
				<tr>
					<td>
						<table class="details"  style="font-size:16px;line-height:180%">
							<tr>
								<td align="left" style="color:#777777">
									User email-id:
								</td>
								<td>
									<?php echo $userEmail ; ?>
								</td>
							</tr>
							<tr>
								<td align="left" style="color:#777777">
									Amount requested for Payout: 
								</td>
								<td>
									&#x20B9;<?php echo $amount; ?>
								</td>
							</tr>
							<tr>
								<td align="left" style="color:#777777">
									Amount remaining after Payout:
								</td>
								<td>
									&#x20B9;<?php echo $remaningAmount; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
					<h3 style="color:#000000;line-height:125%;font-family: 'arial', sans-serif;font-size:20px;font-weight:normal;margin-top:0;margin-bottom:0px;text-align:left;padding:20px 0px;">User bank details</h3>
						<table  style="font-size:16px;line-height:180%">
							
							<tr>
								<td align="left" style="color:#777777">
									Bank name:
								</td>
								<td>
									 <?php echo $bank_name ; ?>
								</td>
							</tr>
							<tr>
								<td align="left" style="color:#777777">
									Account holder name:
								</td>
								<td>
									<?php echo $account_name ; ?>
								</td>
							</tr>
							<tr>
								<td align="left" style="color:#777777">
									Account number:
								</td>
								<td>
									<?php echo $account_number ; ?>
								</td>
							</tr>
							<tr>
								<td align="left" style="color:#777777">
									IFSC Code:
								</td>
								<td>
									 <?php echo $ifsc_code ; ?>
								</td>
							</tr>
							<tr>
								<td align="left" style="color:#777777">
									Bank Address:
								</td>
								<td>
									<?php echo $address ; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			</td>
		</tr>
		<tr>
        <table style="width: 100%; margin: 0 auto; padding: 10px 0 0;">
            <tr>
                <td colspan="2" style="text-align: center;font-size:18px; font-weight:normal; color:#999999; text-align:center;line-height:135%;" align="center">If you need assistance,<br> please contact MintMesh Support <a style="color: #1b8c6e; text-decoration: none;" href="mailto:support@mintmesh.com">(support@mintmesh.com)</a></td>
            </tr>
        </table>            
        </tr>
		
		
	</table>
</body>
</html>