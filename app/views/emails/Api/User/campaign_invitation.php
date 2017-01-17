<table width="100%" height="100%" style="min-width:348px;font-size:15px;" border="0" cellspacing="0" cellpadding="0">
    <tr height="32px"></tr>
    <tr align="center">
        <td width="32px"></td>
        <td>
            <table cellspacing="0" cellpadding="0" style="max-width:600px;border:1px solid #cccccc">
                <tr>
                	<td>
                		<table cellspacing="0" cellpadding="0" width="100%">
                                    <tr>
                                        <td width="28px">&nbsp;</td>
                                        <td style="border-bottom:1px solid #cccccc">
                                                <table cellspacing="0" cellpadding="0" width="100%">
                                                        <tr height="36px"><td>&nbsp;</td></tr>
                                                        <tr>
                                                                <td align="center">
                                                               <?php if(!empty($company_logo)){ ?>
                                                                 <img width="160" src="<?php echo $company_logo ?>" alt="company-logo" style="display:block;width:160px;">
                                                                 <?php }else {
                                                                 echo $company_name; }?>
                                                </td>
                                                        </tr>
                                                        <tr height="36px"><td>&nbsp;</td></tr>
                                                </table>
                                        </td>
                                        <td width="28px">&nbsp;</td>
                                    </tr>
                        </table>	
                	</td>
                </tr>
                <tr>
                    <td width="600px" style="background:url('<?php echo $public_url ; ?>images/campaign-bg.jpg') no-repeat center center/cover">
                       	<table cellspacing="0" cellpadding="0" width="100%">
                           	<tr>
                           		<td width="28px">&nbsp;</td>
                           		<td>
                           			<table cellspacing="0" cellpadding="0" width="100%">
                                        <tr height="35px"><td>&nbsp;</td></tr>
                                        <tr>
                                            <td style="color:#3b70cd"><?php echo $company_name; ?></td>
                                        </tr>
                                        <tr>
                                            <td><h4 style="margin:3px 0 3px;font-weight:900;font-size:21px;color:#252525"><?php echo $campaign_type; ?></h4></td>
                                        </tr>
                                        <tr><td style="color:#999999;font-size:17px;"><?php echo $campaign_name; ?></td></tr>
										<tr height="20px"><td>&nbsp;</td></tr>
                                        <tr style="font-size: 22px;color:#999999">
                                        	<td width="47%" style="border-bottom: 1px solid #ededed;padding-bottom:10px">
                                        		<img width="30px" src="<?php echo $public_url ;?>images/calender.svg" alt="calendar" style="width:30px"><span style="vertical-align: middle;margin-left: 10px">Time</span>
                                        	</td>
                                        	<td width="6%">&nbsp;</td>
                                        	<td width="47%" style="border-bottom: 1px solid #ededed;padding-bottom:10px">
                                        		<img width="20px" src="<?php echo $public_url ;?>images/location.svg" alt="location" style="width:20px"><span style="vertical-align: middle;margin-left: 10px">Location</span>
                                        	</td>
                                        </tr>
	                                </table>
                           		</td>
                           		<td width="28px">&nbsp;</td>
                            </tr>
                       	</table>
                    </td>
                </tr>
                <tr>
                	<td style="background:#ffffff">
                		<table cellspacing="0" cellpadding="0" width="100%">
                			<tr>
                				<td width="28px">&nbsp;</td>
                				<td>
                					<table cellspacing="0" cellpadding="0" width="100%">
                						<tr><td style="display:block;height:10px;">&nbsp;</td></tr>
                						<tr>
                							<td width="47%">
                								<table cellspacing="0" cellpadding="0" width="100%">
                									<tr style="font-size:14px;color:#e99f07;line-height:23px;">
                										<td width="45%"><?php echo $campaign_start_date; ?> <h3 style="margin:0;font-weight: 100;font-size: 24px;"><?php echo $campaign_start_time; ?></h3></td>
                										<td width="10%">to</td>
                										<td width="45%" align="center"><?php echo $campaign_end_date; ?><h3 style="margin:0;font-weight: 100;font-size: 24px;"><?php echo $campaign_end_time; ?></h3></td>
                									</tr>
                								</table>		
                							</td>
                							<td width="6%"></td>
                							<td width="47%" style="font-size:16px;color:#000000">
                								<?php echo $campaign_location; ?>
                							</td>
                						</tr>
                						<tr height="20px"><td>&nbsp;</td></tr>
                						<tr>
                							<td>
                								<a href="<?php echo $view_jobs_link; ?>"><span style="font-size: 13px;padding: 8px 12px;display: inline-block;border: 1px solid  #238260;border-radius: 2px;outline:none;background: #238260;color: #fff">
                									REFER
                								</span></a>
                							</td>
                						</tr>
                						<tr height="40px"><td>&nbsp;</td></tr>
                					</table>
                				</td>
                				<td width="28px">&nbsp;</td>
                			</tr>
                		</table>
                	</td>
                </tr>
                <tr>
                	<td>
                		<table cellspacing="0" cellpadding="0" width="100%">
                			<tr>
                				<td width="28px;">&nbsp;</td>
                				<td style="border-top:1px solid #ededed">
                					<table>
                						<tr><td height="15px"><span>&nbsp;</span></td></tr>
                						<tr><td style="color: #999999;margin-bottom:3px;display:block">Share Campaign</td></tr>
                						<tr>
                							<td>
                								<a href="https://www.facebook.com/dialog/feed?app_id=<?php echo $app_id; ?>&display=popup&amp;caption=<?php echo $campaign_name; ?>&picture=<?php echo !empty($company_logo)?$company_logo:''; ?>&link=<?php echo $view_jobs_link; ?>&redirect_uri=<?php echo $view_jobs_link; ?>"><img src="<?php echo $public_url ;?>images/f_social.png" alt="fb" width="35px" style="width:35px;margin-right:5px"></a>
                								<a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $view_jobs_link; ?>&title=<?php echo $campaign_name; ?>,<?php echo $campaign_type; ?>&summary=starts on<?php echo $campaign_start_date; ?>&source=<?php echo $company_name; ?>"><img src="<?php echo $public_url ;?>images/in_social.png" alt="linkedin" width="35px" style="width:35px;margin-right:5px"></a>
                								<a href="https://twitter.com/intent/tweet?text=<?php echo $campaign_name; ?>&url=<?php echo $view_jobs_link; ?>&hashtags=<?php echo $campaign_type; ?>&via=<?php echo $company_name; ?>&related="><img src="<?php echo $public_url ;?>images/t_social.png" alt="twitter" width="35px" style="width:35px;margin-right:5px"></a>
                								<a href="https://plus.google.com/share?url=<?php echo $view_jobs_link; ?>"><img src="<?php echo $public_url ;?>images/g_social.png" alt="g+" width="35px" style="width:35px"></a>
                							</td>
                						</tr>
                						<tr height="40px"><td>&nbsp;</td></tr>
                					</table>
                                                    
                				</td>
                				<td width="28px;">&nbsp;</td>
                			</tr>		
                		</table>
                	</td>
                </tr>
            </table>
        </td>
        <td width="32px"></td>
    </tr>
    <tr height="32px"></tr>
</table>


