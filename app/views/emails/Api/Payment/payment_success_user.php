<!DOCTYPE html>
<html>
<head>
	<title>MintMesh</title>
</head>
<body style='margin:0px; font-family:arial; font-size:13px; background:#E1E1E1; color:#929395;'>
	<table width="500" style="background:#ffffff;max-width:500px;width:100%;margin:20px auto 0px;">
		<tr>
			<td style="padding:20px;">
				<table  style="border-collapse:collapse;max-width:600px;width:100%;margin:0px auto; ">
					<tr style="width:75%;border-bottom:1px solid #C9C9C9;">
						<td style="width:50%;padding-bottom:20px;">
							<img src="<?php echo $public_url; ?>images/mintmesh-logo.png" style="width:70px;" /></td>
						<td style="width:50%;"align="right">
							<p style="font-size:16px; margin:0px; line-height:20px; padding-bottom:5px;"><?php echo $name; ?>,</p>
                                                        <?php if(!empty($is_doller)){ ?>
                                                            <p style="font-size:16px; margin:0px; line-height:20px;padding-bottom:5px;">Invoice #<?php echo $transaction_id; ?>,</p>
                                                        <?php } ?> 
							<p style="font-size:16px; margin:0px; line-height:20px;padding-bottom:5px;"><?php echo $date_of_payment;?>.</p>
						</td>
					</tr>
					<tr>
				<td colspan="2">
					<h2 align="center" style="margin:0px auto;margin-top:20px; font-size:30px; color:#333; font-weight:normal;padding-bottom:10px; "><?php if($is_doller){ ?>Invoice<?php } else { ?> Summary <?php } ?></h2>
					<p align="center" style="font-family:arial;font-size:16px;margin:0px;padding-bottom:20px;">Thank you for choosing MintMesh to<br> grow your network.</p>
				</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2" style="background-color: #F0F0F0 ;">
				<table  style="border-collapse:collapse;width:100%;margin:0px auto;font-size:20px;line-height:55px; " >
					<tr style="border-bottom:1px solid #C9C9C9;">
						<td style="padding:8px;margin:0px">Referral Reward</td>
						<td align="right" style="padding:8px 20px;margin:0px"><?php if($is_doller){ ?>$<?php } else { ?> &#x20B9;<?php } ?><?php echo $cost;?></td>
					</tr>
					<tr style="border-bottom:1px solid #C9C9C9;">
						<td style="padding:8px;margin:0px;line-height:20px;">Platform Fee<br><span style="font-size:15px;">(MintMesh platform fee 20%)</span></td>
						<td align="right" style="padding:8px 20px;margin:0px"><?php if($is_doller){ ?>$<?php } else { ?> &#x20B9;<?php } ?><?php echo $tax; ?></td>
					</tr>
					<tr style="color:#00B593;">
						<td style="padding:8px;margin:0px"> Total</td>
                                                <td align="right" style="padding:8px 20px;margin:0px"><?php if($is_doller){ ?>$<?php } else { ?> &#x20B9;<?php } ?><?php echo $total; ?></td>
					</tr>
				</table>
			</td>

		</tr>
		<tr style="border-bottom:1px solid #C9C9C9;">
			<td colspan="2" style="font-size:18px;padding:90px 20px 20px;line-height:30px;">
					<p style="padding-bottom:20px;margin:0px">Sincerely, <br>
					<span style="color:#C9C9C9;margin:0px">(MintMesh Team).</span></p>
				
                                        <p align="center" style="padding-top:20px;border-top:1px solid #C9C9C9;color:#999999;margin:0px">If you need assistance, please contact <br>MintMesh Support. <span style="color:black;margin:0px"><a style="color:#1b8c6e;text-decoration:none;" href="mailto:support@mintmesh.com" >(support@mintmesh.com)</a></span></p>
				</td>
		</tr>
	</table>
</body>
</html>