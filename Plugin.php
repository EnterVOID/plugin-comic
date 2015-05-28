<?php namespace Void\Comic;

use App;
use Event;
use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    /**
     * @var boolean Determine if this plugin should have elevated privileges.
     */
    public $elevated = true;

    public function pluginDetails()
    {
        return [
            'name'        => 'void.comic::lang.plugin.name',
            'description' => 'void.comic::lang.plugin.description',
            'author'      => 'Jordan Bobo',
            'icon'        => 'icon-pencil',
        ];
    }

    public function registerComponents()
    {
        return [
            'Void\Comic\Components\Session'       => 'session',
            'Void\Comic\Components\Account'       => 'account',
            'Void\Comic\Components\ResetPassword' => 'resetPassword'
        ];
    }

    public function registerPermissions()
    {
        return [
            'void.comics.access_comics'  => ['tab' => 'Comics', 'label' => 'Manage Comics'],
        ];
    }

    public function registerNavigation()
    {
        return [
            'comic' => [
                'label'       => 'void.comic::lang.comics.menu_label',
                'url'         => Backend::url('void/comic/comics'),
                'icon'        => 'icon-pencil',
                'permissions' => ['void.comics.*'],
                'order'       => 500,

                'sideMenu' => [
                    'comics' => [
                        'label'       => 'void.comic::lang.comics.all_comics',
                        'icon'        => 'icon-pencil',
                        'url'         => Backend::url('void/comic/comics'),
                        'permissions' => ['void.comics.access_comics']
                    ]
                ]
            ]
        ];
    }

    // public function registerSettings()
    // {
    //     return [
    //         'settings' => [
    //             'label'       => 'void.comic::lang.settings.menu_label',
    //             'description' => 'void.comic::lang.settings.menu_description',
    //             'category'    => 'void.comic::lang.settings.comics',
    //             'icon'        => 'icon-cog',
    //             'class'       => 'Void\Comic\Models\Settings',
    //             'order'       => 500,
    //             'permissions' => ['void.comics.*'],
    //         ],
    //     ];
    // }
}
