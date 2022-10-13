<?php

namespace Grav\Plugin\AksoMdExt;

use Grav\Common\Markdown\Parsedown;

class MdTable {
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('[', 'AksoTable', true, true);
        $markdown->blockAksoTable = function($line, $block) {
            if (preg_match('/^\[\[tabelo]]/', $line['text'], $matches)) {
                return array(
                    'char' => $line['text'][0],
                    'element' => array(
                        'name' => 'table',
                        'attributes' => array(
                            'class' => 'content-mdx-table',
                        ),
                        'handler' => 'elements',
                        'text' => [[]],
                    ),
                );
            }
        };
        $markdown->blockAksoTableContinue = function($line, $block) {
            if (isset($block['complete'])) {
                return;
            }
            if (isset($block['interrupted'])) {
                // we don't care about blank lines
                unset($block['interrupted']);
            }

            if (preg_match('/^\[\[\/tabelo]]$/', $line['text'])) {
                // end of the block
                $block['complete'] = true;
                return $block;
            }

            $block['element']['text'][] = preg_split('/\|/',
                preg_replace('/^\s*\|/', '',
                    preg_replace('/\|\s*$/', '', $line['body'])));

            return $block;
        };
        $markdown->blockAksoTableComplete = function($block) {
            $columnCount = 0;
            foreach ($block['element']['text'] as $row) {
                $columnCount = max($columnCount, count($row));
            }
            $rows = [];
            foreach ($block['element']['text'] as $row) {
                $cells = [];
                foreach ($row as $cell) {
                    $cells[] = array(
                        'name' => 'td',
                        'handler' => 'line',
                        'text' => $cell,
                    );
                }
                $rows[] = array(
                    'name' => 'tr',
                    'handler' => 'elements',
                    'text' => $cells,
                );
            }
            $block['element']['text'] = [array(
                'name' => 'tbody',
                'handler' => 'elements',
                'text' => $rows,
            )];
            return $block;
        };
    }
}
