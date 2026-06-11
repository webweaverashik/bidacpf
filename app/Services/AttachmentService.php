<?php
namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Store an uploaded file under public/{directory} and attach it
     * polymorphically to the model. Mirrors the employee-photo upload style
     * (move to public_path) so it does not depend on the storage:link symlink
     * or a configured 'public' disk.
     *
     * @param  Model         $model     Any model using morphMany attachments()
     * @param  UploadedFile  $file      The uploaded file
     * @param  int           $createdBy User id
     * @param  string        $directory Public-relative dir, e.g. 'uploads/cpf-advances/applications'
     */
    public function store(Model $model, UploadedFile $file, int $createdBy, string $directory = 'uploads/attachments'): Attachment
    {
        // Safety net — the form request already validates this, but never trust it blindly.
        if (! $file->isValid() || $file->getSize() <= 0) {
            throw new \RuntimeException('The uploaded file is empty or invalid.');
        }

        $directory = trim($directory, '/');
        $uploadDir = public_path($directory);

        if (! file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension    = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $originalName = $file->getClientOriginalName();
        $mimeType     = $file->getClientMimeType();
        $size         = $file->getSize();

        $prefix   = Str::snake(class_basename($model)); // e.g. cpf_advance
        $filename = $prefix . '_' . ($model->getKey() ?? 'x') . '_' . time() . '_' . Str::random(6) . '.' . $extension;

        // move() consumes the temp file, so capture metadata BEFORE moving.
        $file->move($uploadDir, $filename);

        return $model->attachments()->create([
            'file_name'  => $originalName,
            'file_path'  => $directory . '/' . $filename, // public-relative -> asset($file_path)
            'mime_type'  => $mimeType,
            'file_size'  => $size,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Replace the model's existing attachment(s) with a new upload, deleting
     * the prior files from disk (drafts can be re-edited before submission).
     */
    public function replace(Model $model, UploadedFile $file, int $createdBy, string $directory = 'uploads/attachments'): Attachment
    {
        $this->purge($model);

        return $this->store($model, $file, $createdBy, $directory);
    }

    /**
     * Delete all attachments for a model (records + files on disk).
     */
    public function purge(Model $model): void
    {
        foreach ($model->attachments()->get() as $existing) {
            $full = public_path($existing->file_path);
            if ($existing->file_path && is_file($full)) {
                @unlink($full);
            }
            $existing->delete();
        }
    }
}
