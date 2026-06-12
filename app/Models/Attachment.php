<?php
namespace App\Models;

use App\Traits\HasCreatedBy;

class Attachment extends BaseModel
{
    use HasCreatedBy;
    
    protected $fillable = ['attachable_type', 'attachable_id', 'file_name', 'file_path', 'mime_type', 'file_size', 'created_by'];

    /**
     * Parent model.
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Public URL.
     */
    public function getUrlAttribute(): string
    {
        return asset($this->file_path);
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
