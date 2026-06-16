<?php
namespace App\Models;

use App\Traits\HasCreatedBy;

class Attachment extends BaseModel
{
    use HasCreatedBy;

    protected $fillable = ['uuid', 'attachable_type', 'attachable_id', 'file_name', 'file_path', 'mime_type', 'file_size', 'created_by'];

    /**
     * Auto-assign a uuid on create so the public URL never exposes the
     * sequential primary key.
     */
    protected static function booted(): void
    {
        static::creating(function (self $attachment) {
            if (empty($attachment->uuid)) {
                $attachment->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Route-model-bind {attachment} by uuid instead of id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Parent model.
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Gated serve URL. The uuid is the real key; the trailing file name is
     * cosmetic — it makes the browser tab title and the default save-as name
     * match the original file.
     */
    public function getUrlAttribute(): string
    {
        return route('attachments.show', [
            'attachment' => $this->uuid,
            'name'       => $this->file_name,
        ]);
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
