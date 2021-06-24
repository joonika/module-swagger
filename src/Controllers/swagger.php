<?php


namespace Modules\swagger\Controllers;


use Joonika\Controller;
use Joonika\Database;
use Joonika\Route;


class swagger extends Controller
{
public function init()
{
}

public function get_home()
{
$modules = $this->Route->modules;
//        jdie($modules);
$urls = [];
$found_dir = glob(JK_SITE_PATH() . "modules/*/Controllers");
$urlsString = '{url: "/'.JK_LANG().'/swagger/openapi.json", name: "module not selected"},';

if (!empty($found_dir)) {
    foreach ($found_dir as $fd) {
        $str_replace = str_replace(JK_SITE_PATH() . 'modules' . DS(), '', $fd);
        $module = str_replace("/Controllers", '', $str_replace);
        $urlsString .= '{url: "/'.JK_LANG().'/swagger/openapi.json?module=' . $module . '", name: "' . $module . '"},';
    }
}
$urlsString = rtrim($urlsString, ',');


//        $urls='';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="/modules/swagger/assets/dist/swagger-ui.css"/>
    <link rel="icon" type="image/png" href="/modules/swagger/assets/dist/favicon-32x32.png" sizes="32x32"/>
    <link rel="icon" type="image/png" href="/modules/swagger/assets/dist/favicon-16x16.png" sizes="16x16"/>
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }

        .info a[href^="?urls"] {
            font-size: 20px;
            line-height: 30px;
            position: relative;
        }

    </style>
</head>

<body>
<div id="swagger-ui"></div>

<script src="/modules/swagger/assets/dist/swagger-ui-bundle.js" charset="UTF-8"></script>
<script src="/modules/swagger/assets/dist/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
<script>
    window.onload = function () {
        // Begin Swagger UI call region
        const ui = SwaggerUIBundle({
            urls: [
                <?=$urlsString?>
            ],
            filter: false,
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset // here
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout",
            validatorUrl: "false"
        });
        // End Swagger UI call region

        window.ui = ui;
    };
</script>
</body>
</html>
<?php
die();
}
}