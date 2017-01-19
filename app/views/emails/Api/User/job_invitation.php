<table width="100%" height="100%" style="min-width:348px;font-size:15px;" border="0" cellspacing="0" cellpadding="0">
    <tr height="32px"></tr>
    <tr align="center">
        <td width="32px"></td>
        <td>
            <table cellspacing="0" cellpadding="0" style="max-width:600px;border:1px solid #cccccc">
                <tr>
                    <td width="30px"></td>
                    <td width="540px">
                       <table cellspacing="0" cellpadding="0" width="100%">
                           <tr height="36px"><td>&nbsp;</td></tr>
                           <tr>
                                <td align="center">
                                    <?php if(!empty($company_logo)){ ?>
                                    <img width="160" src="<?php echo $company_logo ; ?>" alt="company-logo" style="display:block;width:62px;">
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
                                                <table>
                                                    <tr height="28px"><td>&nbsp;</td></tr>
                                                    <tr>
                                                        <td style="color:#3b70cd"><?php echo $company_name ; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><h4 style="margin:4px 0 20px;font-weight:900;font-size:20px;color:#252525"><?php echo $looking_for ; ?></h4></td>                        
                                                    </tr>
                                                </table>
                                            </td>
                                            <?php if($free_service){?>
                                            <td width="40%" align="center" >
                                            <!--   Thank you-->
                                            </td>
                                            <?php } else { 
                                             
                                                if($discovery){
                                                ?>
                                            
                                            <td width="20%" style="border-left:1px solid #cccccc ;border-right:1px solid #cccccc">
                                                <table>
                                                    <tr align="center">
                                                        <td><h3 style="margin:0;color:#ee8617;font-size:26px;font-weight:100;"><?php echo $discovery ; ?></h3>
                                                        <?php if($dis_points){ ?> POINTS <?php } ?> 
                                                        </td>
                                                    </tr>
                                                    <tr align="center">
                                                        <td style="color:#929292;line-height:1">Discovery Rewards</td>                        
                                                    </tr>
                                                </table>
                                            </td>
                                                <?php }
                                                if($referral){
                                                    ?>
                                            <td width="20%" style=" <?php if(empty($discovery)){?> border-left:1px solid #cccccc; <?php } ?> border-right:1px solid #cccccc">
                                                <table>
                                                    <tr align="center">
                                                        <td><h3 style="margin:0;color:#ee8617;font-size:26px;font-weight:100;"> <?php echo $referral ; ?></h3>
                                                        <?php if($ref_points){ ?> POINTS <?php } ?> 
                                                        </td>
                                                    </tr>
                                                    <tr align="center">
                                                        <td style="color:#929292;line-height:1">Referral Rewards</td>                        
                                                    </tr>
                                                </table>
                                            </td>
                                                <?php } } ?>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table cellspacing="0" cellpadding="0" width="100%" style="color:#939393;line-height:1.39">
                                        <tr><td width="20%">Job Function:</td><td width="80%" style="color:#252525"><?php echo $job_function ; ?></td></tr>
                                        <tr height="2px"></tr>
                                        <tr><td width="20%">Experience:</td><td width="80%" style="color:#252525"><?php echo $experience ; ?></td></tr>
                                        <tr height="2px"></tr>
                                        <?php if($vacancies){ ?>
                                        <tr><td width="20%">Vacancies:</td><td width="80%" style="color:#252525"><?php echo $vacancies ; ?></td></tr>
                                        <tr height="2px"></tr>
                                        <?php } ?>
                                        <tr><td width="20%">Location:</td><td width="80%" style="color:#252525"><?php echo $location ; ?></td></tr>
                                        <tr height="23px"></tr>
                                        <tr><td>Job Description:</td></tr>
                                    </table>  
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <pre style="line-height: 1.5;width: 507px;white-space: pre-line;border: 0;background: transparent;margin: 0;padding: 0;padding-top: 5px;font-size: inherit;font-family: inherit;">
                                     <?php echo $job_description ; ?>   
                                    </pre>
                                </td>
                            </tr>
                            <tr height="22px"></tr>
                            <tr>
                                <td>
                                    <table cellspacing="0" cellpadding="0" width="100%" >
                                        <tr>
                                            <td align="left">
                                                <?php if($post_type == 'internal'){ ; ?>
                                                <a href="<?php echo $apply_link ; ?>"><span style="font-size: 13px;padding: 9px 13px;display: inline-block;background: #238260;color: #fff;border-radius: 2px;outline:none;cursor:pointer;">APPLY</span></a><?php } ?>
                                               <?php if($post_type == 'external'){ ; ?>
                                                <a href="<?php echo $refer_link ; ?>"><span style="font-size: 13px;padding: 8px 12px;display: inline-block;border: 1px solid  #238260;border-radius: 2px;outline:none;cursor:pointer;margin-left:10px;background: #238260;color: #fff">REFER</span></a><?php } ?>
                                            </td>
                                            <td align="right" style="padding-right:20px"><a href="<?php echo $view_jobs_link ; ?>"><span style="font-size: 13px;padding: 8px 12px;display: inline-block;border-radius: 2px;outline:none;cursor:pointer;border:1px solid #252525;background: #238260;color: #fff;">VIEW ALL JOBS</span></a></td></tr>
                                    </table>
                                </td>
<!--                            <tr>
                                <td style="padding-top:30px">You can also attach your resume and send an email by clicking on the link below <a href="mailto:<?php // cho $reply_emailid; ?>" target="_top"><?php // echo $reply_emailid; ?></a></td> 
                            </tr>-->
                            </tr>
                					
<!--                       </td>
                    </tr>-->
                    <?php if($post_type == 'external'){ ; ?>
                        <tr height="40px"><td style="border-bottom:1px solid #cccccc ">&nbsp;</td></tr>
                        <tr height="20px"><td>&nbsp;</td></tr>
                        <tr><td style="color:#939393 ">Share Job</td></tr>
                        <tr>
                            <td>
                                <table>
                                    <tr style="padding-top:10px;display:inline-block">
                                        <td style="padding-right:15px"><a href="https://www.facebook.com/dialog/feed?app_id=<?php echo $app_id; ?>&display=popup&amp;caption=&name=<?php echo $looking_for; ?>,Location: <?php echo $location; ?>&picture=<?php echo !empty($company_logo)?$company_logo:''; ?>&description=<?php echo $job_description; ?>&link=<?php echo $job_details_link; ?>&redirect_uri=<?php echo $job_details_link; ?>&domain=enterprisestaging.mintmesh.com&origin=http://enterprisestaging.mintmesh.com" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/f_social.png" height="40px" width="40px" alt="fb"></a></td>
                                        <td style="padding-right:15px"><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $job_details_link; ?>&title=<?php echo $looking_for; ?>,Location: <?php echo $location; ?>&summary=<?php echo $job_description; ?>&source=<?php echo $company_name; ?>" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/in_social.png" height="40px" width="40px" alt="linkedin"></a></td>
                                        <td style="padding-right:15px"><a href="https://twitter.com/intent/tweet?text=<?php echo $looking_for; ?>,Location: <?php echo $location; ?>&url=<?php echo $job_details_link; ?>&hashtags=&via=<?php echo $company_name; ?>&related=" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/t_social.png" height="40px" width="40px" alt="twitter"></a></td>
                                        <td style="padding-right:15px"><a href="https://plus.google.com/share?url=<?php echo $job_details_link; ?>" style="cursor:pointer"><img src="<?php echo $public_url ;?>images/g_social.png" height="40px" width="40px" alt="g+"></a></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <tr height="40px"><td style="border-bottom:1px solid #cccccc">&nbsp;</td></tr>
                            <tr height="22px"><td></td></tr>
                            <tr><td align="center">Upload a friend’s resume for future openings  &nbsp;<a href="<?php echo $drop_cv_link ; ?>"><span style="font-size: 14px;padding: 2px 10px;display: inline-block;border-radius: 2px;outline:none;cursor:pointer;border:1px solid #252525;background: #238260;color: #fff;">Upload CV</span></a></td></tr><?php } ?>
                            <tr height="32px"><td></td></tr>
                       </table>
                    </td>
                    <td width="30px"></td>
                </tr>
            </table>
        </td>
        <td width="32px"></td>
    </tr>
    <tr height="32px"></tr>
</table>
