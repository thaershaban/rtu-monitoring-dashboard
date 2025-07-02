<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // لإضافة السجلات

class DocumentController extends Controller
{
    /**
     * جلب جميع المستندات.
     */
    public function index()
    {
        try {
            $documents = Document::orderBy('created_at', 'desc')->get();
            return response()->json($documents);
        } catch (\Exception $e) {
            Log::error('Error fetching documents: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching documents.'], 500);
        }
    }

    /**
     * رفع مستند جديد.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:20480', // الحد الأقصى 20 ميجابايت (20480 كيلوبايت)
        ]);

        try {
            $file = $request->file('file');
            $originalFileName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize(); // بالبايت

            // تخزين الملف في مجلد storage/app/public/documents
            // يجب أن تكون قد نفذت php artisan storage:link مسبقاً
            $path = $file->store('documents', 'public');

            $document = Document::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $path,
                'file_name' => $originalFileName,
                'file_mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);

            return response()->json(['message' => 'Document uploaded successfully!', 'document' => $document], 201);
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return response()->json(['message' => 'Error uploading document: ' . $e->getMessage()], 500);
        }
    }

    /**
     * حذف مستند.
     */
    public function destroy(Document $document)
    {
        try {
            // حذف الملف من التخزين
            Storage::disk('public')->delete($document->file_path);

            // حذف السجل من قاعدة البيانات
            $document->delete();

            return response()->json(['message' => 'Document deleted successfully!'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting document: ' . $e->getMessage()], 500);
        }
    }
}