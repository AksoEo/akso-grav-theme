<?php

namespace Grav\Plugin\AksoMdExt;

use Grav\Common\Markdown\Parsedown;

class MdFigure {
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('[', 'Figure', true, true);
        $markdown->blockFigure = function($line, $block) {
            if (preg_match('/^\[\[figuro(\s+!)?\]\]/', $line['text'], $matches)) {
                $fullWidth = isset($matches[1]);

                return array(
                    'char' => $line['text'][0],
                    'element' => array(
                        'name' => 'figure',
                        'attributes' => array(
                            'class' => ($fullWidth ? 'full-width' : ''),
                        ),
                        // parse markdown inside
                        'handler' => 'blockFigureContents',
                        // line handler needs a string
                        'text' => '',
                    ),
                );
            }
        };
        $markdown->blockFigureContinue = function($line, $block) {
            if (isset($block['complete'])) {
                return;
            }

            // A blank newline has occurred.
            if (isset($block['interrupted'])) {
                return;
            }

            // Check for end of the block.
            if (preg_match('/\[\[\/figuro\]\]/', $line['text'])) {
                $block['complete'] = true;
                return $block;
            }

            $block['element']['text'] .= $line['body'];

            return $block;
        };
        $markdown->blockFigureComplete = function($block) {
            return $block;
        };

        // copy-pasted from Parsedown source (line) and modified
        $markdown->blockFigureContents = \Closure::bind(function($text, $nonNestables = array()) {
            $markup = '';
            $inCaption = false;

            # $excerpt is based on the first occurrence of a marker
            while ($excerpt = strpbrk($text, $this->inlineMarkerList)) {
                $marker = $excerpt[0];
                $markerPosition = strpos($text, $marker);
                $Excerpt = array('text' => $excerpt, 'context' => $text);

                foreach ($this->InlineTypes[$marker] as $inlineType) {
                    # check to see if the current inline type is nestable in the current context
                    if (!empty($nonNestables) and in_array($inlineType, $nonNestables)) {
                        continue;
                    }
                    $Inline = $this->{'inline'.$inlineType}($Excerpt);
                    if (!isset($Inline)) {
                        continue;
                    }

                    # makes sure that the inline belongs to "our" marker
                    if (isset($Inline['position']) and $Inline['position'] > $markerPosition) {
                        continue;
                    }
                    # sets a default inline position
                    if (!isset($Inline['position'])) {
                        $Inline['position'] = $markerPosition;
                    }
                    # cause the new element to 'inherit' our non nestables
                    foreach ($nonNestables as $non_nestable) {
                        $Inline['element']['nonNestables'][] = $non_nestable;
                    }
                    # the text that comes before the inline
                    $unmarkedText = substr($text, 0, $Inline['position']);
                    $unmarkedText = $this->unmarkedText($unmarkedText);

                    if ($unmarkedText != '') {
                        if (!$inCaption) {
                            $markup .= '<figcaption>';
                            $inCaption = true;
                        }

                        # compile the unmarked text
                        $markup .= $unmarkedText;
                    }

                    $element = $Inline['element'];
                    if (isset($element) and isset($element['name']) and $element['name'] === 'img') {
                        if ($inCaption) {
                            $markup .= '</figcaption>';
                            $inCaption = false;
                        }
                    } else if (!$inCaption) {
                        $markup .= '<figcaption>';
                        $inCaption = true;
                    }

                    # compile the inline
                    $markup .= isset($Inline['markup']) ? $Inline['markup'] : $this->element($Inline['element']);
                    # remove the examined text
                    $text = substr($text, $Inline['position'] + $Inline['extent']);
                    continue 2;
                }

                # the marker does not belong to an inline
                $unmarkedText = substr($text, 0, $markerPosition + 1);
                $unmarkedText = $this->unmarkedText($unmarkedText);
                if ($unmarkedText != '') {
                    if (!$inCaption) {
                        $markup .= '<figcaption>';
                        $inCaption = true;
                    }
                    $markup .= $unmarkedText;
                }
                $text = substr($text, $markerPosition + 1);
            }

            $unmarkedText = $this->unmarkedText($text);
            if ($unmarkedText != '') {
                if (!$inCaption) {
                    $markup .= '<figcaption>';
                    $inCaption = true;
                }
                $markup .= $unmarkedText;
            }
            if ($inCaption) {
                $markup .= '</figcaption>';
            }

            return $markup;
        }, $markdown, $markdown);
    }
}
