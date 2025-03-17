<?php

namespace TN\TN_Core\Model\_Deprecated\Util;

/**
 * These are some static functions that handle sanization of quotes
 * 
 *  (just the class structure!)
 * @deprecated
 */
class Quotes
{
    /**
     * this cleans horrible ugly curly quotes from a string. Presumably these come about from MS Word copy+paste
     * @param string $inputString
     * @return string
     */
    public static function cleanCurlyQuotes(string $inputString): string
    {
        /*$ln = strlen($inputString);
        for ($i = 0; $i < $ln; $i += 1) {
            echo $inputString[$i] . ' / ' . ord($inputString[$i]) . "\n";
        }
        exit;� / 195
� / 162
� / 226
� / 130
� / 172
� / 226
� / 132
� / 162*/
        $pdoQuote = chr(195) . chr(162) . chr(226) . chr(130) . chr(172) . chr(226) . chr(132) . chr(162);
        $inputString = str_replace($pdoQuote, '\'', $inputString);

        // First, replace UTF-8 characters.
        $inputString = str_replace(
            array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
            $inputString);
        // Next, replace their Windows-1252 equivalents.
        return str_replace(
            array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
            array("'", "'", '"', '"', '-', '--', '...'),
            $inputString);
    }

    public static function cleanAllQuotes(string $inputString): string
    {
        $str = self::cleanCurlyQuotes($inputString);
        return str_replace('&apos;', '\'', $str);
    }

}

?>