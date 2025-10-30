<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use function back;

class AvatarController extends Controller
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function upload(Request $request)
    {
        $class = app("App\\Models\\{$request->on_type}");
        $model = $class::findOrFail($request->on_id);
        $model->addMediaFromRequest('avatar')
            ->toMediaCollection('avatars');

        return back()->with('message', 'AvatarForm uploaded successfully');
    }

    public function remove(Request $request)
    {
        if (Media::where('model_type', "App\\Models\\{$request->on_type}")
            ->where('model_id', $request->on_id)
            ->delete()) {
            return back()->with('message', 'AvatarForm removed successfully');
        }

        return back()->with('message', 'AvatarForm not found');


    }
}
