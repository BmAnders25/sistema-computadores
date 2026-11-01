<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BootstrapInfoBox extends Component
{
    public $title;
    public $text;
    public $icon;
    public $theme;
    /**
     * Create a new component instance.
     */
    public function __construct($title, $text, $icon = null, $theme = 'info')
    {
        $this->title = $title;
        $this->text = $text;
        $this->icon = $icon;
        $this->theme = $theme;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.bootstrap-info-box');
    }
}
