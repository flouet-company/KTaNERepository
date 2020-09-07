<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ModuleRequest;
use App\Module;
use Cache;

class ModuleController extends Controller
{
    
    /**
    * Create a new controller instance.
    *
    * @return void
    */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function create()
    {
        return view('modules.create');
    }

    public function store(ModuleRequest $request)
    {
        $uid = null;
        $tries = 0;
        
        do {
            $uid = preg_replace('/[^A-Za-z0-9]/i', '', $request->name) . ($tries > 0 ? $tries : '');
            $tries++;
        } while(Cache::get('modules')->contains('uid', $uid));

        $module = new Module();
        $module->name = $request->name;
        $module->description = $request->description;
        $module->uid = $uid;
        $module->credits = $request->credits;
        $module->expert_difficulty = $request->expert_difficulty;
        $module->defuser_difficulty = $request->defuser_difficulty;
        $module->publisher_id = $request->user()->id;
        $module->save();

        if($request->has('tags')) {
            $existingTags = Cache::get('tags')->whereIn('name', $request->tags);
            $creatingTags = collect($request->tags)->filter(function ($item) use ($existingTags) {
                return !$existingTags->contains('name', $item);
            });

            $module->tags()->sync($existingTags);

            foreach ($creatingTags as $tagName) {
                $tag = Tag::create([
                    'name' => $tagName
                ]);

                $module->tags()->attach($existingTags);
            }

        }

        Cache::clear('modules');

        return response()->json($module);
    }
}