<?php
namespace App\Models;

class Attachment extends BaseModel
{
    protected $fillable = ['attachable_type', 'attachable_id', 'file_name', 'file_path', 'mime_type', 'file_size', 'uploaded_by'];

    /**
     * Parent model.
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Uploader.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Public URL.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Formatted size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = $this->file_size ?? 0;

        if ($size >= 1048576) {
            return round($size / 1048576, 2) . ' MB';
        }

        return round($size / 1024, 2) . ' KB';
    }
}
