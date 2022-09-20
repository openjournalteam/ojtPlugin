<?php
import('plugins.generic.ojtPlugin.OjtPlugin');
use Illuminate\Http\Client\PendingRequest as Http;

/**
 * @param Object plugin Plugin class
 * @return array
 * Get the plugin detail information based on product list wordpress
 */
if(! function_exists('getPluginDetail')) {
    function getPluginDetail($plugin)
    {
        if(! is_object($plugin)) {
            return [];
        }
        $request = app(Http::class)->get(OjtPlugin::API . '/product/list');
        if(! $request->failed()) {
            $plugins = $request->json();
            $pluginKey = array_search(basename($plugin->getPluginPath()), array_column($plugins, 'folder'));
            if($pluginKey !== false) {
                return $plugins[$pluginKey];
            }
        }
        return [];
    }
}

if (!function_exists('getPostObject')) {
    function getPostObject()
    {
        return (object) $_POST;
    }
}

if (!function_exists('isAjax')) {
    function isAjax()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
}
if (!function_exists('showJson')) {
    function showJson($data)
    {
        $json = !is_array($data) ? false : json_encode($data);
        header('Content-Type: application/json');
        echo $json;
        return;
    }
}
if (!function_exists('jsonError')) {
    function jsonError($msg = null)
    {
        $json['error']  = 1;
        $json['msg']    = $msg ?? 'Error found';
        showJson($json);
    }
}
if (!function_exists('showErrorPage')) {
    function showErrorPage()
    {
        exit('Error Occured');
    }
}
if (!function_exists('ajaxOrError')) {
    function ajaxOrError()
    {
        if (!isAjax()) {
            showErrorPage();
        }
    }
}
if (!function_exists('cleanHtml')) {
    function cleanHtml($string)
    {
        return strip_tags($string);
    }
}

if (!function_exists('vd')) {
    function vd($value)
    {
        echo '<pre>';
        var_dump($value);
        echo '</pre>';
    }
}

if (!function_exists('getDirs')) {
    function getDirs($path, $recursive = false, array $filtered = [])
    {
        if (!is_dir($path)) {
            throw new RuntimeException("$path does not exist.");
        }

        $filtered += ['.', '..', '.git', 'pluginTemplate'];

        $dirs = [];
        $d = dir($path);
        while (($entry = $d->read()) !== false) {
            if (is_dir("$path/$entry") && !in_array($entry, $filtered)) {
                $dirs[] = $entry;

                if ($recursive) {
                    $newDirs = getDirs("$path/$entry");
                    foreach ($newDirs as $newDir) {
                        $dirs[] = "$entry/$newDir";
                    }
                }
            }
        }

        return $dirs;
    }
}
