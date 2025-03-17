<?php

namespace TN\TN_Core\Model\Theme;

abstract class Theme {

    public string $key;
    public string $inverseKey;

    public static function getTheme(): Theme {
        return new LightTheme();
    }
}