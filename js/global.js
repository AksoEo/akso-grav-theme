// General interactivity for this theme
// Language level ES5

// CSS media width for using the split page layout (also see CSS)
var cssmWidthSplitPage = 800;

// animates node[property] to the target value
function animateScrolling (node, property, target) {
    var easing = function (t) {
        return 1 - Math.pow(2, -10 * t);
    };
    var start = +node[property];
    var t = 0;
    var lastTime = Date.now();
    var loop = function () {
        var dt = Math.min(Math.max(0, (Date.now() - lastTime) / 1000), 1 / 20);
        lastTime = Date.now();
        t += dt * 3;
        node[property] = (target - start) * easing(t) + start;

        if (t < 1) {
            if (window.requestAnimationFrame) window.requestAnimationFrame(loop);
            else setTimeout(loop, 16);
        } else node[property] = target;
    };
    loop();
}

{
    // scrolling header
    var mainNav = document.querySelector('.main-nav');
    mainNav.classList.add('is-scrollable');
    var mainNavScrollable = document.querySelector('.main-nav > ul');
    var mainNavScrollLeft = document.createElement('button');
    mainNavScrollLeft.setAttribute('aria-hidden', 'true');
    var mainNavScrollRight = document.createElement('button');
    mainNavScrollRight.setAttribute('aria-hidden', 'true');
    mainNavScrollLeft.className = 'nav-scroll-button is-left at-edge';
    mainNavScrollRight.className = 'nav-scroll-button is-right at-edge';
    mainNavScrollLeft.setAttribute('aria-label', '←');
    mainNavScrollRight.setAttribute('aria-label', '→');

    function isCompactNav () {
        return mainNav.querySelector('.compact-nav-header').offsetHeight > 0;
    }

    // hides and shows header scrolling buttons
    function updateScrollingHeaderButtons () {
        if (mainNavScrollable.classList.contains('interactive-scrollable')) {
            var atLeftEdge = mainNavScrollable.scrollLeft <= 0;
            var atRightEdge = mainNavScrollable.scrollLeft >= mainNavScrollable.scrollWidth - mainNavScrollable.offsetWidth;
            if (atLeftEdge) mainNavScrollLeft.classList.add('at-edge');
            else mainNavScrollLeft.classList.remove('at-edge');
            if (atRightEdge) mainNavScrollRight.classList.add('at-edge');
            else mainNavScrollRight.classList.remove('at-edge');
        }
    }
    function updateScrollingHeader () {
        if (mainNavScrollable.scrollWidth > mainNavScrollable.offsetWidth) {
            // scrolling
            if (!mainNavScrollLeft.parentNode) {
                mainNavScrollable.classList.add('interactive-scrollable');
                mainNav.appendChild(mainNavScrollLeft);
                mainNav.appendChild(mainNavScrollRight);
            }

            var selected = mainNavScrollable.querySelector('.selected');
            if (selected) {
                var maxScroll = mainNavScrollable.scrollWidth - mainNavScrollable.offsetWidth;
                // center selected
                var targetScroll = selected.offsetLeft - (mainNavScrollable.offsetWidth - selected.offsetWidth) / 2;
                mainNavScrollable.scrollLeft = Math.max(0, Math.min(maxScroll, targetScroll));
            }
            updateScrollingHeaderButtons();
        } else {
            if (mainNavScrollLeft.parentNode) {
                mainNavScrollable.classList.remove('interactive-scrollable');
                mainNav.removeChild(mainNavScrollLeft);
                mainNav.removeChild(mainNavScrollRight);
            }
        }
    }
    mainNavScrollable.addEventListener('scroll', updateScrollingHeaderButtons, { passive: true });
    updateScrollingHeader();
    // sometimes it just does not apply properly due to fonts not being loaded or something (Safari 14)
    window.addEventListener('DOMContentLoaded', updateScrollingHeader);
    setTimeout(updateScrollingHeader, 100);
    setTimeout(updateScrollingHeader, 1000);
    window.addEventListener('resize', updateScrollingHeader);
    mainNavScrollLeft.addEventListener('click', function (e) {
        e.preventDefault(); // prevent double-tap to zoom
        var target = Math.max(0, mainNavScrollable.scrollLeft - 100);
        animateScrolling(mainNavScrollable, 'scrollLeft', target);
    });
    mainNavScrollRight.addEventListener('click', function (e) {
        e.preventDefault(); // prevent double-tap to zoom
        var max = mainNavScrollable.scrollWidth - mainNavScrollable.offsetWidth;
        var target = Math.min(max, mainNavScrollable.scrollLeft + 100);
        animateScrolling(mainNavScrollable, 'scrollLeft', target);
    });
}

{
    var itemsWithSubpages = mainNav.querySelectorAll('.has-subpages');
    for (var i = 0; i < itemsWithSubpages.length; i++) {
        (function (item) {
            var subpageExpandState = item.querySelector('.subpage-expand-state');
            var subpageExpand = item.querySelector('.subpage-expand');
            var mountedSubpagesNode = item.querySelector('ul');
            var subpagesNode = mountedSubpagesNode.cloneNode(true);
            mountedSubpagesNode.classList.add('is-mounted-dup');
            subpagesNode.classList.add('interactive-subpages');
            mainNav.appendChild(subpagesNode);

            var repositionSubpages = function () {
                var width = subpagesNode.offsetWidth;
                var posX = item.getBoundingClientRect().left - mainNav.getBoundingClientRect().left;
                if (posX < 0) posX = 0;
                if (posX + width > innerWidth) posX = innerWidth - width;
                subpagesNode.style.left = posX + 'px';
                subpagesNode.style.minWidth = item.offsetWidth + 'px';
            };

            var interactionTimeout = null;
            var isVisible = false;
            var showSubpages = function () {
                subpageExpandState.checked = true;
                if (isCompactNav()) return;
                clearTimeout(interactionTimeout);
                subpagesNode.classList.add('is-visible');
                subpagesNode.classList.add('is-interactive');
                isVisible = true;

                repositionSubpages();
                if (window.requestAnimationFrame) {
                    // reposition every frame in case the user is scrolling
                    var loop = function () {
                        repositionSubpages();
                        if (isVisible) window.requestAnimationFrame(loop);
                    };
                    window.requestAnimationFrame(loop);
                }
            };
            var hideSubpages = function () {
                subpagesNode.classList.remove('is-visible');
                subpageExpandState.checked = false;
                isVisible = false;
                clearTimeout(interactionTimeout);
                interactionTimeout = setTimeout(function () {
                    subpagesNode.classList.remove('is-interactive');
                }, 250);
            };

            subpageExpand.addEventListener('touchstart', function (e) {
                e.preventDefault();
                e.stopPropagation();
                // simulate effects of label tap
                if (subpageExpandState.checked) hideSubpages();
                else showSubpages();
            });
            subpagesNode.addEventListener('focusin', showSubpages);
            subpagesNode.addEventListener('focusout', hideSubpages);
            subpagesNode.addEventListener('mouseover', showSubpages);
            subpagesNode.addEventListener('mouseout', hideSubpages);

            item.addEventListener('mouseover', showSubpages);
            item.addEventListener('mouseout', hideSubpages);
        })(itemsWithSubpages[i]);
    }
}

// collapse and expand nav bar using the * button
var navCollapseCheckbox = document.querySelector('#nav-collapse');
var compactNavHeader = document.querySelector('.compact-nav-header');
window.addEventListener('keydown', function (e) {
    if (e.key === '*' && document.activeElement === document.body) {
        navCollapseCheckbox.checked = !navCollapseCheckbox.checked;
        if (navCollapseCheckbox.checked) {
            window.scrollTo(0, compactNavHeader.getBoundingClientRect().top + window.scrollY);
        }
    }
});

// expand all sidebar items by default on wide layouts
var pageSplit = document.querySelector('.page-split');
var shouldAnimateSidebarDisclosure = true;
if (pageSplit) {
    // this does not always work
    // var pageHasSplitLayout = getComputedStyle(pageSplit).display.indexOf('flex') > -1;
    var pageHasSplitLayout = window.innerWidth > cssmWidthSplitPage;
    if (pageHasSplitLayout) {
        shouldAnimateSidebarDisclosure = false;
        var disclosures = document.querySelectorAll('#nav-sidebar .subpage-disclosure');
        for (var i = 0; i < disclosures.length; i++) {
            disclosures[i].open = true;
        }
        setTimeout(function () {
            shouldAnimateSidebarDisclosure = true;
        }, 100);
    }
}

// decode email addresses
var addresses = document.querySelectorAll('.non-interactive-address');
for (var i = 0; i < addresses.length; i++) {
    var address = addresses[i];
    address.classList.remove('non-interactive-address');
    address.classList.add('js-address');

    var link = '';
    var parts = address.querySelectorAll('.epart');
    var usesParts = false;
    for (var j = 0; j < parts.length; j++) {
        usesParts = true;
        var part = parts[j];
        if (part.getAttribute('data-show-at') === 'true') link += '@';
        if (part.getAttribute('data-show-dot') === 'true') link += '.';
        link += part.textContent;
        link += part.getAttribute('data-after');
    }
    if (address.getAttribute('data-extra')) {
        link += address.getAttribute('data-extra') + '@';
    }
    if (address.getAttribute('data-extra2')) {
        link += address.getAttribute('data-extra2');
    }
    var fullLink = link;
    if (address.getAttribute('data-params')) {
        fullLink += '?' + address.getAttribute('data-params');
    }

    if (usesParts) {
        address.textContent = link;
    }
    address.href = 'mailto:' + fullLink;
}

// sidebar expand/collapse animation
if ('animate' in HTMLElement.prototype) {
    var sidebarDisclosures = document.querySelectorAll('#nav-sidebar li > .subpage-disclosure');
    for (var i = 0; i < sidebarDisclosures.length; i++) {
        var disclosure = sidebarDisclosures[i];
        var li = disclosure.parentNode;
        disclosure.subpageList = disclosure.querySelector('.subpage-list');

        disclosure.addEventListener('toggle', function (event) {
            var reduceMotion = window.matchMedia
                && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) return;
            if (!shouldAnimateSidebarDisclosure) return;

            var subpageList = this.subpageList;
            var disclosure = this;

            if (subpageList.dataset.animating === 'true') {
                event.preventDefault();
                return;
            }

            if (!disclosure.open) {
                // was checked; should collapse now
                subpageList.dataset.animating = 'true';
                subpageList.style.display = 'block';
                subpageList.style.overflow = 'hidden';

                var fullHeight = subpageList.offsetHeight;
                var animation = subpageList.animate([
                    {
                        height: fullHeight + 'px',
                        opacity: '1'
                    },
                    {
                        height: '0px',
                        paddingTop: '0px',
                        opacity: '0',
                        borderTopWidth: '0px',
                        borderBottomWidth: '0px',
                    }
                ], {
                    duration: 300,
                    easing: 'cubic-bezier(.2, .3, 0, 1)'
                });
                animation.addEventListener('cancel', function () {
                    subpageList.style.display = '';
                    subpageList.style.overflow = '';
                    subpageList.dataset.animating = 'false';
                });
                animation.addEventListener('finish', function () {
                    subpageList.style.display = '';
                    subpageList.style.overflow = '';
                    subpageList.dataset.animating = 'false';
                });
            } else {
                // was not open; should expand now
                subpageList.dataset.animating = 'true';
                subpageList.style.overflow = 'hidden';

                var fullHeight = subpageList.offsetHeight;
                var animation = subpageList.animate([
                    {
                        height: '0px',
                        paddingTop: '0px'
                    },
                    {
                        height: fullHeight + 'px'
                    }
                ], {
                    duration: 300,
                    easing: 'cubic-bezier(.2, .3, 0, 1)'
                });
                animation.addEventListener('cancel', function () {
                    subpageList.style.overflow = '';
                    subpageList.dataset.animating = 'false';
                });
                animation.addEventListener('finish', function () {
                    subpageList.style.overflow = '';
                    subpageList.dataset.animating = 'false';
                });
            }
        });
    }
}

// activate countdowns
var countdowns = document.querySelectorAll('.live-countdown');
window.activeCountdowns = [];
for (var i = 0; i < countdowns.length; i++) {
    var countdown = countdowns[i];
    if (countdown.classList.contains('is-live')) continue;
    countdown.classList.add('is-live');

    var targetTime = new Date(+countdown.getAttribute('data-timestamp') * 1000);
    var countdownHandler = {
        node: countdown,
        targetTime,
        update: function () {
            this.node.textContent = formatDuration(getTimeInterval(new Date(), this.targetTime));
        }
    };
    activeCountdowns.push(countdownHandler);
}
function getTimeInterval(fromDate, toDate) {
    var invert = fromDate > toDate;

    // time order: a -> b
    var a = fromDate > toDate ? toDate : fromDate;
    var b = fromDate < toDate ? toDate : fromDate;

    var totalDeltaSeconds = Math.round((+b - +a) / 1000);
    var seconds = totalDeltaSeconds % 60;
    var minutes = Math.floor(totalDeltaSeconds / 60) % 60;
    var hours = Math.floor(totalDeltaSeconds / 3600) % 24;
    var rawDays = Math.floor(totalDeltaSeconds / 86400);
    a = new Date(+a + (hours * 3600 + minutes * 60 + seconds) * 1000);

    var days = 0;
    if (a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth()) {
        // same month
        days = b.getDate() - a.getDate();
    } else {
        // not the same month
        var daysInMonthA = new Date(a.getFullYear(), a.getMonth() + 1, 0).getDate();
        days = b.getDate() + (daysInMonthA - a.getDate());
    }
    a.setDate(a.getDate() + days);

    var months = 0;
    if (a.getFullYear() === b.getFullYear()) {
        // same year
        months = b.getMonth() - a.getMonth();
    } else {
        // not the same year
        months = b.getMonth() + (13 - a.getMonth());
    }
    a.setMonth(a.getMonth() + months);

    var years = b.getFullYear() - a.getFullYear();

    return { invert, years, months, days, rawDays, hours, minutes, seconds };
}
function formatDuration(timeInterval) {
    var prefix = timeInterval.invert ? 'antaŭ ' : 'post ';
    var years = timeInterval.years;
    var months = timeInterval.months;
    var days = timeInterval.days;
    var hours = timeInterval.hours;
    var minutes = timeInterval.minutes;

    var out = '';
    var space = '\u2060';
    var zspace = '\u2007';

    if (years > 0) {
        return prefix + years + ' jaro' + ((years > 1) ? 'j' : '');
    }
    if (months > 0) {
        return prefix + months + ' monato' + ((months > 1) ? 'j' : '');
    }

    if (days >= 7) {
        return prefix + days + ' tagoj';
    } else if (days > 0) {
        out += days + space + 't' + zspace;
    }
    if (days > 0 || hours > 0) out += hours + space + 'h' + zspace;
    out += minutes + space + 'm';
    return prefix + out;
}
const months = [
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
function formatMonth(month) {
    return months[month];
}
function formatDate(date) {
    return date.getDate() + '-a de ' + formatMonth(date.getMonth()) + ' ' + date.getFullYear();
}
function formatTime(date) {
    var pad2 = x => ('00' + x).substr(-2);
    return pad2(date.getHours()) + ':' + pad2(date.getMinutes());
}
function formatDateTime(date) {
    return formatDate(date) + ' ' + formatTime(date);
}

function updateActiveCountdowns () {
    for (var i = 0; i < activeCountdowns.length; i++) {
        activeCountdowns[i].update();
    }
}
setInterval(updateActiveCountdowns, 30000);

{
    var timestamps = document.querySelectorAll('.dyn-timestamp');
    for (var i = 0; i < timestamps.length; i++) {
        var timestamp = timestamps[i];
        timestamp.textContent = formatDateTime(new Date(timestamp.getAttribute('datetime')));
    }
}

{
    // forms that auto-submit when <select> changes
    var forms = document.querySelectorAll('form[data-select-auto-form]');
    for (var i = 0; i < forms.length; i++) {
        var form = forms[i];
        var selectName = form.getAttribute('data-select-auto-form');
        var select = form.querySelector('[name="' + selectName + '"]');
        if (!select) continue;

        select.addEventListener('change', function () {
            this.form.submit();
        });

        var submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.parentNode.removeChild(submitButton);
        }
    }
}
