<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckApkManifestRequest;
use App\Http\Requests\CheckApkRequest;
use App\Http\Requests\CheckLinkRequest;
use App\Models\Check;
use App\Services\Analysis\ApkUnreadableException;
use App\Services\Checks\CheckService;
use App\Services\Reply\ReplyComposer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckController extends Controller
{
    public function __construct(private CheckService $checks) {}

    public function link(CheckLinkRequest $request): RedirectResponse
    {
        $check = $this->checks->checkMessage($request->validated('input'), 'web');

        return redirect()->route('result', $check);
    }

    public function apk(CheckApkRequest $request): RedirectResponse
    {
        $file = $request->file('apk');
        $fileName = $this->displayName($file->getClientOriginalName());
        $disk = Storage::disk('local');
        $path = $file->storeAs(config('sayno.apk.quarantine_dir'), Str::random(40).'.apk', 'local');

        try {
            $check = $this->checks->checkApk($disk->path($path), $fileName, 'web');
        } catch (ApkUnreadableException) {
            return back()->withErrors(['apk' => __('result.apk_unreadable')]);
        } finally {
            $disk->delete($path);
        }

        // Nama file hanya ditampilkan sekali, tidak disimpan di database.
        return redirect()->route('result', $check)->with('file_name', $fileName);
    }

    /**
     * APK besar: browser sudah mengekstrak AndroidManifest.xml dan menghitung SHA-256.
     * File utuh tidak pernah sampai ke server.
     */
    public function apkManifest(CheckApkManifestRequest $request): RedirectResponse
    {
        $fileName = $this->displayName($request->validated('file_name'));

        try {
            $check = $this->checks->checkApkManifest(
                $request->file('manifest')->get(),
                $fileName,
                $request->validated('sha256'),
                (int) $request->validated('file_size'),
                'web',
            );
        } catch (ApkUnreadableException) {
            return back()->withErrors(['apk' => __('result.apk_unreadable')]);
        }

        return redirect()->route('result', $check)->with('file_name', $fileName);
    }

    public function show(Check $check, ReplyComposer $composer): View
    {
        $check->load('signals');

        return view('checks.show', [
            'check' => $check,
            'summary' => $composer->summary($check),
            'info' => $check->signals->firstWhere('code', $check->input_type->value === 'apk' ? 'apk.info' : 'link.domain')?->meta ?? [],
            'lookalike' => $check->signals->whereIn('code', ['link.lookalike', 'link.other_owner'])->first()?->meta,
            'patterns' => $check->signals->where('code', 'text.pattern')->pluck('meta.category')->all(),
            'fileName' => session('file_name'),
        ]);
    }

    private function displayName(string $name): string
    {
        $name = trim(preg_replace('/[^\p{L}\p{N} ._()-]+/u', '_', $name));

        return Str::limit($name !== '' ? $name : 'file.apk', 80);
    }
}
