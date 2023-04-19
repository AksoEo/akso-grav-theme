<?php

namespace Grav\Plugin\AksoMdExt;

use Grav\Common\Markdown\Parsedown;

class MdTrIntro {
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('[', 'TrIntro', true, true);
        $markdown->blockTrIntro = function($line, $block) {
            if (preg_match('/^\[\[intro]]/', $line['text'], $matches)) {
                return array(
                    'char' => $line['text'][0],
                    'element' => array(
                        'name' => 'div',
                        'attributes' => array(
                            'class' => 'intro-text',
                        ),
                        'handler' => 'elements',
                        'text' => array(
                            'current' => 'eo',
                            'variants' => array('eo' => ['name' => 'Esperanto', 'code' => 'eo', 'contents' => []]),
                        ),
                    ),
                );
            }
        };
        $markdown->blockTrIntroContinue = function($line, $block) {
            if (isset($block['complete'])) {
                return;
            }

            $current = $block['element']['text']['current'];

            // A blank newline has occurred.
            if (isset($block['interrupted'])) {
                $block['element']['text']['variants'][$current]['contents'][] = "\n";
                unset($block['interrupted']);
            }

            if (preg_match('/\[\[(\w{2})\s+(.+?)]]/', $line['text'], $matches)) {
                // language code
                $current = $matches[1];
                $name = $matches[2];
                $block['element']['text']['current'] = $current;
                $block['element']['text']['variants'][$current] = array(
                    'code' => $current,
                    'name' => $name,
                    'contents' => [],
                );
                return $block;
            } else if (preg_match('/\[\[\/intro]]/', $line['text'])) {
                // end of the block
                $block['complete'] = true;
                return $block;
            }

            $block['element']['text']['variants'][$current]['contents'][] = $line['body'];

            return $block;
        };
        $markdown->blockTrIntroComplete = function($block) {
            $preferredLang = substr(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '', 0, 2);

            if (isset($_SESSION['akso_is_probably_logged_in']) && $_SESSION['akso_is_probably_logged_in']) {
                // probably logged in
                $preferredLang = 'eo';
            }

            if (isset($_GET['lang']) && gettype($_GET['lang']) === 'string') {
                $preferredLang = substr($_GET['lang'], 0, 2);
            }
            $variants = $block['element']['text']['variants'];
            if (!isset($variants[$preferredLang])) {
                $preferredLang = 'eo';
            }
            $contents = $variants[$preferredLang]['contents'];

            $options = [];
            usort($variants, function ($a, $b) {
                return $a['name'] <=> $b['name'];
            });
            foreach ($variants as $variant) {
                $attrs = array(
                    'value' => $variant['code'],
                );
                if ($preferredLang === $variant['code']) $attrs['selected'] = 'true';
                $options[] = array(
                    'name' => 'option',
                    'attributes' => $attrs,
                    'handler' => 'line',
                    'text' => $variant['name'],
                );
            }

            $children = [
                array(
                    'name' => 'form',
                    'attributes' => array(
                        'method' => 'GET',
                        'class' => 'intro-text-translation-box',
                        'data-select-auto-form' => 'lang',
                    ),
                    'handler' => 'elements',
                    'text' => [
                        array(
                            'name' => 'select',
                            'attributes' => array('name' => 'lang'),
                            'handler' => 'elements',
                            'text' => $options,
                        ),
                        array(
                            'name' => 'button',
                            'attributes' => array('type' => 'submit'),
                            'handler' => 'line',
                            'text' => '✓',
                        ),
                    ],
                ),
                array(
                    'name' => 'div',
                    'handler' => 'lines',
                    'attributes' => array('lang' => $preferredLang),
                    'text' => $contents,
                ),
            ];

            $block['element']['text'] = $children;
            return $block;
        };
    }
}
