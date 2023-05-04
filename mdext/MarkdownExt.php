<?php
namespace Grav\Plugin\AksoMdExt;

use DiDom\Document;
use DiDom\Element;
use Grav\Common\Grav;
use Grav\Common\Markdown\Parsedown;

class MarkdownExt {
    public function register(Parsedown $markdown) {
        MdBigButton::register($markdown);
        MdBoxes::register($markdown);
        MdCarousel::register($markdown);
        MdFigure::register($markdown);
        MdMultiCol::register($markdown);
        MdNewsSidebar::register($markdown);
        MdSectionMarker::register($markdown);
        MdTable::register($markdown);
        MdTrIntro::register($markdown);
    }

    public function renderHTML() {
        $grav = Grav::instance();
        $content = $grav->output;

        $language = $grav['language'];

        if (empty($content)) return;

        $document = new Document($content);
        $mains = $document->find('main#main-content');
        if (count($mains) === 0) return $content;

        $breadcrumbs = $document->find('.breadcrumbs-container');
        if (count($breadcrumbs) > 0) {
            // fix html a bit
            $breadcrumbs[0]->setAttribute('role', 'navigation');
            $breadcrumbs[0]->setAttribute('aria-label', $language->translate('THEME_AKSO.content.breadcrumbs_title'));
            $document->first('.breadcrumbs-container [itemtype="http://schema.org/BreadcrumbList"]')->setAttribute('role', 'list');
            $itemCount = 0;
            foreach ($document->find('.breadcrumbs-container [itemtype="http://schema.org/Thing"]') as $li) {
                $li->setAttribute('role', 'listitem');
                $itemCount += 1;
            }
            // last item is always a span
            $document->first('.breadcrumbs-container span[itemtype="http://schema.org/Thing"]')->setAttribute('aria-current', 'page');
            $breadcrumbs[0]->remove();
            if ($itemCount == 1) {
                // only one item; probably "home". we dont need breadcrumbs for this
                $breadcrumbs = [];
            }
        }

        // collect top level children into sections
        $rootNode = $mains[0];
        $topLevelChildren = $rootNode->children();
        $sections = array();
        $sidebarNews = null;
        foreach ($topLevelChildren as $child) {
            if ($child->isElementNode() and $child->class === 'news-sidebar') {
                $sidebarNews = $child;
            } else if ($child->isElementNode() and $child->tag === 'figure' and strpos($child->class, 'full-width') !== false) {
                // full-width figure!
                $sections[] = array(
                    'kind' => 'figure',
                    'contents' => $child,
                );
            } else if ($child->isTextNode() and trim($child->text()) === '') {
                continue;
            } else if ($child->isElementNode() && $child->tag === 'h3' && strpos($child->class, 'section-marker') !== false) {
                $sections[] = array(
                    'kind' => 'section',
                    'contents' => [$child],
                );
            } else {
                $isSectionEndingNode = $child->isElementNode() && in_array($child->tag, ['h1', 'h2']);
                $lastSection = count($sections) ? $sections[count($sections) - 1] : null;
                $lastSectionIsSection = $lastSection ? $lastSection['kind'] === 'section' : false;
                $lastSectionCanReceiveNode = $lastSection
                    ? ($lastSectionIsSection ? !$isSectionEndingNode : false)
                    : false;

                if (!$lastSectionCanReceiveNode) {
                    $sections[] = array(
                        'kind' => 'normal',
                        'contents' => array(),
                    );
                }
                $sections[count($sections) - 1]['contents'][] = $child;
            }
            $child->remove();
        }

        // first item is a full-width figure; split here

        $newRootNode = new Element('main');
        $newRootNode->class = 'page-container';
        $contentSplitNode = new Element('div');
        $contentSplitNode->class = 'page-split';
        $contentRootNode = new Element('div');
        $contentRootNode->class = 'page-contents';

        $firstIsBigFigure = (count($sections) > 0) && ($sections[0]['kind'] === 'figure');
        if ($firstIsBigFigure) {
            $fig = $sections[0]['contents'];
            $fig->class .= ' is-top-figure';
            array_splice($sections, 0, 1);
            $newRootNode->appendChild($fig);
        }

        // move sidebar nav
        $navSidebar = $document->find('#nav-sidebar');
        if (count($navSidebar) > 0 || $sidebarNews !== null) {
            if ($sidebarNews !== null) {
                $contentSplitNode->appendChild($sidebarNews);
            }
            if (count($navSidebar) > 0) {
                $navSidebar = $navSidebar[0];
                $navSidebar->remove();
                $contentSplitNode->appendChild($navSidebar);
            }
        } else {
            $newRootNode->class .= ' is-not-split';
        }

        $isFirstContainer = true;
        $didAddBreadcrumbs = false;
        $currentContainer = null;
        $flushContainer = function()
        use
        (&$currentContainer, &$contentRootNode, &$contentSplitNode, &$isFirstContainer)
        {
            if (!$currentContainer) return;
            if ($isFirstContainer) {
                $contentSplitNode->appendChild($currentContainer);
            } else {
                $containerContainerNode = new Element('div');
                $containerContainerNode->class = 'content-split-container';
                $layoutSpacer = new Element('div');
                $layoutSpacer->class = 'layout-spacer';
                $containerContainerNode->appendChild($layoutSpacer);
                $containerContainerNode->appendChild($currentContainer);
                $contentRootNode->appendChild($containerContainerNode);
            }
            $currentContainer = null;
            $isFirstContainer = false;
        };

        foreach ($sections as $section) {
            if ($section['kind'] === 'figure') {
                $flushContainer();
                $contentRootNode->appendChild($section['contents']);
            } else {
                if (!$currentContainer) {
                    $currentContainer = new Element('div');
                    $currentContainer->class = 'content-container';
                }

                $sectionNode = new Element('section');
                $sectionNode->class = 'md-container';

                if (!$didAddBreadcrumbs) {
                    // add breadcrumbs to first section
                    $didAddBreadcrumbs = true;
                    if (count($breadcrumbs) > 0) {
                        $currentContainer->appendChild($breadcrumbs[0]);
                    }
                }

                foreach ($section['contents'] as $contentNode) {
                    $sectionNode->appendChild($contentNode);
                }

                $currentContainer->appendChild($sectionNode);
            }
        }
        $flushContainer();

        $newRootNode->appendChild($contentSplitNode);
        $newRootNode->appendChild($contentRootNode);
        $rootNode->replace($newRootNode);

        $this->renderHTMLComponents($document);

        $html = $document->html();
        // remove html/body tags
        $html = preg_replace('#<html><body>#', '', $html);
        $html = preg_replace('#</body></html>#', '', $html);
        $grav->output = trim($html);
    }

    function renderHTMLComponents(Document $document) {
        MdBoxes::renderInDocument($document);
        MdCarousel::renderInDocument($document);
        MdSectionMarker::renderInDocument($document);
        MdNewsSidebar::renderInDocument($document);

        $this->obfuscateMailLinksInDocument($document);
    }

    private function obfuscateMailLinksInDocument($doc) {
        $anchors = $doc->find('a');
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if (!str_starts_with($href, 'mailto:')) {
                continue;
            }
            $href = substr($href, strlen('mailto:'));
            // split off ?query params
            $hrefParts = explode('?', $href, 2);
            $mailAddress = $hrefParts[0];
            $params = $hrefParts[1] ?? '';

            if (!str_contains($mailAddress, '@')) continue;
            $textContent = trim($anchor->text());
            if (mb_strtolower($textContent) !== mb_strtolower($mailAddress)) {
                // can't obfuscate with contents
                $addrParts = explode('@', $mailAddress, 2);
                if ($params) {
                    $anchor->setAttribute('data-params', $params);
                }
                $anchor->setAttribute('data-extra2', $addrParts[1]);
                $anchor->setAttribute('data-extra', $addrParts[0]);
                $anchor->setAttribute('href', 'javascript:void(0)');
                $anchor->setAttribute('class', ($anchor->getAttribute('class') ?: '') . ' non-interactive-address');
                continue;
            }

            $mailAnchor = Utils::obfuscateEmail($mailAddress);
            if ($params) {
                $mailAnchor->setAttribute('data-params', $params);
            }

            $anchor->replace($mailAnchor);
        }
    }
}
