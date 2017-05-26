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
                                     <?php echo $job_description; ?>   
                                    </pre>
                                </td>
                            </tr>
                            <tr height="22px"></tr>
                            <tr>
                                <td>
                                    <table cellspacing="0" cellpadding="0" width="100%" >
                                        <tr>
                                            <td><span style="font-size: 13px;">Please click the below link to apply</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                               <a href="<?php echo $portalUrl ; ?>"><span style="font-size: 13px;padding: 8px 12px;"><?php echo $portalUrl; ?></span></a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
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
