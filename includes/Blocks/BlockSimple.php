<?php
namespace App\Blocks;

use App\Auth;
use App\Template;
use Exception;

abstract class BlockSimple extends Block
{
    protected $template = null;

    public function __construct()
    {
        if (!isset($this->template)) {
            throw new Exception(
                'Class ' .
                    get_class($this) .
                    ' has to have field $template because it extends class BlockSimple'
            );
        }
    }

    protected function content($get, $post)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = app()->make(Template::class);

        return $template->render($this->template, compact('auth', 'user'));
    }
}