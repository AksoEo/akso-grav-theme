<?php

namespace Grav\Plugin\AksoMdExt;

use DiDom\Element;
use Grav\Common\Grav;
use Grav\Common\Markdown\Parsedown;

class MdCarousel {
    public static function register(Parsedown $markdown) {
        $markdown->addBlockType('[', 'Carousel', true, true);
        $markdown->blockCarousel = function($line, $block) {
            if (preg_match('/^\[\[bildkaruselo]]/', $line['text'], $matches)) {
                return array(
                    'char' => $line['text'][0],
                    'element' => array(
                        'name' => 'figure',
                        'attributes' => array(
                            'class' => 'full-width carousel',
                        ),
                        'handler' => 'lines',
                        'text' => array(),
                    ),
                );
            }
        };
        $markdown->blockCarouselContinue = function($line, $block) {
            if (isset($block['complete'])) {
                return;
            }

            // A blank newline has occurred.
            if (isset($block['interrupted'])) {
                $block['element']['text'][] = "\n";
                unset($block['interrupted']);
            }

            // Check for end of the block.
            if (preg_match('/\[\[\/bildkaruselo]]/', $line['text'])) {
                $block['complete'] = true;
                return $block;
            }

            $block['element']['text'][] = $line['body'];

            return $block;
        };
        $markdown->blockCarouselComplete = function($block) {
            return $block;
        };
    }

    public static function renderInDocument($doc) {
        $language = Grav::instance()['language'];

        $carouselIdCounter = 0;
        $carousels = $doc->find('figure.carousel');
        $didPassImg = false;
        foreach ($carousels as $carousel) {
            $topLevelChildren = $carousel->children();
            $pages = array();
            $currentCaption = array();
            foreach ($topLevelChildren as $tlChild) {
                $tlChild->remove();

                if ($tlChild->isElementNode() && $tlChild->tag === 'p') {
                    $pChildren = $tlChild->children();
                    $newPChildren = array();
                    foreach ($pChildren as $pChild) {
                        $link = null;
                        $imageNode = null;
                        if ($pChild->isElementNode() && $pChild->tag === 'img') {
                            $imageNode = $pChild;
                        } else if ($pChild->isElementNode() && $pChild->tag === 'a') {
                            $ch = $pChild->children();
                            $isValid = true;
                            foreach ($ch as $child) {
                                if ($child->isElementNode() && $child->tag !== 'img') {
                                    $isValid = false;
                                    break;
                                } else if ($child->isTextNode() && !empty(chop($child->text()))) {
                                    $isValid = false;
                                    break;
                                }
                            }
                            if ($isValid) {
                                foreach ($ch as $child) {
                                    if ($child->isElementNode() && $child->tag === 'img') {
                                        $link = $pChild->getAttribute('href');
                                        if (!$link) $link = '';
                                        $imageNode = $child;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($imageNode) {
                            // split here
                            if (count($newPChildren) > 0) {
                                $newP = new Element('p');
                                foreach ($newPChildren as $npc) {
                                    $newP->appendChild($npc);
                                }
                                $currentCaption[] = $newP;
                                $newPChildren = array();
                            }
                            if (count($currentCaption) > 0 && $didPassImg) {
                                $newCaption = &$pages[count($pages) - 1]['caption'];
                                foreach ($currentCaption as $ccc) {
                                    $newCaption->appendChild($ccc);
                                }
                                $currentCaption = array();
                            }

                            $pages[] = array(
                                'img' => $imageNode,
                                'link' => $link,
                                'caption' => new Element('figcaption')
                            );
                            $didPassImg = true;
                        } else {
                            $newPChildren[] = $pChild;
                        }
                    }

                    // flush rest
                    if (count($newPChildren) > 0) {
                        $newP = new Element('p');
                        foreach ($newPChildren as $npc) {
                            $newP->appendChild($npc);
                        }
                        $currentCaption[] = $newP;
                        $newPChildren[] = array();
                    }
                } else {
                    $currentCaption[] = $tlChild;
                }
            }

            // flush rest
            if (count($currentCaption) > 0 && $didPassImg) {
                $newCaption = &$pages[count($pages) - 1]['caption'];
                foreach ($currentCaption as $ccc) {
                    $newCaption->appendChild($ccc);
                }
                $currentCaption = array();
            }

            $carouselId = 'figure-carousel-pages-' . $carouselIdCounter;
            $carouselIdCounter++;

            $pagesContainer = new Element('div');
            $pagesContainer->class = 'carousel-pages';
            $isFirst = true;
            $i = 0;
            foreach ($pages as $ntlChild) {
                $pageContainer = null;
                if ($ntlChild['link']) {
                    $pageContainer = new Element('a');
                    $link = $ntlChild['link'];
                    $pageContainer->href = "$link";
                } else {
                    $pageContainer = new Element('div');
                }
                $pageContainer->class = 'carousel-page';
                $pageContainer->appendChild($ntlChild['img']);
                $pageContainer->appendChild($ntlChild['caption']);
                if (trim($ntlChild['caption']->text())) {
                    $pageContainer->class .= ' page-has-caption';
                }

                $pageLabel = $language->translate(['THEME_AKSO.content.img_carousel_page_label', ($i + 1), count($pages)]);
                $pageContainer->setAttribute('data-label', $pageLabel);

                $pagesContainer->appendChild($pageContainer);

                $radio = new Element('input');
                $radio->class = 'carousel-page-button';
                $radio->type = 'radio';
                $radio->name = $carouselId;
                $radio->setAttribute('aria-label', $pageLabel);
                if ($isFirst) {
                    $isFirst = false;
                    $radio->checked = '';
                }
                $carousel->appendChild($radio);
                $i++;
            }
            $carousel->appendChild($pagesContainer);
            if (sizeof($pages) === 1) {
                $carousel->class .= ' is-single-page';
            }
            $carousel->setAttribute('data-pagination-label', $language->translate('THEME_AKSO.content.img_carousel_pagination'));
        }
    }
}
