<table width="100%" height="100%" style="min-width:348px;font-size:14px;font-family:Arial, Helvetica, sans-serif;" border="0" cellspacing="0" cellpadding="0">
   <tr height="32px"></tr>
   <tr align="center">
      <td width="32px"></td>
      <td>
         <table cellspacing="0" cellpadding="0" style="max-width:600px;border:1px solid #cccccc">
            <tr>
               <td>
                  <table cellspacing="0" cellpadding="0" width="100%">
                     <tr>
                        <td style="padding:0 30px">
                           <table cellspacing="0" cellpadding="0" width="100%" style="border-bottom:1px solid #cccccc;">
                              <tr>
                                 <td align="center" style="padding:36px 0;font-size:38px;">
                                    <?php if(!empty($company_logo)){ ?>
                                    <img width="62" src="<?php echo $company_logo ?>" alt="company-logo" >
                                    <?php }else {
                                    echo $company_name; }?>
                                 </td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                  </table>
               </td>
            </tr>
            <tr>
               <td width="600px" style="background:url('<?php echo $public_url ; ?>images/campaign-bg.jpg') no-repeat center center/cover">
                  <table cellspacing="0" cellpadding="0" width="100%">
                     <tr>
                        <td style="padding: 0 30px">
                           <table cellspacing="0" cellpadding="0" width="100%">
                              <tr>
                                 <td colspan="3" style="color:#3b70cd;padding-top:35px"><?php echo $company_name; ?></td>
                              </tr>
                              <tr>
                                 <td colspan="3" style="padding:3px 0;font-weight:900;font-size:21px;color:#252525">
                                   <?php echo $campaign_type; ?>
                                 </td>
                              </tr>
                              <tr>
                                 <td colspan="3" style="color:#999999;font-size:17px;"><?php echo $campaign_name; ?></td>
                              </tr>
                              <tr style="font-size: 22px;color:#999999">
                                 <td width="47%" style="border-bottom: 1px solid #ededed;padding:20px 0 10px 0;">
                                    <table style="vertical-align:middle;">
                                        <tr>
                                            <td style="vertical-align:middle;padding-right:5px;">
                                                <img width="30" src="<?php echo $public_url ;?>images/calender.png" alt="calendar" />
                                            </td>
                                            <td style="color:#999;font-size:22px;font-family: Arial, Helvetica, sans-serif;">Time</td>
                                        </tr>
                                    </table>
                                 </td>
                                 <td width="6%">&nbsp;</td>
                                 <td width="47%" style="border-bottom: 1px solid #ededed;padding:20px 0 10px 0;vertical-align:middle;">
                                    <table style="vertical-align:middle;">
                                        <tr>
                                            <td style="vertical-align:middle;padding-right:5px;">
                                                <img width="19" src="<?php echo $public_url ;?>images/location.png" alt="location" />
                                            </td>
                                            <td style="color:#999;font-size:22px;font-family: Arial, Helvetica, sans-serif;">Location</td>
                                        </tr>
                                    </table>
                                 </td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                  </table>
               </td>
            </tr>
            <tr>
               <td style="background:#ffffff">
                  <table cellspacing="0" cellpadding="0" width="100%">
                     <tr>
                        <td style="padding:0 30px">
                           <table cellspacing="0" cellpadding="0" width="100%" style="border-bottom:1px solid #ccc;">
                              <tr>
                                 <td style="display:block;height:10px;">&nbsp;</td>
                              </tr>
                              <tr>
                                 <td width="47%">
                                    <table cellspacing="0" cellpadding="0" width="100%" style="font-size:18px;color:#e99f07;line-height:27px;">
                                       <tr>
                                          <td width="45%">
                                             <?php echo $campaign_start_date; ?>
                                             <span style="font-weight: 100;font-size: 24px;"><?php echo $campaign_start_time; ?></span>
                                          </td>
                                          <td width="10%">to</td>
                                          <td width="45%" align="center">
                                             <?php echo $campaign_end_date; ?>
                                             <span style="font-weight: 100;font-size: 24px;"><?php echo $campaign_end_time; ?></span>
                                          </td>
                                       </tr>
                                    </table>
                                 </td>
                                 <td width="6%"></td>
                                 <td width="47%" style="color:#999999 ;font-size:17px">
                                    <?php echo $campaign_location; ?> 
                                 </td>
                              </tr>
                              <tr>
                                 <td style="padding:20px 0 40px 0">
                                    <table cellpadding="0" border="0" style="width:70px;text-align:center;display:inline-block;font-family:Arial,Helvetica,sans-serif;font-size:14px">
                                        <tr>
                                            <td style="background: #238260;">
                                             <a href="<?php echo $view_jobs_link_web; ?>" style="font-size: 13px;padding: 8px 12px;display: inline-block;border: 1px solid  #238260;border-radius: 2px;outline:none;background: #238260;color: #fff;cursor:pointer;text-decoration:none">REFER</a>
                                            </td>
                                        </tr>
                                    </table>
                                 </td>
                              </tr>
                           </table>
                        </td>
                     </tr>
                  </table>
               </td>
            </tr>
            <tr><td style="color:#939393;padding-top:30px;padding-left:30px;">Share Campaign</td></tr>            
            <tr>
                <td style="padding: 0 30px 30px;">
                    <table>
                      <tr style="padding-top:10px;display:inline-block;">
                          <td style="padding-right:15px"><a href="https://www.facebook.com/dialog/feed?app_id=<?php echo $app_id; ?>&quote=click below to apply&display=popup&amp;caption=&name=Here is a campaign at <?php echo $company_name; ?> for <?php echo $campaign_name; ?>&description=Starts on:<?php echo $campaign_start_date; ?> and Ends on:<?php echo $campaign_end_date; ?>, Location: <?php echo $campaign_location; ?>&picture=<?php echo !empty($company_logo)?$company_logo:''; ?>&link=<?php echo $view_jobs_link; ?>&redirect_uri=<?php echo $view_jobs_link; ?>&domain=enterprisestaging.mintmesh.com&origin=http://enterprisestaging.mintmesh.com" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/f_social.png" height="40" width="40" alt="fb"></a></td>
                          <td style="padding-right:15px"><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $bittly_link; ?>&title=Here is a campaign at <?php echo $company_name; ?> for <?php echo $campaign_name; ?>&summary=Starts on:<?php echo $campaign_start_date; ?> and Ends on:<?php echo $campaign_end_date; ?>, Location: <?php echo $campaign_location; ?>&source=<?php echo $company_name; ?>" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/in_social.png" height="40" width="40" alt="linkedin"></a></td>
                          <td style="padding-right:15px"><a href="https://twitter.com/intent/tweet?text=Here is a campaign at <?php echo $company_name; ?> for <?php echo $campaign_name; ?>&url=<?php echo $bittly_link; ?>&hashtags=&via=<?php echo $company_name; ?>&related=" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/t_social.png" height="40" width="40" alt="twitter"></a></td>
                          <td style="padding-right:15px"><a href="https://plus.google.com/share?url=<?php echo $view_jobs_link; ?>" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/g_social.png" height="40" width="40" alt="g+"></a></td>
                      </tr>
                    </table>
                </td>
            </tr>
         </table>
      </td>
      <td width="32px"></td>
   </tr>
</table>