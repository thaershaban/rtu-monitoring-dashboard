<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    // تحديد الحقول التي يمكن تعبئتها جماعياً
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
    ];

    /**
     * الحصول على مسار الملف كاملاً.
     *
     * @return string
     */
    public function getFullFilePathAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}