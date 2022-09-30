<?php

namespace Grav\Plugin\AksoMdExt;

use Grav\Common\Markdown\Parsedown;

class MdMultiCol {
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('[', 'MultiCol', true, true);
        $markdown->blockMultiCol = function($line, $block) {
            if (preg_match('/^\[\[kolumnoj\]\]/', $line['text'], $matches)) {
                return array(
                    'char' => $line['text'][0],
                    'element' => array(
                        'name' => 'div',
                        'attributes' => array(
                            'class' => 'content-multicols',
                        ),
                        'handler' => 'elements',
                        'text' => [['']],
                    ),
                );
            }
        };
        $markdown->blockMultiColContinue = function($line, $block) {
            if (isset($block['complete'])) return;

            $lastIndex = count($block['element']['text']) - 1;

            // A blank newline has occurred.
            if (isset($block['interrupted'])) {
                $block['element']['text'][$lastIndex][] = "\n";
                unset($block['interrupted']);
            }

            if (preg_match('/^===$/', $line['text'], $matches)) {
                // column break
                $block['element']['text'][] = [''];
                return $block;
            } else if (preg_match('/^\[\[\/kolumnoj\]\]$/', $line['text'])) {
                // end of the block
                $block['complete'] = true;
                return $block;
            }

            $block['element']['text'][$lastIndex][] = $line['body'];

            return $block;
        };
        $markdown->blockMultiColComplete = function($block) {
            $block['element']['attributes']['data-columns'] = count($block['element']['text']);
            $els = [];
            foreach ($block['element']['text'] as $lines) {
                if (count($els)) {
                    $els[] = array(
                        'name' => 'hr',
                        'attributes' => array(
                            'class' => 'multicol-column-break',
                        ),
                        'text' => '',
                    );
                }
                $els[] = array(
                    'name' => 'div',
                    'attributes' => array(
                        'class' => 'multicol-column',
                    ),
                    'handler' => 'lines',
                    'text' => $lines,
                );
            }
            $block['element']['text'] = $els;
            return $block;
        };
    }
}
