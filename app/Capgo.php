<?php

namespace App;

use Base;

class Capgo
{



    public static function updateCheck()
    {

        $f3 = Base::instance();

        $config = $f3->get('config');
        $data = json_decode($f3->get('BODY'), true);

        $app_id = $data['app_id'] ?? '';
        $version_build = $data['version_build'] ?? '';
        $version_name = (int) preg_replace('/\D/', '', $data['version_name'] ?? '');
        $platform = $data['platform'] ?? '';

        if (!$app_id || !$version_build || !$platform) {
            echo json_encode(['available' => false]);
            return;
        }

        $dir = "{$config['dirs']['storage_path']}/{$config['dirs']['capgo']}/{$app_id}/{$version_build}/{$platform}";
        if (!is_dir($dir)) {
            echo json_encode(['available' => false]);
            return;
        }


        $files = scandir($dir);
        $found = [];

        $host = $f3->get('HEADERS.Host');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = "{$scheme}://{$host}";


        foreach ($files as $file) {


            $info = explode('-', $file);
            $fileVersion = (int)($info[3] ?? 0);

            if ($fileVersion > $version_name) {
                $found[] = [
                    'available' => true,
                    'version' => $fileVersion,
                    'url' => $baseUrl . "/storage/{$config['dirs']['capgo']}/$file",
                    'mandatory' => true
                ];
            }
        }

        usort($found, fn($a, $b) => $b['version'] <=> $a['version']);
        echo json_encode($found[0] ?? ['available' => false]);
    }
}