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
                                    <img width="160" src="<?php echo $company_logo ; ?>" alt="company-logo" style="display:block;width:62px;">
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
                                                <a href="<?php echo $apply_link ; ?>"><span style="font-size: 13px;padding: 9px 13px;display: inline-block;background: #238260;color: #fff;border-radius: 2px;outline:none;cursor:pointer;">APPLY</span></a>
                                                <a href="<?php echo $refer_link ; ?>"><span style="font-size: 13px;padding: 8px 12px;display: inline-block;border: 1px solid  #238260;color: #238260;border-radius: 2px;outline:none;cursor:pointer;margin-left:10px">REFER</span></a>
                                            </td>
                                            <td align="right"><a href="<?php echo $view_jobs_link ; ?>"><span style="font-size: 13px;padding: 8px 12px;display: inline-block;color: #252525;border-radius: 2px;outline:none;cursor:pointer;border:1px solid #252525">VIEW ALL JOBS</span></a></td></tr>
                                    </table>
                                </td>
                            <tr>
                                <td style="padding-top:30px">You can also attach your resume and send an email by clicking on the link below <a href="mailto:<?php echo $reply_emailid; ?>" target="_top"><?php echo $reply_emailid; ?></a></td> 
                            </tr>
                            </tr>
                            <tr height="40px"><td style="border-bottom:1px solid #cccccc">&nbsp;</td></tr>
                            <tr height="22px"><td></td></tr>
                            <tr><td align="center">Refer friend for future openings here ? &nbsp;<a href="<?php echo $drop_cv_link ; ?>"><span style="font-size: 14px;padding: 2px 10px;display: inline-block;color: #252525;border-radius: 2px;outline:none;cursor:pointer;border:1px solid #252525">Upload CV</span></a></td></tr>
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
