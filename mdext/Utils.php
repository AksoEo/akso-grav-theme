<?php

namespace Grav\Plugin\AksoMdExt;
use Cocur\Slugify\Slugify;
use DiDom\Element;

class Utils {
    static function formatDate($dateString) {
        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        if (!$date) return null;
        $formatted = $date->format('j') . '-a de ' . Utils::formatMonth($date->format('m')) . ' ' . $date->format('Y');
        return $formatted;
    }

    static function formatMonth($number) {
        $months = [
            '???',
            'januaro',
            'februaro',
            'marto',
            'aprilo',
            'majo',
            'junio',
            'julio',
            'aŭgusto',
            'septembro',
            'oktobro',
            'novembro',
            'decembro',
        ];
        return $months[(int) $number];
    }

    static function latinizeEsperanto($s, $useH) {
        $latinizeEsperanto = function ($k) {
            switch ($k) {
                case 'Ĥ': return 'H';
                case 'Ŝ': return 'S';
                case 'Ĝ': return 'G';
                case 'Ĉ': return 'C';
                case 'Ĵ': return 'J';
                case 'Ŭ': return 'U';
                case 'ĥ': return 'h';
                case 'ŝ': return 's';
                case 'ĝ': return 'g';
                case 'ĉ': return 'c';
                case 'ĵ': return 'j';
                case 'ŭ': return 'u';
            }
            return $k;
        };

        $replaceHUpper = function (array $matches) use ($latinizeEsperanto, $useH) {
            return $latinizeEsperanto($matches[1]) . ($useH ? 'H' : '');
        };
        $replaceH = function (array $matches) use ($latinizeEsperanto, $useH) {
            return $latinizeEsperanto($matches[1]) . ($useH ? 'h' : '');
        };

        $s = preg_replace_callback('/([ĤŜĜĈĴŬ])(?=[A-ZĤŜĜĈĴŬ])/u', $replaceHUpper, $s);
        $s = preg_replace_callback('/([ĥŝĝĉĵŭ])/ui', $replaceH, $s);

        return $s;
    }

    static function escapeFileNameLossy($name) {
        $s = \Normalizer::normalize($name);
        $s = self::latinizeEsperanto($s, true);
        $slugify = new Slugify(['lowercase' => false]);
        return $slugify->slugify($s);
    }

    static function obfuscateEmail($email) {
        $emailLink = new Element('a');
        $emailLink->class = 'non-interactive-address';
        $emailLink->href = 'javascript:void(0)';

        // obfuscated email
        $parts = preg_split('/(?=[@.])/', $email);
        for ($i = 0; $i < count($parts); $i++) {
            $text = $parts[$i];
            $after = '';
            $delim = '';
            if ($i !== 0) {
                // split off delimiter
                $delim = substr($text, 0, 1);
                $mid = ceil(strlen($text) * 2 / 3);
                $after = substr($text, $mid);
                $text = substr($text, 1, $mid - 1);
            } else {
                $mid = ceil(strlen($text) * 2 / 3);
                $after = substr($text, $mid);
                $text = substr($text, 0, $mid);
            }
            $emailPart = new Element('span', $text);
            $emailPart->class = 'epart';
            $emailPart->setAttribute('data-at-sign', '@');
            $emailPart->setAttribute('data-after', $after);

            if ($delim === '@') {
                $emailPart->setAttribute('data-show-at', 'true');
            } else if ($delim === '.') {
                $emailPart->setAttribute('data-show-dot', 'true');
            }

            $emailLink->appendChild($emailPart);
        }

        $invisible = new Element('span', ' (uzu retumilon kun CSS por vidi retpoŝtadreson)');
        $invisible->class = 'fpart';
        $emailLink->appendChild($invisible);

        return $emailLink;
    }
}
