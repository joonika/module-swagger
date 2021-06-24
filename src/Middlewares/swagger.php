<?php

namespace Modules\swagger\Middlewares;

use Joonika\Middlewares\Middleware;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\PathItem;
use OpenApi\Generator;
use OpenApi\Serializer;

class swagger extends Middleware
{

    public function run()
    {
        $path = $this->Route->path;
        if (empty($path[1])) {
            redirect_to(JK_DOMAIN_LANG() . 'swagger/home');
            exit();
        }
        if ($path[1] == 'openapi.json') {
            $name = 'api';
            $description = '';
            $version = '1.0.0';

            $dirCheck = file_get_contents(__DIR__ . '/../../../composer.json');
            if ($dirCheck) {
                $jsonDecode = json_decode($dirCheck, JSON_UNESCAPED_UNICODE);
                if (!empty($jsonDecode['name'])) {
                    $name = $jsonDecode['name'];
                }
                if (!empty($jsonDecode['description'])) {
                    $description = $jsonDecode['description'];
                }
                if (!empty($jsonDecode['version'])) {
                    $version = $jsonDecode['version'];
                }
                if (empty($description)) {
                    $description = $name;
                }
                if (empty($name)) {
                    $name = $description;
                }
            }
            /**
             * @OA\Info(
             *     title="{{JSW_info_title}}",
             *     description = "{{JSW_info_description}}",
             *     version="{{JSW_info_version}}")
             */
            if (!empty($_GET['module'])) {
                $module = $_GET['module'];
                $found_files = glob(JK_SITE_PATH() . "modules/" . $module . "/Controllers/*.php");
                array_push($found_files, __DIR__);
                $openapi = \OpenApi\Generator::scan($found_files, ['validate' => false]);
                if ($openapi->paths != Generator::UNDEFINED) {
                    foreach ($openapi->paths as $opK => $op) {
                        if ($op->path == Generator::UNDEFINED) {
                            $controllerAddress = '{{JSW_LANG}}/api/' . $module . '/';
                            $method = $op->_context->method;
                            $opFileName = $op->_context->getRootContext()->filename;
                            $methodType = 'get';
                            if (!empty($op->post->method)) {
                                $methodType = 'post';
                            }
                            $method = str_replace($methodType . '_', '', $method);
                            if (!empty($opFileName)) {
                                $baseName = basename($opFileName);
                                $controllerName = str_replace('.php', '', $baseName);
                                $controllerAddress .= $controllerName . '/' . $method;
                            }
                            $openapi->paths[$opK]->path = $controllerAddress;
                            if ($methodType == 'get') {
                                $openapi->paths[$opK]->get->path = $controllerAddress;
                            } elseif ($methodType == "post") {
                                $openapi->paths[$opK]->post->path = $controllerAddress;
                            }
                        }

                    }
                }
                $addClasses = [];
                if ($openapi->_analysis->classes != Generator::UNDEFINED) {
                    $a_c = $openapi->_analysis->classes;
                    foreach ($a_c as $ac) {
                        $substr = substr($ac['class'], -10);
                        if (!in_array($substr, ["Controller", 'swagger'])) {
                            if (!empty($ac['methods'])) {
                                foreach ($ac['methods'] as $methodName => $acMethods) {
                                    $pathMaker = "";
                                    $pathGroup = "post";
                                    if (substr($methodName, 0, strlen('post_')) == 'post_') {
                                        $pathMaker = "{{JSW_LANG}}/api/" . $module . "/" . $ac['class'] . "/" . str_replace('post_', '', $methodName);
                                    } elseif (substr($methodName, 0, strlen('get_')) == 'get_') {
                                        $pathMaker = "{{JSW_LANG}}/api/" . $module . "/" . $ac['class'] . "/" . str_replace('get_', '', $methodName);
                                        $pathGroup = "get";
                                    }
                                    if (!empty($pathMaker)) {
                                        $addClasses[$pathMaker][$pathGroup] = [
                                            "responses" => [
                                                200 => [
                                                    "description" => "OK",
                                                ]
                                            ],
                                            "tags" => ["Non Documented"],
                                        ];
                                    }
                                }

                            }
                        }
                    }

                }
                $json = $openapi->toJson();
                if (!empty($addClasses)) {
                    $mrg2 = json_decode($json, true);
                    $mrg2['paths'] = !empty($mrg2['paths']) ? $mrg2['paths'] : [];
                    $oldParsedName = [];
                    if (!empty($mrg2['paths'])) {
                        foreach ($mrg2['paths'] as $pt => $mp) {
                            $mpT = isset($mp['get']) ? 'get' : 'post';
                            array_push($oldParsedName, $mpT . '&&' . $pt);
                        }
                    }
                    if (empty($oldParsedName)) {
                        $mrg3 = array_merge($mrg2['paths'], $addClasses);
                    } else {
                        $mrg3 = [];
                        foreach ($addClasses as $adP => $ad) {
                            $adT = isset($mp['get']) ? 'get' : 'post';
                            if (!in_array($adT . '&&' . $adP, $oldParsedName)) {
                                $mrg3[$adP] = $ad;
                            }
                        }
                        $mrg3 = array_merge($mrg2['paths'], $mrg3);
                    }
                    unset($mrg2['paths']);
                    $mrg2['paths'] = $mrg3;
                    $json = json_encode($mrg2);
                }
//                $yaml = $openapi->toYaml();
            } else {

//                $openapi = \OpenApi\Generator::scan([__DIR__],[]);
                $openapi = \OpenApi\Generator::scan([__DIR__], ['validate' => false]);
                $json = $openapi->toJson();

                $description = '<strong>Modules</strong><br/><ul>';
                $found_dir = glob(JK_SITE_PATH() . "modules/*/Controllers");
                $urlsString = '{url: "/fa/swagger/openapi.json", name: "module not selected"},';

                if (!empty($found_dir)) {
                    foreach ($found_dir as $fd) {
                        $str_replace = str_replace(JK_SITE_PATH() . 'modules' . DS(), '', $fd);
                        $module = str_replace("/Controllers", '', $str_replace);
                        $urlsString .= '{url: "/fa/swagger/openapi.json?module=' . $module . '", name: "' . $module . '"},';
                        $description .= '<a href="?urls.primaryName=' . $module . '" >' . $module . '</a><br/>';

                    }
                }
                $description .= '</ul>';
                $description = str_replace('"', "'", $description);

            }
            $yaml = str_replace('{{JSW_info_description}}', $description, $json);
            $yaml = str_replace('{{JSW_info_version}}', $version, $yaml);
            $yaml = str_replace('{{JSW_LANG}}', '/' . JK_LANG(), $yaml);
            $yaml = str_replace('{{JSW_info_title}}', $name, $yaml);
            echo $yaml;
//            header('Content-Type: application/x-yaml');
//            header('Content-Type: application/json');

            die();
        }
    }
}