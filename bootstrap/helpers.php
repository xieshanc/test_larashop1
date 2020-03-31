<?php

function hehe()
{
    return '你看你🐴呢';
}

function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}
