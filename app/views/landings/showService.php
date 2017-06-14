<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Service Details</title>
<style>
@font-face{
	font-family: open sans;
    src: url(<?php echo url(); ?>/public/fonts/OpenSans-Regular.ttf);
}
.container{
 text-align:center;   
}
.sub_ctr{
    
}
</style>
</head>

<body>
    <div class='container'>
        <p><?php echo ucwords($data['user_name']);?> is looking for <?php echo ucfirst($data['service']);?>
            <div class='sub_ctr'>Location:<?php if ($data['service_scope'] == 'global'){
                ?>
            Anywhere in the world
            <?php }else{ echo $data['service_location'];} ?></div>
            <div class='sub_ctr'>Points Awarded:<?php echo $data['service_points'];?> </div>
            <div class='sub_ctr'><?php if (empty($data['free_service'])){ ?>
                Cash Rewarded:<?php echo $data['service_currency'].$data['service_cost'] ; ?></div>
            <?php } ?>
            <div class='sub_ctr'><?php if (!empty($data['service_period'])){ ?>
                Notice Period:<?php echo ucfirst($data['service_period']) ; ?></div>
            <?php } ?>
        </p>
    </div>
    
        
</body>
</html>
