<?php

namespace App;

use Base;

class CodePush
{




    public static function updateCheck()
    {

        $f3 = Base::instance();

        $config = $f3->get('config');
        $deployment_key = $f3->get('GET.deployment_key');
        $app_version = $f3->get('GET.app_version');
        $label = (int) preg_replace('/\D/', '', $f3->get('GET.label') ?? '0');

        if (!$deployment_key || !$app_version) {
            echo json_encode(['update_info' => ['is_available' => false]]);
            return;
        }

        $dir = "{$config['dirs']['storage_path']}/{$config['dirs']['codepush']}/{$deployment_key}/{$app_version}";
        if (!is_dir($dir)) {
            echo json_encode(['update_info' => ['is_available' => false]]);
            return;
        }


        $files = scandir($dir);
        $found = [];

        $host = $f3->get('HEADERS.Host');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = "{$scheme}://{$host}";


        foreach ($files as $file) {

            $info = explode('-', $file);
            $fileLabel = (int)($info[2] ?? 0);
            $packageHash = $info[3] ?? null;

            if ($fileLabel > $label) {
                $found[] = [
                    'download_url' => $baseUrl . "/storage/{$config['dirs']['codepush']}/$file",
                    'description' => "",
                    'is_available' => true,
                    'is_disabled' => false,
                    'target_binary_range' => $app_version,
                    'label' => $fileLabel,
                    'package_hash' => $packageHash,
                    'package_size' => filesize("$dir/$file"),
                    'should_run_binary_version' => false,
                    'update_app_version' => false,
                    'is_mandatory' => true,
                ];
            }
        }

        usort($found, fn($a, $b) => $b['label'] <=> $a['label']);
        echo json_encode(['update_info' => $found[0] ?? ['is_available' => false]]);
    }
}