<?php

namespace App\Services\FileService\Console\Commands;

use App\Services\FileService\Models\File;
use App\Services\FileService\Models\FileObject;
use Exception;
use Illuminate\Console\Command;

class FileRefactorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:refactor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refactor files';

    protected array $fileableArray = [
        'App\Models\UserGroup' => 'user_group',
        'App\Models\User' => 'user',
        'App\Models\Appointment' => 'appointment',
        'App\Services\GeneralService\Models\Patient' => 'patient',
        'App\Models\PatientNote' => 'patient_note',
        'App\Services\GeneralService\Models\TreatmentPlan' => 'treatment_plan',

        'App\Services\LaboratoryService\Models\LabTeam' => 'lab_user_team',
        'App\Services\LaboratoryService\Models\LabUser' => 'lab_user',
        'App\Services\LaboratoryService\Models\LabJobNote' => 'lab_job_note',
        'App\Services\LaboratoryService\Models\LabJobField' => 'lab_job_field',
        'App\Services\LaboratoryService\Models\LabInvoice' => 'lab_collaborator_invoice',
        'App\Services\LaboratoryService\Models\LabCollaboratorInvoice' => 'lab_collaborator_invoice',
        'App\Services\LaboratoryService\Models\LabRequestNote' => 'lab_request_note',
    ];

    protected array $ownerableArray = [
        'App\Models\User' => 'user',
        'App\Services\LaboratoryService\Models\LabUser' => 'lab_user',
    ];

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        File::where('path', 'LIKE', '%media%')
            ->chunkById(200, static function ($files) {
                /** @var File $file */
                foreach ($files as $file) {
                    $file->update([
                        'path' => str_replace('media', 'files', $file->path),
                    ]);
                }
            });

        foreach ($this->fileableArray as $oldValue => $newValue) {
            FileObject::where('fileable_type', $oldValue)
                ->update([
                    'fileable_type' => $newValue,
                ]);
        }

        foreach ($this->ownerableArray as $oldValue => $newValue) {
            FileObject::where('ownerable_type', $oldValue)
                ->update([
                    'ownerable_type' => $newValue,
                ]);
        }
    }
}
