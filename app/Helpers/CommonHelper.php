<?php

if (!function_exists('status_badge')) {
    function status_badge($status)
    {
        switch ($status) {
            case 1:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-success border border-success';
                $text = 'Verified';
                break;
            case 0:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-warning border border-warning';
                $text = 'Pending';
                break;
            case 2:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-danger border border-danger';
                $text = 'Reject';
                break;
            default:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-dark border border-dark';
                $text = 'N/A';
                break;
        }
        return '<span class="' . $class . '">' . $text . '</span>';
    }
}
