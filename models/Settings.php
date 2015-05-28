<?php namespace Void\Comic\Models;

use Lang;
use Model;
use System\Models\MailTemplate;
use Void\Comic\Models\Comic as ComicModel;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'comic_settings';
    public $settingsFields = 'fields.yaml';

    const ACTIVATE_AUTO = 'auto';
    const ACTIVATE_USER = 'comic';
    const ACTIVATE_ADMIN = 'admin';

    const LOGIN_EMAIL = 'email';
    const LOGIN_USERNAME = 'comicname';

    public function initSettingsData()
    {
        $this->require_activation = true;
        $this->activate_mode = self::ACTIVATE_AUTO;
        $this->use_throttle = true;
        $this->default_country = 1;
        $this->default_state = 1;
        $this->welcome_template = 'void.comic::mail.welcome';
        $this->login_attribute = self::LOGIN_EMAIL;
    }

    public function getDefaultCountryOptions()
    {
        return Country::getNameList();
    }

    public function getDefaultStateOptions()
    {
        return State::getNameList($this->default_country);
    }

    public function getActivateModeOptions()
    {
        return [
            self::ACTIVATE_AUTO => ['void.comic::lang.settings.activate_mode_auto', 'void.comic::lang.settings.activate_mode_auto_comment'],
            self::ACTIVATE_USER => ['void.comic::lang.settings.activate_mode_comic', 'void.comic::lang.settings.activate_mode_comic_comment'],
            self::ACTIVATE_ADMIN => ['void.comic::lang.settings.activate_mode_admin', 'void.comic::lang.settings.activate_mode_admin_comment'],
        ];
    }

    public function getLoginAttributeOptions()
    {
        return [
            self::LOGIN_EMAIL => ['void.comic::lang.login.attribute_email'],
            self::LOGIN_USERNAME => ['void.comic::lang.login.attribute_comicname'],
        ];
    }

    public function getActivateModeAttribute($value)
    {
        if (!$value)
            return self::ACTIVATE_AUTO;

        return $value;
    }

    public function getWelcomeTemplateOptions()
    {
        return [''=>'- '.Lang::get('void.comic::lang.settings.no_mail_template').' -'] + MailTemplate::orderBy('code')->lists('code', 'code');
    }

}
