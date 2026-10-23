<?php

function modal_overlay(string $id, string $title, string $bodyHtml, string $size = ''): void
{
    $sizeMap = ['lg' => 'modal-lg', 'xl' => 'modal-xl'];
    $sizeClass = isset($sizeMap[$size]) ? ' ' . $sizeMap[$size] : '';
    echo '<div class="modal' . $sizeClass . '" id="' . e($id) . '" aria-hidden="true">';
    echo '<div class="modal-backdrop" data-modal-close></div>';
    echo '<div class="modal-dialog" role="dialog">';
    echo '<div class="modal-header"><h3>' . e($title) . '</h3>';
    echo '<button type="button" class="modal-close" data-modal-close aria-label="关闭">' . icon('xmark') . '</button></div>';
    echo '<div class="modal-body">' . $bodyHtml . '</div>';
    echo '</div></div>';
}
