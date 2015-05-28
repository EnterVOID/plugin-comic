<?php namespace Void\Comic\Controllers;

use Lang;
use Flash;
use BackendMenu;
use BackendAuth;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use Void\Comic\Models\Comic;
use Void\Comic\Models\Settings as ComicSettings;

class Comics extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['void.comics.access_comics'];

    public $bodyClass = 'compact-container';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Void.Comic', 'comic', 'comics');
        SettingsManager::setContext('Void.Comic', 'settings');
    }

    /**
     * Manually activate a user
     */
    public function update_onActivate($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $model->attemptActivation($model->activation_code);

        Flash::success(Lang::get('rainlab.user::lang.users.activated_success'));

        if ($redirect = $this->makeRedirect('update', $model)) {
            return $redirect;
        }
    }
}
