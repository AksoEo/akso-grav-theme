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
                        'handler' => 'lines',
                        'text' => array(
                            'current' => 'eo',
                            'variants' => array('eo' => []),
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
                $block['element']['text']['variants'][$current][] = "\n";
                unset($block['interrupted']);
            }

            if (preg_match('/\[\[(\w{2})]]/', $line['text'], $matches)) {
                // language code
                $current = $matches[1];
                $block['element']['text']['current'] = $current;
                $block['element']['text']['variants'][$current] = [];
                return $block;
            } else if (preg_match('/\[\[\/intro]]/', $line['text'])) {
                // end of the block
                $block['complete'] = true;
                return $block;
            }

            $block['element']['text']['variants'][$current][] = $line['body'];

            return $block;
        };
        $markdown->blockTrIntroComplete = function($block) {
            $preferredLang = substr(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '', 0, 2);
            /*if ($self->plugin->aksoUser) {
                $preferredLang = 'eo';
            }*/
            if (isset($_GET['lang']) && gettype($_GET['lang']) === 'string') {
                $preferredLang = substr($_GET['lang'], 0, 2);
            }
            if (!isset($block['element']['text']['variants'][$preferredLang])) {
                $preferredLang = 'eo';
            }
            $block['element']['text'] = $block['element']['text']['variants'][$preferredLang];
            return $block;
        };
    }
}
