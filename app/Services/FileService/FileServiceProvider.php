<?php

namespace App\Services\FileService;

use App\Services\FileService\Console\Commands\FileRefactorCommand;
use App\Services\FileService\Console\Commands\FileRegenerateCommand;
use App\Services\FileService\Console\Commands\FilesCleanupCommand;
use App\Services\FileService\Console\Commands\UpdateFilePathCommand;
use App\Services\FileService\Console\Commands\UpdateFilePropertiesCommand;
use App\Services\FileService\Models\FileObject;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use ReflectionException;

class FileServiceProvider extends ServiceProvider
{
    /**
     * @throws Exceptions\FileConfigAlreadyRegisteredException|ReflectionException
     */
    public function boot(): void
    {
        $this->commands([
            UpdateFilePropertiesCommand::class,
            FilesCleanupCommand::class,
            FileRefactorCommand::class,
            FileRegenerateCommand::class,
            UpdateFilePathCommand::class,
        ]);

        Relation::morphMap([
            'file_object' => FileObject::class,
        ]);

        resolve('router')->bind('file_uid', function ($value) {
            return FileObject::findByUuid($value) ?? abort(404);
        });

        // The registry is static: rebuild it on every boot (tests, queue workers) instead of appending.
        FileService::setFileConfigs(get_objects_in_directory(
            app_path('Services/FileService/DefaultConfigs'),
            'App\\Services\\FileService\\DefaultConfigs\\'
        ), append: false);

        // Application presets (App\FileConfigs\*FileConfig).
        FileService::setFileConfigs(get_objects_in_directory(app_path('FileConfigs'), 'App\\FileConfigs\\'));

        if (Config::get('services.file_service.load_migrations', true) && $this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/migrations');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/file.php',
            'file'
        );

        $this->app->singleton('file_service', function () {
            return new FileService();
        });
    }
}
