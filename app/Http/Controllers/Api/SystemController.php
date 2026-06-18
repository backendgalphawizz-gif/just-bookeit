<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemController extends ApiController
{
    /** @var list<string> */
    private const CLEAR_COMMANDS = [
        'optimize:clear',
        'cache:clear',
        'config:clear',
        'view:clear',
        'route:clear',
    ];

    public function clearCache(): JsonResponse
    {
        $results = [];

        $results['migrate'] = $this->runCommand('migrate', ['--force' => true]);

        foreach (self::CLEAR_COMMANDS as $command) {
            $results[$command] = $this->runCommand($command);
        }

        $storageClear = $this->clearStorageFramework();

        return $this->success([
            'commands' => $results,
            'storage_clear' => $storageClear,
        ], 'Migrations run and application cache cleared.');
    }

    /** @param  array<string, mixed>  $parameters */
    private function runCommand(string $command, array $parameters = []): array
    {
        try {
            Artisan::call($command, $parameters);

            return [
                'success' => true,
                'output' => trim(Artisan::output()) ?: 'OK',
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'output' => $exception->getMessage(),
            ];
        }
    }

    /** @return array{success: bool, cleared_paths: list<string>} */
    private function clearStorageFramework(): array
    {
        $paths = [
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
        ];

        $cleared = [];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::files($path) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }

                File::delete($file->getPathname());
            }

            $cleared[] = $path;
        }

        return [
            'success' => true,
            'cleared_paths' => $cleared,
        ];
    }
}
