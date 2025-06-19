<?php
if (!function_exists('dd')) {
    function dd(...$vars) {
        foreach ($vars as $var) {
            echo "<pre>";
            print_r($var);
            echo "</pre>";
        }
        die(1);
    }
} 