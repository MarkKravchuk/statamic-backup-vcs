# Backup Vcs for Statamic

### Checklist to complete backups integrated with 

1. 
``` bash
composer require markkravchuk/statamic-backup-vcs
```

2.
``` bash
php artisan vendor:publish --tag="backup-vcs-config"
```

3. Configure filesystems (```config/filesystems.php```). 

Add the following containers:

```bash
        'local' => [
            'driver' => 'local',
            'root' => storage_path('backup-local'),
        ],

        'content' => [
            'driver' => 'local',
            'root' => base_path('content'),
        ],
        'gcs-backup' => [
            'driver' => 'gcs',
            'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE', null), // optional: /path/to/service-account.json
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'), // optional: is included in key file
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BACKUP_BUCKET', 'your-bucket'),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', '').'backup/list', // optional: /default/path/to/apply/in/bucket
            'visibility' => 'public', // optional: public|private
        ],
```

4. Good luck!
