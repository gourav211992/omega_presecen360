<?php

namespace App\Providers;

use App\Helpers\Helper;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use P360\Core\Interfaces\TagCacheInterface;
use App\Services\Common\FinancialYearService;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->singleton(FinancialYearService::class, function($app){
            return new FinancialYearService(
                $app->make(TagCacheInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // \DB::listen(function ($query) {
        //     \Log::debug('SQL: ' . $query->sql);
        //     \Log::debug('Bindings: ' . json_encode($query->bindings));
        //     \Log::debug('Time: ' . $query->time . ' ms');
        // });

        View::composer('*', function ($view) {

            $authUser = request()->user();

            if ($authUser) {
                $user = Helper::getAuthenticatedUser();
                $organizationId = $authUser->organization_id;
                // Fetch organization menus based on services
                $menues = [];

                // Fetch user organization mappings
                $mappings = $user -> access_rights_org;

                // Fetch Organization Logo
                $orgLogo = Helper::getOrganizationLogo($organizationId);

                //financialyears
                $c_fyear = "";
                $fyears = app(FinancialYearService::class)->getFinancialYears($authUser);
                // $fyears = Helper::getFinancialYears();
                if($fyears!=null)
                    $c_fyear = app(FinancialYearService::class)->getFinancialYear($authUser);
                // $c_fyear = Helper::getFinancialYear(date('Y-m-d'));

                // Pass organization id and mappings
                $view->with([
                    'authSessionUser' => $user,
                    'menues' => $menues,
                    'organizations' => $mappings ,
                    'organization_id' => $organizationId,
                    'orgLogo' => $orgLogo,
                    'logedinUser'=> $user,
                    'fyears' => $fyears,
                    'c_fyear' => $c_fyear['range'] ?? ''
                ]);
            }
        });
    }
}
