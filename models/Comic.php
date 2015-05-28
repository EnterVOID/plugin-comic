<?php namespace Void\Comic\Models;

use Model;

class Comic extends Model {

    protected $table = 'comics';

    // public $belongsTo = [
    //     'match' => ['Void\Match\Models\Match'],
    // ];

    public $morphMany = [
        'creators' => ['Void\Creator\Models\Creator', 'type' => 'creation'],
    ];

    // public $attachOne = [
    //     'cover_image' => ['System\Models\File'],
    // ];

    public $attachMany = [
        'pages' => ['System\Models\File'],
    ];

    /**
     * @var array The attributes that are mass assignable.
     */
    protected $fillable = [
        'match_id',
        'winner',
        'is_accepted',
        'accepted_at',
        'is_extended',
        'extended_at',
        'is_complete',
        'completed_at',
        'is_published',
        'published_at',
    ];

    /**
     * Returns the public image file path to this user's avatar.
     */
    public function getAvatarThumb($size = 25, $options = null)
    {
        if (is_string($options)) {
            $options = ['default' => $options];
        }
        elseif (!is_array($options)) {
            $options = [];
        }

        // Default is "mm" (Mystery man)
        $default = array_get($options, 'default', 'mm');

        if ($this->avatar) {
            return $this->avatar->getThumb($size, $size, $options);
        }
        else {
            return '//www.gravatar.com/avatar/' .
                md5(strtolower(trim($this->email))) .
                '?s='. $size .
                '&d='. urlencode($default);
        }
    }

    /**
     * Sends the confirmation email to a user, after activating
     * @param  string $code
     * @return void
     */
    public function attemptActivation($code)
    {
        $result = parent::attemptActivation($code);
        if ($result === false) {
            return false;
        }

        if ($mailTemplate = UserSettings::get('welcome_template')) {
            $data = [
                'name' => $this->name,
                'email' => $this->email
            ];

            Mail::send($mailTemplate, $data, function($message) {
                $message->to($this->email, $this->name);
            });
        }

        return true;
    }
}
