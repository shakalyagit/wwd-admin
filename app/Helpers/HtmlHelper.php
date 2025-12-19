<?php

function no_record_found_in_table()
{
    $output = '';
    $output = '
            <p class="text-center text-300 p-4 no_record_found"> No record found! </p>
        ';
    return $output;
}