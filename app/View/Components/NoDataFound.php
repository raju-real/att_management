<?php

namespace App\View\Components;

use Illuminate\View\Component;

class NoDataFound extends Component
{
    public function render()
    {
        return view('components.no-data-found');
    }
}
