<div class="<?php print $classes; ?>">
<?php print $smallimage; ?>
<div><strong><?php print l($title, $detailpageurl); ?></strong></div>
<div><strong><?php print t('Manufacturer'); ?>:</strong> <?php if (!empty($manufacturer)) { print $manufacturer; } ?></div>
<div><strong><?php print t('Part Number'); ?>:</strong> <?php if (!empty($mpn)) {print $mpn; }?></div>
<div><strong><?php print t('Price'); ?>:</strong> <?php if (!empty($listpriceformattedprice)) { print $listpriceformattedprice; }?></div>
</div>
