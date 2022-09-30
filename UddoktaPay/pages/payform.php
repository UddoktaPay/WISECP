<?php
$data = $module->get_fields($links["successful-page"], $links["failed-page"]);
if (isset($data) && $data['status']) {
    $status = true;
    $redirect_url = $data['payment_url'];
} else {
    $status = false;
}
?>

<?php if (!$status) : ?>
    <div align="center">
        <div class="progresspayment">
            <div class="lds-ring">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
            <br>
            <h3 id="progressh3"><?php echo $data['message']; ?></h3>
        </div>
    </div>
<?php endif ?>

<?php if ($status) : ?>
    <div align="center">
        <div class="progresspayment">
            <div class="lds-ring">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
            <br>
            <h3 id="progressh3"><?php echo $module->lang["redirect-message"]; ?></h3>
            <h4>
                <div class='angrytext'>
                    <strong><?php echo __("website/others/loader-text2"); ?></strong>
                </div>
            </h4>

        </div>
    </div>
    <script type="text/javascript">
        setTimeout(function() {
            $("#UddoktaPayRedirect").submit();
        }, 2000);
    </script>
    <form action="<?= $redirect_url; ?>" method="get" id="UddoktaPayRedirect">
    </form>
<?php endif ?>