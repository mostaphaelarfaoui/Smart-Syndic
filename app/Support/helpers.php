<?php

use App\Models\Apartment;

if (! function_exists('clean_unit_number')) {
    /**
     * Clean unit number by stripping any redundant "شقة", "شقه", "الشقة", "Appartement" prefixes.
     */
    function clean_unit_number(?string $unit): string
    {
        return Apartment::cleanUnit($unit);
    }
}

if (! function_exists('format_unit_label')) {
    /**
     * Format unit nicely with a single "شقة " prefix (e.g. "شقة 1").
     */
    function format_unit_label(?string $unit): string
    {
        return Apartment::formatUnit($unit);
    }
}

if (! function_exists('format_definite_unit')) {
    /**
     * Format unit nicely with a definite "الشقة " prefix (e.g. "الشقة 1").
     */
    function format_definite_unit(?string $unit): string
    {
        return Apartment::formatDefiniteUnit($unit);
    }
}
