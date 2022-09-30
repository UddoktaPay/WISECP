<?php
if (!defined("CORE_FOLDER")) die();

$lang           = $module->lang;
$config         = $module->config;

Helper::Load(["Money"]);

$api_key                = Filter::init("POST/api_key", "hclear");
$api_url                = Filter::init("POST/api_url", "hclear");
$usd_exchange_rate      = Filter::init("POST/usd_exchange_rate", "hclear");

$sets           = [];

if ($api_key != $config["settings"]["api_key"])
    $sets["settings"]["api_key"] = $api_key;

if ($api_url != $config["settings"]["api_url"])
    $sets["settings"]["api_url"] = $api_url;

if ($usd_exchange_rate != $config["settings"]["usd_exchange_rate"])
    $sets["settings"]["usd_exchange_rate"] = $usd_exchange_rate;

if ($sets) {
    $config_result  = array_replace_recursive($config, $sets);
    $array_export   = Utility::array_export($config_result, ['pwith' => true]);

    $file           = dirname(__DIR__) . DS . "config.php";
    $write          = FileManager::file_write($file, $array_export);

    $adata          = UserManager::LoginData("admin");
    User::addAction($adata["id"], "alteration", "changed-payment-module-settings", [
        'module' => $config["meta"]["name"],
        'name'   => $lang["name"],
    ]);
}

echo Utility::jencode([
    'status' => "successful",
    'message' => $lang["success1"],
]);
