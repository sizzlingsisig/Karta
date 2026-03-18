<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppBrand extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <a href="/" wire:navigate class="group">
                    <div {{ $attributes->class(["hidden-when-collapsed", "hidden", "group-hover:block"]) }}>
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('assets/images/CleanLogo.svg') }}" alt="Karta logo" class="h-7 w-7 shrink-0 rounded" />

                            <div>
                                <p class="font-bold uppercase tracking-[0.18em] text-(--karta-text-strong)">Karta</p>
                                <p class="text-gold-xs text-(--karta-text-muted)">golden system</p>
                            </div>
                        </div>
                    </div>

                    <div class="display-when-collapsed block group-hover:hidden mt-5 mb-1 h-7 w-7">
                        <img src="{{ asset('assets/images/CleanLogo.svg') }}" alt="Karta logo" class="h-7 w-7 rounded" />
                    </div>
                </a>
            HTML;
    }
}
