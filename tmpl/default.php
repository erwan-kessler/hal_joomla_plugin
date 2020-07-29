<?php defined( '_JEXEC' ) or die( 'Restricted access' ); ?>

<div class="hal-publication">
    <?php foreach($data["docs"] as $i=>$value) { ?>
		<?php  if ( $params->get('animation')=='none' ) { ?>
        <div class="hal-item <?php echo ($i%2) ? 'hal-even' : 'hal-odd' ?><?php if ($i===0) echo 'hal-first' ?>">
		<?php } else { ?>
			<div class="hal-item">
		<?php } ?>
				<?php echo $helper->prepareArticles($value)?>
            <div class="hal-clr"></div>
        </div>
    <?php } ?>
</div>
