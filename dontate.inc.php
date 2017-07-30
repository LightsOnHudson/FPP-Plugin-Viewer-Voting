<?php
$DONATE_AMOUNT = "5.00";
$DONATE_ADDRESS = "brbshaver@gmail.com";
?>
<form name="_xclick" action="https://www.paypal.com/yt/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="<?php echo $DONATE_ADDRESS;?>">
<input type="hidden" name="item_name" value="Donation">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="amount" value="<?php echo $DONATE_AMOUNT;?>">
<input type="image" src="http://www.paypal.com/en_US/i/btn/x-click-butcc-donate.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
</form>