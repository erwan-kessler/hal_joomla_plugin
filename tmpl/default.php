<?php
	/*
	# mod_sp_tweet - Twitter Module by JoomShaper.com
	# -----------------------------------------------
	# Author    JoomShaper http://www.joomshaper.com
	# Copyright (C) 2010 - 2014 JoomShaper.com. All Rights Reserved.
	# license - GNU/GPL V2 or Later
	# Websites: http://www.joomshaper.com
	*/
    // no direct access
    defined( '_JEXEC' ) or die( 'Restricted access' );
?>

<div class="sp-tweet">
    <?php foreach($data["docs"] as $i=>$value) { ?>
		<?php  if ( $params->get('animation')=='none' ) { ?>
        <div class="sp-tweet-item <?php echo ($i%2) ? 'sp-tweet-even' : 'sp-tweet-odd' ?><?php if ($i===0) echo ' sp-tweet-first' ?>">
		<?php } else { ?>
			<div class="sp-tweet-item">		
		<?php } ?>
				<?php echo $helper->prepareArticles($value['label_s'])?>
            <div class="sp-tweet-clr"></div>
        </div>
    <?php } ?>
</div>
