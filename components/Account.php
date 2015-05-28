<?php namespace Void\Comic\Components;

use Lang;
use Auth;
use Mail;
use Flash;
use Input;
use Redirect;
use Validator;
use ValidationException;
use ApplicationException;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Void\Comic\Models\Settings as ComicSettings;
use Exception;

class Account extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'void.comic::lang.account.account',
            'description' => 'void.comic::lang.account.account_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'redirect' => [
                'title'       => 'void.comic::lang.account.redirect_to',
                'description' => 'void.comic::lang.account.redirect_to_desc',
                'type'        => 'dropdown',
                'default'     => ''
            ],
            'paramCode' => [
                'title'       => 'void.comic::lang.account.code_param',
                'description' => 'void.comic::lang.account.code_param_desc',
                'type'        => 'string',
                'default'     => 'code'
            ]
        ];
    }

    public function getRedirectOptions()
    {
        return [''=>'- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {
        $routeParameter = $this->property('paramCode');

        /*
         * Activation code supplied
         */
        if ($activationCode = $this->param($routeParameter)) {
            $this->onActivate(false, $activationCode);
        }

        $this->page['comic'] = $this->comic();
        $this->page['loginAttribute'] = $this->loginAttribute();
        $this->page['loginAttributeLabel'] = $this->loginAttributeLabel();
    }

    /**
     * Returns the logged in comic, if available
     */
    public function comic()
    {
        if (!Auth::check())
            return null;

        return Auth::getComic();
    }

    /**
     * Returns the login model attribute.
     */
    public function loginAttribute()
    {
        return ComicSettings::get('login_attribute', ComicSettings::LOGIN_EMAIL);
    }

    /**
     * Returns the login label as a word.
     */
    public function loginAttributeLabel()
    {
        return $this->loginAttribute() == ComicSettings::LOGIN_EMAIL
            ? Lang::get('void.comic::lang.login.attribute_email')
            : Lang::get('void.comic::lang.login.attribute_comicname');
    }

    /**
     * Sign in the comic
     */
    public function onSignin()
    {
        /*
         * Validate input
         */
        $data = post();
        $rules = [];

        $rules['login'] = $this->loginAttribute() == ComicSettings::LOGIN_USERNAME
            ? 'required|between:2,64'
            : 'required|email|between:2,64';

        $rules['password'] = 'required|min:2';

        if (!array_key_exists('login', $data)) {
            $data['login'] = post('comicname', post('email'));
        }

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        /*
         * Authenticate comic
         */
        $comic = Auth::authenticate([
            'login' => array_get($data, 'login'),
            'password' => array_get($data, 'password')
        ], true);

        /*
         * Redirect to the intended page after successful sign in
         */
        $redirectUrl = $this->pageUrl($this->property('redirect'));

        if ($redirectUrl = post('redirect', $redirectUrl))
            return Redirect::intended($redirectUrl);
    }

    /**
     * Register the comic
     */
    public function onRegister()
    {
        /*
         * Validate input
         */
        $data = post();

        if (!array_key_exists('password_confirmation', $data)) {
            $data['password_confirmation'] = post('password');
        }

        $rules = [
            'email'    => 'required|email|between:2,64',
            'password' => 'required|min:2'
        ];

        if ($this->loginAttribute() == ComicSettings::LOGIN_USERNAME) {
            $rules['comicname'] = 'required|between:2,64';
        }

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        /*
         * Register comic
         */
        $requireActivation = ComicSettings::get('require_activation', true);
        $automaticActivation = ComicSettings::get('activate_mode') == ComicSettings::ACTIVATE_AUTO;
        $comicActivation = ComicSettings::get('activate_mode') == ComicSettings::ACTIVATE_USER;
        $comic = Auth::register($data, $automaticActivation);

        /*
         * Activation is by the comic, send the email
         */
        if ($comicActivation) {
            $this->sendActivationEmail($comic);

            Flash::success(Lang::get('void.comic::lang.account.activation_email_sent'));
        }

        /*
         * Automatically activated or not required, log the comic in
         */
        if ($automaticActivation || !$requireActivation) {
            Auth::login($comic);
        }

        /*
         * Redirect to the intended page after successful sign in
         */
        $redirectUrl = $this->pageUrl($this->property('redirect'));

        if ($redirectUrl = post('redirect', $redirectUrl)) {
            return Redirect::intended($redirectUrl);
        }
    }

    /**
     * Activate the comic
     * @param  string $code Activation code
     */
    public function onActivate($isAjax = true, $code = null)
    {
        try {
            $code = post('code', $code);

            /*
             * Break up the code parts
             */
            $parts = explode('!', $code);
            if (count($parts) != 2) {
                throw new ValidationException(['code' => Lang::get('void.comic::lang.account.invalid_activation_code')]);
            }

            list($comicId, $code) = $parts;

            if (!strlen(trim($comicId)) || !($comic = Auth::findComicById($comicId))) {
                throw new ApplicationException(Lang::get('void.comic::lang.account.invalid_comic'));
            }

            if (!$comic->attemptActivation($code)) {
                throw new ValidationException(['code' => Lang::get('void.comic::lang.account.invalid_activation_code')]);
            }

            Flash::success(Lang::get('void.comic::lang.account.success_activation'));

            /*
             * Sign in the comic
             */
            Auth::login($comic);

        }
        catch (Exception $ex) {
            if ($isAjax) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    /**
     * Update the comic
     */
    public function onUpdate()
    {
        if (!$comic = $this->comic())
            return;

        $comic->save(post());

        /*
         * Password has changed, reauthenticate the comic
         */
        if (strlen(post('password'))) {
            Auth::login($comic->reload(), true);
        }

        Flash::success(post('flash', Lang::get('void.comic::lang.account.success_saved')));

        /*
         * Redirect to the intended page after successful update
         */
        $redirectUrl = $this->pageUrl($this->property('redirect'));

        if ($redirectUrl = post('redirect', $redirectUrl))
            return Redirect::to($redirectUrl);
    }

    /**
     * Trigger a subsequent activation email
     */
    public function onSendActivationEmail($isAjax = true)
    {
        try {
            if (!$comic = $this->comic()) {
                throw new ApplicationException(Lang::get('void.comic::lang.account.login_first'));
            }

            if ($comic->is_activated) {
                throw new ApplicationException(Lang::get('void.comic::lang.account.alredy_active'));
            }

            Flash::success(Lang::get('void.comic::lang.account.activation_email_sent'));

            $this->sendActivationEmail($comic);

        }
        catch (Exception $ex) {
            if ($isAjax) throw $ex;
            else Flash::error($ex->getMessage());
        }

        /*
         * Redirect
         */
        $redirectUrl = $this->pageUrl($this->property('redirect'));

        if ($redirectUrl = post('redirect', $redirectUrl))
            return Redirect::to($redirectUrl);
    }

    /**
     * Sends the activation email to a comic
     * @param  Comic $comic
     * @return void
     */
    protected function sendActivationEmail($comic)
    {
        $code = implode('!', [$comic->id, $comic->getActivationCode()]);
        $link = $this->currentPageUrl([
            $this->property('paramCode') => $code
        ]);

        $data = [
            'name' => $comic->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('void.comic::mail.activate', $data, function($message) use ($comic)
        {
            $message->to($comic->email, $comic->name);
        });
    }

}
