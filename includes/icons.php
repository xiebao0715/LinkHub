<?php

function icons_stylesheet(): string
{
    return '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">';
}

function icon(string $name, string $extraClass = ''): string
{
    $classes = trim('fa-solid fa-' . $name . ($extraClass ? ' ' . $extraClass : ''));
    return '<i class="' . e($classes) . '"></i>';
}
