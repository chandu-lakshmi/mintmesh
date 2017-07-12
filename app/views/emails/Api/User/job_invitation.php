<table width="100%" height="100%" style="min-width:348px;font-size:14px;font-family:Arial, Helvetica, sans-serif;" border="0" cellspacing="0" cellpadding="0">
    <tr height="32px"></tr>
    <tr align="center">
        <td width="32px"></td>
        <td>
            <table cellspacing="0" cellpadding="0" style="max-width:600px;border:1px solid #ccc;">
                <tr>
                    <td width="540px" style="padding:0 30px;">
                       <table cellspacing="0" cellpadding="0" width="100%" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                           <tr height="36px"><td>&nbsp;</td></tr>
                           <tr>
                                <td align="center">
                                    <?php if(!empty($company_logo)){ ?>
                                    <img width="62" src="<?php echo $company_logo ; ?>" alt="company-logo">
                                     <?php }else {
                                     echo $company_name; }?>
                                </td>
                            </tr>
                            <tr height="36px"><td style="border-bottom:1px solid #cccccc">&nbsp;</td></tr>
                            <tr>
                                <td style="padding-top:8px">
                                    <table cellspacing="0" cellpadding="0" width="100%">
                                        <tr>
                                            <td width="60%" >
                                                <table cellpadding="0" cellpadding="0" border="0" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                                    <tr height="28px"><td>&nbsp;</td></tr>
                                                    <?php if(!empty($email_template)){?>
                                                    <tr>
                                                        <td style="color:#000;padding: 5px 0;">Hey <?php echo $name;?>, your friend <?php echo $fromName ; ?> referred you for the following job,</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <tr>
                                                        <td style="color:#3b70cd"><?php echo $company_name ; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><h4 style="margin:4px 0 20px;font-weight:600;font-size:20px;color:#252525"><?php echo $looking_for ; ?></h4></td>                        
                                                    </tr>
                                                </table>
                                            </td>
                                            <?php if($free_service){
                                                
                                             } else { ?>
                                             <td width="40%" align="center" >
                                                <!--   Thank you-->
                                                </td>
                                              <?php if($post_type == 'external'){ if($discovery){
                                                ?>
                                            <td width="20%" style="border-left:1px solid #ccc;padding:0 10px;">
                                                <table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                                    <tr align="center">
                                                        <td><h3 style="margin:0;color:#ee8617;font-size:26px;font-weight:500;"><?php echo $discovery ; ?></h3>
                                                        <?php if($dis_points){ ?> POINTS <?php } ?> 
                                                        </td>
                                                    </tr>
                                                    <tr align="center">
                                                        <td style="color:#929292;font-size: 14px;line-height: 20px;">Discovery Rewards</td>                        
                                                    </tr>
                                                </table>
                                            </td>
                                            <?php }
                                            if($referral){
                                                ?>
                                            <td width="20%" style=" <?php if(empty($discovery)){?> border-left:1px solid #cccccc; <?php } ?>border-left:1px solid #ccc;border-right:1px solid #ccc;padding:0 10px;">
                                                <table>
                                                    <tr align="center">
                                                        <td><h3 style="margin:0;color:#ee8617;font-size:26px;font-weight:500;"> <?php echo $referral ; ?></h3>
                                                        <?php if($ref_points){ ?> POINTS <?php } ?> 
                                                        </td>
                                                    </tr>
                                                    <tr align="center">
                                                        <td style="color:#929292;font-size: 14px;line-height: 20px;">Referral Rewards</td>                        
                                                    </tr>
                                                </table>
                                            </td>
                                              <?php } } ?>
                                            
                                            <?php  } ?>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 0 20px;">
                                    <table cellspacing="0" cellpadding="0" width="100%" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;line-height:22px;vertical-align:top;">
                                        <tr><td width="20%" style="color:#939393;padding:5px 0;vertical-align:top;">Job Function:</td><td style="padding:5px 0;" width="80%" style="color:#252525"><?php echo $job_function ; ?></td></tr>
                                        <tr><td width="20%" style="color:#939393;padding:5px 0;vertical-align:top;">Experience:</td><td style="padding:5px 0;" width="80%" style="color:#252525"><?php echo $experience ; ?></td></tr>
                                        <?php if($vacancies){ ?>
                                        <tr><td width="20%" style="color:#939393;padding:5px 0;vertical-align:top;">Vacancies:</td><td style="padding:5px 0;" width="80%" style="color:#252525"><?php echo $vacancies ; ?></td></tr>
                                        <?php } ?>
                                        <tr><td width="20%" style="color:#939393;padding:5px 0;vertical-align:top;">Location:</td><td width="80%" style="color:#252525;padding:5px 0;"><?php echo $location ; ?></td></tr>
                                        <tr><td style="color:#939393;padding:5px 0;vertical-align:top;">Job Description:</td></tr>
                                        <tr><td colspan="2" style="padding:0">
                                                <pre style="white-space: pre-line;font-family:Arial, Helvetica, sans-serif !important;">
                                                 <?php echo $job_description ; ?>   
                                                </pre>
                                            </td>
                                        </tr>
                                    </table>  
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-bottom:30px;">
                                    <table cellspacing="0" cellpadding="0" width="100%" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                        <tr>
                                            <?php if($post_type == 'internal'){ ; ?>
                                            <td align="left" width="80">
                                                <table cellpadding="0" cellpadding="0" border="0" style="width:70px;text-align:center;display:inline-block;font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                                    <tr>
                                                        <td style="background:#238260;border:1px solid #238260;padding: 6px 10px;"><a href="<?php echo $apply_link ; ?>" style="display:inline-block;border-radius: 2px;background:#238260;color:#fff;text-decoration: none;outline:none;width:100%;">APPLY</a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <?php } if($post_type == 'external'){ ; ?>
                                            <td width="80">
                                                <table cellpadding="0" cellpadding="0" border="0" style="width:70px;text-align:center;display:inline-block;font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                                    <tr>
                                                        <td style="background:#238260;border:1px solid #238260;padding: 6px 10px;"><a href="<?php echo $refer_link ; ?>" style="display:inline-block;border-radius: 2px;background:#238260;color:#fff;text-decoration: none;outline:none;width:100%;">REFER</a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <?php } ?>
                                            <td align="right" style="padding-right:20px">
                                                <table cellpadding="0" cellpadding="0" border="0" style="width:140px;text-align:center;display:inline-block;font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                                    <tr>
                                                        <td style="background:#238260;border:1px solid #238260;padding: 6px 10px;"><a href="<?php echo $view_jobs_link ; ?>" style="display:inline-block;border-radius: 2px;background:#238260;color:#fff;text-decoration: none;outline:none;width:100%;">VIEW ALL JOBS</a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                         </tr>
                                    </table>
                                </td>
                            </tr>
                             <?php if($post_type == 'external'){ ; ?>
                        <tr><td style="border-top:1px solid #ccc;color:#939393;padding-top:30px;">Share Job</td></tr>
                        <tr>
                            <td style="border-bottom:1px solid #ccc;padding-bottom:30px;">
                                <table>
                                    <tr style="padding-top:10px;display:inline-block;">
                                        <td style="padding-right:15px"><a href="https://www.facebook.com/dialog/feed?app_id=<?php echo $app_id; ?>&quote=click below to apply&display=popup&amp;caption=&name=<?php echo $company_name; ?> is looking for <?php echo $looking_for; ?>&picture=<?php echo !empty($company_logo)?$company_logo:''; ?>&description=Experience: <?php echo $experience ; ?>, Location: <?php echo $location ; ?>&link=<?php echo $job_details_link; ?>&redirect_uri=<?php echo $job_details_link; ?>&domain=enterprisestaging.mintmesh.com&origin=http://enterprisestaging.mintmesh.com" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/f_social.png" height="40px" width="40px" alt="fb"></a></td>
                                        <td style="padding-right:15px"><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $bittly_link; ?>&title=<?php echo $company_name; ?> is looking for <?php echo $looking_for; ?>&summary=Experience: <?php echo $experience ; ?>, Location: <?php echo $location ; ?>&source=<?php echo $company_name; ?>" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/in_social.png" height="40px" width="40px" alt="linkedin"></a></td>
                                        <td style="padding-right:15px"><a href="https://twitter.com/intent/tweet?text=<?php echo $company_name; ?> is looking for <?php echo $looking_for; ?>, Experience: <?php echo $experience ; ?>, Location: <?php echo $location ; ?>&url=<?php echo $bittly_link; ?>&hashtags=&via=<?php echo $company_name; ?>&related=" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/t_social.png" height="40px" width="40px" alt="twitter"></a></td>
                                        <td style="padding-right:15px"><a href="https://plus.google.com/share?url=<?php echo $job_details_link; ?>" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/g_social.png" height="40px" width="40px" alt="g+"></a></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                            <tr>
                                <td>
                                    <table cellspacing="0" cellpadding="0" width="90%" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                        <tr>
                                            <td align="right" style="padding:30px 0;">Upload a friend’s resume for future openings  &nbsp;</td>
                                            <td align="left" style="padding:30px 0;">
                                                <table cellpadding="0" cellpadding="0" border="0" style="width:100px;text-align:center;display:inline-block;font-family:Arial, Helvetica, sans-serif;font-size:14px;">
                                                    <tr>
                                                        <td style="background:#238260;border:1px solid #238260;padding: 6px 10px;"><a href="<?php echo $drop_cv_link ; ?>" style="display:inline-block;border-radius: 2px;background:#238260;color:#fff;text-decoration: none;outline:none;width:100%;">Upload CV</a>
                                                        </td>
                                                    </tr><?php } ?>
                                                </table>
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
        <td width="32px"></td>
    </tr>
</table>