<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Permission;
use App\Role;
use Cache;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Module' => 'App\Policies\ModulePolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {

        $permissions = null;
        // Check if the cache has permissions stored
        if(Cache::has('permissions')) {
            $permissions = Cache::get('permissions');
        } else {
            // Remember all permissions in cache for 10 minutes
            $permissions = Cache::remember('permissions', 60*10, function () {
                return Permission::all();
            });
        }

        // Iterate over the collected permissions and define seperate gates
        foreach($permissions as $permission) {
            Gate::define($permission->name, function($user) use($permission) {
                return $user->hasPermission($permission->name);
            }); 
        }

        if(!Cache::has('roles')) {
            Cache::remember('roles', 60*10, function() {
                return Role::all();
            });
        }

        $this->registerPolicies();
    }
}
