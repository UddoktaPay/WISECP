<?php
if (!defined("CORE_FOLDER")) die();
$LANG           = $module->lang;
$CONFIG         = $module->config;
$callback_url   = Controllers::$init->CRLink("payment", ['UddoktaPay', $module->get_auth_token(), 'callback'], "none");
?>
<form action="<?php echo Controllers::$init->getData("links")["controller"]; ?>" method="post" id="UddoktaPay">
    <input type="hidden" name="operation" value="module_controller">
    <input type="hidden" name="module" value="UddoktaPay">
    <input type="hidden" name="controller" value="settings">
    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["api_key"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="api_key" value="<?php echo $CONFIG["settings"]["api_key"]; ?>">
            <span class="kinfo"><?php echo $LANG["api_key-desc"]; ?></span>
        </div>
    </div>
    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["api_url"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="api_url" value="<?php echo $CONFIG["settings"]["api_url"]; ?>">
            <span class="kinfo"><?php echo $LANG["api_url-desc"]; ?></span>
        </div>
    </div>
    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["usd_exchange_rate"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="usd_exchange_rate" value="<?php echo $CONFIG["settings"]["usd_exchange_rate"]; ?>">
            <span class="kinfo"><?php echo $LANG["usd_exchange_rate-desc"]; ?></span>
        </div>
    </div>
    <div style="float:right;" class="guncellebtn yuzde30"><a id="UddoktaPay_submit" href="javascript:void(0);" class="yesilbtn gonderbtn"><?php echo $LANG["save-button"]; ?></a></div>
</form>

<script type="text/javascript">
    $(document).ready(function() {
        $("#UddoktaPay_submit").click(function() {
            MioAjaxElement($(this), {
                waiting_text: waiting_text,
                progress_text: progress_text,
                result: "UddoktaPay_handler",
            });
        });

    });

    function UddoktaPay_handler(result) {
        if (result != '') {
            var solve = getJson(result);
            if (solve !== false) {
                if (solve.status == "error") {
                    if (solve.for != undefined && solve.for != '') {
                        $("#UddoktaPay " + solve.for).focus();
                        $("#UddoktaPay " + solve.for).attr("style", "border-bottom:2px solid red; color:red;");
                        $("#UddoktaPay " + solve.for).change(function() {
                            $(this).removeAttr("style");
                        });
                    }
                    if (solve.message != undefined && solve.message != '')
                        alert_error(solve.message, {
                            timer: 5000
                        });
                } else if (solve.status == "successful") {
                    alert_success(solve.message, {
                        timer: 2500
                    });
                }
            } else
                console.log(result);
        }
    }
</script>