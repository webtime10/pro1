<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class FilemanagerController extends Controller
{
    public function __construct(private readonly ImageManagerService $images) {}

    public function index(Request $request): View
    {
        $directory = $this->images->sanitizeDirectory($request->query('directory'));
        $filterName = (string) $request->query('filter_name', '');
        $page = max(1, (int) $request->query('page', 1));
        $target = $this->images->sanitizeDomId($request->query('target'));
        $thumb = $this->images->sanitizeDomId($request->query('thumb'));

        try {
            $listed = $this->images->paginate($directory, $filterName, $page);
        } catch (Throwable) {
            try {
                $listed = $this->images->paginate('', $filterName, 1);
                $directory = '';
            } catch (Throwable $e) {
                abort(500, $e->getMessage());
            }
        }

        $query = array_filter([
            'directory' => $listed['directory'] ?: null,
            'filter_name' => $filterName !== '' ? $filterName : null,
            'target' => $target !== '' ? $target : null,
            'thumb' => $thumb !== '' ? $thumb : null,
        ]);

        $listed['items']->withPath(route('admin.filemanager.index', [], false))->appends($query);

        return view('admin.filemanager.index', [
            'images' => $listed['items'],
            'directory' => $listed['directory'],
            'filterName' => $filterName,
            'target' => $target,
            'thumb' => $thumb,
            'parentUrl' => route('admin.filemanager.index', array_filter([
                'directory' => $listed['parent'] ?: null,
                'target' => $target ?: null,
                'thumb' => $thumb ?: null,
            ]), false),
            'refreshUrl' => route('admin.filemanager.index', array_filter([
                'directory' => $listed['directory'] ?: null,
                'filter_name' => $filterName !== '' ? $filterName : null,
                'target' => $target ?: null,
                'thumb' => $thumb ?: null,
            ]), false),
            'uploadUrl' => route('admin.filemanager.upload', array_filter([
                'directory' => $listed['directory'] ?: null,
            ]), false),
            'folderUrl' => route('admin.filemanager.folder', array_filter([
                'directory' => $listed['directory'] ?: null,
            ]), false),
            'deleteUrl' => route('admin.filemanager.delete', [], false),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $files = $request->file('file', []);
            if (! is_array($files)) {
                $files = $files ? [$files] : [];
            }

            if ($files === []) {
                throw new RuntimeException('Выберите файлы для загрузки');
            }

            $this->images->upload((string) $request->query('directory', ''), $files);

            return response()->json(['success' => 'Файлы загружены']);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * One-file upload for Summernote / WYSIWYG (returns public URL).
     */
    public function editorUpload(Request $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            if (! $file) {
                throw new RuntimeException('Файл не передан');
            }

            $path = $this->images->uploadOne('blog', $file);

            return response()->json([
                'url' => $this->images->publicUrl($path),
                'path' => $path,
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function folder(Request $request): JsonResponse
    {
        try {
            $this->images->createFolder(
                (string) $request->query('directory', ''),
                (string) $request->input('folder', '')
            );

            return response()->json(['success' => 'Папка создана']);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $paths = $request->input('path', []);
            if (! is_array($paths) || $paths === []) {
                throw new RuntimeException('Ничего не выбрано');
            }

            $this->images->delete($paths);

            return response()->json(['success' => 'Удалено']);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
